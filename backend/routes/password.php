<?php

declare(strict_types=1);

function password_response(
    int $status,
    string $code,
    string $message,
    string $i18nKey,
    array $i18nParams = [],
    array $extra = []
): array {
    return [
        'status' => $status,
        'body' => array_merge([
            'success' => $status >= 200 && $status < 300,
            'code' => $code,
            'message' => $message,
            'i18n_key' => $i18nKey,
            'i18n_params' => $i18nParams,
        ], $extra),
    ];
}

function handle_password_forgot(PDO $pdo, array $payload, array $config): array
{
    $email = trim((string) ($payload['email'] ?? ''));
    $username = trim((string) ($payload['username'] ?? ''));

    $genericResponse = password_response(
        200,
        'PASSWORD_FORGOT_ACCEPTED',
        'If the account exists, reset instructions were sent.',
        'forgot.genericSuccess'
    );

    if ($email === '' || !is_valid_email($email)) {
        return $genericResponse;
    }

    $secret = password_reset_secret();
    if ($secret === '') {
        return password_response(
            500,
            'PASSWORD_SECRET_MISSING',
            'Server secret is not configured.',
            'password.secretMissing',
            [],
            ['error' => 'Server secret is not configured.']
        );
    }

    try {
        if ($username !== '') {
            $statement = $pdo->prepare(
                'SELECT id, username, email
                 FROM users
                 WHERE email = :email AND username = :username
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $statement->execute([
                'email' => $email,
                'username' => $username,
            ]);
        } else {
            $statement = $pdo->prepare(
                'SELECT id, username, email
                 FROM users
                 WHERE email = :email
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $statement->execute([
                'email' => $email,
            ]);
        }

        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($user)) {
            return $genericResponse;
        }

        $token = generate_reset_token();
        $tokenHash = hash_reset_token($token, $secret);
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify('+30 minutes');

        $insert = $pdo->prepare(
            'INSERT INTO password_resets (
                user_id,
                token_hash,
                expires_at,
                used_at,
                request_ip,
                user_agent,
                created_at
            ) VALUES (
                :user_id,
                :token_hash,
                :expires_at,
                NULL,
                :request_ip,
                :user_agent,
                :created_at
            )'
        );

        $requestIp = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $insert->execute([
            'user_id' => (int) $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'request_ip' => $requestIp !== '' ? $requestIp : null,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $resetLink = build_password_reset_link($token);
        $mailSent = send_password_reset_email(
            (string) $user['email'],
            (string) $user['username'],
            $resetLink,
            $config
        );

        if (!$mailSent) {
            $delete = $pdo->prepare('DELETE FROM password_resets WHERE token_hash = :token_hash');
            $delete->execute(['token_hash' => $tokenHash]);
        }

        return $genericResponse;
    } catch (Throwable $exception) {
        return $genericResponse;
    }
}

function handle_password_reset(PDO $pdo, array $payload): array
{
    $token = trim((string) ($payload['token'] ?? ''));
    $newPassword = (string) ($payload['newPassword'] ?? '');

    if ($token === '') {
        return password_response(
            422,
            'PASSWORD_RESET_TOKEN_REQUIRED',
            'Token is required.',
            'reset.tokenRequired',
            [],
            ['error' => 'Token is required.']
        );
    }

    if (strlen($newPassword) < 8) {
        return password_response(
            422,
            'PASSWORD_RESET_TOO_SHORT',
            'Password must be at least 8 characters.',
            'reset.passwordTooShort',
            ['minLength' => 8],
            ['error' => 'Password must be at least 8 characters.']
        );
    }

    if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        return password_response(
            422,
            'PASSWORD_RESET_WEAK',
            'Password must contain letters and numbers.',
            'reset.passwordWeak',
            [],
            ['error' => 'Password must contain letters and numbers.']
        );
    }

    $secret = password_reset_secret();
    if ($secret === '') {
        return password_response(
            500,
            'PASSWORD_SECRET_MISSING',
            'Server secret is not configured.',
            'password.secretMissing',
            [],
            ['error' => 'Server secret is not configured.']
        );
    }

    $tokenHash = hash_reset_token($token, $secret);

    try {
        $select = $pdo->prepare(
            'SELECT pr.id AS reset_id, pr.user_id AS user_id
             FROM password_resets pr
             WHERE pr.token_hash = :token_hash
               AND pr.used_at IS NULL
               AND pr.expires_at > :now
             ORDER BY pr.id DESC
             LIMIT 1'
        );
        $select->execute([
            'token_hash' => $tokenHash,
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $row = $select->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return password_response(
                400,
                'PASSWORD_RESET_TOKEN_INVALID',
                'Token is invalid or expired.',
                'reset.tokenInvalidOrExpired',
                [],
                ['error' => 'Token is invalid or expired.']
            );
        }

        $userId = (int) $row['user_id'];
        $resetId = (int) $row['reset_id'];
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $nowText = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $pdo->beginTransaction();

        $updateUser = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash
             WHERE id = :id
             LIMIT 1'
        );
        $updateUser->execute([
            'password_hash' => $passwordHash,
            'id' => $userId,
        ]);

        $markAll = $pdo->prepare(
            'UPDATE password_resets
             SET used_at = :used_at
             WHERE user_id = :user_id
               AND used_at IS NULL'
        );
        $markAll->execute([
            'used_at' => $nowText,
            'user_id' => $userId,
        ]);

        $markCurrent = $pdo->prepare(
            'UPDATE password_resets
             SET used_at = :used_at
             WHERE id = :id
               AND used_at IS NULL
             LIMIT 1'
        );
        $markCurrent->execute([
            'used_at' => $nowText,
            'id' => $resetId,
        ]);

        $pdo->commit();

        return password_response(
            200,
            'PASSWORD_RESET_SUCCESS',
            'Password reset successful. Please sign in with your new password.',
            'reset.success'
        );
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return password_response(
            500,
            'PASSWORD_RESET_SERVER_ERROR',
            'Password reset failed due to server error.',
            'reset.serverError',
            [],
            ['error' => 'Password reset failed due to server error.']
        );
    }
}

function password_reset_secret(): string
{
    $projectConfig = backend_config();
    $appConfig = is_array($projectConfig['app'] ?? null) ? $projectConfig['app'] : [];

    $secret = trim((string) ($appConfig['secret'] ?? ''));
    if ($secret !== '') {
        return $secret;
    }
    else{
        return "temp_secret";
    }
}

function build_password_reset_link(string $token): string
{
    $projectConfig = backend_config();
    $appConfig = is_array($projectConfig['app'] ?? null) ? $projectConfig['app'] : [];

    $baseUrl = trim((string) ($appConfig['base_url'] ?? ''));
    if ($baseUrl === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $baseUrl = $scheme . '://' . $host;
    }

    return rtrim($baseUrl, '/') . '/?mode=reset&token=' . rawurlencode($token);
}
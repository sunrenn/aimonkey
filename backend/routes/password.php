<?php

declare(strict_types=1);

function handle_password_forgot(PDO $pdo, array $payload, array $config): array
{
    $email = trim((string) ($payload['email'] ?? ''));
    $username = trim((string) ($payload['username'] ?? ''));

    $genericResponse = [
        'status' => 200,
        'body' => [
            'success' => true,
            'message' => '如果账号存在，重置链接将发送到您的邮箱。',
        ],
    ];

    if ($email === '' || !is_valid_email($email)) {
        return $genericResponse;
    }

    $secret = password_reset_secret();
    if ($secret === '') {
        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'error' => 'Server secret is not configured.',
            ],
        ];
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
        return [
            'status' => 422,
            'body' => ['success' => false, 'error' => 'Token is required.'],
        ];
    }

    if (strlen($newPassword) < 8) {
        return [
            'status' => 422,
            'body' => ['success' => false, 'error' => 'Password must be at least 8 characters.'],
        ];
    }

    if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        return [
            'status' => 422,
            'body' => ['success' => false, 'error' => 'Password must contain letters and numbers.'],
        ];
    }

    $secret = password_reset_secret();
    if ($secret === '') {
        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'error' => 'Server secret is not configured.',
            ],
        ];
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
            return [
                'status' => 400,
                'body' => ['success' => false, 'error' => 'Token is invalid or expired.'],
            ];
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

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => '密码重置成功，请使用新密码登录。',
            ],
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'error' => 'Password reset failed due to server error.',
            ],
        ];
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
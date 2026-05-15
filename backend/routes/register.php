<?php

declare(strict_types=1);

function users_table_columns(PDO $pdo): array
{
    static $columns = null;

    if (is_array($columns)) {
        return $columns;
    }

    $columns = [];
    $statement = $pdo->query('SHOW COLUMNS FROM users');
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = (string) ($row['Field'] ?? '');
        if ($name !== '') {
            $columns[$name] = true;
        }
    }

    return $columns;
}

function register_response(
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

function handle_register(PDO $pdo, array $payload, array $config): array
{
    $username = trim((string) ($payload['username'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));

    if ($username === '') {
        return register_response(
            422,
            'REG_USERNAME_REQUIRED',
            'Username is required.',
            'register.usernameRequired',
            [],
            ['error' => 'Username is required.']
        );
    }

    if ($email === '') {
        return register_response(
            422,
            'REG_EMAIL_REQUIRED',
            'Email is required.',
            'register.emailRequired',
            [],
            ['error' => 'Email is required.']
        );
    }

    if (!is_valid_email($email)) {
        return register_response(
            422,
            'REG_EMAIL_INVALID',
            'Email format is invalid.',
            'register.emailInvalid',
            [],
            ['error' => 'Email format is invalid.']
        );
    }

    $duplicateStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND username = :username');
    $duplicateStatement->execute([
        'email' => $email,
        'username' => $username,
    ]);
    $sameIdentityCount = (int) $duplicateStatement->fetchColumn();

    if ($sameIdentityCount > 0) {
        return register_response(
            409,
            'REG_DUPLICATE_USERNAME_FOR_EMAIL',
            'This email already has that username. Please choose another username.',
            'register.duplicateUsernameForEmail',
            [],
            ['error' => '该邮箱已注册过这个用户名，请使用其他用户名。']
        );
    }

    // Check for existing email to determine avatar state and count.
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $countStatement->execute(['email' => $email]);
    $sameEmailCount = (int) $countStatement->fetchColumn();

    $avatarLimit = (int) ($config['max_avatar_count'] ?? 1);
    if ($sameEmailCount >= $avatarLimit) {
        $usernamesStatement = $pdo->prepare('SELECT username FROM users WHERE email = :email ORDER BY id ASC');
        $usernamesStatement->execute(['email' => $email]);
        $registeredUsernames = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['username'] ?? '')),
            $usernamesStatement->fetchAll(PDO::FETCH_ASSOC)
        ), static fn (string $name): bool => $name !== ''));

        return register_response(
            409,
            'REG_EMAIL_LIMIT_REACHED',
            sprintf('This email has reached the registration limit (%d).', $avatarLimit),
            'register.emailLimitReached',
            ['limit' => $avatarLimit],
            [
                'error' => sprintf('该邮箱最多只能注册 %d 个账户，已达到上限。', $avatarLimit),
                'registered_usernames' => $registeredUsernames,
            ]
        );
    }

    $avatarCountAfterCreate = $sameEmailCount + 1;
    $avatarState = 'active';

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $accountUid = uniqid('user_', true);
    $password = generate_password();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $columns = users_table_columns($pdo);

    $insertFields = [
        'account_uid',
        'username',
        'email',
        'password_hash',
        'avatar_state',
        'avatar_count_for_contact',
        'created_at',
    ];
    $insertValues = [
        'account_uid' => $accountUid,
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'avatar_state' => $avatarState,
        'avatar_count_for_contact' => $avatarCountAfterCreate,
        'created_at' => $now,
    ];

    // Backward compatibility for old users schema before reset.php migration.
    if (isset($columns['contact_type'])) {
        $insertFields[] = 'contact_type';
        $insertValues['contact_type'] = 'email';
    }
    if (isset($columns['phone'])) {
        $insertFields[] = 'phone';
        $insertValues['phone'] = null;
    }
    if (isset($columns['wants_verification'])) {
        $insertFields[] = 'wants_verification';
        $insertValues['wants_verification'] = 0;
    }
    if (isset($columns['verification_code'])) {
        $insertFields[] = 'verification_code';
        $insertValues['verification_code'] = null;
    }
    if (isset($columns['is_valid_contact'])) {
        $insertFields[] = 'is_valid_contact';
        $insertValues['is_valid_contact'] = 1;
    }
    if (isset($columns['is_restricted'])) {
        $insertFields[] = 'is_restricted';
        $insertValues['is_restricted'] = 0;
    }
    if (isset($columns['purge_at'])) {
        $insertFields[] = 'purge_at';
        $insertValues['purge_at'] = null;
    }

    $placeholders = array_map(static fn (string $field): string => ':' . $field, $insertFields);
    $insertSql = sprintf(
        'INSERT INTO users (%s) VALUES (%s)',
        implode(', ', $insertFields),
        implode(', ', $placeholders)
    );

    try {
        $pdo->beginTransaction();

        $insertStatement = $pdo->prepare($insertSql);
        $insertStatement->execute($insertValues);

        $mailSent = send_password_email($email, $username, $password, $config);
        if (!$mailSent) {
            $pdo->rollBack();
            return register_response(
                500,
                'REG_SMTP_DELIVERY_FAILED',
                'SMTP delivery failed. Please check mail settings and try again.',
                'register.smtpFailed',
                [],
                ['error' => 'SMTP delivery failed. Please check mail settings and try again.']
            );
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($exception instanceof PDOException && $exception->getCode() === '23000') {
            return register_response(
                409,
                'REG_DUPLICATE_USERNAME_FOR_EMAIL',
                'This email already has that username. Please choose another username.',
                'register.duplicateUsernameForEmail',
                [],
                ['error' => '该邮箱已注册过这个用户名，请使用其他用户名。']
            );
        }

        return register_response(
            500,
            'REG_SERVER_ERROR',
            'Registration failed due to server error.',
            'register.serverError',
            [],
            ['error' => 'Registration failed due to server error.']
        );
    }

    return register_response(
        201,
        'REG_SUCCESS',
        'Registration successful. Password has been sent to your email.',
        'register.success',
        []
    );
}
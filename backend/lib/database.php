<?php

declare(strict_types=1);

function backend_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config.php';
    }

    return $config;
}

function backend_create_pdo(?string $databaseName = null): PDO
{
    $config = backend_config();
    $dbConfig = is_array($config['db'] ?? null) ? $config['db'] : [];

    // Backward compatibility for legacy flat config keys.
    $host = (string) ($dbConfig['host'] ?? $config['db_host'] ?? '127.0.0.1');
    $port = (int) ($dbConfig['port'] ?? $config['db_port'] ?? 3306);
    $charset = (string) ($dbConfig['charset'] ?? $config['db_charset'] ?? 'utf8mb4');
    $defaultDbName = (string) ($dbConfig['name'] ?? $config['db_name'] ?? '');
    $dbName = $databaseName ?? $defaultDbName;
    $dbUser = (string) ($dbConfig['user'] ?? $config['db_user'] ?? 'root');
    $dbPassword = (string) ($dbConfig['password'] ?? $config['db_password'] ?? '');

    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
    if ($dbName !== '') {
        $dsn .= ';dbname=' . $dbName;
    }

    return new PDO(
        $dsn,
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function backend_load_runtime_config(PDO $pdo): array
{
    $projectConfig = backend_config();
    $smtpConfig = is_array($projectConfig['smtp'] ?? null) ? $projectConfig['smtp'] : [];

    $defaults = [
        'verification_enabled' => true,
        'max_avatar_count' => 3,
        'restricted_data_retention_hours' => 2400,
        'smtp_enabled' => (bool) ($smtpConfig['enabled'] ?? false),
        'smtp_host' => (string) ($smtpConfig['host'] ?? ''),
        'smtp_port' => (int) ($smtpConfig['port'] ?? 587),
        'smtp_secure' => (string) ($smtpConfig['secure'] ?? 'tls'),
        'smtp_username' => (string) ($smtpConfig['username'] ?? ''),
        'smtp_password' => (string) ($smtpConfig['password'] ?? ''),
        'smtp_from_email' => (string) ($smtpConfig['from_email'] ?? ''),
        'smtp_from_name' => (string) ($smtpConfig['from_name'] ?? 'AIMonkey'),
        'smtp_timeout' => (int) ($smtpConfig['timeout'] ?? 10),
    ];

    $statement = $pdo->query('SELECT config_key, config_value FROM app_config');
    $rows = $statement->fetchAll();

    foreach ($rows as $row) {
        $key = (string) ($row['config_key'] ?? '');
        $value = $row['config_value'] ?? null;
        if ($key === '') {
            continue;
        }

        if (in_array($key, ['verification_enabled', 'smtp_enabled'], true)) {
            $defaults[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            continue;
        }

        if (in_array($key, ['max_avatar_count', 'restricted_data_retention_hours', 'smtp_port', 'smtp_timeout'], true)) {
            $defaults[$key] = (int) $value;
            continue;
        }

        $defaults[$key] = $value;
    }

    $defaults = backend_apply_project_smtp_fallbacks($defaults, $smtpConfig);

    return backend_apply_runtime_env_overrides($defaults);
}

function backend_apply_project_smtp_fallbacks(array $runtimeConfig, array $smtpConfig): array
{
    $host = trim((string) ($smtpConfig['host'] ?? ''));
    $secure = trim((string) ($smtpConfig['secure'] ?? ''));
    $username = trim((string) ($smtpConfig['username'] ?? ''));
    $password = (string) ($smtpConfig['password'] ?? '');
    $fromEmail = trim((string) ($smtpConfig['from_email'] ?? ''));
    $fromName = trim((string) ($smtpConfig['from_name'] ?? ''));
    $port = (int) ($smtpConfig['port'] ?? 0);
    $timeout = (int) ($smtpConfig['timeout'] ?? 0);
    $enabled = (bool) ($smtpConfig['enabled'] ?? false);

    if ($host !== '') {
        $runtimeConfig['smtp_host'] = $host;
    }
    if ($port > 0) {
        $runtimeConfig['smtp_port'] = $port;
    }
    if ($secure !== '') {
        $runtimeConfig['smtp_secure'] = $secure;
    }
    if ($username !== '') {
        $runtimeConfig['smtp_username'] = $username;
    }
    if ($password !== '') {
        $runtimeConfig['smtp_password'] = $password;
    }
    if ($fromEmail !== '') {
        $runtimeConfig['smtp_from_email'] = $fromEmail;
    }
    if ($fromName !== '') {
        $runtimeConfig['smtp_from_name'] = $fromName;
    }
    if ($timeout > 0) {
        $runtimeConfig['smtp_timeout'] = $timeout;
    }

    // 当项目配置显式启用 SMTP 时，应优先于数据库初始化值（例如 smtp_enabled=0）。
    if ($enabled) {
        $runtimeConfig['smtp_enabled'] = true;
    }

    return $runtimeConfig;
}

function backend_apply_runtime_env_overrides(array $config): array
{
    $map = [
        'smtp_enabled' => 'AIMONKEY_SMTP_ENABLED',
        'smtp_host' => 'AIMONKEY_SMTP_HOST',
        'smtp_port' => 'AIMONKEY_SMTP_PORT',
        'smtp_secure' => 'AIMONKEY_SMTP_SECURE',
        'smtp_username' => 'AIMONKEY_SMTP_USERNAME',
        'smtp_password' => 'AIMONKEY_SMTP_PASSWORD',
        'smtp_from_email' => 'AIMONKEY_SMTP_FROM_EMAIL',
        'smtp_from_name' => 'AIMONKEY_SMTP_FROM_NAME',
        'smtp_timeout' => 'AIMONKEY_SMTP_TIMEOUT',
    ];

    foreach ($map as $key => $envName) {
        $raw = getenv($envName);
        if ($raw === false || $raw === '') {
            continue;
        }

        if (in_array($key, ['smtp_enabled'], true)) {
            $config[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            continue;
        }

        if (in_array($key, ['smtp_port', 'smtp_timeout'], true)) {
            $config[$key] = (int) $raw;
            continue;
        }

        $config[$key] = $raw;
    }

    return $config;
}

function backend_split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && $prev !== '\\' && !$inDoubleQuote) {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && $prev !== '\\' && !$inSingleQuote) {
            $inDoubleQuote = !$inDoubleQuote;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
            $trimmed = trim($buffer);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $trimmed = trim($buffer);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}

function backend_execute_sql_file(PDO $pdo, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('Failed to read SQL file: ' . basename($filePath));
    }

    $sql = preg_replace('/^\s*(--|#).*$\R?/m', '', $sql) ?? $sql;

    foreach (backend_split_sql_statements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

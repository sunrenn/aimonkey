<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/database.php';

try {
    $config = backend_config();
    $serverPdo = backend_create_pdo('');
    $dbConfig = is_array($config['db'] ?? null) ? $config['db'] : [];
    $databaseName = (string) ($dbConfig['name'] ?? $config['db_name'] ?? '');

    if ($databaseName === '') {
        throw new RuntimeException('Database name is empty in backend/config.php.');
    }

    $quotedDatabaseName = '`' . str_replace('`', '``', $databaseName) . '`';

    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS {$quotedDatabaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = backend_create_pdo($databaseName);
    backend_execute_sql_file($pdo, __DIR__ . '/struct.sql');
    backend_execute_sql_file($pdo, __DIR__ . '/config_data.sql');
    backend_execute_sql_file($pdo, __DIR__ . '/basic_data.sql');

    header('Content-Type: text/plain; charset=utf-8');
    echo "Database reset completed for '{$databaseName}'.\n";
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Database reset failed. Check MySQL settings in backend/config.php or AIMONKEY_DB_* environment variables.\n";
    echo $exception->getMessage() . "\n";
}

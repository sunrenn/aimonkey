<?php

declare(strict_types=1);

return [
    'app' => [
        'secret' => getenv('AIMONKEY_APP_SECRET') ?: 'woyouhenduoxiaomimi',
        'baseurl' => getenv('AIMONKEY_APP_BASE_URL') ?: 'http://localhost:8000',
    ],
    'db' => [
        'host' => getenv('AIMONKEY_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('AIMONKEY_DB_PORT') ?: 3306),
        'name' => getenv('AIMONKEY_DB_NAME') ?: 'aimonkey_db',
        'user' => getenv('AIMONKEY_DB_USER') ?: 'lukelin',
        'password' => getenv('AIMONKEY_DB_PASSWORD') ?: 'getenv_getenv',
        'charset' => getenv('AIMONKEY_DB_CHARSET') ?: 'utf8mb4',
    ],
    'smtp' => [
        'enabled' => filter_var(getenv('AIMONKEY_SMTP_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'host' => getenv('AIMONKEY_SMTP_HOST') ?: 'smtp.qq.com',
        'port' => (int) (getenv('AIMONKEY_SMTP_PORT') ?: 465),
        'secure' => getenv('AIMONKEY_SMTP_SECURE') ?: 'ssl',
        'username' => getenv('AIMONKEY_SMTP_USERNAME') ?: '75191805@qq.com',
        'password' => getenv('AIMONKEY_SMTP_PASSWORD') ?: 'its#fake13pswd%&hbjeh',
        'from_email' => getenv('AIMONKEY_SMTP_FROM_EMAIL') ?: '75191805@qq.com',
        'from_name' => getenv('AIMONKEY_SMTP_FROM_NAME') ?: 'AIMonkey SMTP Test',
        'timeout' => (int) (getenv('AIMONKEY_SMTP_TIMEOUT') ?: 10),
    ],
];

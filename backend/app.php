<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/database.php';
require_once __DIR__ . '/routes/register.php';
require_once __DIR__ . '/routes/password.php';
require_once __DIR__ . '/routes/login.php';

function backend_handle_request(string $requestPath, string $method): bool
{
    if (!str_starts_with($requestPath, '/api')) {
        return false;
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    try {
        $pdo = backend_create_pdo();
        $config = backend_load_runtime_config($pdo);
    } catch (Throwable $exception) {
        json_response(500, ['error' => 'Database connection failed. Run backend/data/reset.php and verify MySQL settings.']);
    }

    if ($requestPath === '/api/health' && $method === 'GET') {
        json_response(200, ['ok' => true, 'service' => 'AIMonkey API', 'database' => 'connected']);
    }

    if ($requestPath === '/api/config' && $method === 'GET') {
        json_response(200, [
            'verificationEnabled' => (bool) ($config['verification_enabled'] ?? false),
            'maxAvatarCount' => (int) ($config['max_avatar_count'] ?? 1),
            'restrictedDataRetentionHours' => (int) ($config['restricted_data_retention_hours'] ?? 2400),
            'mail' => [
                'smtpEnabled' => (bool) ($config['smtp_enabled'] ?? false),
                'host' => (string) ($config['smtp_host'] ?? ''),
                'port' => (int) ($config['smtp_port'] ?? 587),
                'secure' => (string) ($config['smtp_secure'] ?? 'tls'),
                'username' => (string) ($config['smtp_username'] ?? ''),
                'fromEmail' => (string) ($config['smtp_from_email'] ?? ''),
                'fromName' => (string) ($config['smtp_from_name'] ?? 'AIMonkey'),
                'timeout' => (int) ($config['smtp_timeout'] ?? 10),
                'passwordConfigured' => ((string) ($config['smtp_password'] ?? '')) !== '',
            ],
        ]);
    }

    if ($requestPath === '/api/admin/mail-config' && $method === 'GET') {
        json_response(200, [
            'smtpEnabled' => (bool) ($config['smtp_enabled'] ?? false),
            'host' => (string) ($config['smtp_host'] ?? ''),
            'port' => (int) ($config['smtp_port'] ?? 587),
            'secure' => (string) ($config['smtp_secure'] ?? 'tls'),
            'username' => (string) ($config['smtp_username'] ?? ''),
            'fromEmail' => (string) ($config['smtp_from_email'] ?? ''),
            'fromName' => (string) ($config['smtp_from_name'] ?? 'AIMonkey'),
            'timeout' => (int) ($config['smtp_timeout'] ?? 10),
            'passwordConfigured' => ((string) ($config['smtp_password'] ?? '')) !== '',
        ]);
    }

    if ($requestPath === '/api/admin/mail-config' && $method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'Invalid JSON body.']);
        }

        $updates = [
            'smtp_enabled' => normalize_bool($payload['smtpEnabled'] ?? false) ? '1' : '0',
            'smtp_host' => trim((string) ($payload['host'] ?? '')),
            'smtp_port' => (string) max(1, (int) ($payload['port'] ?? 587)),
            'smtp_secure' => trim((string) ($payload['secure'] ?? 'tls')),
            'smtp_username' => trim((string) ($payload['username'] ?? '')),
            'smtp_from_email' => trim((string) ($payload['fromEmail'] ?? '')),
            'smtp_from_name' => trim((string) ($payload['fromName'] ?? 'AIMonkey')),
            'smtp_timeout' => (string) max(1, (int) ($payload['timeout'] ?? 10)),
        ];

        if (array_key_exists('password', $payload)) {
            $updates['smtp_password'] = (string) ($payload['password'] ?? '');
        }

        $upsert = $pdo->prepare(
            'INSERT INTO app_config (config_key, config_value) VALUES (:config_key, :config_value)
             ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)'
        );

        foreach ($updates as $key => $value) {
            $upsert->execute([
                'config_key' => $key,
                'config_value' => $value,
            ]);
        }

        json_response(200, ['success' => true, 'message' => 'Mail config updated.']);
    }

    if ($requestPath === '/api/register' && $method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'Invalid JSON body.']);
        }

        $result = handle_register($pdo, $payload, $config);
        json_response($result['status'], $result['body']);
    }

    if ($requestPath === '/api/password/forgot' && $method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'Invalid JSON body.']);
        }

        $result = handle_password_forgot($pdo, $payload, $config);
        json_response($result['status'], $result['body']);
    }

    if ($requestPath === '/api/password/reset' && $method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'Invalid JSON body.']);
        }

        $result = handle_password_reset($pdo, $payload);
        json_response($result['status'], $result['body']);
    }

    if ($requestPath === '/api/login' && $method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            json_response(400, ['error' => 'Invalid JSON body.']);
        }

        $result = handle_login($pdo, $payload);
        json_response($result['status'], $result['body']);
    }

    json_response(404, ['error' => 'Route not found.']);
}

function frontend_render_entrypoint(string $frontend_id): void
{
    $distIndexPath = __DIR__ . '/../'.$frontend_id.'/dist/index.html';
    if (!file_exists($distIndexPath)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Frontend build is missing. Run 'cd frontend && npm run build' first.";
        exit;
    }

    $html = file_get_contents($distIndexPath);
    if ($html === false) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Failed to load frontend entrypoint.';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
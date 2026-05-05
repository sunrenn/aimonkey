<?php
declare(strict_types=1);

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_file(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function write_json_file(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function normalize_bool(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generate_password(int $length = 12): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
    $max = strlen($chars) - 1;
    $result = '';

    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, $max)];
    }

    return $result;
}

function generate_verification_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generate_reset_token(): string
{
    return bin2hex(random_bytes(32));
}

function hash_reset_token(string $token, string $secret): string
{
    return hash_hmac('sha256', $token, $secret);
}

function send_password_email(string $email, string $username, string $password, array $config): bool
{
    $smtpEnabled = (bool) ($config['smtp_enabled'] ?? false);
    if (!$smtpEnabled) {
        return false;
    }

    $smtpHost = trim((string) ($config['smtp_host'] ?? ''));
    $smtpPort = (int) ($config['smtp_port'] ?? 587);
    $smtpSecure = strtolower(trim((string) ($config['smtp_secure'] ?? 'tls')));
    $smtpUser = trim((string) ($config['smtp_username'] ?? ''));
    $smtpPass = (string) ($config['smtp_password'] ?? '');
    $fromEmail = trim((string) ($config['smtp_from_email'] ?? ''));
    $fromName = trim((string) ($config['smtp_from_name'] ?? 'AIMonkey'));
    $timeout = (int) ($config['smtp_timeout'] ?? 10);

    if ($smtpHost === '' || $fromEmail === '') {
        return false;
    }

    $subject = 'AIMonkey Registration Password';
    $message = "Hello {$username},\n\nYour temporary password is: {$password}\n\nPlease change it after login.";

    $sent = smtp_send_plain_text_mail([
        'host' => $smtpHost,
        'port' => $smtpPort,
        'secure' => $smtpSecure,
        'username' => $smtpUser,
        'password' => $smtpPass,
        'timeout' => max(3, $timeout),
        'from_email' => $fromEmail,
        'from_name' => $fromName,
    ], $email, $subject, $message);

    if (!$sent) {
        $logLine = sprintf("[%s] SMTP delivery failed for %s (%s)%s", date('c'), $email, $username, PHP_EOL);
        file_put_contents(__DIR__ . '/../storage/mail_dev.log', $logLine, FILE_APPEND);
    }

    return $sent;
}

function send_password_reset_email(string $email, string $username, string $resetLink, array $config): bool
{
    $smtpEnabled = (bool) ($config['smtp_enabled'] ?? false);
    if (!$smtpEnabled) {
        return false;
    }

    $smtpHost = trim((string) ($config['smtp_host'] ?? ''));
    $smtpPort = (int) ($config['smtp_port'] ?? 587);
    $smtpSecure = strtolower(trim((string) ($config['smtp_secure'] ?? 'tls')));
    $smtpUser = trim((string) ($config['smtp_username'] ?? ''));
    $smtpPass = (string) ($config['smtp_password'] ?? '');
    $fromEmail = trim((string) ($config['smtp_from_email'] ?? ''));
    $fromName = trim((string) ($config['smtp_from_name'] ?? 'AIMonkey'));
    $timeout = (int) ($config['smtp_timeout'] ?? 10);

    if ($smtpHost === '' || $fromEmail === '') {
        return false;
    }

    $subject = 'AIMonkey Password Reset';
    $message =
        "Hello {$username},\n\n" .
        "We received a password reset request for your account.\n" .
        "Use this link within 30 minutes:\n{$resetLink}\n\n" .
        "If you did not request this, you can ignore this email.";

    return smtp_send_plain_text_mail([
        'host' => $smtpHost,
        'port' => $smtpPort,
        'secure' => $smtpSecure,
        'username' => $smtpUser,
        'password' => $smtpPass,
        'timeout' => max(3, $timeout),
        'from_email' => $fromEmail,
        'from_name' => $fromName,
    ], $email, $subject, $message);
}

function smtp_send_plain_text_mail(array $smtp, string $toEmail, string $subject, string $body): bool
{
    $host = (string) $smtp['host'];
    $port = (int) $smtp['port'];
    $secure = strtolower((string) $smtp['secure']);
    $username = (string) $smtp['username'];
    $password = (string) $smtp['password'];
    $timeout = (int) $smtp['timeout'];
    $fromEmail = (string) $smtp['from_email'];
    $fromName = (string) $smtp['from_name'];

    $transportHost = $secure === 'ssl' ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client(
        sprintf('%s:%d', $transportHost, $port),
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );

    if (!is_resource($socket)) {
        return false;
    }

    stream_set_timeout($socket, $timeout);

    try {
        if (!smtp_expect_code($socket, 220)) {
            return false;
        }
        if (!smtp_send_cmd($socket, 'EHLO localhost', 250)) {
            return false;
        }

        if ($secure === 'tls') {
            if (!smtp_send_cmd($socket, 'STARTTLS', 220)) {
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return false;
            }
            if (!smtp_send_cmd($socket, 'EHLO localhost', 250)) {
                return false;
            }
        }

        if ($username !== '') {
            if (!smtp_send_cmd($socket, 'AUTH LOGIN', 334)) {
                return false;
            }
            if (!smtp_send_cmd($socket, base64_encode($username), 334)) {
                return false;
            }
            if (!smtp_send_cmd($socket, base64_encode($password), 235)) {
                return false;
            }
        }

        if (!smtp_send_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', 250)) {
            return false;
        }
        if (!smtp_send_cmd($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251])) {
            return false;
        }
        if (!smtp_send_cmd($socket, 'DATA', 354)) {
            return false;
        }

        $headers = [
            'From: ' . smtp_header_display_name($fromName) . ' <' . $fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: ' . smtp_header_subject($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $data = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.\r\n";
        fwrite($socket, $data);
        if (!smtp_expect_code($socket, 250)) {
            return false;
        }

        smtp_send_cmd($socket, 'QUIT', 221);
        return true;
    } finally {
        fclose($socket);
    }
}

function smtp_send_cmd($socket, string $command, int|array $expectedCodes): bool
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect_code($socket, $expectedCodes);
}

function smtp_expect_code($socket, int|array $expectedCodes): bool
{
    $expected = is_array($expectedCodes) ? $expectedCodes : [$expectedCodes];
    $line = '';

    while (!feof($socket)) {
        $chunk = fgets($socket, 515);
        if ($chunk === false) {
            break;
        }

        $line = $chunk;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    if (strlen($line) < 3) {
        return false;
    }

    $code = (int) substr($line, 0, 3);
    return in_array($code, $expected, true);
}

function smtp_header_subject(string $subject): string
{
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function smtp_header_display_name(string $name): string
{
    if ($name === '') {
        return 'AIMonkey';
    }

    return '=?UTF-8?B?' . base64_encode($name) . '?=';
}
<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/lib/helpers.php';

/**
 * SMTP 发信测试脚本
 * 可直接在 CLI 执行：php test/smtp.php
 */
function test_smtp_send_mail(): void
{
    if (!function_exists('smtp_send_plain_text_mail')) {
        echo "函数 smtp_send_plain_text_mail 不存在，请检查 backend/lib/helpers.php\n";
        return;
    }

    /** @var array{smtp?: array<string, mixed>} $projectConfig */
    $projectConfig = require __DIR__ . '/../backend/config.php';
    $configSmtp = is_array($projectConfig['smtp'] ?? null) ? $projectConfig['smtp'] : [];

    // 默认读取 backend/config.php 的 smtp 配置，也可在此处临时覆盖。
    $smtp = [
        'host' => (string) ($configSmtp['host'] ?? ''),
        'port' => (int) ($configSmtp['port'] ?? 465),
        'secure' => (string) ($configSmtp['secure'] ?? 'ssl'), // 可选: ssl / tls / none
        'username' => (string) ($configSmtp['username'] ?? ''),
        'password' => (string) ($configSmtp['password'] ?? ''), // 不是邮箱登录密码，通常是 SMTP 授权码
        'timeout' => (int) ($configSmtp['timeout'] ?? 10),
        'from_email' => (string) ($configSmtp['from_email'] ?? ''),
        'from_name' => (string) ($configSmtp['from_name'] ?? 'AIMonkey SMTP Test'),
    ];

    $toEmail = 'luke.l.lin@hotmail.com'; // 改成你的目标收件邮箱
    $subject = 'AIMonkey SMTP Test Mail';
    $body = "这是一封 SMTP 测试邮件。\n\n发送时间: " . date('Y-m-d H:i:s') . "\n如果你收到此邮件，说明 SMTP 配置可用。";

    if (
        trim($smtp['host']) === '' ||
        trim($smtp['username']) === '' ||
        trim($smtp['password']) === '' ||
        trim($smtp['from_email']) === '' ||
        trim($toEmail) === ''
    ) {
        echo "SMTP 配置不完整，请先填写 host/username/password/from_email/toEmail。\n";
        return;
    }

    try {
        $result = smtp_send_plain_text_mail($smtp, $toEmail, $subject, $body);

        if ($result) {
            echo "测试邮件发送成功，请检查收件箱（也请查看垃圾箱）。\n";
            return;
        }

        echo "测试邮件发送失败，请检查 SMTP 服务器、端口、安全类型、账号和授权码。\n";
    } catch (Throwable $e) {
        echo "调用失败: " . $e->getMessage() . "\n";
    }
}

test_smtp_send_mail();
<?php

declare(strict_types=1);

function handle_login(PDO $pdo, array $payload): array
{
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        return [
            'status' => 422,
            'body' => ['error' => 'Email and password are required.'],
        ];
    }

    try {
        $statement = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'status' => 401,
                'body' => ['error' => 'Invalid email or password.'],
            ];
        }

        // Optional: Update last login timestamp
        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => $user['id']]);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $email,
                ],
            ],
        ];
    } catch (Throwable $exception) {
        return [
            'status' => 500,
            'body' => ['error' => 'Server error. Please try again later.'],
        ];
    }
}
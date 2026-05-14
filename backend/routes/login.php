<?php

declare(strict_types=1);

function handle_login(PDO $pdo, array $payload): array
{
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $username = trim((string) ($payload['username'] ?? ''));

    if ($email === '' || $password === '') {
        return [
            'status' => 422,
            'body' => ['error' => 'Email and password are required.'],
        ];
    }

    try {
        if ($username !== '') {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE email = :email AND username = :username LIMIT 1');
            $statement->execute(['email' => $email, 'username' => $username]);
        } else {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE email = :email ORDER BY id ASC LIMIT 1');
            $statement->execute(['email' => $email]);
        }
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'status' => 401,
                'body' => ['error' => 'Invalid email or password.'],
            ];
        }

        // Optional: Update last login timestamp
        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        // return [
        //     'status' => 401,
        //     'body' => ['error' => $user['id']],
        // ];
        $update->execute(['id' => $user['id']]);
        
        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'avatar_state' => $user['avatar_state'],
                    'created_at' => $user['created_at'],
                    'last_login_at' => $user['last_login_at'],
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
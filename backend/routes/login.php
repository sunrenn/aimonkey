<?php

declare(strict_types=1);

function handle_login(PDO $pdo, array $payload): array
{
    $identifier = trim((string) ($payload['identifier'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($identifier === '' || $password === '') {
        return [
            'status' => 422,
            'body' => ['error' => 'Username/email and password are required.'],
        ];
    }

    try {
        
        if (str_contains($identifier, '@')) {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE email = :email');
            $statement->execute(['email' => $identifier]);
        } else {
            $statement = $pdo->prepare('SELECT id, username, email, password_hash, avatar_state, created_at, last_login_at FROM users WHERE username = :username');
            $statement->execute(['username' => $identifier]);
        }

        $users = $statement->fetchAll(PDO::FETCH_ASSOC);

        $users_passed = [];

        for ($i = 0; $i < count($users); $i++) {
            $pswdchk = password_verify($password, $users[$i]['password_hash']);
            if ($pswdchk){
                array_push($users_passed,$users[$i]);
            }
        }
        
        
        if (count($users_passed) !== 1) {
            return [
                'status' => 401,
                'body' => ['error' => count($users_passed).' '.'Users Found! Invalid credentials or ambiguous login.'],
            ];
        }
        
        $user = $users_passed[0];

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
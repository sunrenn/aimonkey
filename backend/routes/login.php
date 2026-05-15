<?php

declare(strict_types=1);

function login_response(
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

function handle_login(PDO $pdo, array $payload): array
{
    $identifier = trim((string) ($payload['identifier'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($identifier === '' || $password === '') {
        return login_response(
            422,
            'LOGIN_IDENTIFIER_PASSWORD_REQUIRED',
            'Username/email and password are required.',
            'login.identifierOrPasswordRequired',
            [],
            ['error' => 'Username/email and password are required.']
        );
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
            return login_response(
                401,
                'LOGIN_INVALID_OR_AMBIGUOUS',
                'Invalid credentials or ambiguous login.',
                'login.invalidOrAmbiguous',
                ['matches' => count($users_passed)],
                ['error' => count($users_passed) . ' Users Found! Invalid credentials or ambiguous login.']
            );
        }
        
        $user = $users_passed[0];

        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        return login_response(
            200,
            'LOGIN_SUCCESS',
            'Login successful.',
            'login.success',
            [],
            [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'avatar_state' => $user['avatar_state'],
                    'created_at' => $user['created_at'],
                    'last_login_at' => $user['last_login_at'],
                ],
            ]
        );
    } catch (Throwable $exception) {
        return login_response(
            500,
            'LOGIN_SERVER_ERROR',
            'Server error. Please try again later.',
            'login.serverError',
            [],
            ['error' => 'Server error. Please try again later.']
        );
    }
}
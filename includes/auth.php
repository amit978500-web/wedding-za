<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function wz_csrf_token(): string
{
    if (empty($_SESSION['wz_csrf'])) {
        $_SESSION['wz_csrf'] = bin2hex(random_bytes(24));
    }

    return (string)$_SESSION['wz_csrf'];
}

function wz_csrf_valid(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && hash_equals(wz_csrf_token(), $token);
}

function wz_user(): ?array
{
    $user = $_SESSION['wz_user'] ?? null;

    return is_array($user) ? $user : null;
}

function wz_is_logged_in(): bool
{
    return wz_user() !== null;
}

function wz_role(): ?string
{
    return wz_user()['role'] ?? null;
}

function wz_set_user_session(array $user): void
{
    $_SESSION['wz_user'] = [
        'id' => isset($user['id']) ? (int)$user['id'] : null,
        'name' => trim((string)($user['name'] ?? 'Wedding Za User')),
        'email' => strtolower(trim((string)($user['email'] ?? ''))),
        'role' => (string)($user['role'] ?? 'host'),
        'logged_in_at' => date('c'),
    ];

    session_regenerate_id(true);
}

function wz_login_demo(string $name, string $email, string $role = 'host'): void
{
    wz_set_user_session([
        'id' => null,
        'name' => trim($name) ?: 'Wedding Za User',
        'email' => strtolower(trim($email)),
        'role' => in_array($role, ['host', 'vendor'], true)
            ? $role
            : 'host',
    ]);
}

function wz_register_account(
    string $name,
    string $email,
    string $password,
    string $role = 'host'
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not configured yet.',
        ];
    }

    $role = in_array($role, ['host', 'vendor'], true)
        ? $role
        : 'host';

    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '') {
        return [
            'ok' => false,
            'message' => 'Please enter your name.',
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'ok' => false,
            'message' => 'Please enter a valid email address.',
        ];
    }

    if (strlen($password) < 8) {
        return [
            'ok' => false,
            'message' => 'Use at least 8 characters for your password.',
        ];
    }

    $check = $pdo->prepare(
        'SELECT id FROM users WHERE email = :email LIMIT 1'
    );

    $check->execute([
        'email' => $email,
    ]);

    if ($check->fetch()) {
        return [
            'ok' => false,
            'message' => 'An account already exists for this email.',
        ];
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role)
         VALUES (:name, :email, :password_hash, :role)'
    );

    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    $user = [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ];

    wz_set_user_session($user);

    return [
        'ok' => true,
        'user' => $user,
    ];
}

function wz_login_account(
    string $email,
    string $password,
    string $role = 'host'
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not configured yet.',
        ];
    }

    $email = strtolower(trim($email));

    $statement = $pdo->prepare(
        'SELECT id, name, email, password_hash, role, status
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $statement->execute([
        'email' => $email,
    ]);

    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        return [
            'ok' => false,
            'message' => 'Email or password is incorrect.',
        ];
    }

    if (($user['status'] ?? '') !== 'active') {
        return [
            'ok' => false,
            'message' => 'This account is not active.',
        ];
    }

    if ($role !== '' && ($user['role'] ?? '') !== $role) {
        return [
            'ok' => false,
            'message' => 'This account does not match the selected account type.',
        ];
    }

    wz_set_user_session($user);

    return [
        'ok' => true,
        'user' => $user,
    ];
}

function wz_logout(): void
{
    unset($_SESSION['wz_user']);

    session_regenerate_id(true);
}

function wz_require_role(string $role): void
{
    if (!wz_is_logged_in() || wz_role() !== $role) {
        header('Location: login.php?role=' . urlencode($role));
        exit;
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function wz_client_ip(): string
{
    return substr(
        (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        0,
        64
    );
}

function wz_csrf_token(): string
{
    if (empty($_SESSION['wz_csrf'])) {
        $_SESSION['wz_csrf'] = bin2hex(
            random_bytes(24)
        );
    }

    return (string)$_SESSION['wz_csrf'];
}

function wz_csrf_valid(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && hash_equals(
            wz_csrf_token(),
            $token
        );
}

function wz_user(): ?array
{
    $user = $_SESSION['wz_user'] ?? null;

    return is_array($user)
        ? $user
        : null;
}

function wz_is_logged_in(): bool
{
    return wz_user() !== null;
}

function wz_role(): ?string
{
    return wz_user()['role'] ?? null;
}

function wz_is_admin(): bool
{
    return wz_role() === 'admin';
}

function wz_set_user_session(array $user): void
{
    $_SESSION['wz_user'] = [
        'id' => isset($user['id'])
            ? (int)$user['id']
            : null,
        'name' => trim(
            (string)($user['name'] ?? 'Wedding Za User')
        ),
        'email' => strtolower(
            trim(
                (string)($user['email'] ?? '')
            )
        ),
        'role' => (string)($user['role'] ?? 'host'),
        'logged_in_at' => date('c'),
    ];

    session_regenerate_id(true);
}

function wz_login_demo(
    string $name,
    string $email,
    string $role = 'host'
): void {
    $allowedRoles = [
        'host',
        'vendor',
    ];

    wz_set_user_session([
        'id' => null,
        'name' => trim($name)
            ?: 'Wedding Za User',
        'email' => strtolower(
            trim($email)
        ),
        'role' => in_array(
            $role,
            $allowedRoles,
            true
        )
            ? $role
            : 'host',
    ]);
}

function wz_password_is_strong(string $password): bool
{
    if (strlen($password) < 10) {
        return false;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }

    return true;
}

function wz_login_allowed(
    string $email,
    string $ip
): bool {
    $email = strtolower(
        trim($email)
    );

    $pdo = wz_db();

    if ($pdo) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM login_attempts
             WHERE email = :email
             AND ip = :ip
             AND was_successful = 0
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );

        $statement->execute([
            'email' => $email,
            'ip' => $ip,
        ]);

        return (int)$statement->fetchColumn() < 5;
    }

    $attempts = $_SESSION['wz_login_attempts'] ?? [];
    $key = hash(
        'sha256',
        $email . '|' . $ip
    );

    $recent = array_values(
        array_filter(
            $attempts[$key] ?? [],
            fn (int $timestamp): bool =>
                $timestamp >= time() - 900
        )
    );

    $_SESSION['wz_login_attempts'][$key] = $recent;

    return count($recent) < 5;
}

function wz_record_login_attempt(
    string $email,
    string $ip,
    bool $successful
): void {
    $email = strtolower(
        trim($email)
    );

    $pdo = wz_db();

    if ($pdo) {
        $statement = $pdo->prepare(
            'INSERT INTO login_attempts (
                email,
                ip,
                was_successful
            ) VALUES (
                :email,
                :ip,
                :was_successful
            )'
        );

        $statement->execute([
            'email' => $email,
            'ip' => $ip,
            'was_successful' => $successful
                ? 1
                : 0,
        ]);

        if ($successful) {
            $cleanup = $pdo->prepare(
                'DELETE FROM login_attempts
                 WHERE email = :email
                 AND ip = :ip
                 AND was_successful = 0'
            );

            $cleanup->execute([
                'email' => $email,
                'ip' => $ip,
            ]);
        }

        return;
    }

    if ($successful) {
        return;
    }

    $key = hash(
        'sha256',
        $email . '|' . $ip
    );

    $_SESSION['wz_login_attempts'][$key][] = time();
}

function wz_audit(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    array $metadata = []
): void {
    $pdo = wz_db();

    if (!$pdo) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO audit_log (
            user_id,
            action,
            entity_type,
            entity_id,
            metadata_json,
            ip
        ) VALUES (
            :user_id,
            :action,
            :entity_type,
            :entity_id,
            :metadata_json,
            :ip
        )'
    );

    $statement->execute([
        'user_id' => wz_user()['id'] ?? null,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'metadata_json' => $metadata
            ? json_encode($metadata)
            : null,
        'ip' => wz_client_ip(),
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

    $role = in_array(
        $role,
        [
            'host',
            'vendor',
        ],
        true
    )
        ? $role
        : 'host';

    $name = trim($name);
    $email = strtolower(
        trim($email)
    );

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

    if (!wz_password_is_strong($password)) {
        return [
            'ok' => false,
            'message' => 'Use at least 10 characters with uppercase, lowercase and a number.',
        ];
    }

    $check = $pdo->prepare(
        'SELECT id
         FROM users
         WHERE email = :email
         LIMIT 1'
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
        'INSERT INTO users (
            name,
            email,
            password_hash,
            role
        ) VALUES (
            :name,
            :email,
            :password_hash,
            :role
        )'
    );

    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash(
            $password,
            PASSWORD_DEFAULT
        ),
        'role' => $role,
    ]);

    $user = [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ];

    wz_set_user_session($user);

    wz_audit(
        'account.registered',
        'user',
        $user['id'],
        [
            'role' => $role,
        ]
    );

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

    $email = strtolower(
        trim($email)
    );

    $ip = wz_client_ip();

    if (!wz_login_allowed($email, $ip)) {
        return [
            'ok' => false,
            'message' => 'Too many sign-in attempts. Please wait 15 minutes and try again.',
        ];
    }

    $statement = $pdo->prepare(
        'SELECT
            id,
            name,
            email,
            password_hash,
            role,
            status
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $statement->execute([
        'email' => $email,
    ]);

    $user = $statement->fetch();

    $passwordMatches = $user
        && password_verify(
            $password,
            (string)$user['password_hash']
        );

    if (!$passwordMatches) {
        wz_record_login_attempt(
            $email,
            $ip,
            false
        );

        return [
            'ok' => false,
            'message' => 'Email or password is incorrect.',
        ];
    }

    if (($user['status'] ?? '') !== 'active') {
        wz_record_login_attempt(
            $email,
            $ip,
            false
        );

        return [
            'ok' => false,
            'message' => 'This account is not active.',
        ];
    }

    if (
        $role !== ''
        && ($user['role'] ?? '') !== $role
    ) {
        wz_record_login_attempt(
            $email,
            $ip,
            false
        );

        return [
            'ok' => false,
            'message' => 'This account does not match the selected account type.',
        ];
    }

    wz_set_user_session($user);

    wz_record_login_attempt(
        $email,
        $ip,
        true
    );

    wz_audit(
        'account.login',
        'user',
        (int)$user['id'],
        [
            'role' => $user['role'],
        ]
    );

    return [
        'ok' => true,
        'user' => $user,
    ];
}

function wz_logout(): void
{
    if (wz_is_logged_in()) {
        wz_audit(
            'account.logout',
            'user',
            isset(wz_user()['id'])
                ? (int)wz_user()['id']
                : null
        );
    }

    unset(
        $_SESSION['wz_user']
    );

    session_regenerate_id(true);
}

function wz_require_role(string $role): void
{
    if (
        !wz_is_logged_in()
        || wz_role() !== $role
    ) {
        if ($role === 'admin') {
            header(
                'Location: ' .
                wz_app_url('admin/login.php')
            );

            exit;
        }

        header(
            'Location: login.php?role=' .
            urlencode($role)
        );

        exit;
    }
}

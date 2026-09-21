<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function wz_csrf_token(): string {
    if (empty($_SESSION['wz_csrf'])) {
        $_SESSION['wz_csrf'] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION['wz_csrf'];
}
function wz_csrf_valid(?string $token): bool {
    return is_string($token) && $token !== '' && hash_equals(wz_csrf_token(), $token);
}
function wz_user(): ?array {
    $user = $_SESSION['wz_user'] ?? null;
    return is_array($user) ? $user : null;
}
function wz_is_logged_in(): bool {
    return wz_user() !== null;
}
function wz_role(): ?string {
    return wz_user()['role'] ?? null;
}
function wz_login_demo(string $name, string $email, string $role='host'): void {
    $_SESSION['wz_user'] = [
    'name' => trim($name) ?: 'Wedding Za User',
    'email' => strtolower(trim($email)),
    'role' => in_array($role, ['host','vendor'], true) ? $role : 'host',
    'logged_in_at' => date('c')
    ];
    session_regenerate_id(true);
}
function wz_logout(): void {
    unset($_SESSION['wz_user']);
    session_regenerate_id(true);
}
function wz_require_role(string $role): void {
    if (!wz_is_logged_in() || wz_role() !== $role) {
        header('Location: login.php?role=' . urlencode($role));
        exit;
    }
}

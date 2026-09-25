<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/database.php';

$pdo = wz_db();

if (!$pdo) {
    fwrite(STDERR, "Database is not configured or unavailable.\n");
    exit(1);
}

$name = trim((string)($argv[1] ?? ''));
$email = strtolower(trim((string)($argv[2] ?? '')));
$password = (string)($argv[3] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(
        STDERR,
        "Usage: php scripts/create-admin.php \"Admin Name\" admin@example.com \"StrongPassword\"\n"
    );

    exit(1);
}

if (strlen($password) < 12) {
    fwrite(
        STDERR,
        "Admin password must contain at least 12 characters.\n"
    );

    exit(1);
}

$statement = $pdo->prepare(
    'INSERT INTO users (
        name,
        email,
        password_hash,
        role,
        status
    ) VALUES (
        :name,
        :email,
        :password_hash,
        :role,
        :status
    )
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        password_hash = VALUES(password_hash),
        role = VALUES(role),
        status = VALUES(status)'
);

$statement->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash(
        $password,
        PASSWORD_DEFAULT
    ),
    'role' => 'admin',
    'status' => 'active',
]);

fwrite(
    STDOUT,
    "Admin account created or updated for {$email}.\n"
);

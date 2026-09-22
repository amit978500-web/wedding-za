<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function wz_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $config = [
        'app_url' => getenv('WZ_APP_URL') ?: '',
        'database' => [
            'host' => getenv('WZ_DB_HOST') ?: '',
            'port' => (int)(getenv('WZ_DB_PORT') ?: 3306),
            'name' => getenv('WZ_DB_NAME') ?: '',
            'user' => getenv('WZ_DB_USER') ?: '',
            'password' => getenv('WZ_DB_PASSWORD') ?: '',
            'charset' => getenv('WZ_DB_CHARSET') ?: 'utf8mb4',
        ],
    ];

    $localConfig = WZ_ROOT . '/config.local.php';

    if (is_file($localConfig)) {
        $local = require $localConfig;

        if (is_array($local)) {
            $config = array_replace_recursive($config, $local);
        }
    }

    return $config;
}

function wz_db_is_configured(): bool
{
    $database = wz_config()['database'] ?? [];

    return !empty($database['host'])
        && !empty($database['name'])
        && !empty($database['user']);
}

function wz_db(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }

    $attempted = true;

    if (!wz_db_is_configured()) {
        return null;
    }

    $database = wz_config()['database'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $database['host'],
        $database['port'],
        $database['name'],
        $database['charset']
    );

    try {
        $pdo = new PDO(
            $dsn,
            $database['user'],
            $database['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log('Wedding Za database connection failed: ' . $exception->getMessage());
        $pdo = null;
    }

    return $pdo;
}

function wz_database_ready(): bool
{
    return wz_db() instanceof PDO;
}

function wz_app_url(string $path = ''): string
{
    $configuredUrl = trim((string)(wz_config()['app_url'] ?? ''));

    if ($configuredUrl !== '') {
        $base = rtrim($configuredUrl, '/');
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host;
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

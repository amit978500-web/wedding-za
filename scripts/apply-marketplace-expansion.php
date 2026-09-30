<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "Run this migration from the command line.\n"
    );

    exit(1);
}

$pdo = wz_db();

if (!$pdo) {
    fwrite(
        STDERR,
        "Database is not configured or reachable.\n"
    );

    exit(1);
}

$check = $pdo->query(
    "SELECT COUNT(*)
     FROM information_schema.tables
     WHERE table_schema = DATABASE()
     AND table_name = 'marketplace_reviews'"
);

if ((int)$check->fetchColumn() > 0) {
    fwrite(
        STDOUT,
        "Marketplace expansion already appears to be installed.\n"
    );

    exit(0);
}

$file = dirname(__DIR__)
    . '/database/migrations/007-marketplace-expansion.sql';

$sql = file_get_contents($file);

if ($sql === false) {
    fwrite(
        STDERR,
        "Could not read migration 007.\n"
    );

    exit(1);
}

$statements = preg_split(
    '/;\s*(?:\r?\n|$)/',
    $sql
) ?: [];

$applied = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);

    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
        $applied++;

        fwrite(
            STDOUT,
            "Applied statement "
            . $applied
            . ".\n"
        );
    } catch (Throwable $exception) {
        fwrite(
            STDERR,
            "Migration stopped at statement "
            . ($applied + 1)
            . ": "
            . $exception->getMessage()
            . "\n"
        );

        exit(1);
    }
}

fwrite(
    STDOUT,
    "Wedding Za marketplace expansion installed successfully.\n"
);

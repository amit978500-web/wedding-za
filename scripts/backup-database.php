<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/database.php';

$config = wz_config();
$database = $config['database'] ?? [];

if (!wz_db_is_configured()) {
    fwrite(
        STDERR,
        "Database configuration is missing.\n"
    );

    exit(1);
}

$backupDirectory = dirname(__DIR__) . '/storage/backups';

if (
    !is_dir($backupDirectory)
    && !mkdir(
        $backupDirectory,
        0770,
        true
    )
    && !is_dir($backupDirectory)
) {
    fwrite(
        STDERR,
        "Could not create backup directory.\n"
    );

    exit(1);
}

$filename = 'wedding-za-' .
    date('Ymd-His') .
    '.sql';

$targetPath = $backupDirectory .
    '/' .
    $filename;

$command = sprintf(
    'MYSQL_PWD=%s mysqldump --single-transaction --quick --host=%s --port=%d --user=%s %s > %s',
    escapeshellarg(
        (string)$database['password']
    ),
    escapeshellarg(
        (string)$database['host']
    ),
    (int)$database['port'],
    escapeshellarg(
        (string)$database['user']
    ),
    escapeshellarg(
        (string)$database['name']
    ),
    escapeshellarg(
        $targetPath
    )
);

exec(
    $command,
    $output,
    $exitCode
);

if ($exitCode !== 0) {
    @unlink($targetPath);

    fwrite(
        STDERR,
        "Database backup failed. Ensure mysqldump is installed.\n"
    );

    exit(1);
}

chmod(
    $targetPath,
    0600
);

fwrite(
    STDOUT,
    "Backup created: {$targetPath}\n"
);

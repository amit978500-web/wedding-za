<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "Run this upgrade from the command line.\n"
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

function wz_upgrade_column_exists(
    PDO $pdo,
    string $table,
    string $column
): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
         AND table_name = :table_name
         AND column_name = :column_name'
    );

    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int)$statement->fetchColumn() > 0;
}

function wz_upgrade_table_exists(
    PDO $pdo,
    string $table
): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name = :table_name'
    );

    $statement->execute([
        'table_name' => $table,
    ]);

    return (int)$statement->fetchColumn() > 0;
}

function wz_upgrade_index_exists(
    PDO $pdo,
    string $table,
    string $index
): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.statistics
         WHERE table_schema = DATABASE()
         AND table_name = :table_name
         AND index_name = :index_name'
    );

    $statement->execute([
        'table_name' => $table,
        'index_name' => $index,
    ]);

    return (int)$statement->fetchColumn() > 0;
}

function wz_upgrade_run(
    PDO $pdo,
    string $label,
    string $sql
): void {
    fwrite(
        STDOUT,
        'Applying: ' . $label . "...\n"
    );

    $pdo->exec($sql);

    fwrite(
        STDOUT,
        'Done: ' . $label . "\n"
    );
}

fwrite(
    STDOUT,
    "Wedding Za database upgrade started.\n\n"
);

/*
|--------------------------------------------------------------------------
| Migration 005 prerequisites
|--------------------------------------------------------------------------
*/

if (!wz_upgrade_column_exists(
    $pdo,
    'crm_enquiries',
    'site_visit_at'
)) {
    wz_upgrade_run(
        $pdo,
        'crm_enquiries.site_visit_at',
        'ALTER TABLE crm_enquiries
         ADD COLUMN site_visit_at DATETIME DEFAULT NULL
         AFTER next_follow_up'
    );
}

if (!wz_upgrade_column_exists(
    $pdo,
    'crm_enquiries',
    'site_visit_status'
)) {
    wz_upgrade_run(
        $pdo,
        'crm_enquiries.site_visit_status',
        "ALTER TABLE crm_enquiries
         ADD COLUMN site_visit_status ENUM(
            'not_scheduled',
            'scheduled',
            'completed',
            'cancelled'
         ) NOT NULL DEFAULT 'not_scheduled'
         AFTER site_visit_at"
    );
}

if (!wz_upgrade_index_exists(
    $pdo,
    'crm_enquiries',
    'crm_enquiries_site_visit_index'
)) {
    wz_upgrade_run(
        $pdo,
        'crm_enquiries site visit index',
        'ALTER TABLE crm_enquiries
         ADD INDEX crm_enquiries_site_visit_index (
            business_user_id,
            business_type,
            site_visit_at
         )'
    );
}

if (!wz_upgrade_table_exists(
    $pdo,
    'crm_payments'
)) {
    wz_upgrade_run(
        $pdo,
        'crm_payments table',
        "CREATE TABLE crm_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            booking_id BIGINT UNSIGNED NOT NULL,
            customer_user_id BIGINT UNSIGNED DEFAULT NULL,
            business_user_id BIGINT UNSIGNED NOT NULL,
            business_type ENUM('vendor', 'venue') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            method ENUM(
                'cash',
                'bank_transfer',
                'upi',
                'card',
                'cheque',
                'other'
            ) NOT NULL DEFAULT 'bank_transfer',
            status ENUM(
                'pending',
                'received',
                'refunded',
                'failed'
            ) NOT NULL DEFAULT 'received',
            reference VARCHAR(180) DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
            INDEX crm_payments_booking_index (booking_id),
            INDEX crm_payments_business_index (
                business_user_id,
                business_type,
                paid_at
            ),
            INDEX crm_payments_customer_index (customer_user_id),
            CONSTRAINT crm_payments_booking_fk
                FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
                ON DELETE CASCADE,
            CONSTRAINT crm_payments_customer_fk
                FOREIGN KEY (customer_user_id) REFERENCES users(id)
                ON DELETE SET NULL,
            CONSTRAINT crm_payments_business_fk
                FOREIGN KEY (business_user_id) REFERENCES users(id)
                ON DELETE CASCADE,
            CONSTRAINT crm_payments_creator_fk
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci"
    );
}

/*
|--------------------------------------------------------------------------
| Migration 006 prerequisites
|--------------------------------------------------------------------------
*/

$adminMigrationFile =
    dirname(__DIR__)
    . '/database/migrations/006-admin-crm-operations.sql';

if (
    !wz_upgrade_table_exists(
        $pdo,
        'crm_invoices'
    )
    || !wz_upgrade_table_exists(
        $pdo,
        'crm_commissions'
    )
    || !wz_upgrade_table_exists(
        $pdo,
        'admin_team_profiles'
    )
) {
    $adminSql = file_get_contents(
        $adminMigrationFile
    );

    if ($adminSql === false) {
        fwrite(
            STDERR,
            "Could not read migration 006.\n"
        );

        exit(1);
    }

    $adminStatements = preg_split(
        '/;\s*(?:\r?\n|$)/',
        $adminSql
    ) ?: [];

    foreach ($adminStatements as $statement) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        if (
            str_contains(
                $statement,
                'CREATE TABLE crm_invoices'
            )
            && wz_upgrade_table_exists(
                $pdo,
                'crm_invoices'
            )
        ) {
            continue;
        }

        if (
            str_contains(
                $statement,
                'CREATE TABLE crm_commissions'
            )
            && wz_upgrade_table_exists(
                $pdo,
                'crm_commissions'
            )
        ) {
            continue;
        }

        if (
            str_contains(
                $statement,
                'CREATE TABLE admin_team_profiles'
            )
            && wz_upgrade_table_exists(
                $pdo,
                'admin_team_profiles'
            )
        ) {
            continue;
        }

        $pdo->exec($statement);
    }

    fwrite(
        STDOUT,
        "Migration 006 prerequisites checked.\n"
    );
}

/*
|--------------------------------------------------------------------------
| Migration 007 marketplace expansion
|--------------------------------------------------------------------------
*/

if (!wz_upgrade_table_exists(
    $pdo,
    'marketplace_reviews'
)) {
    $marketplaceFile =
        dirname(__DIR__)
        . '/database/migrations/007-marketplace-expansion.sql';

    $marketplaceSql = file_get_contents(
        $marketplaceFile
    );

    if ($marketplaceSql === false) {
        fwrite(
            STDERR,
            "Could not read migration 007.\n"
        );

        exit(1);
    }

    $marketplaceStatements = preg_split(
        '/;\s*(?:\r?\n|$)/',
        $marketplaceSql
    ) ?: [];

    foreach (
        $marketplaceStatements
        as $statement
    ) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
    }

    fwrite(
        STDOUT,
        "Migration 007 marketplace expansion installed.\n"
    );
} else {
    fwrite(
        STDOUT,
        "Migration 007 already appears installed.\n"
    );
}

/*
|--------------------------------------------------------------------------
| Migration 008 notification delivery
|--------------------------------------------------------------------------
*/

if (
    !wz_upgrade_table_exists(
        $pdo,
        'notification_preferences'
    )
    || !wz_upgrade_table_exists(
        $pdo,
        'notification_delivery_log'
    )
) {
    $notificationFile =
        dirname(__DIR__)
        . '/database/migrations/008-notification-delivery.sql';

    $notificationSql = file_get_contents(
        $notificationFile
    );

    if ($notificationSql === false) {
        fwrite(
            STDERR,
            "Could not read migration 008.\n"
        );

        exit(1);
    }

    $notificationStatements = preg_split(
        '/;\s*(?:\r?\n|$)/',
        $notificationSql
    ) ?: [];

    foreach (
        $notificationStatements
        as $statement
    ) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        if (
            str_contains(
                $statement,
                'CREATE TABLE notification_preferences'
            )
            && wz_upgrade_table_exists(
                $pdo,
                'notification_preferences'
            )
        ) {
            continue;
        }

        if (
            str_contains(
                $statement,
                'CREATE TABLE notification_delivery_log'
            )
            && wz_upgrade_table_exists(
                $pdo,
                'notification_delivery_log'
            )
        ) {
            continue;
        }

        $pdo->exec($statement);
    }

    fwrite(
        STDOUT,
        "Migration 008 notification delivery installed.\n"
    );
} else {
    fwrite(
        STDOUT,
        "Migration 008 already appears installed.\n"
    );
}

fwrite(
    STDOUT,
    "\nWedding Za database upgrade completed successfully.\n"
);

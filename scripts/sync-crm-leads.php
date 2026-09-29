<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/crm.php';

$pdo = wz_db();

if (!$pdo) {
    fwrite(
        STDERR,
        "Database is not configured or unavailable.\n"
    );

    exit(1);
}

$statement = $pdo->query(
    "SELECT id
     FROM leads
     WHERE type = 'vendor-enquiry'
        OR (
            vendor IS NOT NULL
            AND vendor <> ''
        )
     ORDER BY id"
);

$leadIds = $statement->fetchAll(
    PDO::FETCH_COLUMN
);

$created = 0;
$skipped = 0;

foreach ($leadIds as $leadId) {
    $before = $pdo->prepare(
        'SELECT id
         FROM crm_enquiries
         WHERE source_lead_id = :source_lead_id
         LIMIT 1'
    );

    $before->execute([
        'source_lead_id' => (int)$leadId,
    ]);

    if ($before->fetchColumn()) {
        $skipped++;
        continue;
    }

    $enquiryId = wz_crm_sync_lead(
        (int)$leadId
    );

    if ($enquiryId) {
        $created++;
    }
}

fwrite(
    STDOUT,
    "CRM lead sync complete. Created: {$created}. Existing: {$skipped}.\n"
);

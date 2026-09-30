<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/includes/admin-crm.php';

$pdo = wz_db();

if (!$pdo) {
    fwrite(
        STDERR,
        "Admin CRM QA requires MySQL.\n"
    );

    exit(1);
}

$emails = [
    'qa-admin-ops@weddingza.test',
    'qa-admin-customer@weddingza.test',
    'qa-admin-venue@weddingza.test',
];

$placeholders = implode(
    ',',
    array_fill(
        0,
        count($emails),
        '?'
    )
);

$existing = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE email IN ({$placeholders})"
);

$existing->execute($emails);

$oldUserIds = array_map(
    'intval',
    $existing->fetchAll(
        PDO::FETCH_COLUMN
    )
);

if ($oldUserIds) {
    $userPlaceholders = implode(
        ',',
        array_fill(
            0,
            count($oldUserIds),
            '?'
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_invoices
         WHERE customer_user_id IN ({$userPlaceholders})
         OR business_user_id IN ({$userPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_commissions
         WHERE business_user_id IN ({$userPlaceholders})"
    )->execute($oldUserIds);

    $pdo->prepare(
        "DELETE FROM crm_payments
         WHERE customer_user_id IN ({$userPlaceholders})
         OR business_user_id IN ({$userPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_bookings
         WHERE customer_user_id IN ({$userPlaceholders})
         OR business_user_id IN ({$userPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_enquiries
         WHERE customer_user_id IN ({$userPlaceholders})
         OR business_user_id IN ({$userPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM leads
         WHERE user_id IN ({$userPlaceholders})"
    )->execute($oldUserIds);

    $pdo->prepare(
        "DELETE FROM users
         WHERE id IN ({$userPlaceholders})"
    )->execute($oldUserIds);
}

$insertUser = $pdo->prepare(
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
    )'
);

$makeUser = function (
    string $name,
    string $email,
    string $role
) use (
    $insertUser,
    $pdo
): int {
    $insertUser->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash(
            'Testing12345',
            PASSWORD_DEFAULT
        ),
        'role' => $role,
        'status' => 'active',
    ]);

    return (int)$pdo->lastInsertId();
};

$adminId = $makeUser(
    'QA Admin Ops',
    $emails[0],
    'admin'
);

$customerId = $makeUser(
    'QA Admin Customer',
    $emails[1],
    'host'
);

$venueId = $makeUser(
    'QA Admin Venue Owner',
    $emails[2],
    'venue'
);

$pdo->prepare(
    'INSERT INTO admin_team_profiles (
        user_id,
        job_title,
        phone
    ) VALUES (
        :user_id,
        :job_title,
        :phone
    )'
)->execute([
    'user_id' => $adminId,
    'job_title' => 'Operations Manager',
    'phone' => '+91 99999 22222',
]);

$pdo->prepare(
    'INSERT INTO venue_profiles (
        user_id,
        venue_name,
        city,
        venue_type,
        approval_status
    ) VALUES (
        :user_id,
        :venue_name,
        :city,
        :venue_type,
        :approval_status
    )'
)->execute([
    'user_id' => $venueId,
    'venue_name' => 'Admin QA Palace',
    'city' => 'Jaipur',
    'venue_type' => 'Palace',
    'approval_status' => 'approved',
]);

$pdo->prepare(
    'INSERT INTO leads (
        user_id,
        type,
        name,
        email,
        city,
        vendor,
        category,
        event_date,
        topic,
        message
    ) VALUES (
        :user_id,
        :type,
        :name,
        :email,
        :city,
        :vendor,
        :category,
        :event_date,
        :topic,
        :message
    )'
)->execute([
    'user_id' => $customerId,
    'type' => 'vendor-enquiry',
    'name' => 'QA Admin Customer',
    'email' => $emails[1],
    'city' => 'Jaipur',
    'vendor' => 'Admin QA Palace',
    'category' => 'Venues',
    'event_date' => '2027-05-20',
    'topic' => 'Wedding',
    'message' => 'Admin CRM QA enquiry',
]);

$leadId = (int)$pdo->lastInsertId();

$enquiryId = wz_crm_sync_lead(
    $leadId
);

if (!$enquiryId) {
    throw new RuntimeException(
        'Admin CRM lead synchronization failed.'
    );
}

$pdo->prepare(
    'UPDATE crm_enquiries
     SET next_follow_up = :next_follow_up,
         site_visit_at = :site_visit_at,
         site_visit_status = :site_visit_status
     WHERE id = :id'
)->execute([
    'next_follow_up' => '2027-01-15 10:00:00',
    'site_visit_at' => '2027-01-18 15:00:00',
    'site_visit_status' => 'scheduled',
    'id' => $enquiryId,
]);

$newLeads = wz_admin_crm_leads('new');

if (!array_filter(
    $newLeads,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'Admin New Leads filter failed.'
    );
}

$followUps = wz_admin_crm_leads('followups');

if (!array_filter(
    $followUps,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'Admin Follow-ups filter failed.'
    );
}

$siteVisits = wz_admin_crm_leads('site-visits');

if (!array_filter(
    $siteVisits,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'Admin Site Visits filter failed.'
    );
}

$bookingId = wz_crm_create_booking_from_enquiry(
    $enquiryId
);

if (!$bookingId) {
    throw new RuntimeException(
        'Admin booking creation failed.'
    );
}

$pdo->prepare(
    'UPDATE crm_bookings
     SET amount = :amount,
         status = :status
     WHERE id = :id'
)->execute([
    'amount' => 300000,
    'status' => 'confirmed',
    'id' => $bookingId,
]);

$payment = wz_venue_add_payment(
    $venueId,
    $bookingId,
    [
        'amount' => 60000,
        'method' => 'bank_transfer',
        'status' => 'received',
        'reference' => 'ADMIN-QA-001',
        'paid_at' => '2027-01-05 10:00:00',
        'notes' => 'Admin CRM QA payment',
    ]
);

if (!$payment['ok']) {
    throw new RuntimeException(
        'Admin payment data setup failed.'
    );
}

$invoice = wz_admin_crm_create_invoice(
    [
        'invoice_number' => 'WZ-QA-ADMIN-001',
        'booking_id' => $bookingId,
        'subtotal' => 300000,
        'tax_amount' => 54000,
        'status' => 'issued',
        'due_date' => '2027-02-01',
        'notes' => 'Admin CRM QA invoice',
    ]
);

if (!$invoice['ok']) {
    throw new RuntimeException(
        'Admin invoice creation failed.'
    );
}

$commission = wz_admin_crm_save_commission(
    [
        'booking_id' => $bookingId,
        'gross_amount' => 300000,
        'commission_rate' => 10,
        'status' => 'pending',
        'due_date' => '2027-02-05',
        'notes' => 'Admin CRM QA commission',
    ]
);

if (!$commission['ok']) {
    throw new RuntimeException(
        'Admin commission creation failed.'
    );
}

$functions = wz_admin_crm_functions(
    'upcoming'
);

if (!array_filter(
    $functions,
    fn (array $function): bool =>
        (int)$function['id'] === $bookingId
)) {
    throw new RuntimeException(
        'Admin Upcoming Functions view failed.'
    );
}

$payments = wz_admin_crm_payments();

if (!array_filter(
    $payments,
    fn (array $item): bool =>
        (int)$item['booking_id'] === $bookingId
)) {
    throw new RuntimeException(
        'Admin Payments view failed.'
    );
}

$invoices = wz_admin_crm_invoices();

if (!array_filter(
    $invoices,
    fn (array $item): bool =>
        (int)$item['booking_id'] === $bookingId
)) {
    throw new RuntimeException(
        'Admin Invoices view failed.'
    );
}

$commissions = wz_admin_crm_commissions();

if (!array_filter(
    $commissions,
    fn (array $item): bool =>
        (int)$item['booking_id'] === $bookingId
)) {
    throw new RuntimeException(
        'Admin Commission view failed.'
    );
}

$report = wz_admin_crm_report_summary();

if (
    (int)($report['total_leads'] ?? 0) < 1
    || (int)($report['total_bookings'] ?? 0) < 1
    || (float)($report['received'] ?? 0) < 60000
    || (float)($report['commission_value'] ?? 0) < 30000
) {
    throw new RuntimeException(
        'Admin Reports summary failed.'
    );
}

$pdo->prepare(
    'DELETE FROM crm_invoices
     WHERE booking_id = :booking_id'
)->execute([
    'booking_id' => $bookingId,
]);

$pdo->prepare(
    'DELETE FROM crm_commissions
     WHERE booking_id = :booking_id'
)->execute([
    'booking_id' => $bookingId,
]);

$pdo->prepare(
    'DELETE FROM crm_payments
     WHERE booking_id = :booking_id'
)->execute([
    'booking_id' => $bookingId,
]);

$pdo->prepare(
    'DELETE FROM crm_bookings
     WHERE id = :id'
)->execute([
    'id' => $bookingId,
]);

$pdo->prepare(
    'DELETE FROM crm_enquiries
     WHERE id = :id'
)->execute([
    'id' => $enquiryId,
]);

$pdo->prepare(
    'DELETE FROM leads
     WHERE id = :id'
)->execute([
    'id' => $leadId,
]);

$pdo->prepare(
    'DELETE FROM users
     WHERE id IN (?, ?, ?)'
)->execute([
    $adminId,
    $customerId,
    $venueId,
]);

fwrite(
    STDOUT,
    "Wedding Za Admin CRM operations QA passed.\n"
);

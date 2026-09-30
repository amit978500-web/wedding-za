<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/includes/crm.php';

$pdo = wz_db();

if (!$pdo) {
    fwrite(
        STDERR,
        "Venue CRM QA requires MySQL.\n"
    );

    exit(1);
}

$customerEmail = 'qa-venue-customer@weddingza.test';
$venueEmail = 'qa-venue-owner@weddingza.test';

$cleanupUsers = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE email IN (?, ?)'
);

$cleanupUsers->execute([
    $customerEmail,
    $venueEmail,
]);

$oldUserIds = array_map(
    'intval',
    $cleanupUsers->fetchAll(
        PDO::FETCH_COLUMN
    )
);

if ($oldUserIds) {
    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($oldUserIds),
            '?'
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_payments
         WHERE business_user_id IN ({$placeholders})
         OR customer_user_id IN ({$placeholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_bookings
         WHERE business_user_id IN ({$placeholders})
         OR customer_user_id IN ({$placeholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_enquiries
         WHERE business_user_id IN ({$placeholders})
         OR customer_user_id IN ({$placeholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM leads
         WHERE user_id IN ({$placeholders})"
    )->execute($oldUserIds);

    $pdo->prepare(
        "DELETE FROM users
         WHERE id IN ({$placeholders})"
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

$insertUser->execute([
    'name' => 'Venue QA Customer',
    'email' => $customerEmail,
    'password_hash' => password_hash(
        'Testing12345',
        PASSWORD_DEFAULT
    ),
    'role' => 'host',
    'status' => 'active',
]);

$customerId = (int)$pdo->lastInsertId();

$insertUser->execute([
    'name' => 'Venue QA Owner',
    'email' => $venueEmail,
    'password_hash' => password_hash(
        'Testing12345',
        PASSWORD_DEFAULT
    ),
    'role' => 'venue',
    'status' => 'active',
]);

$venueId = (int)$pdo->lastInsertId();

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
    'venue_name' => 'Venue QA Palace',
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
    'name' => 'Venue QA Customer',
    'email' => $customerEmail,
    'city' => 'Jaipur',
    'vendor' => 'Venue QA Palace',
    'category' => 'Venues',
    'event_date' => '2027-03-14',
    'topic' => 'Wedding',
    'message' => 'Venue operations QA enquiry',
]);

$leadId = (int)$pdo->lastInsertId();

$enquiryId = wz_crm_sync_lead(
    $leadId
);

if (!$enquiryId) {
    throw new RuntimeException(
        'Venue enquiry synchronization failed.'
    );
}

$venueEnquiry = wz_crm_enquiry_for_user(
    $enquiryId,
    $venueId,
    'venue'
);

if (!$venueEnquiry) {
    throw new RuntimeException(
        'Venue lead assignment failed.'
    );
}

$pdo->prepare(
    'UPDATE crm_enquiries
     SET next_follow_up = :next_follow_up,
         site_visit_at = :site_visit_at,
         site_visit_status = :site_visit_status
     WHERE id = :id'
)->execute([
    'next_follow_up' => '2027-01-10 11:00:00',
    'site_visit_at' => '2027-01-12 15:00:00',
    'site_visit_status' => 'scheduled',
    'id' => $enquiryId,
]);

$newLeads = wz_venue_leads(
    $venueId,
    'new'
);

if (!array_filter(
    $newLeads,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'New Leads view failed.'
    );
}

$followUps = wz_venue_leads(
    $venueId,
    'followups'
);

if (!array_filter(
    $followUps,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'Follow-ups view failed.'
    );
}

$siteVisits = wz_venue_leads(
    $venueId,
    'site-visits'
);

if (!array_filter(
    $siteVisits,
    fn (array $lead): bool =>
        (int)$lead['id'] === $enquiryId
)) {
    throw new RuntimeException(
        'Site Visits view failed.'
    );
}

$bookingId = wz_crm_create_booking_from_enquiry(
    $enquiryId
);

if (!$bookingId) {
    throw new RuntimeException(
        'Venue booking creation failed.'
    );
}

$pdo->prepare(
    'UPDATE crm_bookings
     SET amount = :amount,
         status = :status
     WHERE id = :id'
)->execute([
    'amount' => 250000,
    'status' => 'confirmed',
    'id' => $bookingId,
]);

$paymentResult = wz_venue_add_payment(
    $venueId,
    $bookingId,
    [
        'amount' => 50000,
        'method' => 'upi',
        'status' => 'received',
        'reference' => 'QA-UPI-001',
        'paid_at' => '2027-01-05 10:00:00',
        'notes' => 'QA advance payment',
    ]
);

if (!$paymentResult['ok']) {
    throw new RuntimeException(
        'Venue payment recording failed.'
    );
}

$booking = wz_crm_booking_for_user(
    $bookingId,
    $venueId,
    'venue'
);

if (
    !$booking
    || (float)$booking['deposit_amount'] !== 50000.0
    || $booking['payment_status'] !== 'partial'
) {
    throw new RuntimeException(
        'Venue payment status synchronization failed.'
    );
}

$paymentTotals = wz_venue_payment_totals(
    $venueId
);

if (
    (float)$paymentTotals['received'] !== 50000.0
    || (float)$paymentTotals['booked_value'] !== 250000.0
    || (float)$paymentTotals['balance'] !== 200000.0
) {
    throw new RuntimeException(
        'Venue payment totals are incorrect.'
    );
}

$upcomingFunctions = wz_venue_functions(
    $venueId,
    'upcoming'
);

if (!array_filter(
    $upcomingFunctions,
    fn (array $function): bool =>
        (int)$function['id'] === $bookingId
)) {
    throw new RuntimeException(
        'Upcoming Functions view failed.'
    );
}

$report = wz_venue_report_summary(
    $venueId
);

if (
    (int)($report['total_leads'] ?? 0) !== 1
    || (int)($report['total_bookings'] ?? 0) !== 1
    || (float)($report['received'] ?? 0) !== 50000.0
) {
    throw new RuntimeException(
        'Venue Reports summary failed.'
    );
}

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
     WHERE id IN (?, ?)'
)->execute([
    $customerId,
    $venueId,
]);

fwrite(
    STDOUT,
    "Wedding Za Venue CRM operations QA passed.\n"
);

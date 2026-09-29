<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/includes/crm.php';
require dirname(__DIR__) . '/includes/vendors.php';

$pdo = wz_db();

if (!$pdo) {
    fwrite(
        STDERR,
        "CRM QA requires MySQL.\n"
    );

    exit(1);
}

$emails = [
    'qa-customer@weddingza.test',
    'qa-vendor@weddingza.test',
    'qa-venue@weddingza.test',
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
    $oldPlaceholders = implode(
        ',',
        array_fill(
            0,
            count($oldUserIds),
            '?'
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_messages
         WHERE sender_user_id IN ({$oldPlaceholders})
         OR recipient_user_id IN ({$oldPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_tasks
         WHERE owner_user_id IN ({$oldPlaceholders})
         OR created_by IN ({$oldPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_bookings
         WHERE customer_user_id IN ({$oldPlaceholders})
         OR business_user_id IN ({$oldPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM crm_enquiries
         WHERE customer_user_id IN ({$oldPlaceholders})
         OR business_user_id IN ({$oldPlaceholders})"
    )->execute(
        array_merge(
            $oldUserIds,
            $oldUserIds
        )
    );

    $pdo->prepare(
        "DELETE FROM leads
         WHERE user_id IN ({$oldPlaceholders})"
    )->execute($oldUserIds);

    $pdo->prepare(
        "DELETE FROM users
         WHERE id IN ({$oldPlaceholders})"
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

$customerId = $makeUser(
    'QA Customer',
    $emails[0],
    'host'
);

$vendorId = $makeUser(
    'QA Vendor Owner',
    $emails[1],
    'vendor'
);

$venueId = $makeUser(
    'QA Venue Owner',
    $emails[2],
    'venue'
);

$pdo->prepare(
    'INSERT INTO vendor_profiles (
        user_id,
        business_name,
        category,
        city,
        events_json,
        approval_status
    ) VALUES (
        :user_id,
        :business_name,
        :category,
        :city,
        :events_json,
        :approval_status
    )'
)->execute([
    'user_id' => $vendorId,
    'business_name' => 'QA Vendor',
    'category' => 'Photography & Films',
    'city' => 'Jaipur',
    'events_json' => json_encode([
        'Wedding',
    ]),
    'approval_status' => 'approved',
]);

$pdo->prepare(
    'INSERT INTO venue_profiles (
        user_id,
        venue_name,
        city,
        venue_type,
        events_json,
        approval_status
    ) VALUES (
        :user_id,
        :venue_name,
        :city,
        :venue_type,
        :events_json,
        :approval_status
    )'
)->execute([
    'user_id' => $venueId,
    'venue_name' => 'QA Palace',
    'city' => 'Jaipur',
    'venue_type' => 'Palace',
    'events_json' => json_encode([
        'Wedding',
    ]),
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
    'name' => 'QA Customer',
    'email' => $emails[0],
    'city' => 'Jaipur',
    'vendor' => 'QA Vendor',
    'category' => 'Photography & Films',
    'event_date' => '2027-02-20',
    'topic' => 'Wedding',
    'message' => 'QA enquiry',
]);

$leadId = (int)$pdo->lastInsertId();

$enquiryId = wz_crm_sync_lead(
    $leadId
);

if (!$enquiryId) {
    throw new RuntimeException(
        'CRM enquiry sync failed.'
    );
}

$enquiry = wz_crm_enquiry_for_user(
    $enquiryId,
    $vendorId,
    'vendor'
);

if (
    !$enquiry
    || (int)$enquiry['customer_user_id'] !== $customerId
) {
    throw new RuntimeException(
        'Vendor CRM enquiry assignment failed.'
    );
}

$bookingId = wz_crm_create_booking_from_enquiry(
    $enquiryId
);

if (!$bookingId) {
    throw new RuntimeException(
        'CRM booking creation failed.'
    );
}

$booking = wz_crm_booking_for_user(
    $bookingId,
    $customerId,
    'host'
);

if (!$booking) {
    throw new RuntimeException(
        'Customer CRM booking lookup failed.'
    );
}

$pdo->prepare(
    'INSERT INTO crm_tasks (
        owner_user_id,
        created_by,
        title,
        priority
    ) VALUES (
        :owner_user_id,
        :created_by,
        :title,
        :priority
    )'
)->execute([
    'owner_user_id' => $vendorId,
    'created_by' => $vendorId,
    'title' => 'QA follow-up',
    'priority' => 'high',
]);

if (!wz_crm_tasks_for_user($vendorId)) {
    throw new RuntimeException(
        'CRM task lookup failed.'
    );
}

$pdo->prepare(
    'INSERT INTO crm_messages (
        sender_user_id,
        recipient_user_id,
        enquiry_id,
        body
    ) VALUES (
        :sender_user_id,
        :recipient_user_id,
        :enquiry_id,
        :body
    )'
)->execute([
    'sender_user_id' => $customerId,
    'recipient_user_id' => $vendorId,
    'enquiry_id' => $enquiryId,
    'body' => 'QA message',
]);

if (!wz_crm_messages_for_user($vendorId)) {
    throw new RuntimeException(
        'CRM message lookup failed.'
    );
}

$publicNames = array_map(
    fn (array $business): string =>
        (string)$business['name'],
    wz_public_vendors()
);

if (!in_array(
    'QA Vendor',
    $publicNames,
    true
)) {
    throw new RuntimeException(
        'Approved vendor is missing from public marketplace.'
    );
}

if (!in_array(
    'QA Palace',
    $publicNames,
    true
)) {
    throw new RuntimeException(
        'Approved venue is missing from public marketplace.'
    );
}

$pdo->prepare(
    'DELETE FROM crm_messages
     WHERE sender_user_id IN (?, ?, ?)
     OR recipient_user_id IN (?, ?, ?)'
)->execute([
    $customerId,
    $vendorId,
    $venueId,
    $customerId,
    $vendorId,
    $venueId,
]);

$pdo->prepare(
    'DELETE FROM crm_tasks
     WHERE owner_user_id IN (:customer_id, :vendor_id, :venue_id)'
)->execute([
    'customer_id' => $customerId,
    'vendor_id' => $vendorId,
    'venue_id' => $venueId,
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
     WHERE id IN (:customer_id, :vendor_id, :venue_id)'
)->execute([
    'customer_id' => $customerId,
    'vendor_id' => $vendorId,
    'venue_id' => $venueId,
]);

fwrite(
    STDOUT,
    "Wedding Za CRM integration QA passed.\n"
);

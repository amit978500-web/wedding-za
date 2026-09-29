<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function wz_crm_role_label(string $role): string
{
    return match ($role) {
        'host' => 'Customer',
        'vendor' => 'Vendor',
        'venue' => 'Venue',
        'admin' => 'Admin',
        default => 'Account',
    };
}

function wz_crm_portal_path(string $role): string
{
    return match ($role) {
        'host' => 'crm/customer/index.php',
        'vendor' => 'crm/vendor/index.php',
        'venue' => 'crm/venue/index.php',
        'admin' => 'admin/index.php',
        default => 'index.php',
    };
}

function wz_crm_require_role(string $role): array
{
    if (
        !wz_is_logged_in()
        || wz_role() !== $role
    ) {
        header(
            'Location: ' .
            wz_app_url(
                'login.php?role=' .
                urlencode($role)
            )
        );

        exit;
    }

    return wz_user() ?? [];
}

function wz_crm_customer_profile(int $userId): ?array
{
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM customer_profiles
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetch() ?: null;
}

function wz_crm_business_profile(
    int $userId,
    string $role
): ?array {
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    if ($role === 'venue') {
        $statement = $pdo->prepare(
            'SELECT *
             FROM venue_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );
    } else {
        $statement = $pdo->prepare(
            'SELECT *
             FROM vendor_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );
    }

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetch() ?: null;
}

function wz_crm_business_name(
    int $userId,
    string $role
): string {
    $profile = wz_crm_business_profile(
        $userId,
        $role
    );

    if (!$profile) {
        return '';
    }

    if ($role === 'venue') {
        return (string)(
            $profile['venue_name']
            ?? ''
        );
    }

    return (string)(
        $profile['business_name']
        ?? ''
    );
}

function wz_crm_find_business_by_name(
    string $name
): ?array {
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    $name = trim($name);

    if ($name === '') {
        return null;
    }

    $venue = $pdo->prepare(
        'SELECT
            user_id,
            id AS profile_id,
            venue_name AS business_name
         FROM venue_profiles
         WHERE venue_name = :name
         LIMIT 1'
    );

    $venue->execute([
        'name' => $name,
    ]);

    $venueProfile = $venue->fetch();

    if ($venueProfile) {
        return [
            'user_id' => (int)$venueProfile['user_id'],
            'profile_id' => (int)$venueProfile['profile_id'],
            'business_name' => (string)$venueProfile['business_name'],
            'business_type' => 'venue',
        ];
    }

    $vendor = $pdo->prepare(
        'SELECT
            user_id,
            id AS profile_id,
            business_name
         FROM vendor_profiles
         WHERE business_name = :name
         LIMIT 1'
    );

    $vendor->execute([
        'name' => $name,
    ]);

    $vendorProfile = $vendor->fetch();

    if ($vendorProfile) {
        return [
            'user_id' => (int)$vendorProfile['user_id'],
            'profile_id' => (int)$vendorProfile['profile_id'],
            'business_name' => (string)$vendorProfile['business_name'],
            'business_type' => 'vendor',
        ];
    }

    return null;
}

function wz_crm_sync_lead(int $leadId): ?int
{
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    $existing = $pdo->prepare(
        'SELECT id
         FROM crm_enquiries
         WHERE source_lead_id = :source_lead_id
         LIMIT 1'
    );

    $existing->execute([
        'source_lead_id' => $leadId,
    ]);

    $existingId = $existing->fetchColumn();

    if ($existingId) {
        return (int)$existingId;
    }

    $leadStatement = $pdo->prepare(
        'SELECT *
         FROM leads
         WHERE id = :id
         LIMIT 1'
    );

    $leadStatement->execute([
        'id' => $leadId,
    ]);

    $lead = $leadStatement->fetch();

    if (!$lead) {
        return null;
    }

    $business = wz_crm_find_business_by_name(
        (string)($lead['vendor'] ?? '')
    );

    $businessType = $business['business_type']
        ?? (
            strcasecmp(
                (string)($lead['category'] ?? ''),
                'Venues'
            ) === 0
                ? 'venue'
                : 'vendor'
        );

    $subjectParts = array_values(
        array_filter([
            (string)($lead['topic'] ?? ''),
            (string)($lead['vendor'] ?? ''),
        ])
    );

    $subject = $subjectParts
        ? implode(
            ' · ',
            $subjectParts
        )
        : 'Wedding Za enquiry';

    $insert = $pdo->prepare(
        'INSERT INTO crm_enquiries (
            source_lead_id,
            customer_user_id,
            business_user_id,
            business_type,
            subject,
            event_type,
            event_date,
            city,
            budget,
            message
        ) VALUES (
            :source_lead_id,
            :customer_user_id,
            :business_user_id,
            :business_type,
            :subject,
            :event_type,
            :event_date,
            :city,
            :budget,
            :message
        )'
    );

    $insert->execute([
        'source_lead_id' => $leadId,
        'customer_user_id' => !empty($lead['user_id'])
            ? (int)$lead['user_id']
            : null,
        'business_user_id' => $business['user_id']
            ?? null,
        'business_type' => $businessType,
        'subject' => $subject,
        'event_type' => (string)($lead['topic'] ?? ''),
        'event_date' => !empty($lead['event_date'])
            ? (string)$lead['event_date']
            : null,
        'city' => (string)($lead['city'] ?? ''),
        'budget' => (string)($lead['price'] ?? ''),
        'message' => (string)($lead['message'] ?? ''),
    ]);

    $enquiryId = (int)$pdo->lastInsertId();

    wz_audit(
        'crm.enquiry.created',
        'crm_enquiry',
        $enquiryId,
        [
            'source_lead_id' => $leadId,
            'business_type' => $businessType,
        ]
    );

    return $enquiryId;
}

function wz_crm_enquiries_for_user(
    int $userId,
    string $role
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    if ($role === 'host') {
        $statement = $pdo->prepare(
            'SELECT ce.*,
                    bu.name AS business_contact
             FROM crm_enquiries ce
             LEFT JOIN users bu
                ON bu.id = ce.business_user_id
             WHERE ce.customer_user_id = :user_id
             ORDER BY ce.updated_at DESC'
        );
    } else {
        $statement = $pdo->prepare(
            'SELECT ce.*,
                    cu.name AS customer_name,
                    cu.email AS customer_email
             FROM crm_enquiries ce
             LEFT JOIN users cu
                ON cu.id = ce.customer_user_id
             WHERE ce.business_user_id = :user_id
             AND ce.business_type = :business_type
             ORDER BY
                FIELD(
                    ce.stage,
                    "new",
                    "qualified",
                    "proposal",
                    "negotiation",
                    "won",
                    "lost"
                ),
                ce.updated_at DESC'
        );
    }

    $params = [
        'user_id' => $userId,
    ];

    if ($role !== 'host') {
        $params['business_type'] = $role;
    }

    $statement->execute($params);

    return $statement->fetchAll();
}

function wz_crm_bookings_for_user(
    int $userId,
    string $role
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    if ($role === 'host') {
        $statement = $pdo->prepare(
            'SELECT cb.*,
                    bu.name AS business_contact
             FROM crm_bookings cb
             LEFT JOIN users bu
                ON bu.id = cb.business_user_id
             WHERE cb.customer_user_id = :user_id
             ORDER BY cb.event_date ASC, cb.updated_at DESC'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT cb.*,
                    cu.name AS customer_name,
                    cu.email AS customer_email
             FROM crm_bookings cb
             LEFT JOIN users cu
                ON cu.id = cb.customer_user_id
             WHERE cb.business_user_id = :user_id
             AND cb.business_type = :business_type
             ORDER BY cb.event_date ASC, cb.updated_at DESC'
        );

        $statement->execute([
            'user_id' => $userId,
            'business_type' => $role,
        ]);
    }

    return $statement->fetchAll();
}

function wz_crm_tasks_for_user(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM crm_tasks
         WHERE owner_user_id = :owner_user_id
         ORDER BY
            status = "done",
            due_at IS NULL,
            due_at ASC,
            created_at DESC'
    );

    $statement->execute([
        'owner_user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_crm_messages_for_user(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT cm.*,
                su.name AS sender_name,
                ru.name AS recipient_name
         FROM crm_messages cm
         INNER JOIN users su
            ON su.id = cm.sender_user_id
         INNER JOIN users ru
            ON ru.id = cm.recipient_user_id
         WHERE cm.sender_user_id = :sender_user_id
         OR cm.recipient_user_id = :recipient_user_id
         ORDER BY cm.created_at DESC
         LIMIT 200'
    );

    $statement->execute([
        'sender_user_id' => $userId,
        'recipient_user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_crm_create_booking_from_enquiry(
    int $enquiryId
): ?int {
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    $existing = $pdo->prepare(
        'SELECT id
         FROM crm_bookings
         WHERE enquiry_id = :enquiry_id
         LIMIT 1'
    );

    $existing->execute([
        'enquiry_id' => $enquiryId,
    ]);

    $existingId = $existing->fetchColumn();

    if ($existingId) {
        return (int)$existingId;
    }

    $enquiryStatement = $pdo->prepare(
        'SELECT *
         FROM crm_enquiries
         WHERE id = :id
         LIMIT 1'
    );

    $enquiryStatement->execute([
        'id' => $enquiryId,
    ]);

    $enquiry = $enquiryStatement->fetch();

    if (!$enquiry) {
        return null;
    }

    $insert = $pdo->prepare(
        'INSERT INTO crm_bookings (
            enquiry_id,
            customer_user_id,
            business_user_id,
            business_type,
            title,
            event_type,
            event_date,
            city,
            amount,
            status,
            payment_status,
            notes
        ) VALUES (
            :enquiry_id,
            :customer_user_id,
            :business_user_id,
            :business_type,
            :title,
            :event_type,
            :event_date,
            :city,
            :amount,
            :status,
            :payment_status,
            :notes
        )'
    );

    $insert->execute([
        'enquiry_id' => $enquiryId,
        'customer_user_id' => $enquiry['customer_user_id'],
        'business_user_id' => $enquiry['business_user_id'],
        'business_type' => $enquiry['business_type'],
        'title' => (string)(
            $enquiry['subject']
            ?: 'Event booking'
        ),
        'event_type' => $enquiry['event_type'],
        'event_date' => $enquiry['event_date'],
        'city' => $enquiry['city'],
        'amount' => $enquiry['value_estimate'],
        'status' => 'tentative',
        'payment_status' => 'pending',
        'notes' => $enquiry['message'],
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    wz_audit(
        'crm.booking.created',
        'crm_booking',
        $bookingId,
        [
            'enquiry_id' => $enquiryId,
        ]
    );

    return $bookingId;
}


function wz_crm_enquiry_for_user(
    int $enquiryId,
    int $userId,
    string $role
): ?array {
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    if ($role === 'host') {
        $statement = $pdo->prepare(
            'SELECT ce.*,
                    bu.name AS business_contact,
                    bu.email AS business_email
             FROM crm_enquiries ce
             LEFT JOIN users bu
                ON bu.id = ce.business_user_id
             WHERE ce.id = :id
             AND ce.customer_user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $enquiryId,
            'user_id' => $userId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT ce.*,
                    cu.name AS customer_name,
                    cu.email AS customer_email
             FROM crm_enquiries ce
             LEFT JOIN users cu
                ON cu.id = ce.customer_user_id
             WHERE ce.id = :id
             AND ce.business_user_id = :user_id
             AND ce.business_type = :business_type
             LIMIT 1'
        );

        $statement->execute([
            'id' => $enquiryId,
            'user_id' => $userId,
            'business_type' => $role,
        ]);
    }

    return $statement->fetch() ?: null;
}

function wz_crm_booking_for_user(
    int $bookingId,
    int $userId,
    string $role
): ?array {
    $pdo = wz_db();

    if (!$pdo) {
        return null;
    }

    if ($role === 'host') {
        $statement = $pdo->prepare(
            'SELECT cb.*,
                    bu.name AS business_contact,
                    bu.email AS business_email
             FROM crm_bookings cb
             LEFT JOIN users bu
                ON bu.id = cb.business_user_id
             WHERE cb.id = :id
             AND cb.customer_user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $bookingId,
            'user_id' => $userId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT cb.*,
                    cu.name AS customer_name,
                    cu.email AS customer_email
             FROM crm_bookings cb
             LEFT JOIN users cu
                ON cu.id = cb.customer_user_id
             WHERE cb.id = :id
             AND cb.business_user_id = :user_id
             AND cb.business_type = :business_type
             LIMIT 1'
        );

        $statement->execute([
            'id' => $bookingId,
            'user_id' => $userId,
            'business_type' => $role,
        ]);
    }

    return $statement->fetch() ?: null;
}

function wz_crm_notes(
    string $relatedType,
    int $relatedId,
    bool $includeInternal
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT cn.*,
                   u.name AS author_name
            FROM crm_notes cn
            LEFT JOIN users u
                ON u.id = cn.user_id
            WHERE cn.related_type = :related_type
            AND cn.related_id = :related_id';

    if (!$includeInternal) {
        $sql .= ' AND cn.visibility = "shared"';
    }

    $sql .= ' ORDER BY cn.created_at DESC';

    $statement = $pdo->prepare($sql);

    $statement->execute([
        'related_type' => $relatedType,
        'related_id' => $relatedId,
    ]);

    return $statement->fetchAll();
}

function wz_crm_allowed_contacts(
    int $userId,
    string $role
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    if ($role === 'host') {
        $statement = $pdo->prepare(
            'SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.role
             FROM users u
             INNER JOIN crm_enquiries ce
                ON ce.business_user_id = u.id
             WHERE ce.customer_user_id = :enquiry_customer_user_id

             UNION

             SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.role
             FROM users u
             INNER JOIN crm_bookings cb
                ON cb.business_user_id = u.id
             WHERE cb.customer_user_id = :booking_customer_user_id

             ORDER BY name'
        );

        $statement->execute([
            'enquiry_customer_user_id' => $userId,
            'booking_customer_user_id' => $userId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.role
             FROM users u
             INNER JOIN crm_enquiries ce
                ON ce.customer_user_id = u.id
             WHERE ce.business_user_id = :enquiry_business_user_id
             AND ce.business_type = :enquiry_business_type

             UNION

             SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.role
             FROM users u
             INNER JOIN crm_bookings cb
                ON cb.customer_user_id = u.id
             WHERE cb.business_user_id = :booking_business_user_id
             AND cb.business_type = :booking_business_type

             ORDER BY name'
        );

        $statement->execute([
            'enquiry_business_user_id' => $userId,
            'enquiry_business_type' => $role,
            'booking_business_user_id' => $userId,
            'booking_business_type' => $role,
        ]);
    }

    $contacts = $statement->fetchAll();

    $messageContacts = $pdo->prepare(
        'SELECT DISTINCT
            u.id,
            u.name,
            u.email,
            u.role
         FROM users u
         INNER JOIN crm_messages cm
            ON (
                cm.sender_user_id = u.id
                AND cm.recipient_user_id = :received_by_user_id
            )
            OR (
                cm.recipient_user_id = u.id
                AND cm.sender_user_id = :sent_by_user_id
            )
         WHERE u.id <> :excluded_user_id
         ORDER BY u.name'
    );

    $messageContacts->execute([
        'received_by_user_id' => $userId,
        'sent_by_user_id' => $userId,
        'excluded_user_id' => $userId,
    ]);

    $merged = [];

    foreach (
        array_merge(
            $contacts,
            $messageContacts->fetchAll()
        ) as $contact
    ) {
        $merged[
            (int)$contact['id']
        ] = $contact;
    }

    return array_values($merged);
}

function wz_crm_can_message(
    int $userId,
    string $role,
    int $recipientId
): bool {
    foreach (
        wz_crm_allowed_contacts(
            $userId,
            $role
        ) as $contact
    ) {
        if (
            (int)$contact['id']
            === $recipientId
        ) {
            return true;
        }
    }

    return false;
}

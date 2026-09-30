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


function wz_crm_update_customer_profile(
    int $userId,
    array $input
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not available.',
        ];
    }

    $name = trim(
        (string)($input['name'] ?? '')
    );

    if ($name === '') {
        return [
            'ok' => false,
            'message' => 'Please enter your name.',
        ];
    }

    $eventDate = trim(
        (string)($input['event_date'] ?? '')
    );

    if ($eventDate !== '') {
        $date = DateTime::createFromFormat(
            'Y-m-d',
            $eventDate
        );

        $isValidDate = $date
            && $date->format('Y-m-d') === $eventDate;

        if (!$isValidDate) {
            return [
                'ok' => false,
                'message' => 'Please enter a valid event date.',
            ];
        }
    }

    $guestCount = null;

    if (
        isset($input['guest_count'])
        && trim((string)$input['guest_count']) !== ''
    ) {
        $guestCount = max(
            1,
            (int)$input['guest_count']
        );
    }

    try {
        $pdo->beginTransaction();

        $userUpdate = $pdo->prepare(
            'UPDATE users
             SET name = :name
             WHERE id = :id
             AND role = :role'
        );

        $userUpdate->execute([
            'name' => $name,
            'id' => $userId,
            'role' => 'host',
        ]);

        $roleCheck = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE id = :id
             AND role = :role
             LIMIT 1'
        );

        $roleCheck->execute([
            'id' => $userId,
            'role' => 'host',
        ]);

        if (!$roleCheck->fetchColumn()) {
            $pdo->rollBack();

            return [
                'ok' => false,
                'message' => 'Customer account was not found.',
            ];
        }

        $profileUpdate = $pdo->prepare(
            'INSERT INTO customer_profiles (
                user_id,
                phone,
                city,
                event_type,
                event_date,
                guest_count,
                budget,
                notes
            ) VALUES (
                :user_id,
                :phone,
                :city,
                :event_type,
                :event_date,
                :guest_count,
                :budget,
                :notes
            )
            ON DUPLICATE KEY UPDATE
                phone = VALUES(phone),
                city = VALUES(city),
                event_type = VALUES(event_type),
                event_date = VALUES(event_date),
                guest_count = VALUES(guest_count),
                budget = VALUES(budget),
                notes = VALUES(notes)'
        );

        $profileUpdate->execute([
            'user_id' => $userId,
            'phone' => trim(
                (string)($input['phone'] ?? '')
            ),
            'city' => trim(
                (string)($input['city'] ?? '')
            ),
            'event_type' => trim(
                (string)($input['event_type'] ?? '')
            ),
            'event_date' => $eventDate !== ''
                ? $eventDate
                : null,
            'guest_count' => $guestCount,
            'budget' => trim(
                (string)($input['budget'] ?? '')
            ),
            'notes' => trim(
                (string)($input['notes'] ?? '')
            ),
        ]);

        $pdo->commit();

        return [
            'ok' => true,
            'message' => 'Customer profile updated.',
            'name' => $name,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Wedding Za customer profile update failed: '
            . $exception->getMessage()
        );

        return [
            'ok' => false,
            'message' => 'Customer profile could not be saved.',
        ];
    }
}


function wz_venue_leads(
    int $userId,
    string $filter = 'all'
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT
                ce.*,
                cu.name AS customer_name,
                cu.email AS customer_email,
                cp.phone AS customer_phone
            FROM crm_enquiries ce
            LEFT JOIN users cu
                ON cu.id = ce.customer_user_id
            LEFT JOIN customer_profiles cp
                ON cp.user_id = ce.customer_user_id
            WHERE ce.business_user_id = :user_id
            AND ce.business_type = "venue"';

    if ($filter === 'new') {
        $sql .= ' AND ce.stage = "new"';
    }

    if ($filter === 'followups') {
        $sql .= ' AND ce.next_follow_up IS NOT NULL
                  AND ce.stage NOT IN ("won", "lost")';
    }

    if ($filter === 'site-visits') {
        $sql .= ' AND ce.site_visit_at IS NOT NULL
                  AND ce.site_visit_status <> "cancelled"';
    }

    if ($filter === 'lost') {
        $sql .= ' AND ce.stage = "lost"';
    }

    if ($filter === 'site-visits') {
        $sql .= ' ORDER BY ce.site_visit_at ASC';
    } elseif ($filter === 'followups') {
        $sql .= ' ORDER BY ce.next_follow_up ASC';
    } else {
        $sql .= ' ORDER BY ce.updated_at DESC';
    }

    $statement = $pdo->prepare($sql);

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_venue_functions(
    int $userId,
    string $filter = 'all'
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT
                cb.*,
                cu.name AS customer_name,
                cu.email AS customer_email
            FROM crm_bookings cb
            LEFT JOIN users cu
                ON cu.id = cb.customer_user_id
            WHERE cb.business_user_id = :user_id
            AND cb.business_type = "venue"';

    if ($filter === 'upcoming') {
        $sql .= ' AND cb.event_date >= CURDATE()
                  AND cb.status IN ("tentative", "confirmed")';
    }

    if ($filter === 'calendar') {
        $sql .= ' AND cb.event_date IS NOT NULL
                  AND cb.event_date >= DATE_SUB(
                      CURDATE(),
                      INTERVAL 30 DAY
                  )';
    }

    $sql .= ' ORDER BY
                cb.event_date IS NULL,
                cb.event_date ASC,
                cb.updated_at DESC';

    $statement = $pdo->prepare($sql);

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_venue_payments(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT
            cp.*,
            cb.title AS booking_title,
            cb.event_date,
            cb.amount AS booking_amount,
            cb.payment_status AS booking_payment_status,
            cu.name AS customer_name,
            cu.email AS customer_email
         FROM crm_payments cp
         INNER JOIN crm_bookings cb
            ON cb.id = cp.booking_id
         LEFT JOIN users cu
            ON cu.id = cp.customer_user_id
         WHERE cp.business_user_id = :user_id
         AND cp.business_type = "venue"
         ORDER BY
            cp.paid_at IS NULL,
            cp.paid_at DESC,
            cp.created_at DESC'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_venue_payment_totals(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'booked_value' => 0.0,
            'received' => 0.0,
            'refunded' => 0.0,
            'balance' => 0.0,
        ];
    }

    $bookingStatement = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0)
         FROM crm_bookings
         WHERE business_user_id = :user_id
         AND business_type = "venue"
         AND status <> "cancelled"'
    );

    $bookingStatement->execute([
        'user_id' => $userId,
    ]);

    $bookedValue = (float)$bookingStatement->fetchColumn();

    $paymentStatement = $pdo->prepare(
        'SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN status = "received"
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS received,
            COALESCE(
                SUM(
                    CASE
                        WHEN status = "refunded"
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS refunded
         FROM crm_payments
         WHERE business_user_id = :user_id
         AND business_type = "venue"'
    );

    $paymentStatement->execute([
        'user_id' => $userId,
    ]);

    $paymentTotals = $paymentStatement->fetch() ?: [];

    $received = (float)($paymentTotals['received'] ?? 0);
    $refunded = (float)($paymentTotals['refunded'] ?? 0);
    $netReceived = max(
        0,
        $received - $refunded
    );

    return [
        'booked_value' => $bookedValue,
        'received' => $netReceived,
        'refunded' => $refunded,
        'balance' => max(
            0,
            $bookedValue - $netReceived
        ),
    ];
}

function wz_crm_sync_booking_payment_status(
    int $bookingId
): void {
    $pdo = wz_db();

    if (!$pdo) {
        return;
    }

    $bookingStatement = $pdo->prepare(
        'SELECT amount
         FROM crm_bookings
         WHERE id = :id
         LIMIT 1'
    );

    $bookingStatement->execute([
        'id' => $bookingId,
    ]);

    $bookingAmount = $bookingStatement->fetchColumn();

    if ($bookingAmount === false) {
        return;
    }

    $paymentStatement = $pdo->prepare(
        'SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN status = "received"
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            )
            -
            COALESCE(
                SUM(
                    CASE
                        WHEN status = "refunded"
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            )
         FROM crm_payments
         WHERE booking_id = :booking_id'
    );

    $paymentStatement->execute([
        'booking_id' => $bookingId,
    ]);

    $paidAmount = max(
        0,
        (float)$paymentStatement->fetchColumn()
    );

    $bookingAmount = max(
        0,
        (float)$bookingAmount
    );

    $paymentStatus = 'pending';

    if ($paidAmount > 0) {
        $paymentStatus = 'partial';
    }

    if (
        $bookingAmount > 0
        && $paidAmount >= $bookingAmount
    ) {
        $paymentStatus = 'paid';
    }

    $update = $pdo->prepare(
        'UPDATE crm_bookings
         SET deposit_amount = :deposit_amount,
             payment_status = :payment_status
         WHERE id = :id'
    );

    $update->execute([
        'deposit_amount' => $paidAmount,
        'payment_status' => $paymentStatus,
        'id' => $bookingId,
    ]);
}

function wz_venue_add_payment(
    int $venueUserId,
    int $bookingId,
    array $input
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not available.',
        ];
    }

    $booking = wz_crm_booking_for_user(
        $bookingId,
        $venueUserId,
        'venue'
    );

    if (!$booking) {
        return [
            'ok' => false,
            'message' => 'Booking not found.',
        ];
    }

    $amount = (float)($input['amount'] ?? 0);

    if ($amount <= 0) {
        return [
            'ok' => false,
            'message' => 'Enter a valid payment amount.',
        ];
    }

    $method = (string)($input['method'] ?? 'bank_transfer');

    if (!in_array(
        $method,
        [
            'cash',
            'bank_transfer',
            'upi',
            'card',
            'cheque',
            'other',
        ],
        true
    )) {
        $method = 'other';
    }

    $status = (string)($input['status'] ?? 'received');

    if (!in_array(
        $status,
        [
            'pending',
            'received',
            'refunded',
            'failed',
        ],
        true
    )) {
        $status = 'received';
    }

    $paidAt = trim(
        (string)($input['paid_at'] ?? '')
    );

    if ($paidAt === '') {
        $paidAt = date('Y-m-d H:i:s');
    }

    $statement = $pdo->prepare(
        'INSERT INTO crm_payments (
            booking_id,
            customer_user_id,
            business_user_id,
            business_type,
            amount,
            method,
            status,
            reference,
            paid_at,
            notes,
            created_by
        ) VALUES (
            :booking_id,
            :customer_user_id,
            :business_user_id,
            :business_type,
            :amount,
            :method,
            :status,
            :reference,
            :paid_at,
            :notes,
            :created_by
        )'
    );

    $statement->execute([
        'booking_id' => $bookingId,
        'customer_user_id' => $booking['customer_user_id'],
        'business_user_id' => $venueUserId,
        'business_type' => 'venue',
        'amount' => $amount,
        'method' => $method,
        'status' => $status,
        'reference' => trim(
            (string)($input['reference'] ?? '')
        ),
        'paid_at' => $paidAt,
        'notes' => trim(
            (string)($input['notes'] ?? '')
        ),
        'created_by' => wz_user()['id'] ?? null,
    ]);

    $paymentId = (int)$pdo->lastInsertId();

    wz_crm_sync_booking_payment_status(
        $bookingId
    );

    wz_audit(
        'crm.venue_payment.created',
        'crm_payment',
        $paymentId,
        [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'status' => $status,
        ]
    );

    return [
        'ok' => true,
        'message' => 'Payment recorded.',
        'payment_id' => $paymentId,
    ];
}

function wz_venue_report_summary(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $leadStatement = $pdo->prepare(
        'SELECT
            COUNT(*) AS total_leads,
            SUM(stage = "new") AS new_leads,
            SUM(stage = "won") AS won_leads,
            SUM(stage = "lost") AS lost_leads,
            SUM(site_visit_status = "completed") AS completed_visits
         FROM crm_enquiries
         WHERE business_user_id = :user_id
         AND business_type = "venue"'
    );

    $leadStatement->execute([
        'user_id' => $userId,
    ]);

    $leadStats = $leadStatement->fetch() ?: [];

    $bookingStatement = $pdo->prepare(
        'SELECT
            COUNT(*) AS total_bookings,
            SUM(status = "confirmed") AS confirmed_bookings,
            SUM(
                event_date >= CURDATE()
                AND status IN ("tentative", "confirmed")
            ) AS upcoming_functions,
            COALESCE(
                SUM(
                    CASE
                        WHEN status <> "cancelled"
                        THEN amount
                        ELSE 0
                    END
                ),
                0
            ) AS booked_value
         FROM crm_bookings
         WHERE business_user_id = :user_id
         AND business_type = "venue"'
    );

    $bookingStatement->execute([
        'user_id' => $userId,
    ]);

    $bookingStats = $bookingStatement->fetch() ?: [];
    $paymentTotals = wz_venue_payment_totals(
        $userId
    );

    return array_merge(
        $leadStats,
        $bookingStats,
        $paymentTotals
    );
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function wz_marketplace_review_summary(
    int $businessUserId,
    string $businessType
): array {
    $pdo = wz_db();

    if (!$pdo || $businessUserId <= 0) {
        return [
            'rating' => 0.0,
            'count' => 0,
        ];
    }

    $statement = $pdo->prepare(
        'SELECT
            COUNT(*) AS review_count,
            COALESCE(AVG(rating), 0) AS average_rating
         FROM marketplace_reviews
         WHERE business_user_id = :business_user_id
         AND business_type = :business_type
         AND status = "published"'
    );

    $statement->execute([
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
    ]);

    $row = $statement->fetch() ?: [];

    return [
        'rating' => round(
            (float)($row['average_rating'] ?? 0),
            1
        ),
        'count' => (int)($row['review_count'] ?? 0),
    ];
}

function wz_marketplace_reviews_for_business(
    int $businessUserId,
    string $businessType,
    int $limit = 20
): array {
    $pdo = wz_db();

    if (!$pdo || $businessUserId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT
            mr.*,
            u.name AS reviewer_name
         FROM marketplace_reviews mr
         INNER JOIN users u
            ON u.id = mr.reviewer_user_id
         WHERE mr.business_user_id = :business_user_id
         AND mr.business_type = :business_type
         AND mr.status = "published"
         ORDER BY mr.created_at DESC
         LIMIT ' . max(1, min($limit, 100))
    );

    $statement->execute([
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
    ]);

    return $statement->fetchAll();
}

function wz_marketplace_create_review(array $input): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not available.',
        ];
    }

    $reviewerUserId = (int)($input['reviewer_user_id'] ?? 0);
    $businessUserId = (int)($input['business_user_id'] ?? 0);
    $businessType = (string)($input['business_type'] ?? '');
    $rating = (int)($input['rating'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $body = trim((string)($input['body'] ?? ''));

    if (
        $reviewerUserId <= 0
        || $businessUserId <= 0
        || !in_array($businessType, ['vendor', 'venue'], true)
        || $rating < 1
        || $rating > 5
        || $body === ''
    ) {
        return [
            'ok' => false,
            'message' => 'Complete the rating and review.',
        ];
    }

    $bookingStatement = $pdo->prepare(
        'SELECT id
         FROM crm_bookings
         WHERE customer_user_id = :customer_user_id
         AND business_user_id = :business_user_id
         AND business_type = :business_type
         AND status IN ("confirmed", "completed")
         ORDER BY created_at DESC
         LIMIT 1'
    );

    $bookingStatement->execute([
        'customer_user_id' => $reviewerUserId,
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
    ]);

    $bookingId = $bookingStatement->fetchColumn();

    $statement = $pdo->prepare(
        'INSERT INTO marketplace_reviews (
            reviewer_user_id,
            business_user_id,
            business_type,
            booking_id,
            rating,
            title,
            body,
            is_verified_booking,
            status
        ) VALUES (
            :reviewer_user_id,
            :business_user_id,
            :business_type,
            :booking_id,
            :rating,
            :title,
            :body,
            :is_verified_booking,
            "pending"
        )'
    );

    $statement->execute([
        'reviewer_user_id' => $reviewerUserId,
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
        'booking_id' => $bookingId ?: null,
        'rating' => $rating,
        'title' => $title !== '' ? $title : null,
        'body' => $body,
        'is_verified_booking' => $bookingId ? 1 : 0,
    ]);

    wz_marketplace_notify(
        $businessUserId,
        'review',
        'New review submitted',
        'A customer submitted a review. It will appear after moderation.',
        null
    );

    return [
        'ok' => true,
        'message' => 'Review submitted for moderation.',
    ];
}

function wz_marketplace_notify(
    int $userId,
    string $type,
    string $title,
    ?string $body = null,
    ?string $actionUrl = null
): void {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO user_notifications (
            user_id,
            type,
            title,
            body,
            action_url
        ) VALUES (
            :user_id,
            :type,
            :title,
            :body,
            :action_url
        )'
    );

    $statement->execute([
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'action_url' => $actionUrl,
    ]);
}

function wz_marketplace_notifications(
    int $userId,
    int $limit = 50
): array {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM user_notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT ' . max(1, min($limit, 100))
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_marketplace_unread_notification_count(
    int $userId
): int {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM user_notifications
         WHERE user_id = :user_id
         AND read_at IS NULL'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return (int)$statement->fetchColumn();
}

function wz_marketplace_mark_notifications_read(
    int $userId
): void {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return;
    }

    $statement = $pdo->prepare(
        'UPDATE user_notifications
         SET read_at = COALESCE(read_at, NOW())
         WHERE user_id = :user_id'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);
}

function wz_marketplace_record_view(
    int $userId,
    string $entityType,
    string $entityKey
): void {
    $pdo = wz_db();

    if (
        !$pdo
        || $userId <= 0
        || !in_array(
            $entityType,
            ['vendor', 'venue', 'article', 'wedding'],
            true
        )
        || trim($entityKey) === ''
    ) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO recently_viewed (
            user_id,
            entity_type,
            entity_key,
            viewed_at
        ) VALUES (
            :user_id,
            :entity_type,
            :entity_key,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            viewed_at = NOW()'
    );

    $statement->execute([
        'user_id' => $userId,
        'entity_type' => $entityType,
        'entity_key' => $entityKey,
    ]);
}

function wz_marketplace_recently_viewed(
    int $userId,
    int $limit = 12
): array {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM recently_viewed
         WHERE user_id = :user_id
         ORDER BY viewed_at DESC
         LIMIT ' . max(1, min($limit, 50))
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetchAll();
}

function wz_marketplace_record_business_event(
    int $businessUserId,
    string $businessType,
    string $eventType,
    ?int $customerUserId = null,
    ?int $entityId = null
): void {
    $pdo = wz_db();

    if (
        !$pdo
        || $businessUserId <= 0
        || !in_array($businessType, ['vendor', 'venue'], true)
        || !in_array(
            $eventType,
            [
                'profile_view',
                'shortlist',
                'enquiry',
                'message',
                'quote',
                'booking',
            ],
            true
        )
    ) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO business_analytics_events (
            business_user_id,
            business_type,
            customer_user_id,
            event_type,
            entity_id
        ) VALUES (
            :business_user_id,
            :business_type,
            :customer_user_id,
            :event_type,
            :entity_id
        )'
    );

    $statement->execute([
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
        'customer_user_id' => $customerUserId,
        'event_type' => $eventType,
        'entity_id' => $entityId,
    ]);
}

function wz_marketplace_business_analytics(
    int $businessUserId,
    string $businessType,
    int $days = 30
): array {
    $pdo = wz_db();

    if (!$pdo || $businessUserId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT event_type, COUNT(*) AS total
         FROM business_analytics_events
         WHERE business_user_id = :business_user_id
         AND business_type = :business_type
         AND created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min($days, 365)) . ' DAY)
         GROUP BY event_type'
    );

    $statement->execute([
        'business_user_id' => $businessUserId,
        'business_type' => $businessType,
    ]);

    $summary = [
        'profile_view' => 0,
        'shortlist' => 0,
        'enquiry' => 0,
        'message' => 0,
        'quote' => 0,
        'booking' => 0,
    ];

    foreach ($statement->fetchAll() as $row) {
        $summary[(string)$row['event_type']] =
            (int)$row['total'];
    }

    return $summary;
}

function wz_marketplace_recommendations(
    int $customerUserId,
    int $limit = 8
): array {
    $pdo = wz_db();

    if (!$pdo || $customerUserId <= 0) {
        return [];
    }

    $profileStatement = $pdo->prepare(
        'SELECT city, event_type, guest_count
         FROM customer_profiles
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $profileStatement->execute([
        'user_id' => $customerUserId,
    ]);

    $profile = $profileStatement->fetch() ?: [];
    $city = trim((string)($profile['city'] ?? ''));
    $guestCount = (int)($profile['guest_count'] ?? 0);

    $sql = 'SELECT
                vp.id AS profile_id,
                vp.user_id,
                vp.venue_name AS name,
                vp.city,
                vp.locality,
                vp.venue_type,
                vp.starting_price,
                vp.hero_image_url,
                vp.capacity_max,
                vp.featured
            FROM venue_profiles vp
            INNER JOIN users u
                ON u.id = vp.user_id
            WHERE vp.approval_status = "approved"
            AND u.status = "active"';

    $params = [];

    if ($city !== '') {
        $sql .= ' AND vp.city = :city';
        $params['city'] = $city;
    }

    if ($guestCount > 0) {
        $sql .= ' AND (
            vp.capacity_max IS NULL
            OR vp.capacity_max >= :guest_count
        )';
        $params['guest_count'] = $guestCount;
    }

    $sql .= ' ORDER BY vp.featured DESC, vp.updated_at DESC
              LIMIT ' . max(1, min($limit, 24));

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function wz_marketplace_wedding_timeline(
    ?string $eventDate
): array {
    if (!$eventDate) {
        return [];
    }

    try {
        $event = new DateTimeImmutable($eventDate);
    } catch (Throwable $exception) {
        return [];
    }

    $milestones = [
        ['months' => -10, 'title' => 'Lock venue and core budget'],
        ['months' => -8, 'title' => 'Book planner, photography and catering'],
        ['months' => -6, 'title' => 'Confirm decor, entertainment and key vendors'],
        ['months' => -4, 'title' => 'Finalise outfits, invitations and guest logistics'],
        ['months' => -2, 'title' => 'Confirm menus, production and rooming lists'],
        ['months' => -1, 'title' => 'Close RSVPs and final vendor payments'],
        ['days' => -7, 'title' => 'Final production and family briefing'],
        ['days' => -1, 'title' => 'Reconfirm timings and emergency contacts'],
    ];

    $timeline = [];

    foreach ($milestones as $milestone) {
        $date = isset($milestone['months'])
            ? $event->modify(
                ($milestone['months'] >= 0 ? '+' : '')
                . $milestone['months']
                . ' months'
            )
            : $event->modify(
                ($milestone['days'] >= 0 ? '+' : '')
                . $milestone['days']
                . ' days'
            );

        $timeline[] = [
            'date' => $date->format('Y-m-d'),
            'title' => $milestone['title'],
            'is_past' => $date < new DateTimeImmutable('today'),
        ];
    }

    $timeline[] = [
        'date' => $event->format('Y-m-d'),
        'title' => 'Wedding day',
        'is_past' => $event < new DateTimeImmutable('today'),
    ];

    return $timeline;
}

function wz_marketplace_customer_quotes(
    int $customerUserId
): array {
    $pdo = wz_db();

    if (!$pdo || $customerUserId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT q.*, u.name AS business_contact
         FROM crm_quotes q
         LEFT JOIN users u
            ON u.id = q.business_user_id
         WHERE q.customer_user_id = :customer_user_id
         ORDER BY q.created_at DESC'
    );

    $statement->execute([
        'customer_user_id' => $customerUserId,
    ]);

    return $statement->fetchAll();
}

function wz_marketplace_customer_invoices(
    int $customerUserId
): array {
    $pdo = wz_db();

    if (!$pdo || $customerUserId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT i.*, b.title AS booking_title
         FROM crm_invoices i
         INNER JOIN crm_bookings b
            ON b.id = i.booking_id
         WHERE i.customer_user_id = :customer_user_id
         ORDER BY i.created_at DESC'
    );

    $statement->execute([
        'customer_user_id' => $customerUserId,
    ]);

    return $statement->fetchAll();
}

function wz_marketplace_create_venue_plan_purchase(
    int $customerUserId
): ?int {
    $pdo = wz_db();

    if (!$pdo || $customerUserId <= 0) {
        return null;
    }

    $existing = $pdo->prepare(
        'SELECT id
         FROM venue_plan_purchases
         WHERE customer_user_id = :customer_user_id
         AND status IN ("pending", "paid", "active")
         ORDER BY created_at DESC
         LIMIT 1'
    );

    $existing->execute([
        'customer_user_id' => $customerUserId,
    ]);

    $existingId = $existing->fetchColumn();

    if ($existingId) {
        return (int)$existingId;
    }

    $statement = $pdo->prepare(
        'INSERT INTO venue_plan_purchases (
            customer_user_id
        ) VALUES (
            :customer_user_id
        )'
    );

    $statement->execute([
        'customer_user_id' => $customerUserId,
    ]);

    return (int)$pdo->lastInsertId();
}

function wz_marketplace_venue_plan_purchase(
    int $customerUserId
): ?array {
    $pdo = wz_db();

    if (!$pdo || $customerUserId <= 0) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM venue_plan_purchases
         WHERE customer_user_id = :customer_user_id
         ORDER BY created_at DESC
         LIMIT 1'
    );

    $statement->execute([
        'customer_user_id' => $customerUserId,
    ]);

    return $statement->fetch() ?: null;
}

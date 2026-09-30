<?php

declare(strict_types=1);

require_once __DIR__ . '/crm.php';

function wz_admin_crm_require_admin(): array
{
    if (!wz_is_admin()) {
        header(
            'Location: ' .
            wz_app_url('admin/login.php')
        );

        exit;
    }

    return wz_user() ?? [];
}

function wz_admin_crm_leads(
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
                cp.phone AS customer_phone,
                bu.name AS business_contact
            FROM crm_enquiries ce
            LEFT JOIN users cu
                ON cu.id = ce.customer_user_id
            LEFT JOIN customer_profiles cp
                ON cp.user_id = ce.customer_user_id
            LEFT JOIN users bu
                ON bu.id = ce.business_user_id
            WHERE 1 = 1';

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

    if ($filter === 'followups') {
        $sql .= ' ORDER BY ce.next_follow_up ASC';
    } elseif ($filter === 'site-visits') {
        $sql .= ' ORDER BY ce.site_visit_at ASC';
    } else {
        $sql .= ' ORDER BY ce.updated_at DESC';
    }

    $sql .= ' LIMIT 500';

    return $pdo
        ->query($sql)
        ->fetchAll();
}

function wz_admin_crm_businesses(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $vendors = $pdo
        ->query(
            "SELECT
                vp.user_id,
                vp.business_name AS name,
                'vendor' AS business_type
             FROM vendor_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE u.status = 'active'
             ORDER BY vp.business_name"
        )
        ->fetchAll();

    $venues = $pdo
        ->query(
            "SELECT
                vp.user_id,
                vp.venue_name AS name,
                'venue' AS business_type
             FROM venue_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE u.status = 'active'
             ORDER BY vp.venue_name"
        )
        ->fetchAll();

    return array_merge(
        $venues,
        $vendors
    );
}

function wz_admin_crm_functions(
    string $filter = 'all'
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT
                cb.*,
                cu.name AS customer_name,
                cu.email AS customer_email,
                bu.name AS business_contact
            FROM crm_bookings cb
            LEFT JOIN users cu
                ON cu.id = cb.customer_user_id
            LEFT JOIN users bu
                ON bu.id = cb.business_user_id
            WHERE 1 = 1';

    if ($filter === 'upcoming') {
        $sql .= ' AND cb.event_date >= CURDATE()
                  AND cb.status IN ("tentative", "confirmed")';
    }

    if ($filter === 'calendar') {
        $sql .= ' AND cb.event_date IS NOT NULL
                  AND cb.event_date >= DATE_SUB(
                      CURDATE(),
                      INTERVAL 60 DAY
                  )';
    }

    $sql .= ' ORDER BY
                cb.event_date IS NULL,
                cb.event_date ASC,
                cb.updated_at DESC
              LIMIT 500';

    return $pdo
        ->query($sql)
        ->fetchAll();
}

function wz_admin_crm_payments(
    ?string $status = null
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT
                cp.*,
                cb.title AS booking_title,
                cb.event_date,
                cb.amount AS booking_amount,
                cu.name AS customer_name,
                bu.name AS business_contact
            FROM crm_payments cp
            INNER JOIN crm_bookings cb
                ON cb.id = cp.booking_id
            LEFT JOIN users cu
                ON cu.id = cp.customer_user_id
            LEFT JOIN users bu
                ON bu.id = cp.business_user_id';

    $params = [];

    if ($status !== null) {
        $sql .= ' WHERE cp.status = :status';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY
                cp.paid_at IS NULL,
                cp.paid_at DESC,
                cp.created_at DESC
              LIMIT 500';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function wz_admin_crm_payment_totals(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'received' => 0.0,
            'refunded' => 0.0,
            'pending' => 0.0,
        ];
    }

    $row = $pdo
        ->query(
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
                ) AS refunded,
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = "pending"
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS pending
             FROM crm_payments'
        )
        ->fetch() ?: [];

    return [
        'received' => (float)($row['received'] ?? 0),
        'refunded' => (float)($row['refunded'] ?? 0),
        'pending' => (float)($row['pending'] ?? 0),
    ];
}

function wz_admin_crm_invoices(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    return $pdo
        ->query(
            'SELECT
                ci.*,
                cb.title AS booking_title,
                cb.event_date,
                cu.name AS customer_name,
                bu.name AS business_contact
             FROM crm_invoices ci
             INNER JOIN crm_bookings cb
                ON cb.id = ci.booking_id
             LEFT JOIN users cu
                ON cu.id = ci.customer_user_id
             LEFT JOIN users bu
                ON bu.id = ci.business_user_id
             ORDER BY ci.created_at DESC
             LIMIT 500'
        )
        ->fetchAll();
}

function wz_admin_crm_next_invoice_number(): string
{
    $pdo = wz_db();

    $sequence = 1;

    if ($pdo) {
        $sequence = (int)$pdo
            ->query(
                'SELECT COALESCE(MAX(id), 0) + 1
                 FROM crm_invoices'
            )
            ->fetchColumn();
    }

    return sprintf(
        'WZ-%s-%05d',
        date('Ym'),
        max(
            1,
            $sequence
        )
    );
}

function wz_admin_crm_create_invoice(
    array $input
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not available.',
        ];
    }

    $bookingId = (int)($input['booking_id'] ?? 0);

    $bookingStatement = $pdo->prepare(
        'SELECT *
         FROM crm_bookings
         WHERE id = :id
         LIMIT 1'
    );

    $bookingStatement->execute([
        'id' => $bookingId,
    ]);

    $booking = $bookingStatement->fetch();

    if (!$booking) {
        return [
            'ok' => false,
            'message' => 'Booking not found.',
        ];
    }

    $subtotal = max(
        0,
        (float)($input['subtotal'] ?? 0)
    );

    $taxAmount = max(
        0,
        (float)($input['tax_amount'] ?? 0)
    );

    $totalAmount = $subtotal + $taxAmount;

    if ($totalAmount <= 0) {
        return [
            'ok' => false,
            'message' => 'Enter a valid invoice amount.',
        ];
    }

    $status = (string)($input['status'] ?? 'draft');

    if (!in_array(
        $status,
        [
            'draft',
            'issued',
            'paid',
            'overdue',
            'void',
        ],
        true
    )) {
        $status = 'draft';
    }

    $invoiceNumber = trim(
        (string)($input['invoice_number'] ?? '')
    );

    if ($invoiceNumber === '') {
        $invoiceNumber =
            wz_admin_crm_next_invoice_number();
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO crm_invoices (
                invoice_number,
                booking_id,
                customer_user_id,
                business_user_id,
                business_type,
                subtotal,
                tax_amount,
                total_amount,
                status,
                issued_at,
                due_date,
                notes,
                created_by
            ) VALUES (
                :invoice_number,
                :booking_id,
                :customer_user_id,
                :business_user_id,
                :business_type,
                :subtotal,
                :tax_amount,
                :total_amount,
                :status,
                :issued_at,
                :due_date,
                :notes,
                :created_by
            )'
        );

        $statement->execute([
            'invoice_number' => $invoiceNumber,
            'booking_id' => $bookingId,
            'customer_user_id' => $booking['customer_user_id'],
            'business_user_id' => $booking['business_user_id'],
            'business_type' => $booking['business_type'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => $status,
            'issued_at' => $status === 'draft'
                ? null
                : date('Y-m-d H:i:s'),
            'due_date' => !empty($input['due_date'])
                ? (string)$input['due_date']
                : null,
            'notes' => trim(
                (string)($input['notes'] ?? '')
            ),
            'created_by' => wz_user()['id'] ?? null,
        ]);

        $invoiceId = (int)$pdo->lastInsertId();

        wz_audit(
            'admin.invoice.created',
            'crm_invoice',
            $invoiceId,
            [
                'booking_id' => $bookingId,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
            ]
        );

        return [
            'ok' => true,
            'message' => 'Invoice created.',
            'invoice_id' => $invoiceId,
        ];
    } catch (Throwable $exception) {
        error_log(
            'Wedding Za invoice creation failed: '
            . $exception->getMessage()
        );

        return [
            'ok' => false,
            'message' => 'Invoice could not be created.',
        ];
    }
}

function wz_admin_crm_commissions(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    return $pdo
        ->query(
            'SELECT
                cc.*,
                cb.title AS booking_title,
                cb.event_date,
                bu.name AS business_contact
             FROM crm_commissions cc
             INNER JOIN crm_bookings cb
                ON cb.id = cc.booking_id
             LEFT JOIN users bu
                ON bu.id = cc.business_user_id
             ORDER BY cc.created_at DESC
             LIMIT 500'
        )
        ->fetchAll();
}

function wz_admin_crm_save_commission(
    array $input
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is not available.',
        ];
    }

    $bookingId = (int)($input['booking_id'] ?? 0);

    $bookingStatement = $pdo->prepare(
        'SELECT *
         FROM crm_bookings
         WHERE id = :id
         LIMIT 1'
    );

    $bookingStatement->execute([
        'id' => $bookingId,
    ]);

    $booking = $bookingStatement->fetch();

    if (!$booking) {
        return [
            'ok' => false,
            'message' => 'Booking not found.',
        ];
    }

    $grossAmount = max(
        0,
        (float)(
            $input['gross_amount']
            ?? $booking['amount']
            ?? 0
        )
    );

    $rate = max(
        0,
        (float)($input['commission_rate'] ?? 0)
    );

    $commissionAmount = round(
        $grossAmount * ($rate / 100),
        2
    );

    $status = (string)($input['status'] ?? 'pending');

    if (!in_array(
        $status,
        [
            'pending',
            'invoiced',
            'paid',
            'waived',
        ],
        true
    )) {
        $status = 'pending';
    }

    $statement = $pdo->prepare(
        'INSERT INTO crm_commissions (
            booking_id,
            business_user_id,
            business_type,
            gross_amount,
            commission_rate,
            commission_amount,
            status,
            due_date,
            paid_at,
            notes,
            created_by
        ) VALUES (
            :booking_id,
            :business_user_id,
            :business_type,
            :gross_amount,
            :commission_rate,
            :commission_amount,
            :status,
            :due_date,
            :paid_at,
            :notes,
            :created_by
        )
        ON DUPLICATE KEY UPDATE
            gross_amount = VALUES(gross_amount),
            commission_rate = VALUES(commission_rate),
            commission_amount = VALUES(commission_amount),
            status = VALUES(status),
            due_date = VALUES(due_date),
            paid_at = VALUES(paid_at),
            notes = VALUES(notes)'
    );

    $statement->execute([
        'booking_id' => $bookingId,
        'business_user_id' => $booking['business_user_id'],
        'business_type' => $booking['business_type'],
        'gross_amount' => $grossAmount,
        'commission_rate' => $rate,
        'commission_amount' => $commissionAmount,
        'status' => $status,
        'due_date' => !empty($input['due_date'])
            ? (string)$input['due_date']
            : null,
        'paid_at' => $status === 'paid'
            ? date('Y-m-d H:i:s')
            : null,
        'notes' => trim(
            (string)($input['notes'] ?? '')
        ),
        'created_by' => wz_user()['id'] ?? null,
    ]);

    $commissionId = (int)$pdo->lastInsertId();

    if ($commissionId === 0) {
        $lookup = $pdo->prepare(
            'SELECT id
             FROM crm_commissions
             WHERE booking_id = :booking_id
             LIMIT 1'
        );

        $lookup->execute([
            'booking_id' => $bookingId,
        ]);

        $commissionId = (int)$lookup->fetchColumn();
    }

    wz_audit(
        'admin.commission.saved',
        'crm_commission',
        $commissionId,
        [
            'booking_id' => $bookingId,
            'rate' => $rate,
            'amount' => $commissionAmount,
            'status' => $status,
        ]
    );

    return [
        'ok' => true,
        'message' => 'Commission saved.',
        'commission_id' => $commissionId,
    ];
}

function wz_admin_crm_report_summary(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $leadStats = $pdo
        ->query(
            'SELECT
                COUNT(*) AS total_leads,
                SUM(stage = "new") AS new_leads,
                SUM(stage = "won") AS won_leads,
                SUM(stage = "lost") AS lost_leads,
                SUM(site_visit_status = "completed")
                    AS completed_visits
             FROM crm_enquiries'
        )
        ->fetch() ?: [];

    $bookingStats = $pdo
        ->query(
            'SELECT
                COUNT(*) AS total_bookings,
                SUM(status = "confirmed")
                    AS confirmed_bookings,
                SUM(
                    event_date >= CURDATE()
                    AND status IN (
                        "tentative",
                        "confirmed"
                    )
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
             FROM crm_bookings'
        )
        ->fetch() ?: [];

    $payments = wz_admin_crm_payment_totals();

    $commissionStats = $pdo
        ->query(
            'SELECT
                COALESCE(
                    SUM(
                        CASE
                            WHEN status <> "waived"
                            THEN commission_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS commission_value,
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = "paid"
                            THEN commission_amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS commission_paid
             FROM crm_commissions'
        )
        ->fetch() ?: [];

    return array_merge(
        $leadStats,
        $bookingStats,
        $payments,
        $commissionStats
    );
}

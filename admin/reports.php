<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$pdo = wz_db();
$summary = wz_admin_crm_report_summary();

$totalLeads = (int)($summary['total_leads'] ?? 0);
$wonLeads = (int)($summary['won_leads'] ?? 0);

$conversionRate = $totalLeads > 0
    ? round(
        ($wonLeads / $totalLeads) * 100,
        1
    )
    : 0.0;

$monthlyRows = [];
$businessRows = [];

if ($pdo) {
    $monthlyRows = $pdo
        ->query(
            'SELECT
                DATE_FORMAT(
                    event_date,
                    "%Y-%m"
                ) AS month_key,
                COUNT(*) AS function_count,
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
             WHERE event_date IS NOT NULL
             AND event_date >= DATE_SUB(
                 CURDATE(),
                 INTERVAL 5 MONTH
             )
             GROUP BY month_key
             ORDER BY month_key ASC'
        )
        ->fetchAll();

    $businessRows = $pdo
        ->query(
            'SELECT
                cb.business_type,
                COUNT(*) AS booking_count,
                COALESCE(
                    SUM(
                        CASE
                            WHEN cb.status <> "cancelled"
                            THEN cb.amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS booked_value
             FROM crm_bookings cb
             GROUP BY cb.business_type
             ORDER BY cb.business_type'
        )
        ->fetchAll();
}

$adminPage = 'reports';
$adminTitle = 'Reports';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>PERFORMANCE</small>
        <h1>Reports</h1>

        <p class="admin-section-note">
            Platform-wide lead, booking, function, payment and commission performance.
        </p>
    </div>
</div>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Total leads</span>
        <strong><?= h((string)$totalLeads) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Lead conversion</span>
        <strong>
            <?= h(number_format($conversionRate, 1)) ?>%
        </strong>
    </article>

    <article class="admin-stat">
        <span>Bookings</span>
        <strong>
            <?= h(
                (string)($summary['total_bookings'] ?? 0)
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Upcoming functions</span>
        <strong>
            <?= h(
                (string)($summary['upcoming_functions'] ?? 0)
            ) ?>
        </strong>
    </article>
</div>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Booked value</span>

        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    (float)($summary['booked_value'] ?? 0)
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Payments received</span>

        <strong
            class="admin-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)($summary['received'] ?? 0)
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Refunded</span>

        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    (float)($summary['refunded'] ?? 0)
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Commission value</span>

        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    (float)($summary['commission_value'] ?? 0)
                )
            ) ?>
        </strong>
    </article>
</div>

<div
    style="
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
        gap: 28px;
        align-items: start;
    "
>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2>Monthly function value</h2>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Functions</th>
                        <th>Booked value</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($monthlyRows as $row): ?>
                        <tr>
                            <td>
                                <?= h(
                                    date(
                                        'F Y',
                                        strtotime(
                                            (string)$row['month_key']
                                            . '-01'
                                        )
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h((string)$row['function_count']) ?>
                            </td>

                            <td>
                                ₹<?= h(
                                    number_format(
                                        (float)$row['booked_value']
                                    )
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$monthlyRows): ?>
                        <tr>
                            <td colspan="3">
                                No monthly function data yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="admin-panel">
        <div class="admin-panel-head">
            <h2>Business mix</h2>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Bookings</th>
                        <th>Value</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($businessRows as $row): ?>
                        <tr>
                            <td>
                                <?= h(ucfirst((string)$row['business_type'])) ?>
                            </td>

                            <td>
                                <?= h((string)$row['booking_count']) ?>
                            </td>

                            <td>
                                ₹<?= h(
                                    number_format(
                                        (float)$row['booked_value']
                                    )
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$businessRows): ?>
                        <tr>
                            <td colspan="3">
                                No booking mix data yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </aside>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Lead funnel</h2>
    </div>

    <div
        class="admin-grid"
        style="margin: 0;"
    >
        <article class="admin-stat">
            <span>New</span>
            <strong>
                <?= h(
                    (string)($summary['new_leads'] ?? 0)
                ) ?>
            </strong>
        </article>

        <article class="admin-stat">
            <span>Won</span>
            <strong><?= h((string)$wonLeads) ?></strong>
        </article>

        <article class="admin-stat">
            <span>Lost</span>
            <strong>
                <?= h(
                    (string)($summary['lost_leads'] ?? 0)
                ) ?>
            </strong>
        </article>

        <article class="admin-stat">
            <span>Completed site visits</span>
            <strong>
                <?= h(
                    (string)($summary['completed_visits'] ?? 0)
                ) ?>
            </strong>
        </article>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

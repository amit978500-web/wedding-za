<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$crmPage = 'reports';
$crmTitle = 'Reports';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);
$pdo = wz_db();

$summary = wz_venue_report_summary(
    $userId
);

$totalLeads = (int)($summary['total_leads'] ?? 0);
$wonLeads = (int)($summary['won_leads'] ?? 0);
$conversionRate = $totalLeads > 0
    ? round(
        ($wonLeads / $totalLeads) * 100,
        1
    )
    : 0.0;

$monthlyRows = [];

if ($pdo) {
    $statement = $pdo->prepare(
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
         WHERE business_user_id = :user_id
         AND business_type = "venue"
         AND event_date IS NOT NULL
         AND event_date >= DATE_SUB(
             CURDATE(),
             INTERVAL 5 MONTH
         )
         GROUP BY month_key
         ORDER BY month_key ASC'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    $monthlyRows = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            PERFORMANCE
        </span>

        <h1>
            Reports
        </h1>

        <p>
            Lead conversion, function volume, booking value, collections and outstanding balance.
        </p>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Total leads</span>

        <strong>
            <?= h((string)$totalLeads) ?>
        </strong>

        <small>All venue CRM leads</small>
    </article>

    <article class="crm-stat">
        <span>Lead conversion</span>

        <strong>
            <?= h(number_format($conversionRate, 1)) ?>%
        </strong>

        <small>
            <?= h((string)$wonLeads) ?> won leads
        </small>
    </article>

    <article class="crm-stat">
        <span>Bookings</span>

        <strong>
            <?= h(
                (string)($summary['total_bookings'] ?? 0)
            ) ?>
        </strong>

        <small>
            <?= h(
                (string)($summary['confirmed_bookings'] ?? 0)
            ) ?> confirmed
        </small>
    </article>

    <article class="crm-stat">
        <span>Upcoming functions</span>

        <strong>
            <?= h(
                (string)($summary['upcoming_functions'] ?? 0)
            ) ?>
        </strong>

        <small>Future tentative + confirmed</small>
    </article>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Booked value</span>

        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    (float)($summary['booked_value'] ?? 0)
                )
            ) ?>
        </strong>

        <small>Non-cancelled booking value</small>
    </article>

    <article class="crm-stat">
        <span>Received</span>

        <strong
            class="crm-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)($summary['received'] ?? 0)
                )
            ) ?>
        </strong>

        <small>Net payment collections</small>
    </article>

    <article class="crm-stat">
        <span>Balance due</span>

        <strong
            class="crm-money-due"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)($summary['balance'] ?? 0)
                )
            ) ?>
        </strong>

        <small>Outstanding against bookings</small>
    </article>

    <article class="crm-stat">
        <span>Site visits completed</span>

        <strong>
            <?= h(
                (string)($summary['completed_visits'] ?? 0)
            ) ?>
        </strong>

        <small>Completed lead site visits</small>
    </article>
</div>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Monthly function value</h2>
                <p>Recent function volume and booked value.</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
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
                                No function reporting data yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Lead funnel</h2>
                </div>
            </div>

            <div class="crm-list">
                <div class="crm-list-item">
                    <strong>
                        <?= h(
                            (string)($summary['new_leads'] ?? 0)
                        ) ?>
                    </strong>
                    <p>New leads</p>
                </div>

                <div class="crm-list-item">
                    <strong>
                        <?= h((string)$wonLeads) ?>
                    </strong>
                    <p>Won leads</p>
                </div>

                <div class="crm-list-item">
                    <strong>
                        <?= h(
                            (string)($summary['lost_leads'] ?? 0)
                        ) ?>
                    </strong>
                    <p>Lost leads</p>
                </div>
            </div>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$pdo = wz_db();
$summary = wz_admin_crm_report_summary();

$allLeads = wz_admin_crm_leads('all');
$newLeads = wz_admin_crm_leads('new');
$followUps = wz_admin_crm_leads('followups');
$siteVisits = wz_admin_crm_leads('site-visits');
$upcomingFunctions = wz_admin_crm_functions('upcoming');
$paymentTotals = wz_admin_crm_payment_totals();

$today = date('Y-m-d');

$followUpsDue = array_values(
    array_filter(
        $followUps,
        fn (array $lead): bool =>
            !empty($lead['next_follow_up'])
            && substr(
                (string)$lead['next_follow_up'],
                0,
                10
            ) <= $today
    )
);

$upcomingVisits = array_values(
    array_filter(
        $siteVisits,
        fn (array $lead): bool =>
            !empty($lead['site_visit_at'])
            && strtotime(
                (string)$lead['site_visit_at']
            ) >= time()
            && $lead['site_visit_status'] === 'scheduled'
    )
);

$counts = [
    'customers' => 0,
    'active_venues' => 0,
    'active_vendors' => 0,
    'unassigned_leads' => 0,
    'pending_invoices' => 0,
];

if ($pdo) {
    $counts['customers'] = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM users
             WHERE role = 'host'"
        )
        ->fetchColumn();

    $counts['active_venues'] = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM venue_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE vp.approval_status = "approved"
             AND u.status = "active"'
        )
        ->fetchColumn();

    $counts['active_vendors'] = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM vendor_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE vp.approval_status = "approved"
             AND u.status = "active"'
        )
        ->fetchColumn();

    $counts['unassigned_leads'] = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM crm_enquiries
             WHERE business_user_id IS NULL
             AND stage NOT IN ("won", "lost")'
        )
        ->fetchColumn();

    $counts['pending_invoices'] = (int)$pdo
        ->query(
            'SELECT COUNT(*)
             FROM crm_invoices
             WHERE status IN (
                 "draft",
                 "issued",
                 "overdue"
             )'
        )
        ->fetchColumn();
}

$adminPage = 'dashboard';
$adminTitle = 'Dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>ADMIN CRM</small>
        <h1>Dashboard</h1>

        <p class="admin-section-note">
            Platform operations across leads, functions, bookings, payments and marketplace supply.
        </p>
    </div>

    <div class="admin-actions">
        <a
            class="admin-button"
            href="leads-new.php"
        >
            New leads
        </a>

        <a
            class="admin-button secondary"
            href="functions-upcoming.php"
        >
            Upcoming functions
        </a>
    </div>
</div>

<?php if (!$pdo): ?>
    <div class="admin-notice">
        MySQL is not connected. Admin CRM requires the Wedding Za database.
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-stat">
        <span>New leads</span>
        <strong><?= h((string)count($newLeads)) ?></strong>
        <small>Need first action</small>
    </article>

    <article class="admin-stat">
        <span>Follow-ups due</span>
        <strong><?= h((string)count($followUpsDue)) ?></strong>
        <small>Due today or overdue</small>
    </article>

    <article class="admin-stat">
        <span>Site visits</span>
        <strong><?= h((string)count($upcomingVisits)) ?></strong>
        <small>Upcoming scheduled visits</small>
    </article>

    <article class="admin-stat">
        <span>Upcoming functions</span>
        <strong><?= h((string)count($upcomingFunctions)) ?></strong>
        <small>Tentative + confirmed</small>
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
        <small>Non-cancelled bookings</small>
    </article>

    <article class="admin-stat">
        <span>Payments received</span>
        <strong
            class="admin-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)$paymentTotals['received']
                )
            ) ?>
        </strong>
        <small>Recorded receipts</small>
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
        <small>Non-waived commission</small>
    </article>

    <article class="admin-stat">
        <span>Pending invoices</span>
        <strong><?= h((string)$counts['pending_invoices']) ?></strong>
        <small>Draft / issued / overdue</small>
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
            <div>
                <h2>Latest leads</h2>
                <p class="admin-section-note">
                    Most recently updated CRM opportunities.
                </p>
            </div>

            <a
                class="admin-button secondary"
                href="leads.php"
            >
                View all
            </a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Event</th>
                        <th>Business</th>
                        <th>Stage</th>
                        <th>Updated</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (array_slice($allLeads, 0, 8) as $lead): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= h(
                                        (string)(
                                            $lead['customer_name']
                                            ?: 'Guest lead'
                                        )
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $lead['event_type']
                                        ?: 'Event'
                                    )
                                ) ?>

                                <?php if (!empty($lead['event_date'])): ?>
                                    <br>
                                    <small>
                                        <?= h((string)$lead['event_date']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $lead['business_contact']
                                        ?: 'Unassigned'
                                    )
                                ) ?>

                                <br>

                                <small>
                                    <?= h((string)$lead['business_type']) ?>
                                </small>
                            </td>

                            <td>
                                <span class="admin-badge <?= h((string)$lead['stage']) ?>">
                                    <?= h(ucfirst((string)$lead['stage'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= h((string)$lead['updated_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$allLeads): ?>
                        <tr>
                            <td colspan="5">
                                No CRM leads yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="admin-panel">
        <div class="admin-panel-head">
            <h2>Operations</h2>
        </div>

        <div
            class="admin-grid"
            style="
                margin: 0;
                grid-template-columns: repeat(2, 1fr);
            "
        >
            <article class="admin-stat">
                <span>Customers</span>
                <strong><?= h((string)$counts['customers']) ?></strong>
            </article>

            <article class="admin-stat">
                <span>Active venues</span>
                <strong><?= h((string)$counts['active_venues']) ?></strong>
            </article>

            <article class="admin-stat">
                <span>Active vendors</span>
                <strong><?= h((string)$counts['active_vendors']) ?></strong>
            </article>

            <article class="admin-stat">
                <span>Unassigned leads</span>
                <strong><?= h((string)$counts['unassigned_leads']) ?></strong>
            </article>
        </div>
    </aside>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Next functions</h2>
            <p class="admin-section-note">
                Upcoming platform functions from all businesses.
            </p>
        </div>

        <a
            class="admin-button secondary"
            href="functions-upcoming.php"
        >
            View calendar
        </a>
    </div>

    <div class="admin-calendar-grid">
        <?php foreach (array_slice($upcomingFunctions, 0, 6) as $function): ?>
            <article class="admin-calendar-card">
                <strong>
                    <?= h(
                        (string)(
                            $function['event_date']
                            ?: 'Date not set'
                        )
                    ) ?>
                </strong>

                <span>
                    <?= h((string)$function['title']) ?>
                </span>

                <small>
                    <?= h(
                        (string)(
                            $function['customer_name']
                            ?: 'Customer'
                        )
                    ) ?>
                    ·
                    <?= h(
                        (string)(
                            $function['business_contact']
                            ?: 'Business'
                        )
                    ) ?>
                </small>

                <small>
                    <?= h(ucfirst((string)$function['status'])) ?>
                </small>
            </article>
        <?php endforeach; ?>

        <?php if (!$upcomingFunctions): ?>
            <article class="admin-calendar-card">
                No upcoming functions.
            </article>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

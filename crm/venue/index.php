<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$crmPage = 'dashboard';
$crmTitle = 'Venue Dashboard';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);

$allLeads = wz_venue_leads(
    $userId,
    'all'
);

$newLeads = wz_venue_leads(
    $userId,
    'new'
);

$followUps = wz_venue_leads(
    $userId,
    'followups'
);

$siteVisits = wz_venue_leads(
    $userId,
    'site-visits'
);

$upcomingFunctions = wz_venue_functions(
    $userId,
    'upcoming'
);

$paymentTotals = wz_venue_payment_totals(
    $userId
);

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

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            VENUE CRM
        </span>

        <h1>
            Dashboard
        </h1>

        <p>
            Leads, visits, functions, bookings and money — one operational view for the venue team.
        </p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button"
            href="<?= h(wz_app_url('crm/venue/leads-new.php')) ?>"
        >
            New leads
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/functions-upcoming.php')) ?>"
        >
            Upcoming functions
        </a>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>New leads</span>

        <strong>
            <?= h((string)count($newLeads)) ?>
        </strong>

        <small>Need first action</small>
    </article>

    <article class="crm-stat">
        <span>Follow-ups due</span>

        <strong>
            <?= h((string)count($followUpsDue)) ?>
        </strong>

        <small>Due today or overdue</small>
    </article>

    <article class="crm-stat">
        <span>Site visits</span>

        <strong>
            <?= h((string)count($upcomingVisits)) ?>
        </strong>

        <small>Upcoming scheduled visits</small>
    </article>

    <article class="crm-stat">
        <span>Upcoming functions</span>

        <strong>
            <?= h((string)count($upcomingFunctions)) ?>
        </strong>

        <small>Tentative + confirmed</small>
    </article>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Total leads</span>

        <strong>
            <?= h((string)count($allLeads)) ?>
        </strong>

        <small>All venue enquiries</small>
    </article>

    <article class="crm-stat">
        <span>Booked value</span>

        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    (float)$paymentTotals['booked_value']
                )
            ) ?>
        </strong>

        <small>Non-cancelled bookings</small>
    </article>

    <article class="crm-stat">
        <span>Received</span>

        <strong
            class="crm-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)$paymentTotals['received']
                )
            ) ?>
        </strong>

        <small>Net payment receipts</small>
    </article>

    <article class="crm-stat">
        <span>Balance due</span>

        <strong
            class="crm-money-due"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    (float)$paymentTotals['balance']
                )
            ) ?>
        </strong>

        <small>Outstanding against bookings</small>
    </article>
</div>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Latest leads</h2>
                <p>Most recently updated venue opportunities.</p>
            </div>

            <a
                class="crm-button secondary small"
                href="<?= h(wz_app_url('crm/venue/leads.php')) ?>"
            >
                View all
            </a>
        </div>

        <?php if ($allLeads): ?>
            <div class="crm-list">
                <?php foreach (array_slice($allLeads, 0, 6) as $lead): ?>
                    <a
                        class="crm-list-item"
                        href="<?= h(
                            wz_app_url(
                                'crm/venue/enquiry.php?id='
                                . urlencode((string)$lead['id'])
                            )
                        ) ?>"
                    >
                        <strong>
                            <?= h(
                                (string)(
                                    $lead['customer_name']
                                    ?: 'Guest lead'
                                )
                            ) ?>
                        </strong>

                        <p>
                            <?= h(
                                (string)(
                                    $lead['event_type']
                                    ?: 'Event'
                                )
                            ) ?>

                            <?php if (!empty($lead['event_date'])): ?>
                                · <?= h((string)$lead['event_date']) ?>
                            <?php endif; ?>

                            <?php if (!empty($lead['city'])): ?>
                                · <?= h((string)$lead['city']) ?>
                            <?php endif; ?>
                        </p>

                        <small>
                            <?= h(ucfirst((string)$lead['stage'])) ?>
                            ·
                            Updated <?= h((string)$lead['updated_at']) ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="crm-empty">
                No leads yet.
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Next functions</h2>
                    <p>Upcoming operational dates.</p>
                </div>
            </div>

            <?php if ($upcomingFunctions): ?>
                <div class="crm-list">
                    <?php foreach (array_slice($upcomingFunctions, 0, 5) as $function): ?>
                        <div class="crm-list-item">
                            <strong>
                                <?= h((string)$function['title']) ?>
                            </strong>

                            <p>
                                <?= h(
                                    (string)(
                                        $function['customer_name']
                                        ?: 'Customer'
                                    )
                                ) ?>
                            </p>

                            <small>
                                <?= h(
                                    (string)(
                                        $function['event_date']
                                        ?: 'Date not set'
                                    )
                                ) ?>
                                ·
                                <?= h(ucfirst((string)$function['status'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="crm-empty">
                    No upcoming functions.
                </div>
            <?php endif; ?>
        </section>

        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Quick access</h2>
                </div>
            </div>

            <div class="crm-list">
                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url('crm/venue/leads-followups.php')) ?>"
                >
                    <strong>Follow-ups</strong>
                    <p><?= h((string)count($followUpsDue)) ?> due now</p>
                </a>

                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url('crm/venue/leads-site-visits.php')) ?>"
                >
                    <strong>Site visits</strong>
                    <p><?= h((string)count($upcomingVisits)) ?> upcoming</p>
                </a>

                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url('crm/venue/payments.php')) ?>"
                >
                    <strong>Payments</strong>
                    <p>
                        ₹<?= h(
                            number_format(
                                (float)$paymentTotals['balance']
                            )
                        ) ?> due
                    </p>
                </a>
            </div>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

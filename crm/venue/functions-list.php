<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$functionFilter = $functionFilter ?? 'all';
$crmPage = $crmPage ?? 'functions-all';
$crmTitle = $crmTitle ?? 'Functions';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);

$functions = wz_venue_functions(
    $userId,
    $functionFilter
);

$headings = [
    'all' => [
        'title' => 'All Functions',
        'description' => 'Every event/function attached to a venue booking.',
    ],
    'upcoming' => [
        'title' => 'Upcoming Functions',
        'description' => 'Tentative and confirmed functions from today onward.',
    ],
    'calendar' => [
        'title' => 'Function Calendar',
        'description' => 'A date-first view of upcoming and recent functions.',
    ],
];

$heading = $headings[$functionFilter]
    ?? $headings['all'];

$confirmedCount = count(
    array_filter(
        $functions,
        fn (array $function): bool =>
            $function['status'] === 'confirmed'
    )
);

$tentativeCount = count(
    array_filter(
        $functions,
        fn (array $function): bool =>
            $function['status'] === 'tentative'
    )
);

$calendarGroups = [];

if ($functionFilter === 'calendar') {
    foreach ($functions as $function) {
        if (empty($function['event_date'])) {
            continue;
        }

        $monthKey = date(
            'Y-m',
            strtotime((string)$function['event_date'])
        );

        $calendarGroups[$monthKey][] = $function;
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            FUNCTIONS
        </span>

        <h1>
            <?= h($heading['title']) ?>
        </h1>

        <p>
            <?= h($heading['description']) ?>
        </p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/functions.php')) ?>"
        >
            All
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/functions-upcoming.php')) ?>"
        >
            Upcoming
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/functions-calendar.php')) ?>"
        >
            Calendar
        </a>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Functions</span>
        <strong><?= h((string)count($functions)) ?></strong>
        <small><?= h($heading['title']) ?></small>
    </article>

    <article class="crm-stat">
        <span>Confirmed</span>
        <strong><?= h((string)$confirmedCount) ?></strong>
        <small>Confirmed bookings</small>
    </article>

    <article class="crm-stat">
        <span>Tentative</span>
        <strong><?= h((string)$tentativeCount) ?></strong>
        <small>Still on hold</small>
    </article>

    <article class="crm-stat">
        <span>Booked value</span>
        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    array_sum(
                        array_map(
                            fn (array $function): float =>
                                (float)($function['amount'] ?? 0),
                            array_filter(
                                $functions,
                                fn (array $function): bool =>
                                    $function['status'] !== 'cancelled'
                            )
                        )
                    )
                )
            ) ?>
        </strong>
        <small>For functions shown</small>
    </article>
</div>

<?php if ($functionFilter === 'calendar'): ?>
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Calendar</h2>
                <p>Grouped month by month.</p>
            </div>
        </div>

        <?php if ($calendarGroups): ?>
            <?php foreach ($calendarGroups as $monthKey => $monthFunctions): ?>
                <div class="crm-calendar-month">
                    <h3>
                        <?= h(
                            date(
                                'F Y',
                                strtotime($monthKey . '-01')
                            )
                        ) ?>
                    </h3>

                    <div class="crm-calendar-grid">
                        <?php foreach ($monthFunctions as $function): ?>
                            <article class="crm-day crm-function-card">
                                <strong>
                                    <?= h(
                                        date(
                                            'd M',
                                            strtotime(
                                                (string)$function['event_date']
                                            )
                                        )
                                    ) ?>
                                </strong>

                                <span>
                                    <?= h((string)$function['title']) ?>
                                </span>

                                <span>
                                    <?= h(
                                        (string)(
                                            $function['customer_name']
                                            ?: 'Customer'
                                        )
                                    ) ?>
                                </span>

                                <span class="crm-badge <?= h((string)$function['status']) ?>">
                                    <?= h(ucfirst((string)$function['status'])) ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="crm-empty">
                No dated functions found.
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2><?= h($heading['title']) ?></h2>
                <p>Operational function schedule.</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Function</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>City</th>
                        <th>Booking</th>
                        <th>Payment</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($functions as $function): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= h((string)$function['title']) ?>
                                </strong>

                                <br>

                                <small>
                                    <?= h(
                                        (string)(
                                            $function['event_type']
                                            ?: 'Event'
                                        )
                                    ) ?>
                                </small>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $function['customer_name']
                                        ?: 'Customer'
                                    )
                                ) ?>

                                <?php if (!empty($function['customer_email'])): ?>
                                    <br>
                                    <small>
                                        <?= h((string)$function['customer_email']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $function['event_date']
                                        ?: 'Date not set'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h((string)($function['city'] ?: '—')) ?>
                            </td>

                            <td>
                                <span class="crm-badge <?= h((string)$function['status']) ?>">
                                    <?= h(ucfirst((string)$function['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="crm-badge <?= h((string)$function['payment_status']) ?>">
                                    <?= h(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string)$function['payment_status']
                                            )
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= !empty($function['amount'])
                                    ? '₹' . number_format(
                                        (float)$function['amount']
                                    )
                                    : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$functions): ?>
                        <tr>
                            <td colspan="7">
                                No functions found in this section.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

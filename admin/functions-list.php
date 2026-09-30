<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$functionFilter = $functionFilter ?? 'all';
$adminPage = $adminPage ?? 'functions-all';
$adminTitle = $adminTitle ?? 'All Functions';

$functions = wz_admin_crm_functions(
    $functionFilter
);

$headings = [
    'all' => [
        'title' => 'All Functions',
        'description' => 'Every event/function booking across the platform.',
    ],
    'upcoming' => [
        'title' => 'Upcoming Functions',
        'description' => 'Future tentative and confirmed functions.',
    ],
    'calendar' => [
        'title' => 'Function Calendar',
        'description' => 'Date-first operational view across venues and vendors.',
    ],
];

$heading = $headings[$functionFilter]
    ?? $headings['all'];

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

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>FUNCTIONS</small>
        <h1><?= h($heading['title']) ?></h1>
        <p class="admin-section-note">
            <?= h($heading['description']) ?>
        </p>
    </div>
</div>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Functions</span>
        <strong><?= h((string)count($functions)) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Confirmed</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $functions,
                        fn (array $function): bool =>
                            $function['status'] === 'confirmed'
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Tentative</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $functions,
                        fn (array $function): bool =>
                            $function['status'] === 'tentative'
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
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
    </article>
</div>

<?php if ($functionFilter === 'calendar'): ?>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2>Calendar</h2>
        </div>

        <?php if ($calendarGroups): ?>
            <?php foreach ($calendarGroups as $monthKey => $monthFunctions): ?>
                <div style="padding: 20px;">
                    <h3
                        style="
                            margin: 0 0 14px;
                            font: 400 28px Georgia, serif;
                        "
                    >
                        <?= h(
                            date(
                                'F Y',
                                strtotime($monthKey . '-01')
                            )
                        ) ?>
                    </h3>

                    <div class="admin-calendar-grid">
                        <?php foreach ($monthFunctions as $function): ?>
                            <article class="admin-calendar-card">
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
                                            ?: 'Unassigned'
                                        )
                                    ) ?>
                                </small>

                                <small>
                                    <?= h(ucfirst((string)$function['status'])) ?>
                                    ·
                                    <?= h((string)$function['business_type']) ?>
                                </small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 20px;">
                No dated functions found.
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2><?= h($heading['title']) ?></h2>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Function</th>
                        <th>Customer</th>
                        <th>Business</th>
                        <th>Date</th>
                        <th>Status</th>
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
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $function['business_contact']
                                        ?: 'Unassigned'
                                    )
                                ) ?>

                                <br>

                                <small>
                                    <?= h((string)$function['business_type']) ?>
                                </small>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $function['event_date']
                                        ?: 'Not set'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span class="admin-badge <?= h((string)$function['status']) ?>">
                                    <?= h(ucfirst((string)$function['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-badge <?= h((string)$function['payment_status']) ?>">
                                    <?= h(ucfirst((string)$function['payment_status'])) ?>
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
                                No functions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

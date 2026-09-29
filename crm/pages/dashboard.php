<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'dashboard';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);

$enquiries = wz_crm_enquiries_for_user(
    $userId,
    $crmRole
);

$bookings = wz_crm_bookings_for_user(
    $userId,
    $crmRole
);

$tasks = wz_crm_tasks_for_user(
    $userId
);

$messages = wz_crm_messages_for_user(
    $userId
);

$openTasks = array_values(
    array_filter(
        $tasks,
        fn (array $task): bool =>
            ($task['status'] ?? '') !== 'done'
    )
);

$unreadMessages = array_values(
    array_filter(
        $messages,
        fn (array $message): bool =>
            (int)$message['recipient_user_id'] === $userId
            && empty($message['read_at'])
    )
);

$activeBookings = array_values(
    array_filter(
        $bookings,
        fn (array $booking): bool =>
            !in_array(
                (string)$booking['status'],
                [
                    'completed',
                    'cancelled',
                ],
                true
            )
    )
);

$pipelineOpen = array_values(
    array_filter(
        $enquiries,
        fn (array $enquiry): bool =>
            !in_array(
                (string)$enquiry['stage'],
                [
                    'won',
                    'lost',
                ],
                true
            )
    )
);

$basePath = match ($crmRole) {
    'host' => 'crm/customer',
    'venue' => 'crm/venue',
    default => 'crm/vendor',
};

$roleLabel = wz_crm_role_label($crmRole);

$crmTitle = $roleLabel . ' CRM';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            <?= h(strtoupper($roleLabel)) ?> CRM
        </span>

        <h1>
            Welcome back,
            <br>
            <em>
                <?= h(explode(' ', (string)$crmUser['name'])[0]) ?>.
            </em>
        </h1>

        <p>
            <?php if ($crmRole === 'host'): ?>
                Keep enquiries, bookings, tasks and planning activity together.
            <?php else: ?>
                Manage your sales pipeline, follow-ups, bookings and customer communication.
            <?php endif; ?>
        </p>
    </div>

    <div class="crm-actions">
        <?php if ($crmRole === 'host'): ?>
            <a
                class="crm-button"
                href="<?= h(wz_app_url('vendors.php')) ?>"
            >
                Find vendors ↗
            </a>
        <?php else: ?>
            <a
                class="crm-button"
                href="<?= h(wz_app_url($basePath . '/enquiries.php')) ?>"
            >
                Open pipeline ↗
            </a>
        <?php endif; ?>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url($basePath . '/tasks.php')) ?>"
        >
            Tasks
        </a>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>
            <?= $crmRole === 'host' ? 'My enquiries' : 'Open pipeline' ?>
        </span>

        <strong>
            <?= h(
                (string)(
                    $crmRole === 'host'
                        ? count($enquiries)
                        : count($pipelineOpen)
                )
            ) ?>
        </strong>

        <small>
            <?= $crmRole === 'host'
                ? 'Across venues and vendors'
                : 'Not yet won or lost' ?>
        </small>
    </article>

    <article class="crm-stat">
        <span>Active bookings</span>

        <strong>
            <?= h((string)count($activeBookings)) ?>
        </strong>

        <small>
            Tentative and confirmed
        </small>
    </article>

    <article class="crm-stat">
        <span>Open tasks</span>

        <strong>
            <?= h((string)count($openTasks)) ?>
        </strong>

        <small>
            Follow-ups and actions
        </small>
    </article>

    <article class="crm-stat">
        <span>Unread messages</span>

        <strong>
            <?= h((string)count($unreadMessages)) ?>
        </strong>

        <small>
            Customer and business conversations
        </small>
    </article>
</div>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>
                    Recent enquiries
                </h2>

                <p>
                    Latest enquiry activity in your CRM.
                </p>
            </div>

            <a
                class="crm-button secondary small"
                href="<?= h(wz_app_url($basePath . '/enquiries.php')) ?>"
            >
                View all
            </a>
        </div>

        <?php if ($enquiries): ?>
            <div class="crm-list">
                <?php foreach (array_slice($enquiries, 0, 5) as $enquiry): ?>
                    <a
                        class="crm-list-item"
                        href="<?= h(
                            wz_app_url(
                                $basePath .
                                '/enquiry.php?id=' .
                                urlencode((string)$enquiry['id'])
                            )
                        ) ?>"
                    >
                        <strong>
                            <?= h((string)($enquiry['subject'] ?: 'Event enquiry')) ?>
                        </strong>

                        <p>
                            <?= h((string)($enquiry['event_type'] ?: 'Event')) ?>
                            <?php if (!empty($enquiry['city'])): ?>
                                · <?= h((string)$enquiry['city']) ?>
                            <?php endif; ?>
                        </p>

                        <small>
                            Stage:
                            <?= h(ucfirst((string)$enquiry['stage'])) ?>
                            ·
                            <?= h((string)$enquiry['updated_at']) ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="crm-empty">
                No enquiries yet.
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        Next tasks
                    </h2>

                    <p>
                        Keep follow-ups moving.
                    </p>
                </div>
            </div>

            <?php if ($openTasks): ?>
                <div class="crm-list">
                    <?php foreach (array_slice($openTasks, 0, 5) as $task): ?>
                        <div class="crm-list-item">
                            <strong>
                                <?= h((string)$task['title']) ?>
                            </strong>

                            <small>
                                <?= h(ucfirst((string)$task['priority'])) ?>
                                <?php if (!empty($task['due_at'])): ?>
                                    · Due <?= h((string)$task['due_at']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="crm-empty">
                    Nothing urgent right now.
                </div>
            <?php endif; ?>
        </section>

        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        CRM shortcuts
                    </h2>
                </div>
            </div>

            <div class="crm-list">
                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url($basePath . '/messages.php')) ?>"
                >
                    <strong>Messages</strong>
                    <p>Open customer or business conversations.</p>
                </a>

                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url($basePath . '/bookings.php')) ?>"
                >
                    <strong>Bookings</strong>
                    <p>Track confirmed event work.</p>
                </a>

                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url($basePath . '/profile.php')) ?>"
                >
                    <strong>Profile</strong>
                    <p>Keep account and business details current.</p>
                </a>
            </div>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

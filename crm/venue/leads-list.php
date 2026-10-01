<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$leadFilter = $leadFilter ?? 'all';
$crmPage = $crmPage ?? 'leads-all';
$crmTitle = $crmTitle ?? 'Leads';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);

$message = '';
$isSuccess = false;
$pdo = wz_db();

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $enquiryId = (int)($_POST['enquiry_id'] ?? 0);

        $enquiry = wz_crm_enquiry_for_user(
            $enquiryId,
            $userId,
            'venue'
        );

        if (!$enquiry) {
            $message = 'Lead not found.';
        } elseif ($action === 'site-visit') {
            $siteVisitAt = trim(
                (string)($_POST['site_visit_at'] ?? '')
            );

            $siteVisitStatus = (string)(
                $_POST['site_visit_status']
                ?? 'scheduled'
            );

            if (!in_array(
                $siteVisitStatus,
                [
                    'not_scheduled',
                    'scheduled',
                    'completed',
                    'cancelled',
                ],
                true
            )) {
                $siteVisitStatus = 'scheduled';
            }

            $statement = $pdo->prepare(
                'UPDATE crm_enquiries
                 SET site_visit_at = :site_visit_at,
                     site_visit_status = :site_visit_status
                 WHERE id = :id
                 AND business_user_id = :business_user_id
                 AND business_type = "venue"'
            );

            $statement->execute([
                'site_visit_at' => $siteVisitAt !== ''
                    ? $siteVisitAt
                    : null,
                'site_visit_status' => $siteVisitAt !== ''
                    ? $siteVisitStatus
                    : 'not_scheduled',
                'id' => $enquiryId,
                'business_user_id' => $userId,
            ]);

            wz_audit(
                'crm.venue_site_visit.updated',
                'crm_enquiry',
                $enquiryId,
                [
                    'site_visit_at' => $siteVisitAt,
                    'site_visit_status' => $siteVisitStatus,
                ]
            );

            if (!empty($enquiry['customer_user_id'])) {
                $visitTitle = match ($siteVisitStatus) {
                    'completed' => 'Site visit completed',
                    'cancelled' => 'Site visit cancelled',
                    default => $siteVisitAt !== ''
                        ? 'Site visit scheduled'
                        : 'Site visit updated',
                };

                $visitBody = $siteVisitAt !== ''
                    ? 'Venue visit · '
                        . date(
                            'd M Y, h:i A',
                            strtotime($siteVisitAt)
                        )
                    : 'Your venue visit details were updated.';

                wz_notification_send(
                    (int)$enquiry['customer_user_id'],
                    'site_visit',
                    $visitTitle,
                    $visitBody,
                    'crm/customer/site-visits-upcoming.php'
                );
            }

            $message = 'Site visit updated.';
            $isSuccess = true;
        } elseif ($action === 'lead-stage') {
            $stage = (string)($_POST['stage'] ?? 'new');

            if (!in_array(
                $stage,
                [
                    'new',
                    'qualified',
                    'proposal',
                    'negotiation',
                    'won',
                    'lost',
                ],
                true
            )) {
                $stage = 'new';
            }

            $followUp = trim(
                (string)($_POST['next_follow_up'] ?? '')
            );

            $statement = $pdo->prepare(
                'UPDATE crm_enquiries
                 SET stage = :stage,
                     next_follow_up = :next_follow_up
                 WHERE id = :id
                 AND business_user_id = :business_user_id
                 AND business_type = "venue"'
            );

            $statement->execute([
                'stage' => $stage,
                'next_follow_up' => $followUp !== ''
                    ? $followUp
                    : null,
                'id' => $enquiryId,
                'business_user_id' => $userId,
            ]);

            if ($stage === 'won') {
                wz_crm_create_booking_from_enquiry(
                    $enquiryId
                );
            }

            wz_audit(
                'crm.venue_lead.updated',
                'crm_enquiry',
                $enquiryId,
                [
                    'stage' => $stage,
                    'next_follow_up' => $followUp,
                ]
            );

            $message = $stage === 'won'
                ? 'Lead marked won and booking created.'
                : 'Lead updated.';

            $isSuccess = true;
        }
    }
}

$leads = wz_venue_leads(
    $userId,
    $leadFilter
);

$headings = [
    'all' => [
        'eyebrow' => 'LEADS',
        'title' => 'All Leads',
        'description' => 'Every venue enquiry assigned to your team.',
    ],
    'new' => [
        'eyebrow' => 'LEADS',
        'title' => 'New Leads',
        'description' => 'Fresh enquiries that still need first action.',
    ],
    'followups' => [
        'eyebrow' => 'LEADS',
        'title' => 'Follow-ups',
        'description' => 'Leads with a scheduled next follow-up.',
    ],
    'site-visits' => [
        'eyebrow' => 'LEADS',
        'title' => 'Site Visits',
        'description' => 'Scheduled venue visits and their current status.',
    ],
    'lost' => [
        'eyebrow' => 'LEADS',
        'title' => 'Lost Leads',
        'description' => 'Closed opportunities that did not convert.',
    ],
];

$heading = $headings[$leadFilter]
    ?? $headings['all'];

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            <?= h($heading['eyebrow']) ?>
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
            href="<?= h(wz_app_url('crm/venue/leads-new.php')) ?>"
        >
            New leads
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/leads-followups.php')) ?>"
        >
            Follow-ups
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/leads-site-visits.php')) ?>"
        >
            Site visits
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Showing</span>
        <strong><?= h((string)count($leads)) ?></strong>
        <small><?= h($heading['title']) ?></small>
    </article>

    <article class="crm-stat">
        <span>New</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $leads,
                        fn (array $lead): bool =>
                            $lead['stage'] === 'new'
                    )
                )
            ) ?>
        </strong>
        <small>Need first action</small>
    </article>

    <article class="crm-stat">
        <span>Site visits</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $leads,
                        fn (array $lead): bool =>
                            !empty($lead['site_visit_at'])
                            && $lead['site_visit_status'] !== 'cancelled'
                    )
                )
            ) ?>
        </strong>
        <small>Scheduled / completed</small>
    </article>

    <article class="crm-stat">
        <span>Lost</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $leads,
                        fn (array $lead): bool =>
                            $lead['stage'] === 'lost'
                    )
                )
            ) ?>
        </strong>
        <small>Closed without booking</small>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2><?= h($heading['title']) ?></h2>
            <p><?= h((string)count($leads)) ?> lead records</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Function</th>
                    <th>Lead stage</th>
                    <th>Follow-up</th>
                    <th>Site visit</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leads as $lead): ?>
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

                            <?php if (!empty($lead['customer_email'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$lead['customer_email']) ?>
                                </small>
                            <?php endif; ?>

                            <?php if (!empty($lead['customer_phone'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$lead['customer_phone']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($lead['event_type'] ?: 'Event')) ?>

                            <?php if (!empty($lead['event_date'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$lead['event_date']) ?>
                                </small>
                            <?php endif; ?>

                            <?php if (!empty($lead['city'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$lead['city']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="crm-badge <?= h((string)$lead['stage']) ?>">
                                <?= h(ucfirst((string)$lead['stage'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= h((string)($lead['next_follow_up'] ?: '—')) ?>
                        </td>

                        <td>
                            <?php if (!empty($lead['site_visit_at'])): ?>
                                <?= h((string)$lead['site_visit_at']) ?>
                                <br>
                                <span class="crm-badge">
                                    <?= h(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string)$lead['site_visit_status']
                                            )
                                        )
                                    ) ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td>
                            <a
                                class="crm-button secondary small"
                                href="<?= h(
                                    wz_app_url(
                                        'crm/venue/enquiry.php?id='
                                        . urlencode((string)$lead['id'])
                                    )
                                ) ?>"
                            >
                                Open
                            </a>

                            <details style="margin-top: 8px;">
                                <summary
                                    style="cursor: pointer; font-size: 9px;"
                                >
                                    Quick update
                                </summary>

                                <form
                                    method="post"
                                    class="crm-form"
                                    style="padding: 10px 0 0;"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= h(wz_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="lead-stage"
                                    >

                                    <input
                                        type="hidden"
                                        name="enquiry_id"
                                        value="<?= h((string)$lead['id']) ?>"
                                    >

                                    <div class="crm-field">
                                        <label>Stage</label>

                                        <select name="stage">
                                            <?php foreach (
                                                [
                                                    'new',
                                                    'qualified',
                                                    'proposal',
                                                    'negotiation',
                                                    'won',
                                                    'lost',
                                                ] as $stage
                                            ): ?>
                                                <option
                                                    value="<?= h($stage) ?>"
                                                    <?= $stage === $lead['stage'] ? 'selected' : '' ?>
                                                >
                                                    <?= h(ucfirst($stage)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="crm-field">
                                        <label>Next follow-up</label>

                                        <input
                                            type="datetime-local"
                                            name="next_follow_up"
                                            value="<?= !empty($lead['next_follow_up'])
                                                ? h(
                                                    date(
                                                        'Y-m-d\TH:i',
                                                        strtotime(
                                                            (string)$lead['next_follow_up']
                                                        )
                                                    )
                                                )
                                                : '' ?>"
                                        >
                                    </div>

                                    <button
                                        class="crm-button small"
                                        type="submit"
                                    >
                                        Save lead
                                    </button>
                                </form>

                                <form
                                    method="post"
                                    class="crm-form"
                                    style="padding: 10px 0 0;"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= h(wz_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="site-visit"
                                    >

                                    <input
                                        type="hidden"
                                        name="enquiry_id"
                                        value="<?= h((string)$lead['id']) ?>"
                                    >

                                    <div class="crm-field">
                                        <label>Site visit</label>

                                        <input
                                            type="datetime-local"
                                            name="site_visit_at"
                                            value="<?= !empty($lead['site_visit_at'])
                                                ? h(
                                                    date(
                                                        'Y-m-d\TH:i',
                                                        strtotime(
                                                            (string)$lead['site_visit_at']
                                                        )
                                                    )
                                                )
                                                : '' ?>"
                                        >
                                    </div>

                                    <div class="crm-field">
                                        <label>Visit status</label>

                                        <select name="site_visit_status">
                                            <?php foreach (
                                                [
                                                    'scheduled',
                                                    'completed',
                                                    'cancelled',
                                                ] as $visitStatus
                                            ): ?>
                                                <option
                                                    value="<?= h($visitStatus) ?>"
                                                    <?= $visitStatus === $lead['site_visit_status'] ? 'selected' : '' ?>
                                                >
                                                    <?= h(
                                                        ucwords(
                                                            str_replace(
                                                                '_',
                                                                ' ',
                                                                $visitStatus
                                                            )
                                                        )
                                                    ) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <button
                                        class="crm-button secondary small"
                                        type="submit"
                                    >
                                        Save visit
                                    </button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$leads): ?>
                    <tr>
                        <td colspan="6">
                            No leads found in this section.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

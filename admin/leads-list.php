<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$leadFilter = $leadFilter ?? 'all';
$adminPage = $adminPage ?? 'leads-all';
$adminTitle = $adminTitle ?? 'All Leads';

$pdo = wz_db();
$message = '';
$isSuccess = false;

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
        $stage = (string)($_POST['stage'] ?? 'new');
        $assignment = (string)($_POST['assignment'] ?? '');
        $followUp = trim(
            (string)($_POST['next_follow_up'] ?? '')
        );
        $siteVisitAt = trim(
            (string)($_POST['site_visit_at'] ?? '')
        );
        $siteVisitStatus = (string)(
            $_POST['site_visit_status']
            ?? 'not_scheduled'
        );

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
            $siteVisitStatus = 'not_scheduled';
        }

        $businessUserId = null;
        $businessType = 'vendor';

        if ($assignment !== '') {
            [$assignmentType, $assignmentUserId] = array_pad(
                explode(
                    ':',
                    $assignment,
                    2
                ),
                2,
                ''
            );

            if (in_array(
                $assignmentType,
                [
                    'vendor',
                    'venue',
                ],
                true
            )) {
                $businessType = $assignmentType;
                $businessUserId = (int)$assignmentUserId;
            }
        }

        if ($businessUserId === null) {
            $current = $pdo->prepare(
                'SELECT business_type
                 FROM crm_enquiries
                 WHERE id = :id
                 LIMIT 1'
            );

            $current->execute([
                'id' => $enquiryId,
            ]);

            $currentType = $current->fetchColumn();

            if (in_array(
                $currentType,
                [
                    'vendor',
                    'venue',
                ],
                true
            )) {
                $businessType = (string)$currentType;
            }
        }

        $statement = $pdo->prepare(
            'UPDATE crm_enquiries
             SET business_user_id = :business_user_id,
                 business_type = :business_type,
                 stage = :stage,
                 next_follow_up = :next_follow_up,
                 site_visit_at = :site_visit_at,
                 site_visit_status = :site_visit_status
             WHERE id = :id'
        );

        $statement->execute([
            'business_user_id' => $businessUserId,
            'business_type' => $businessType,
            'stage' => $stage,
            'next_follow_up' => $followUp !== ''
                ? $followUp
                : null,
            'site_visit_at' => $siteVisitAt !== ''
                ? $siteVisitAt
                : null,
            'site_visit_status' => $siteVisitAt !== ''
                ? $siteVisitStatus
                : 'not_scheduled',
            'id' => $enquiryId,
        ]);

        if ($stage === 'won') {
            wz_crm_create_booking_from_enquiry(
                $enquiryId
            );
        }

        wz_audit(
            'admin.lead.updated',
            'crm_enquiry',
            $enquiryId,
            [
                'stage' => $stage,
                'business_user_id' => $businessUserId,
                'business_type' => $businessType,
                'next_follow_up' => $followUp,
                'site_visit_at' => $siteVisitAt,
                'site_visit_status' => $siteVisitStatus,
            ]
        );

        $message = $stage === 'won'
            ? 'Lead updated and booking created.'
            : 'Lead updated.';

        $isSuccess = true;
    }
}

$leads = wz_admin_crm_leads(
    $leadFilter
);

$businesses = wz_admin_crm_businesses();

$headings = [
    'all' => [
        'title' => 'All Leads',
        'description' => 'Every CRM lead across venues and vendors.',
    ],
    'new' => [
        'title' => 'New Leads',
        'description' => 'Fresh leads still waiting for first action.',
    ],
    'followups' => [
        'title' => 'Follow-ups',
        'description' => 'Open opportunities with a scheduled next action.',
    ],
    'site-visits' => [
        'title' => 'Site Visits',
        'description' => 'Venue leads with scheduled or completed site visits.',
    ],
    'lost' => [
        'title' => 'Lost Leads',
        'description' => 'Opportunities closed without conversion.',
    ],
];

$heading = $headings[$leadFilter]
    ?? $headings['all'];

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>LEADS</small>
        <h1><?= h($heading['title']) ?></h1>
        <p class="admin-section-note">
            <?= h($heading['description']) ?>
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Showing</span>
        <strong><?= h((string)count($leads)) ?></strong>
    </article>

    <article class="admin-stat">
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
    </article>

    <article class="admin-stat">
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
    </article>

    <article class="admin-stat">
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
    </article>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2><?= h($heading['title']) ?></h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Event</th>
                    <th>Assigned business</th>
                    <th>Stage</th>
                    <th>Follow-up</th>
                    <th>Site visit</th>
                    <th>Value</th>
                    <th>Update</th>
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

                            <?php if (!empty($lead['city'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$lead['city']) ?>
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
                            <?= h(
                                (string)(
                                    $lead['next_follow_up']
                                    ?: '—'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?php if (!empty($lead['site_visit_at'])): ?>
                                <?= h((string)$lead['site_visit_at']) ?>

                                <br>

                                <small>
                                    <?= h(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string)$lead['site_visit_status']
                                            )
                                        )
                                    ) ?>
                                </small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= !empty($lead['value_estimate'])
                                ? '₹' . number_format(
                                    (float)$lead['value_estimate']
                                )
                                : '—' ?>
                        </td>

                        <td>
                            <details>
                                <summary
                                    style="cursor: pointer; font-size: 10px;"
                                >
                                    Edit
                                </summary>

                                <form
                                    method="post"
                                    class="admin-form"
                                    style="padding: 12px 0 0; min-width: 260px;"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= h(wz_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="enquiry_id"
                                        value="<?= h((string)$lead['id']) ?>"
                                    >

                                    <div class="admin-field">
                                        <label>Assign</label>

                                        <select name="assignment">
                                            <option value="">
                                                Unassigned
                                            </option>

                                            <?php foreach ($businesses as $business): ?>
                                                <?php
                                                $assignmentValue =
                                                    $business['business_type']
                                                    . ':'
                                                    . $business['user_id'];

                                                $selected =
                                                    (int)$business['user_id']
                                                    === (int)$lead['business_user_id']
                                                    && $business['business_type']
                                                    === $lead['business_type'];
                                                ?>

                                                <option
                                                    value="<?= h($assignmentValue) ?>"
                                                    <?= $selected ? 'selected' : '' ?>
                                                >
                                                    <?= h(
                                                        ucfirst(
                                                            (string)$business['business_type']
                                                        )
                                                        . ' · '
                                                        . (string)$business['name']
                                                    ) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="admin-field">
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

                                    <div class="admin-field">
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

                                    <div class="admin-field">
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

                                    <div class="admin-field">
                                        <label>Visit status</label>

                                        <select name="site_visit_status">
                                            <?php foreach (
                                                [
                                                    'not_scheduled',
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
                                        class="admin-button"
                                        type="submit"
                                    >
                                        Save lead
                                    </button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$leads): ?>
                    <tr>
                        <td colspan="8">
                            No leads found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

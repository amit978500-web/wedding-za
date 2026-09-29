<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'enquiries';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $crmRole !== 'host'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
        $enquiry = wz_crm_enquiry_for_user(
            $enquiryId,
            $userId,
            $crmRole
        );

        if (!$enquiry) {
            $message = 'Enquiry not found.';
        } else {
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

            $valueEstimate = trim(
                (string)($_POST['value_estimate'] ?? '')
            );

            $followUp = trim(
                (string)($_POST['next_follow_up'] ?? '')
            );

            $pdo = wz_db();

            $statement = $pdo->prepare(
                'UPDATE crm_enquiries
                 SET stage = :stage,
                     value_estimate = :value_estimate,
                     next_follow_up = :next_follow_up
                 WHERE id = :id
                 AND business_user_id = :business_user_id
                 AND business_type = :business_type'
            );

            $statement->execute([
                'stage' => $stage,
                'value_estimate' => $valueEstimate !== ''
                    ? (float)$valueEstimate
                    : null,
                'next_follow_up' => $followUp !== ''
                    ? $followUp
                    : null,
                'id' => $enquiryId,
                'business_user_id' => $userId,
                'business_type' => $crmRole,
            ]);

            if ($stage === 'won') {
                wz_crm_create_booking_from_enquiry(
                    $enquiryId
                );
            }

            wz_audit(
                'crm.enquiry.updated',
                'crm_enquiry',
                $enquiryId,
                [
                    'stage' => $stage,
                ]
            );

            $message = $stage === 'won'
                ? 'Enquiry updated and booking created.'
                : 'Enquiry updated.';

            $isSuccess = true;
        }
    }
}

$enquiries = wz_crm_enquiries_for_user(
    $userId,
    $crmRole
);

$basePath = match ($crmRole) {
    'host' => 'crm/customer',
    'venue' => 'crm/venue',
    default => 'crm/vendor',
};

$crmTitle = $crmRole === 'host'
    ? 'My Enquiries'
    : 'Sales Pipeline';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            <?= $crmRole === 'host' ? 'CUSTOMER CRM' : 'SALES CRM' ?>
        </span>

        <h1>
            <?= $crmRole === 'host'
                ? 'My enquiries'
                : 'Sales pipeline' ?>
        </h1>

        <p>
            <?= $crmRole === 'host'
                ? 'See every venue and vendor conversation in one place.'
                : 'Move every enquiry from first contact to confirmed work.' ?>
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<?php if ($crmRole !== 'host'): ?>
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Pipeline board</h2>
                <p>Current opportunities by stage.</p>
            </div>
        </div>

        <div class="crm-pipeline">
            <?php
            $stages = [
                'new',
                'qualified',
                'proposal',
                'negotiation',
                'won',
                'lost',
            ];
            ?>

            <?php foreach ($stages as $stage): ?>
                <div class="crm-pipeline-column">
                    <strong>
                        <?= h(ucfirst($stage)) ?>
                    </strong>

                    <?php
                    $stageItems = array_values(
                        array_filter(
                            $enquiries,
                            fn (array $item): bool =>
                                (string)$item['stage'] === $stage
                        )
                    );
                    ?>

                    <?php foreach ($stageItems as $item): ?>
                        <a
                            class="crm-pipeline-card"
                            href="<?= h(
                                wz_app_url(
                                    $basePath .
                                    '/enquiry.php?id=' .
                                    urlencode((string)$item['id'])
                                )
                            ) ?>"
                        >
                            <strong>
                                <?= h((string)($item['customer_name'] ?: 'Customer')) ?>
                            </strong>

                            <small>
                                <?= h((string)($item['event_type'] ?: 'Event')) ?>
                                <?php if (!empty($item['city'])): ?>
                                    · <?= h((string)$item['city']) ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>
                All enquiries
            </h2>

            <p>
                <?= h((string)count($enquiries)) ?> records
            </p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>
                        <?= $crmRole === 'host' ? 'Enquiry' : 'Customer' ?>
                    </th>

                    <th>Event</th>
                    <th>City</th>
                    <th>Stage</th>
                    <th>Follow-up</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($enquiries as $enquiry): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h(
                                    (string)(
                                        $crmRole === 'host'
                                            ? ($enquiry['subject'] ?: 'Event enquiry')
                                            : ($enquiry['customer_name'] ?: 'Customer')
                                    )
                                ) ?>
                            </strong>

                            <?php if ($crmRole !== 'host'): ?>
                                <br>
                                <small>
                                    <?= h((string)$enquiry['customer_email']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($enquiry['event_type'] ?: '—')) ?>

                            <?php if (!empty($enquiry['event_date'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$enquiry['event_date']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($enquiry['city'] ?: '—')) ?>
                        </td>

                        <td>
                            <span class="crm-badge <?= h((string)$enquiry['stage']) ?>">
                                <?= h(ucfirst((string)$enquiry['stage'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= h((string)($enquiry['next_follow_up'] ?: '—')) ?>
                        </td>

                        <td>
                            <a
                                class="crm-button secondary small"
                                href="<?= h(
                                    wz_app_url(
                                        $basePath .
                                        '/enquiry.php?id=' .
                                        urlencode((string)$enquiry['id'])
                                    )
                                ) ?>"
                            >
                                Open
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$enquiries): ?>
                    <tr>
                        <td colspan="6">
                            No enquiries yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

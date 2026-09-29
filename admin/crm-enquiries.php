<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/crm.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

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
        $assignment = (string)($_POST['assignment'] ?? '');
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

        $businessUserId = null;
        $businessType = (string)($_POST['business_type'] ?? 'vendor');

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
                ['vendor', 'venue'],
                true
            )) {
                $businessType = $assignmentType;
                $businessUserId = (int)$assignmentUserId;
            }
        }

        if (!in_array(
            $businessType,
            ['vendor', 'venue'],
            true
        )) {
            $businessType = 'vendor';
        }

        $statement = $pdo->prepare(
            'UPDATE crm_enquiries
             SET business_user_id = :business_user_id,
                 business_type = :business_type,
                 stage = :stage
             WHERE id = :id'
        );

        $statement->execute([
            'business_user_id' => $businessUserId,
            'business_type' => $businessType,
            'stage' => $stage,
            'id' => $enquiryId,
        ]);

        if ($stage === 'won') {
            wz_crm_create_booking_from_enquiry(
                $enquiryId
            );
        }

        wz_audit(
            'admin.crm_enquiry.updated',
            'crm_enquiry',
            $enquiryId,
            [
                'business_user_id' => $businessUserId,
                'business_type' => $businessType,
                'stage' => $stage,
            ]
        );

        $message = 'CRM enquiry updated.';
        $isSuccess = true;
    }
}

$businesses = [];

if ($pdo) {
    $vendors = $pdo
        ->query(
            "SELECT
                vp.user_id,
                vp.business_name AS name,
                'vendor' AS business_type
             FROM vendor_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE u.status = 'active'
             ORDER BY vp.business_name"
        )
        ->fetchAll();

    $venues = $pdo
        ->query(
            "SELECT
                vp.user_id,
                vp.venue_name AS name,
                'venue' AS business_type
             FROM venue_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             WHERE u.status = 'active'
             ORDER BY vp.venue_name"
        )
        ->fetchAll();

    $businesses = array_merge(
        $venues,
        $vendors
    );
}

$enquiries = [];

if ($pdo) {
    $enquiries = $pdo
        ->query(
            'SELECT
                ce.*,
                cu.name AS customer_name,
                cu.email AS customer_email,
                bu.name AS business_contact
             FROM crm_enquiries ce
             LEFT JOIN users cu
                ON cu.id = ce.customer_user_id
             LEFT JOIN users bu
                ON bu.id = ce.business_user_id
             ORDER BY
                ce.business_user_id IS NULL DESC,
                ce.updated_at DESC
             LIMIT 300'
        )
        ->fetchAll();
}

$adminPage = 'crm-enquiries';
$adminTitle = 'CRM Enquiries';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>CRM PIPELINE</small>
        <h1>Enquiries</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Event</th>
                    <th>Assigned business</th>
                    <th>Stage</th>
                    <th>Value</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($enquiries as $enquiry): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)($enquiry['customer_name'] ?: 'Guest lead')) ?>
                            </strong>

                            <br>

                            <small>
                                <?= h((string)$enquiry['customer_email']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)($enquiry['event_type'] ?: 'Event')) ?>

                            <?php if (!empty($enquiry['city'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$enquiry['city']) ?>
                                </small>
                            <?php endif; ?>

                            <?php if (!empty($enquiry['event_date'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$enquiry['event_date']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($enquiry['business_contact'] ?: 'Unassigned')) ?>
                            <br>
                            <small>
                                <?= h((string)$enquiry['business_type']) ?>
                            </small>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$enquiry['stage']) ?>">
                                <?= h((string)$enquiry['stage']) ?>
                            </span>
                        </td>

                        <td>
                            <?= !empty($enquiry['value_estimate'])
                                ? '₹' . number_format((float)$enquiry['value_estimate'])
                                : '—' ?>
                        </td>

                        <td>
                            <form
                                method="post"
                                class="admin-form"
                                style="padding: 0; min-width: 260px;"
                            >
                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= h(wz_csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="enquiry_id"
                                    value="<?= h((string)$enquiry['id']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="business_type"
                                    value="<?= h((string)$enquiry['business_type']) ?>"
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
                                                === (int)$enquiry['business_user_id']
                                                && $business['business_type']
                                                === $enquiry['business_type'];
                                            ?>

                                            <option
                                                value="<?= h($assignmentValue) ?>"
                                                <?= $selected ? 'selected' : '' ?>
                                            >
                                                <?= h(
                                                    ucfirst((string)$business['business_type'])
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
                                                <?= $stage === $enquiry['stage'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($stage)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button
                                    class="admin-button"
                                    type="submit"
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$enquiries): ?>
                    <tr>
                        <td colspan="6">
                            No CRM enquiries yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

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
        $venueId = (int)($_POST['venue_id'] ?? 0);
        $approval = (string)($_POST['approval_status'] ?? 'pending');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $plan = (string)($_POST['plan'] ?? 'free');
        $billingStatus = (string)($_POST['billing_status'] ?? 'inactive');

        if (!in_array(
            $approval,
            ['pending', 'approved', 'rejected'],
            true
        )) {
            $approval = 'pending';
        }

        if (!in_array(
            $plan,
            ['free', 'featured', 'pro'],
            true
        )) {
            $plan = 'free';
        }

        if (!in_array(
            $billingStatus,
            ['inactive', 'trial', 'active', 'past_due', 'cancelled'],
            true
        )) {
            $billingStatus = 'inactive';
        }

        $statement = $pdo->prepare(
            'UPDATE venue_profiles
             SET approval_status = :approval_status,
                 featured = :featured,
                 plan = :plan,
                 billing_status = :billing_status
             WHERE id = :id'
        );

        $statement->execute([
            'approval_status' => $approval,
            'featured' => $featured,
            'plan' => $plan,
            'billing_status' => $billingStatus,
            'id' => $venueId,
        ]);

        wz_audit(
            'admin.venue.updated',
            'venue_profile',
            $venueId,
            [
                'approval_status' => $approval,
                'featured' => $featured,
                'plan' => $plan,
                'billing_status' => $billingStatus,
            ]
        );

        $message = 'Venue profile updated.';
        $isSuccess = true;
    }
}

$venues = [];

if ($pdo) {
    $venues = $pdo
        ->query(
            'SELECT
                vp.*,
                u.name AS owner_name,
                u.email AS owner_email,
                u.status AS user_status
             FROM venue_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             ORDER BY
                vp.approval_status = "pending" DESC,
                vp.featured DESC,
                vp.updated_at DESC'
        )
        ->fetchAll();
}

$adminPage = 'venues';
$adminTitle = 'Venues';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>MARKETPLACE CRM</small>
        <h1>Venues</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Venue moderation</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>Owner</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Approval</th>
                    <th>Plan</th>
                    <th>Billing</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($venues as $venue): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$venue['venue_name']) ?>
                            </strong>

                            <br>

                            <small>
                                <?= h((string)$venue['venue_type']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$venue['owner_name']) ?>
                            <br>
                            <small>
                                <?= h((string)$venue['owner_email']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$venue['city']) ?>
                            <?php if (!empty($venue['locality'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$venue['locality']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($venue['capacity_min'] ?: '—')) ?>
                            –
                            <?= h((string)($venue['capacity_max'] ?: '—')) ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$venue['approval_status']) ?>">
                                <?= h((string)$venue['approval_status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= h((string)$venue['plan']) ?>
                        </td>

                        <td>
                            <?= h((string)$venue['billing_status']) ?>
                        </td>

                        <td>
                            <form
                                method="post"
                                class="admin-inline-form"
                            >
                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= h(wz_csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="venue_id"
                                    value="<?= h((string)$venue['id']) ?>"
                                >

                                <select name="approval_status">
                                    <?php foreach (['pending', 'approved', 'rejected'] as $status): ?>
                                        <option
                                            value="<?= h($status) ?>"
                                            <?= $status === $venue['approval_status'] ? 'selected' : '' ?>
                                        >
                                            <?= h(ucfirst($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="plan">
                                    <?php foreach (['free', 'featured', 'pro'] as $plan): ?>
                                        <option
                                            value="<?= h($plan) ?>"
                                            <?= $plan === $venue['plan'] ? 'selected' : '' ?>
                                        >
                                            <?= h(ucfirst($plan)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="billing_status">
                                    <?php foreach (['inactive', 'trial', 'active', 'past_due', 'cancelled'] as $billingStatus): ?>
                                        <option
                                            value="<?= h($billingStatus) ?>"
                                            <?= $billingStatus === $venue['billing_status'] ? 'selected' : '' ?>
                                        >
                                            <?= h(
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $billingStatus
                                                    )
                                                )
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label>
                                    <input
                                        type="checkbox"
                                        name="featured"
                                        value="1"
                                        <?= !empty($venue['featured']) ? 'checked' : '' ?>
                                    >
                                    Featured
                                </label>

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

                <?php if (!$venues): ?>
                    <tr>
                        <td colspan="8">
                            No venue profiles yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

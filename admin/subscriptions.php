<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-subscriptions';
$adminTitle = 'Business Plans';
$message = '';
$isSuccess = false;
$pdo = wz_db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $role = (string)($_POST['business_type'] ?? '');
        $userId = (int)($_POST['user_id'] ?? 0);
        $plan = (string)($_POST['plan'] ?? 'free');
        $billingStatus = (string)($_POST['billing_status'] ?? 'inactive');
        $featured = !empty($_POST['featured']) ? 1 : 0;

        if (
            in_array($role, ['vendor','venue'], true)
            && in_array($plan, ['free','featured','pro'], true)
            && in_array($billingStatus, ['inactive','trial','active','past_due','cancelled'], true)
        ) {
            $table = $role === 'venue'
                ? 'venue_profiles'
                : 'vendor_profiles';

            $statement = $pdo->prepare(
                'UPDATE '.$table.'
                 SET plan = :plan,
                     billing_status = :billing_status,
                     featured = :featured,
                     subscription_started_at = CASE
                        WHEN :billing_active IN ("trial","active")
                        THEN COALESCE(subscription_started_at, NOW())
                        ELSE subscription_started_at
                     END
                 WHERE user_id = :user_id'
            );

            $statement->execute([
                'plan' => $plan,
                'billing_status' => $billingStatus,
                'featured' => $featured,
                'billing_active' => $billingStatus,
                'user_id' => $userId,
            ]);

            $message = 'Business plan updated.';
            $isSuccess = true;

            wz_audit(
                'marketplace.subscription.updated',
                $role.'_profile',
                $userId,
                [
                    'plan' => $plan,
                    'billing_status' => $billingStatus,
                    'featured' => $featured,
                ]
            );
        }
    }
}

$rows = [];

if ($pdo) {
    $rows = $pdo->query(
        'SELECT
            "venue" AS business_type,
            vp.user_id,
            vp.venue_name AS business_name,
            vp.plan,
            vp.billing_status,
            vp.featured,
            vp.approval_status,
            u.email
         FROM venue_profiles vp
         INNER JOIN users u ON u.id = vp.user_id

         UNION ALL

         SELECT
            "vendor" AS business_type,
            vp.user_id,
            vp.business_name,
            vp.plan,
            vp.billing_status,
            vp.featured,
            vp.approval_status,
            u.email
         FROM vendor_profiles vp
         INNER JOIN users u ON u.id = vp.user_id

         ORDER BY business_name'
    )->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>MARKETPLACE REVENUE</small>
        <h1>Business plans</h1>
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
                    <th>Business</th>
                    <th>Type</th>
                    <th>Approval</th>
                    <th>Plan</th>
                    <th>Billing</th>
                    <th>Featured</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$row['business_name']) ?></strong>
                            <br><small><?= h((string)$row['email']) ?></small>
                        </td>
                        <td><?= h(ucfirst((string)$row['business_type'])) ?></td>
                        <td><?= h(ucfirst((string)$row['approval_status'])) ?></td>
                        <td><?= h(ucfirst((string)$row['plan'])) ?></td>
                        <td><?= h(ucfirst((string)$row['billing_status'])) ?></td>
                        <td><?= !empty($row['featured'])?'Yes':'No' ?></td>
                        <td>
                            <form method="post" class="admin-inline-form">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="business_type" value="<?= h((string)$row['business_type']) ?>">
                                <input type="hidden" name="user_id" value="<?= h((string)$row['user_id']) ?>">

                                <select name="plan">
                                    <?php foreach (['free','featured','pro'] as $plan): ?>
                                        <option value="<?= h($plan) ?>" <?= $plan===$row['plan']?'selected':'' ?>>
                                            <?= h(ucfirst($plan)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="billing_status">
                                    <?php foreach (['inactive','trial','active','past_due','cancelled'] as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $status===$row['billing_status']?'selected':'' ?>>
                                            <?= h(ucfirst(str_replace('_',' ',$status))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label>
                                    <input type="checkbox" name="featured" value="1" <?= !empty($row['featured'])?'checked':'' ?>>
                                    Featured
                                </label>

                                <button class="admin-button" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="7">No businesses yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-venue-plans';
$adminTitle = 'Venue Assistance Plans';
$message = '';
$isSuccess = false;
$pdo = wz_db();
$adminUser = wz_user() ?? [];

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'pending');
        $assignedAdmin = (int)($_POST['assigned_admin_user_id'] ?? 0);

        if (!in_array(
            $status,
            ['pending','paid','active','completed','cancelled','refunded'],
            true
        )) {
            $status = 'pending';
        }

        $statement = $pdo->prepare(
            'UPDATE venue_plan_purchases
             SET status = :status,
                 assigned_admin_user_id = :assigned_admin_user_id,
                 paid_at = CASE
                    WHEN :status_paid IN ("paid","active","completed")
                    THEN COALESCE(paid_at, NOW())
                    ELSE paid_at
                 END,
                 activated_at = CASE
                    WHEN :status_active IN ("active","completed")
                    THEN COALESCE(activated_at, NOW())
                    ELSE activated_at
                 END,
                 completed_at = CASE
                    WHEN :status_completed = "completed"
                    THEN COALESCE(completed_at, NOW())
                    ELSE completed_at
                 END
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'assigned_admin_user_id' => $assignedAdmin > 0 ? $assignedAdmin : null,
            'status_paid' => $status,
            'status_active' => $status,
            'status_completed' => $status,
            'id' => $id,
        ]);

        $customer = $pdo->prepare(
            'SELECT customer_user_id
             FROM venue_plan_purchases
             WHERE id = :id'
        );

        $customer->execute([
            'id' => $id,
        ]);

        $customerId = (int)$customer->fetchColumn();

        if ($customerId > 0) {
            wz_marketplace_notify(
                $customerId,
                'venue_plan',
                'Venue assistance plan updated',
                'Your One Wedding venue assistance plan is now '.$status.'.',
                'crm/customer/find-venues.php'
            );
        }

        wz_audit(
            'marketplace.venue_plan.updated',
            'venue_plan_purchase',
            $id,
            [
                'status' => $status,
                'assigned_admin_user_id' => $assignedAdmin,
            ]
        );

        $message = 'Venue assistance plan updated.';
        $isSuccess = true;
    }
}

$admins = [];
$rows = [];

if ($pdo) {
    $admins = $pdo->query(
        'SELECT id, name, email
         FROM users
         WHERE role = "admin"
         AND status = "active"
         ORDER BY name'
    )->fetchAll();

    $rows = $pdo->query(
        'SELECT
            vpp.*,
            customer.name AS customer_name,
            customer.email AS customer_email,
            admin.name AS assigned_admin_name
         FROM venue_plan_purchases vpp
         INNER JOIN users customer
            ON customer.id = vpp.customer_user_id
         LEFT JOIN users admin
            ON admin.id = vpp.assigned_admin_user_id
         ORDER BY vpp.created_at DESC'
    )->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>WEDDINGZA VENUE ASSIST</small>
        <h1>Venue assistance plans</h1>
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
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Assigned support</th>
                    <th>Status</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$row['customer_name']) ?></strong>
                            <br>
                            <small><?= h((string)$row['customer_email']) ?></small>
                        </td>
                        <td>One Wedding</td>
                        <td>₹<?= h(number_format((float)$row['amount'])) ?></td>
                        <td>
                            <?= h((string)($row['payment_provider'] ?: 'Not recorded')) ?>
                            <?php if (!empty($row['provider_payment_id'])): ?>
                                <br><small><?= h((string)$row['provider_payment_id']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h((string)($row['assigned_admin_name'] ?: 'Unassigned')) ?></td>
                        <td>
                            <span class="admin-badge <?= h((string)$row['status']) ?>">
                                <?= h(ucfirst((string)$row['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" class="admin-inline-form" style="min-width:360px;">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">

                                <select name="assigned_admin_user_id">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option
                                            value="<?= h((string)$admin['id']) ?>"
                                            <?= (int)$row['assigned_admin_user_id']===(int)$admin['id']?'selected':'' ?>
                                        >
                                            <?= h((string)$admin['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="status">
                                    <?php foreach (['pending','paid','active','completed','cancelled','refunded'] as $status): ?>
                                        <option
                                            value="<?= h($status) ?>"
                                            <?= $status===$row['status']?'selected':'' ?>
                                        >
                                            <?= h(ucfirst($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button class="admin-button" type="submit">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="7">No venue assistance purchases yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

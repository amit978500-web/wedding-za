<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$vendorFilter = $vendorFilter ?? 'all';
$adminPage = $adminPage ?? 'vendors-all';
$adminTitle = $adminTitle ?? 'All Vendors';

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
        $vendorId = (int)($_POST['vendor_id'] ?? 0);
        $approval = (string)($_POST['approval_status'] ?? 'pending');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $plan = (string)($_POST['plan'] ?? 'free');
        $billingStatus = (string)($_POST['billing_status'] ?? 'inactive');

        if (!in_array(
            $approval,
            [
                'pending',
                'approved',
                'rejected',
            ],
            true
        )) {
            $approval = 'pending';
        }

        if (!in_array(
            $plan,
            [
                'free',
                'featured',
                'pro',
            ],
            true
        )) {
            $plan = 'free';
        }

        if (!in_array(
            $billingStatus,
            [
                'inactive',
                'trial',
                'active',
                'past_due',
                'cancelled',
            ],
            true
        )) {
            $billingStatus = 'inactive';
        }

        $statement = $pdo->prepare(
            'UPDATE vendor_profiles
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
            'id' => $vendorId,
        ]);

        wz_audit(
            'admin.vendor.updated',
            'vendor_profile',
            $vendorId,
            [
                'approval_status' => $approval,
                'featured' => $featured,
                'plan' => $plan,
                'billing_status' => $billingStatus,
            ]
        );

        $message = 'Vendor profile updated.';
        $isSuccess = true;
    }
}

$vendors = [];

if ($pdo) {
    $sql = 'SELECT
                vp.*,
                u.name AS owner_name,
                u.email AS owner_email,
                u.status AS user_status
            FROM vendor_profiles vp
            INNER JOIN users u
                ON u.id = vp.user_id';

    if ($vendorFilter === 'active') {
        $sql .= ' WHERE
                    vp.approval_status = "approved"
                    AND u.status = "active"';
    }

    $sql .= ' ORDER BY
                vp.approval_status = "pending" DESC,
                vp.featured DESC,
                vp.updated_at DESC';

    $vendors = $pdo
        ->query($sql)
        ->fetchAll();
}

$heading = $vendorFilter === 'active'
    ? 'Active Vendors'
    : 'All Vendors';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>VENDORS</small>
        <h1><?= h($heading) ?></h1>

        <p class="admin-section-note">
            Active means the vendor is approved and its owner account is active.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2><?= h($heading) ?></h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>City</th>
                    <th>Account</th>
                    <th>Approval</th>
                    <th>Plan</th>
                    <th>Billing</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($vendors as $vendor): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$vendor['business_name']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h((string)$vendor['owner_name']) ?>

                            <br>

                            <small>
                                <?= h((string)$vendor['owner_email']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$vendor['category']) ?>
                        </td>

                        <td>
                            <?= h((string)$vendor['city']) ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$vendor['user_status']) ?>">
                                <?= h((string)$vendor['user_status']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$vendor['approval_status']) ?>">
                                <?= h((string)$vendor['approval_status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= h((string)$vendor['plan']) ?>
                        </td>

                        <td>
                            <?= h((string)$vendor['billing_status']) ?>
                        </td>

                        <td>
                            <form
                                method="post"
                                class="admin-form"
                                style="padding: 0; min-width: 250px;"
                            >
                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= h(wz_csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="vendor_id"
                                    value="<?= h((string)$vendor['id']) ?>"
                                >

                                <div class="admin-inline-form">
                                    <select name="approval_status">
                                        <?php foreach (
                                            [
                                                'pending',
                                                'approved',
                                                'rejected',
                                            ] as $status
                                        ): ?>
                                            <option
                                                value="<?= h($status) ?>"
                                                <?= $status === $vendor['approval_status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select name="plan">
                                        <?php foreach (
                                            [
                                                'free',
                                                'featured',
                                                'pro',
                                            ] as $plan
                                        ): ?>
                                            <option
                                                value="<?= h($plan) ?>"
                                                <?= $plan === $vendor['plan'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($plan)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-inline-form">
                                    <select name="billing_status">
                                        <?php foreach (
                                            [
                                                'inactive',
                                                'trial',
                                                'active',
                                                'past_due',
                                                'cancelled',
                                            ] as $billingStatus
                                        ): ?>
                                            <option
                                                value="<?= h($billingStatus) ?>"
                                                <?= $billingStatus === $vendor['billing_status'] ? 'selected' : '' ?>
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
                                            <?= !empty($vendor['featured']) ? 'checked' : '' ?>
                                        >
                                        Featured
                                    </label>
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

                <?php if (!$vendors): ?>
                    <tr>
                        <td colspan="9">
                            No vendors found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

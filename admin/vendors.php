<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$message = '';

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

        if (!in_array(
            $approval,
            ['pending', 'approved', 'rejected'],
            true
        )) {
            $approval = 'pending';
        }

        $statement = $pdo->prepare(
            'UPDATE vendor_profiles
             SET approval_status = :approval_status,
                 featured = :featured
             WHERE id = :id'
        );

        $statement->execute([
            'approval_status' => $approval,
            'featured' => $featured,
            'id' => $vendorId,
        ]);

        wz_audit(
            'admin.vendor.updated',
            'vendor_profile',
            $vendorId,
            [
                'approval_status' => $approval,
                'featured' => $featured,
            ]
        );

        $message = 'Vendor profile updated.';
    }
}

$vendors = [];

if ($pdo) {
    $vendors = $pdo
        ->query(
            'SELECT
                vp.*,
                u.name AS owner_name,
                u.email AS owner_email,
                u.status AS user_status
             FROM vendor_profiles vp
             INNER JOIN users u
                ON u.id = vp.user_id
             ORDER BY
                vp.approval_status = "pending" DESC,
                vp.updated_at DESC'
        )
        ->fetchAll();
}

$adminPage = 'vendors';
$adminTitle = 'Vendors';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>MARKETPLACE</small>
        <h1>Vendors</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice success">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Vendor moderation</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>City</th>
                    <th>Price</th>
                    <th>Approval</th>
                    <th>Featured</th>
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

                        <td><?= h((string)$vendor['category']) ?></td>
                        <td><?= h((string)$vendor['city']) ?></td>
                        <td><?= h((string)$vendor['starting_price']) ?></td>

                        <td>
                            <span class="admin-badge <?= h((string)$vendor['approval_status']) ?>">
                                <?= h((string)$vendor['approval_status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= !empty($vendor['featured']) ? 'Yes' : 'No' ?>
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
                                    name="vendor_id"
                                    value="<?= h((string)$vendor['id']) ?>"
                                >

                                <select name="approval_status">
                                    <?php foreach (['pending', 'approved', 'rejected'] as $status): ?>
                                        <option
                                            value="<?= h($status) ?>"
                                            <?= $status === $vendor['approval_status'] ? 'selected' : '' ?>
                                        >
                                            <?= h(ucfirst($status)) ?>
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
                        <td colspan="8">
                            No vendor profiles yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

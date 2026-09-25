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
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'active');

        if (!in_array(
            $status,
            ['active', 'pending', 'suspended'],
            true
        )) {
            $status = 'active';
        }

        if ($userId === (int)(wz_user()['id'] ?? 0)) {
            $message = 'You cannot suspend your own admin account here.';
        } else {
            $statement = $pdo->prepare(
                'UPDATE users
                 SET status = :status
                 WHERE id = :id'
            );

            $statement->execute([
                'status' => $status,
                'id' => $userId,
            ]);

            wz_audit(
                'admin.user.status_updated',
                'user',
                $userId,
                [
                    'status' => $status,
                ]
            );

            $message = 'User updated.';
        }
    }
}

$users = [];

if ($pdo) {
    $users = $pdo
        ->query(
            'SELECT id, name, email, role, status, created_at, updated_at
             FROM users
             ORDER BY created_at DESC
             LIMIT 250'
        )
        ->fetchAll();
}

$adminPage = 'users';
$adminTitle = 'Users';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>ACCOUNTS</small>
        <h1>Users</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= str_contains($message, 'cannot') ? '' : 'success' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?= h((string)$item['name']) ?></td>
                        <td><?= h((string)$item['email']) ?></td>
                        <td><?= h((string)$item['role']) ?></td>
                        <td>
                            <span class="admin-badge <?= h((string)$item['status']) ?>">
                                <?= h((string)$item['status']) ?>
                            </span>
                        </td>
                        <td><?= h((string)$item['created_at']) ?></td>
                        <td>
                            <?php if ((int)$item['id'] !== (int)(wz_user()['id'] ?? 0)): ?>
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
                                        name="user_id"
                                        value="<?= h((string)$item['id']) ?>"
                                    >

                                    <select name="status">
                                        <?php foreach (['active', 'pending', 'suspended'] as $status): ?>
                                            <option
                                                value="<?= h($status) ?>"
                                                <?= $status === $item['status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button
                                        class="admin-button"
                                        type="submit"
                                    >
                                        Save
                                    </button>
                                </form>
                            <?php else: ?>
                                Current admin
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$users): ?>
                    <tr>
                        <td colspan="6">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

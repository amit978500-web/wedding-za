<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$currentUser = wz_user() ?? [];
$message = '';
$isSuccess = false;

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $jobTitle = trim(
            (string)($_POST['job_title'] ?? '')
        );
        $phone = trim(
            (string)($_POST['phone'] ?? '')
        );
        $status = (string)($_POST['status'] ?? 'active');

        if (!in_array(
            $status,
            [
                'active',
                'suspended',
            ],
            true
        )) {
            $status = 'active';
        }

        if (
            $userId === (int)($currentUser['id'] ?? 0)
            && $status !== 'active'
        ) {
            $message = 'You cannot suspend your own admin account.';
        } else {
            $adminCheck = $pdo->prepare(
                'SELECT id
                 FROM users
                 WHERE id = :id
                 AND role = "admin"
                 LIMIT 1'
            );

            $adminCheck->execute([
                'id' => $userId,
            ]);

            if (!$adminCheck->fetchColumn()) {
                $message = 'Admin team member not found.';
            } else {
                $pdo->prepare(
                    'UPDATE users
                     SET status = :status
                     WHERE id = :id
                     AND role = "admin"'
                )->execute([
                    'status' => $status,
                    'id' => $userId,
                ]);

                $pdo->prepare(
                    'INSERT INTO admin_team_profiles (
                        user_id,
                        job_title,
                        phone
                    ) VALUES (
                        :user_id,
                        :job_title,
                        :phone
                    )
                    ON DUPLICATE KEY UPDATE
                        job_title = VALUES(job_title),
                        phone = VALUES(phone)'
                )->execute([
                    'user_id' => $userId,
                    'job_title' => $jobTitle,
                    'phone' => $phone,
                ]);

                wz_audit(
                    'admin.team_member.updated',
                    'user',
                    $userId,
                    [
                        'job_title' => $jobTitle,
                        'status' => $status,
                    ]
                );

                $message = 'Team member updated.';
                $isSuccess = true;
            }
        }
    }
}

$teamMembers = [];

if ($pdo) {
    $teamMembers = $pdo
        ->query(
            'SELECT
                u.id,
                u.name,
                u.email,
                u.status,
                u.created_at,
                atp.job_title,
                atp.phone
             FROM users u
             LEFT JOIN admin_team_profiles atp
                ON atp.user_id = u.id
             WHERE u.role = "admin"
             ORDER BY
                u.status = "active" DESC,
                u.name ASC'
        )
        ->fetchAll();
}

$adminPage = 'team';
$adminTitle = 'Team';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>ADMIN ACCESS</small>
        <h1>Team</h1>

        <p class="admin-section-note">
            Manage existing Admin CRM team accounts and operational profile details.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="admin-notice">
    New Admin accounts are created through the secure
    <code>scripts/create-admin.php</code>
    command. This page manages existing team members.
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Admin team</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Job title</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($teamMembers as $member): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$member['name']) ?>
                            </strong>

                            <?php if (
                                (int)$member['id']
                                === (int)($currentUser['id'] ?? 0)
                            ): ?>
                                <br>
                                <small>Current account</small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)$member['email']) ?>
                        </td>

                        <td>
                            <?= h(
                                (string)(
                                    $member['job_title']
                                    ?: '—'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= h(
                                (string)(
                                    $member['phone']
                                    ?: '—'
                                )
                            ) ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$member['status']) ?>">
                                <?= h(ucfirst((string)$member['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= h((string)$member['created_at']) ?>
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
                                    style="padding: 12px 0 0; min-width: 240px;"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= h(wz_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= h((string)$member['id']) ?>"
                                    >

                                    <div class="admin-field">
                                        <label>Job title</label>

                                        <input
                                            name="job_title"
                                            value="<?= h((string)$member['job_title']) ?>"
                                        >
                                    </div>

                                    <div class="admin-field">
                                        <label>Phone</label>

                                        <input
                                            name="phone"
                                            value="<?= h((string)$member['phone']) ?>"
                                        >
                                    </div>

                                    <div class="admin-field">
                                        <label>Status</label>

                                        <select name="status">
                                            <option
                                                value="active"
                                                <?= $member['status'] === 'active' ? 'selected' : '' ?>
                                            >
                                                Active
                                            </option>

                                            <option
                                                value="suspended"
                                                <?= $member['status'] === 'suspended' ? 'selected' : '' ?>
                                            >
                                                Suspended
                                            </option>
                                        </select>
                                    </div>

                                    <button
                                        class="admin-button"
                                        type="submit"
                                    >
                                        Save
                                    </button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$teamMembers): ?>
                    <tr>
                        <td colspan="7">
                            No Admin team accounts found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

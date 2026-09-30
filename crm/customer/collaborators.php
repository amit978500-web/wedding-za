<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'host';
$crmPage = 'planning-collaborators';
$crmTitle = 'Wedding Collaborators';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$message = '';
$isSuccess = false;

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'add');

        if ($action === 'remove') {
            $id = (int)($_POST['id'] ?? 0);

            $statement = $pdo->prepare(
                'UPDATE wedding_collaborators
                 SET status = "removed"
                 WHERE id = :id
                 AND owner_user_id = :owner_user_id'
            );

            $statement->execute([
                'id' => $id,
                'owner_user_id' => $userId,
            ]);

            $message = 'Collaborator removed.';
            $isSuccess = true;
        } else {
            $email = strtolower(
                trim((string)($_POST['email'] ?? ''))
            );

            $name = trim((string)($_POST['name'] ?? ''));
            $relation = trim((string)($_POST['relation_label'] ?? ''));
            $permission = (string)($_POST['permission'] ?? 'view');

            if (!in_array($permission, ['view', 'edit'], true)) {
                $permission = 'view';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Enter a valid collaborator email.';
            } else {
                $token = bin2hex(random_bytes(24));

                $statement = $pdo->prepare(
                    'INSERT INTO wedding_collaborators (
                        owner_user_id,
                        email,
                        name,
                        relation_label,
                        permission,
                        invite_token,
                        status
                    ) VALUES (
                        :owner_user_id,
                        :email,
                        :name,
                        :relation_label,
                        :permission,
                        :invite_token,
                        "invited"
                    )
                    ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        relation_label = VALUES(relation_label),
                        permission = VALUES(permission),
                        invite_token = VALUES(invite_token),
                        collaborator_user_id = NULL,
                        accepted_at = NULL,
                        status = "invited"'
                );

                $statement->execute([
                    'owner_user_id' => $userId,
                    'email' => $email,
                    'name' => $name !== '' ? $name : null,
                    'relation_label' => $relation !== '' ? $relation : null,
                    'permission' => $permission,
                    'invite_token' => $token,
                ]);

                $message = 'Collaborator invitation created.';
                $isSuccess = true;
            }
        }
    }
}

$collaborators = [];

if ($pdo) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM wedding_collaborators
         WHERE owner_user_id = :owner_user_id
         AND status <> "removed"
         ORDER BY created_at DESC'
    );

    $statement->execute([
        'owner_user_id' => $userId,
    ]);

    $collaborators = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">SHARED PLANNING</span>
        <h1>Collaborators</h1>
        <p>
            Invite your partner, family or planner to follow the same wedding brief instead of planning in separate chats.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Wedding collaborators</h2>
                <p><?= h((string)count($collaborators)) ?> active invitation(s)</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Relation</th>
                        <th>Permission</th>
                        <th>Status</th>
                        <th>Invite link</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collaborators as $item): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= h((string)($item['name'] ?: $item['email'])) ?>
                                </strong>
                                <br>
                                <small><?= h((string)$item['email']) ?></small>
                            </td>
                            <td><?= h((string)($item['relation_label'] ?: '—')) ?></td>
                            <td><?= h(ucfirst((string)$item['permission'])) ?></td>
                            <td>
                                <span class="crm-badge <?= h((string)$item['status']) ?>">
                                    <?= h(ucfirst((string)$item['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <input
                                    value="<?= h(
                                        wz_app_url(
                                            'collaborate.php?token=' .
                                            urlencode((string)$item['invite_token'])
                                        )
                                    ) ?>"
                                    readonly
                                    style="min-width:280px;"
                                >
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?= h((string)$item['id']) ?>">
                                    <button class="crm-button secondary small" type="submit">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$collaborators): ?>
                        <tr>
                            <td colspan="6">No collaborators invited yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Invite someone</h2>
                </div>
            </div>

            <form method="post" class="crm-form">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                <input type="hidden" name="action" value="add">

                <div class="crm-field">
                    <label for="collabName">Name</label>
                    <input id="collabName" name="name">
                </div>

                <div class="crm-field">
                    <label for="collabEmail">Email</label>
                    <input id="collabEmail" type="email" name="email" required>
                </div>

                <div class="crm-field">
                    <label for="collabRelation">Relation</label>
                    <input id="collabRelation" name="relation_label" placeholder="Partner, sister, planner...">
                </div>

                <div class="crm-field">
                    <label for="collabPermission">Permission</label>
                    <select id="collabPermission" name="permission">
                        <option value="view">View</option>
                        <option value="edit">Edit</option>
                    </select>
                </div>

                <button class="crm-button" type="submit">
                    Create invite
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

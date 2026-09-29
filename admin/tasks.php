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
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'create') {
            $ownerUserId = (int)($_POST['owner_user_id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));

            if (
                $ownerUserId > 0
                && $title !== ''
            ) {
                $priority = (string)($_POST['priority'] ?? 'normal');

                if (!in_array(
                    $priority,
                    ['low', 'normal', 'high'],
                    true
                )) {
                    $priority = 'normal';
                }

                $statement = $pdo->prepare(
                    'INSERT INTO crm_tasks (
                        owner_user_id,
                        created_by,
                        title,
                        description,
                        due_at,
                        priority
                    ) VALUES (
                        :owner_user_id,
                        :created_by,
                        :title,
                        :description,
                        :due_at,
                        :priority
                    )'
                );

                $statement->execute([
                    'owner_user_id' => $ownerUserId,
                    'created_by' => wz_user()['id'] ?? null,
                    'title' => $title,
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'due_at' => !empty($_POST['due_at'])
                        ? (string)$_POST['due_at']
                        : null,
                    'priority' => $priority,
                ]);

                $message = 'CRM task assigned.';
                $isSuccess = true;
            }
        }

        if ($action === 'toggle') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $status = (string)($_POST['status'] ?? 'open');

            if (!in_array(
                $status,
                ['open', 'done'],
                true
            )) {
                $status = 'open';
            }

            $statement = $pdo->prepare(
                'UPDATE crm_tasks
                 SET status = :status
                 WHERE id = :id'
            );

            $statement->execute([
                'status' => $status,
                'id' => $taskId,
            ]);

            $message = 'Task updated.';
            $isSuccess = true;
        }
    }
}

$users = [];

if ($pdo) {
    $users = $pdo
        ->query(
            "SELECT id, name, email, role
             FROM users
             WHERE status = 'active'
             ORDER BY role, name"
        )
        ->fetchAll();
}

$tasks = [];

if ($pdo) {
    $tasks = $pdo
        ->query(
            'SELECT
                ct.*,
                ou.name AS owner_name,
                ou.role AS owner_role,
                cu.name AS creator_name
             FROM crm_tasks ct
             INNER JOIN users ou
                ON ou.id = ct.owner_user_id
             LEFT JOIN users cu
                ON cu.id = ct.created_by
             ORDER BY
                ct.status = "done",
                ct.due_at IS NULL,
                ct.due_at ASC,
                ct.created_at DESC
             LIMIT 300'
        )
        ->fetchAll();
}

$adminPage = 'tasks';
$adminTitle = 'CRM Tasks';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>FOLLOW-UP</small>
        <h1>Tasks</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Assign task</h2>
    </div>

    <form
        method="post"
        class="admin-form"
    >
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="create"
        >

        <div class="admin-form-grid">
            <div class="admin-field">
                <label for="taskOwner">
                    Assign to
                </label>

                <select
                    id="taskOwner"
                    name="owner_user_id"
                    required
                >
                    <option value="">
                        Choose user
                    </option>

                    <?php foreach ($users as $user): ?>
                        <option value="<?= h((string)$user['id']) ?>">
                            <?= h(
                                ucfirst((string)$user['role'])
                                . ' · '
                                . (string)$user['name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field">
                <label for="taskTitle">
                    Task
                </label>

                <input
                    id="taskTitle"
                    name="title"
                    required
                >
            </div>

            <div class="admin-field">
                <label for="taskDue">
                    Due
                </label>

                <input
                    id="taskDue"
                    type="datetime-local"
                    name="due_at"
                >
            </div>

            <div class="admin-field">
                <label for="taskPriority">
                    Priority
                </label>

                <select
                    id="taskPriority"
                    name="priority"
                >
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                    <option value="low">Low</option>
                </select>
            </div>

            <div class="admin-field full">
                <label for="taskDescription">
                    Description
                </label>

                <textarea
                    id="taskDescription"
                    name="description"
                ></textarea>
            </div>
        </div>

        <button
            class="admin-button"
            type="submit"
        >
            Assign task
        </button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>All CRM tasks</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Owner</th>
                    <th>Due</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$task['title']) ?>
                            </strong>

                            <?php if (!empty($task['description'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$task['description']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)$task['owner_name']) ?>
                            <br>
                            <small>
                                <?= h((string)$task['owner_role']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)($task['due_at'] ?: '—')) ?>
                        </td>

                        <td>
                            <?= h((string)$task['priority']) ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$task['status']) ?>">
                                <?= h((string)$task['status']) ?>
                            </span>
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
                                    name="action"
                                    value="toggle"
                                >

                                <input
                                    type="hidden"
                                    name="task_id"
                                    value="<?= h((string)$task['id']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="status"
                                    value="<?= $task['status'] === 'done' ? 'open' : 'done' ?>"
                                >

                                <button
                                    class="admin-button secondary"
                                    type="submit"
                                >
                                    <?= $task['status'] === 'done'
                                        ? 'Reopen'
                                        : 'Done' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$tasks): ?>
                    <tr>
                        <td colspan="6">
                            No CRM tasks yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

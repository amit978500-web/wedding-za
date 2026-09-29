<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'tasks';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $pdo = wz_db();

        if ($action === 'create') {
            $title = trim(
                (string)($_POST['title'] ?? '')
            );

            if ($title !== '') {
                $priority = (string)($_POST['priority'] ?? 'normal');

                if (!in_array(
                    $priority,
                    [
                        'low',
                        'normal',
                        'high',
                    ],
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
                    'owner_user_id' => $userId,
                    'created_by' => $userId,
                    'title' => $title,
                    'description' => trim(
                        (string)($_POST['description'] ?? '')
                    ),
                    'due_at' => !empty($_POST['due_at'])
                        ? (string)$_POST['due_at']
                        : null,
                    'priority' => $priority,
                ]);

                $message = 'Task created.';
                $isSuccess = true;
            }
        }

        if ($action === 'toggle') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $status = (string)($_POST['status'] ?? 'open');

            if (!in_array(
                $status,
                [
                    'open',
                    'done',
                ],
                true
            )) {
                $status = 'open';
            }

            $statement = $pdo->prepare(
                'UPDATE crm_tasks
                 SET status = :status
                 WHERE id = :id
                 AND owner_user_id = :owner_user_id'
            );

            $statement->execute([
                'status' => $status,
                'id' => $taskId,
                'owner_user_id' => $userId,
            ]);

            $message = 'Task updated.';
            $isSuccess = true;
        }
    }
}

$tasks = wz_crm_tasks_for_user(
    $userId
);

$crmTitle = 'Tasks';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            FOLLOW-UP
        </span>

        <h1>
            Tasks
        </h1>

        <p>
            Keep follow-ups and event actions from getting lost.
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
                <h2>
                    My task list
                </h2>
            </div>
        </div>

        <?php if ($tasks): ?>
            <div class="crm-list">
                <?php foreach ($tasks as $task): ?>
                    <div class="crm-list-item">
                        <div class="crm-inline">
                            <strong>
                                <?= h((string)$task['title']) ?>
                            </strong>

                            <span class="crm-badge <?= h((string)$task['status']) ?>">
                                <?= h((string)$task['status']) ?>
                            </span>
                        </div>

                        <?php if (!empty($task['description'])): ?>
                            <p>
                                <?= h((string)$task['description']) ?>
                            </p>
                        <?php endif; ?>

                        <small>
                            <?= h(ucfirst((string)$task['priority'])) ?>
                            <?php if (!empty($task['due_at'])): ?>
                                · Due <?= h((string)$task['due_at']) ?>
                            <?php endif; ?>
                        </small>

                        <form
                            method="post"
                            class="crm-inline"
                            style="margin-top: 10px;"
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
                                class="crm-button secondary small"
                                type="submit"
                            >
                                <?= $task['status'] === 'done'
                                    ? 'Reopen'
                                    : 'Mark done' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="crm-empty">
                No tasks yet.
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        New task
                    </h2>
                </div>
            </div>

            <form
                method="post"
                class="crm-form"
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

                <div class="crm-field">
                    <label for="newTaskTitle">
                        Task
                    </label>

                    <input
                        id="newTaskTitle"
                        name="title"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="newTaskDescription">
                        Description
                    </label>

                    <textarea
                        id="newTaskDescription"
                        name="description"
                    ></textarea>
                </div>

                <div class="crm-field">
                    <label for="newTaskDue">
                        Due
                    </label>

                    <input
                        id="newTaskDue"
                        type="datetime-local"
                        name="due_at"
                    >
                </div>

                <div class="crm-field">
                    <label for="newTaskPriority">
                        Priority
                    </label>

                    <select
                        id="newTaskPriority"
                        name="priority"
                    >
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <button
                    class="crm-button"
                    type="submit"
                >
                    Create task
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

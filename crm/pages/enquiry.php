<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'enquiries';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$enquiryId = (int)($_GET['id'] ?? 0);
$message = '';
$isSuccess = false;

$enquiry = wz_crm_enquiry_for_user(
    $enquiryId,
    $userId,
    $crmRole
);

if (!$enquiry) {
    http_response_code(404);
    exit('Enquiry not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if (
            $action === 'update'
            && $crmRole !== 'host'
        ) {
            $stage = (string)($_POST['stage'] ?? 'new');

            if (!in_array(
                $stage,
                [
                    'new',
                    'qualified',
                    'proposal',
                    'negotiation',
                    'won',
                    'lost',
                ],
                true
            )) {
                $stage = 'new';
            }

            $valueEstimate = trim(
                (string)($_POST['value_estimate'] ?? '')
            );

            $followUp = trim(
                (string)($_POST['next_follow_up'] ?? '')
            );

            $pdo = wz_db();

            $statement = $pdo->prepare(
                'UPDATE crm_enquiries
                 SET stage = :stage,
                     value_estimate = :value_estimate,
                     next_follow_up = :next_follow_up
                 WHERE id = :id
                 AND business_user_id = :business_user_id'
            );

            $statement->execute([
                'stage' => $stage,
                'value_estimate' => $valueEstimate !== ''
                    ? (float)$valueEstimate
                    : null,
                'next_follow_up' => $followUp !== ''
                    ? $followUp
                    : null,
                'id' => $enquiryId,
                'business_user_id' => $userId,
            ]);

            if ($stage === 'won') {
                wz_crm_create_booking_from_enquiry(
                    $enquiryId
                );
            }

            $message = 'Enquiry updated.';
            $isSuccess = true;
        }

        if ($action === 'note') {
            $noteBody = trim(
                (string)($_POST['note'] ?? '')
            );

            $visibility = $crmRole === 'host'
                ? 'shared'
                : (string)($_POST['visibility'] ?? 'internal');

            if (!in_array(
                $visibility,
                [
                    'internal',
                    'shared',
                ],
                true
            )) {
                $visibility = 'internal';
            }

            if ($noteBody !== '') {
                $pdo = wz_db();

                $statement = $pdo->prepare(
                    'INSERT INTO crm_notes (
                        user_id,
                        related_type,
                        related_id,
                        body,
                        visibility
                    ) VALUES (
                        :user_id,
                        :related_type,
                        :related_id,
                        :body,
                        :visibility
                    )'
                );

                $statement->execute([
                    'user_id' => $userId,
                    'related_type' => 'enquiry',
                    'related_id' => $enquiryId,
                    'body' => $noteBody,
                    'visibility' => $visibility,
                ]);

                $message = 'Note added.';
                $isSuccess = true;
            }
        }

        if ($action === 'task') {
            $taskTitle = trim(
                (string)($_POST['task_title'] ?? '')
            );

            if ($taskTitle !== '') {
                $pdo = wz_db();

                $statement = $pdo->prepare(
                    'INSERT INTO crm_tasks (
                        owner_user_id,
                        created_by,
                        related_type,
                        related_id,
                        title,
                        due_at,
                        priority
                    ) VALUES (
                        :owner_user_id,
                        :created_by,
                        :related_type,
                        :related_id,
                        :title,
                        :due_at,
                        :priority
                    )'
                );

                $statement->execute([
                    'owner_user_id' => $userId,
                    'created_by' => $userId,
                    'related_type' => 'enquiry',
                    'related_id' => $enquiryId,
                    'title' => $taskTitle,
                    'due_at' => !empty($_POST['task_due'])
                        ? (string)$_POST['task_due']
                        : null,
                    'priority' => in_array(
                        (string)($_POST['task_priority'] ?? 'normal'),
                        [
                            'low',
                            'normal',
                            'high',
                        ],
                        true
                    )
                        ? (string)$_POST['task_priority']
                        : 'normal',
                ]);

                $message = 'Follow-up task added.';
                $isSuccess = true;
            }
        }

        $enquiry = wz_crm_enquiry_for_user(
            $enquiryId,
            $userId,
            $crmRole
        );
    }
}

$notes = wz_crm_notes(
    'enquiry',
    $enquiryId,
    $crmRole !== 'host'
);

$basePath = match ($crmRole) {
    'host' => 'crm/customer',
    'venue' => 'crm/venue',
    default => 'crm/vendor',
};

$crmTitle = 'Enquiry';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            ENQUIRY #<?= h((string)$enquiryId) ?>
        </span>

        <h1>
            <?= h((string)($enquiry['subject'] ?: 'Event enquiry')) ?>
        </h1>

        <p>
            <?= h((string)($enquiry['event_type'] ?: 'Event')) ?>
            <?php if (!empty($enquiry['city'])): ?>
                · <?= h((string)$enquiry['city']) ?>
            <?php endif; ?>
            <?php if (!empty($enquiry['event_date'])): ?>
                · <?= h((string)$enquiry['event_date']) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url($basePath . '/enquiries.php')) ?>"
        >
            Back to enquiries
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-layout">
    <div>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        Enquiry details
                    </h2>
                </div>
            </div>

            <div class="crm-list">
                <div class="crm-list-item">
                    <strong>
                        <?= h(
                            (string)(
                                $crmRole === 'host'
                                    ? ($enquiry['business_contact'] ?: 'Business')
                                    : ($enquiry['customer_name'] ?: 'Customer')
                            )
                        ) ?>
                    </strong>

                    <p>
                        <?= h(
                            (string)(
                                $crmRole === 'host'
                                    ? ($enquiry['business_email'] ?: '')
                                    : ($enquiry['customer_email'] ?: '')
                            )
                        ) ?>
                    </p>
                </div>

                <div class="crm-list-item">
                    <strong>
                        Message
                    </strong>

                    <p>
                        <?= nl2br(
                            h(
                                (string)(
                                    $enquiry['message']
                                    ?: 'No message was supplied.'
                                )
                            )
                        ) ?>
                    </p>
                </div>

                <div class="crm-list-item">
                    <strong>
                        Commercial context
                    </strong>

                    <p>
                        Budget:
                        <?= h((string)($enquiry['budget'] ?: 'Not supplied')) ?>
                        <br>
                        Estimated value:
                        <?= !empty($enquiry['value_estimate'])
                            ? '₹' . number_format((float)$enquiry['value_estimate'])
                            : 'Not set' ?>
                    </p>
                </div>
            </div>
        </section>

        <?php if ($crmRole !== 'host'): ?>
            <section class="crm-panel">
                <div class="crm-panel-head">
                    <div>
                        <h2>
                            Pipeline controls
                        </h2>

                        <p>
                            Keep stage, value and follow-up current.
                        </p>
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
                        value="update"
                    >

                    <div class="crm-form-grid">
                        <div class="crm-field">
                            <label for="enquiryStage">
                                Stage
                            </label>

                            <select
                                id="enquiryStage"
                                name="stage"
                            >
                                <?php foreach (
                                    [
                                        'new',
                                        'qualified',
                                        'proposal',
                                        'negotiation',
                                        'won',
                                        'lost',
                                    ] as $stage
                                ): ?>
                                    <option
                                        value="<?= h($stage) ?>"
                                        <?= $stage === $enquiry['stage'] ? 'selected' : '' ?>
                                    >
                                        <?= h(ucfirst($stage)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="crm-field">
                            <label for="enquiryValue">
                                Estimated value
                            </label>

                            <input
                                id="enquiryValue"
                                type="number"
                                min="0"
                                step="1"
                                name="value_estimate"
                                value="<?= h((string)$enquiry['value_estimate']) ?>"
                            >
                        </div>

                        <div class="crm-field full">
                            <label for="enquiryFollowUp">
                                Next follow-up
                            </label>

                            <input
                                id="enquiryFollowUp"
                                type="datetime-local"
                                name="next_follow_up"
                                value="<?= !empty($enquiry['next_follow_up'])
                                    ? h(
                                        date(
                                            'Y-m-d\TH:i',
                                            strtotime((string)$enquiry['next_follow_up'])
                                        )
                                    )
                                    : '' ?>"
                            >
                        </div>
                    </div>

                    <button
                        class="crm-button"
                        type="submit"
                    >
                        Update enquiry
                    </button>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        Notes
                    </h2>

                    <p>
                        Shared and internal context.
                    </p>
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
                    value="note"
                >

                <div class="crm-field">
                    <label for="enquiryNote">
                        Add note
                    </label>

                    <textarea
                        id="enquiryNote"
                        name="note"
                        required
                    ></textarea>
                </div>

                <?php if ($crmRole !== 'host'): ?>
                    <div class="crm-field">
                        <label for="noteVisibility">
                            Visibility
                        </label>

                        <select
                            id="noteVisibility"
                            name="visibility"
                        >
                            <option value="internal">
                                Internal only
                            </option>

                            <option value="shared">
                                Share with customer
                            </option>
                        </select>
                    </div>
                <?php endif; ?>

                <button
                    class="crm-button secondary"
                    type="submit"
                >
                    Add note
                </button>
            </form>

            <?php if ($notes): ?>
                <div class="crm-list">
                    <?php foreach ($notes as $note): ?>
                        <div class="crm-list-item">
                            <strong>
                                <?= h((string)($note['author_name'] ?: 'Wedding Za')) ?>
                            </strong>

                            <p>
                                <?= nl2br(h((string)$note['body'])) ?>
                            </p>

                            <small>
                                <?= h((string)$note['visibility']) ?>
                                ·
                                <?= h((string)$note['created_at']) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        Add follow-up
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
                    value="task"
                >

                <div class="crm-field">
                    <label for="taskTitle">
                        Task
                    </label>

                    <input
                        id="taskTitle"
                        name="task_title"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="taskDue">
                        Due
                    </label>

                    <input
                        id="taskDue"
                        type="datetime-local"
                        name="task_due"
                    >
                </div>

                <div class="crm-field">
                    <label for="taskPriority">
                        Priority
                    </label>

                    <select
                        id="taskPriority"
                        name="task_priority"
                    >
                        <option value="normal">
                            Normal
                        </option>

                        <option value="high">
                            High
                        </option>

                        <option value="low">
                            Low
                        </option>
                    </select>
                </div>

                <button
                    class="crm-button secondary"
                    type="submit"
                >
                    Add task
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

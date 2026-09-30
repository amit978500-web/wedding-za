<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'requirements-functions';
$crmTitle = 'Functions';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$message = '';
$isSuccess = false;

$workspace = wz_customer_workspace($userId);
$functions = is_array($workspace['brief']['functions'] ?? null)
    ? $workspace['brief']['functions']
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'add');

        if ($action === 'remove') {
            $removeIndex = (int)($_POST['index'] ?? -1);

            if (isset($functions[$removeIndex])) {
                unset($functions[$removeIndex]);
                $functions = array_values($functions);
                $message = 'Function removed.';
                $isSuccess = true;
            }
        } else {
            $name = trim((string)($_POST['name'] ?? ''));
            $date = trim((string)($_POST['date'] ?? ''));
            $guests = max(0, (int)($_POST['guests'] ?? 0));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($name === '') {
                $message = 'Enter a function name.';
            } else {
                $functions[] = [
                    'name' => $name,
                    'date' => $date,
                    'guests' => $guests,
                    'notes' => $notes,
                ];

                $message = 'Function added.';
                $isSuccess = true;
            }
        }

        if ($isSuccess) {
            $workspace['brief']['functions'] = $functions;

            wz_customer_save_workspace(
                $userId,
                $workspace
            );
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">MY REQUIREMENTS</span>
        <h1>Functions</h1>
        <p>Add the individual functions that make up your celebration, such as Haldi, Mehendi, Sangeet, Wedding and Reception.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Add function</h2>
            <p>Build the working schedule for your event.</p>
        </div>
    </div>

    <form method="post" class="crm-form">
        <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
        <input type="hidden" name="action" value="add">

        <div class="crm-form-grid">
            <div class="crm-field">
                <label for="functionName">Function name</label>
                <input id="functionName" name="name" required placeholder="Mehendi">
            </div>

            <div class="crm-field">
                <label for="functionDate">Date</label>
                <input id="functionDate" type="date" name="date">
            </div>

            <div class="crm-field">
                <label for="functionGuests">Expected guests</label>
                <input id="functionGuests" type="number" min="1" name="guests" placeholder="120">
            </div>

            <div class="crm-field">
                <label for="functionNotes">Notes</label>
                <input id="functionNotes" name="notes" placeholder="Lunch, outdoor lawn, floral setup...">
            </div>
        </div>

        <div>
            <button class="crm-button" type="submit">
                Add function
            </button>
        </div>
    </form>
</section>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Planned functions</h2>
            <p><?= h((string)count($functions)) ?> function(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Function</th>
                    <th>Date</th>
                    <th>Guests</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($functions as $index => $function): ?>
                    <tr>
                        <td><strong><?= h((string)($function['name'] ?? 'Function')) ?></strong></td>
                        <td><?= h((string)($function['date'] ?? '—')) ?></td>
                        <td><?= h((string)($function['guests'] ?? '—')) ?></td>
                        <td><?= h((string)($function['notes'] ?? '—')) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="index" value="<?= h((string)$index) ?>">
                                <button class="crm-button secondary small" type="submit">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$functions): ?>
                    <tr>
                        <td colspan="5">No functions added yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

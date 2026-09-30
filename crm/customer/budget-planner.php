<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'budget-planner';
$crmTitle = 'Budget Planner';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$message = '';
$isSuccess = false;

$categories = [
    'Venue & space',
    'Food & beverage',
    'Photography & film',
    'Decor & production',
    'Styling & artists',
    'Entertainment',
    'Hospitality & transport',
    'Invites & gifting',
];

$workspace = wz_customer_workspace($userId);
$budget = is_array($workspace['budget'] ?? null)
    ? $workspace['budget']
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $nextBudget = [];

        foreach ($categories as $index => $category) {
            $plannedKey = 'b' . $index . ':planned';
            $spentKey = 'b' . $index . ':spent';

            $nextBudget[$plannedKey] = max(
                0,
                (float)($_POST['planned'][$index] ?? 0)
            );

            $nextBudget[$spentKey] = max(
                0,
                (float)($_POST['spent'][$index] ?? 0)
            );
        }

        $workspace['budget'] = $nextBudget;

        wz_customer_save_workspace(
            $userId,
            $workspace
        );

        $budget = $nextBudget;
        $message = 'Budget planner saved.';
        $isSuccess = true;
    }
}

$plannedTotal = 0;
$spentTotal = 0;

foreach ($budget as $key => $value) {
    if (str_ends_with((string)$key, ':planned')) {
        $plannedTotal += (float)$value;
    }

    if (str_ends_with((string)$key, ':spent')) {
        $spentTotal += (float)$value;
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">BUDGET PLANNER</span>
        <h1>Plan the money</h1>
        <p>Track planned versus spent amounts by category. This uses the same saved workspace as the public Planning Studio.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Planned total</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($plannedTotal)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Spent / paid</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($spentTotal)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Remaining</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($plannedTotal - $spentTotal)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Tracked categories</span>
        <strong><?= h((string)count($categories)) ?></strong>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Category budget</h2>
            <p>Enter planned and spent values, then save.</p>
        </div>
    </div>

    <form method="post" class="crm-form">
        <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Planned</th>
                        <th>Spent</th>
                        <th>Balance</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($categories as $index => $category): ?>
                        <?php
                        $planned = (float)($budget['b' . $index . ':planned'] ?? 0);
                        $spent = (float)($budget['b' . $index . ':spent'] ?? 0);
                        ?>
                        <tr>
                            <td><strong><?= h($category) ?></strong></td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="planned[<?= h((string)$index) ?>]"
                                    value="<?= h((string)$planned) ?>"
                                >
                            </td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="spent[<?= h((string)$index) ?>]"
                                    value="<?= h((string)$spent) ?>"
                                >
                            </td>
                            <td>
                                ₹<?= h(number_format($planned - $spent)) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div>
            <button class="crm-button" type="submit">
                Save budget planner
            </button>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

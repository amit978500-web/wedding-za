<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'requirements-budget';
$crmTitle = 'Budget Requirement';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$message = '';
$isSuccess = false;

$profile = wz_customer_profile_row($userId);
$workspace = wz_customer_workspace($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $budget = trim((string)($_POST['budget'] ?? ''));
        $pdo = wz_db();

        if ($pdo) {
            $statement = $pdo->prepare(
                'INSERT INTO customer_profiles (
                    user_id,
                    budget
                ) VALUES (
                    :user_id,
                    :budget
                )
                ON DUPLICATE KEY UPDATE
                    budget = VALUES(budget)'
            );

            $statement->execute([
                'user_id' => $userId,
                'budget' => $budget !== '' ? $budget : null,
            ]);

            $workspace['brief']['budget'] = $budget;

            wz_customer_save_workspace(
                $userId,
                $workspace
            );

            $profile = wz_customer_profile_row($userId);
            $message = 'Budget requirement saved.';
            $isSuccess = true;
        } else {
            $message = 'Database is not available.';
        }
    }
}

$planned = 0;
$spent = 0;

foreach (($workspace['budget'] ?? []) as $key => $value) {
    if (str_ends_with((string)$key, ':planned')) {
        $planned += (float)$value;
    } elseif (str_ends_with((string)$key, ':spent')) {
        $spent += (float)$value;
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">MY REQUIREMENTS</span>
        <h1>Budget</h1>
        <p>Set your overall budget requirement here, then use Budget Planner for category-level tracking.</p>
    </div>

    <div class="crm-actions">
        <a class="crm-button" href="<?= h(wz_app_url('crm/customer/budget-planner.php')) ?>">
            Open Budget Planner ↗
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Budget range</span>
        <strong style="font-size: 24px;">
            <?= h((string)($profile['budget'] ?? 'Not set')) ?>
        </strong>
    </article>

    <article class="crm-stat">
        <span>Planned</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($planned)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Spent</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($spent)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Remaining</span>
        <strong style="font-size: 30px;">₹<?= h(number_format($planned - $spent)) ?></strong>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Overall budget requirement</h2>
            <p>This is shown with your event brief and enquiries.</p>
        </div>
    </div>

    <form method="post" class="crm-form">
        <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">

        <div class="crm-field">
            <label for="budget">Budget range</label>
            <select id="budget" name="budget">
                <?php
                $options = [
                    '',
                    'Under ₹5 lakh',
                    '₹5–15 lakh',
                    '₹15–30 lakh',
                    '₹30–60 lakh',
                    '₹60 lakh+',
                ];
                ?>

                <?php foreach ($options as $option): ?>
                    <option
                        value="<?= h($option) ?>"
                        <?= (string)($profile['budget'] ?? '') === $option ? 'selected' : '' ?>
                    >
                        <?= h($option === '' ? 'Choose budget' : $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button class="crm-button" type="submit">
                Save budget
            </button>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

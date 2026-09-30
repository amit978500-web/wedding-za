<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'subscription';
$crmTitle = 'Business Plan';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$profile = wz_crm_business_profile($userId, $crmRole) ?: [];
$pdo = wz_db();
$message = '';
$isSuccess = false;

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && wz_csrf_valid($_POST['csrf'] ?? null)
) {
    $requestedPlan = (string)($_POST['plan'] ?? '');

    if (in_array($requestedPlan, ['featured', 'pro'], true)) {
        $admins = $pdo->query(
            'SELECT id
             FROM users
             WHERE role = "admin"
             AND status = "active"'
        )->fetchAll();

        foreach ($admins as $admin) {
            wz_marketplace_notify(
                (int)$admin['id'],
                'subscription',
                'Business plan upgrade requested',
                wz_crm_business_name($userId, $crmRole)
                    .' requested '
                    .ucfirst($requestedPlan)
                    .'.',
                null
            );
        }

        $message = 'Upgrade request sent to Wedding Za.';
        $isSuccess = true;
    }
}

$currentPlan = (string)($profile['plan'] ?? 'free');
$billingStatus = (string)($profile['billing_status'] ?? 'inactive');

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">BUSINESS PLAN</span>
        <h1><?= h(ucfirst($currentPlan)) ?> plan</h1>
        <p>
            Featured and Pro plans can unlock stronger marketplace placement and deeper business tools after Wedding Za activation.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Current plan</span>
        <strong style="font-size:30px;"><?= h(ucfirst($currentPlan)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Billing</span>
        <strong style="font-size:30px;"><?= h(ucfirst($billingStatus)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Featured placement</span>
        <strong style="font-size:30px;">
            <?= in_array($currentPlan,['featured','pro'],true)?'Yes':'No' ?>
        </strong>
    </article>

    <article class="crm-stat">
        <span>Analytics</span>
        <strong style="font-size:30px;"><?= $currentPlan==='pro'?'Full':'Basic' ?></strong>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Plan options</h2>
            <p>Upgrade requests are reviewed before activation.</p>
        </div>
    </div>

    <div class="crm-calendar-grid">
        <article class="crm-day">
            <strong>Free</strong>
            <span>Verified profile · enquiries · CRM</span>
        </article>

        <article class="crm-day">
            <strong>Featured</strong>
            <span>Higher marketplace placement · featured badge</span>
            <form method="post" style="margin-top:12px;">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                <button class="crm-button small" type="submit" name="plan" value="featured">
                    Request Featured
                </button>
            </form>
        </article>

        <article class="crm-day">
            <strong>Pro</strong>
            <span>Featured placement · full analytics · priority marketplace tools</span>
            <form method="post" style="margin-top:12px;">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                <button class="crm-button small" type="submit" name="plan" value="pro">
                    Request Pro
                </button>
            </form>
        </article>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

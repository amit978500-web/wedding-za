<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'analytics';
$crmTitle = 'Business Analytics';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$analytics = wz_marketplace_business_analytics(
    $userId,
    $crmRole,
    30
);

$views = (int)($analytics['profile_view'] ?? 0);
$enquiries = (int)($analytics['enquiry'] ?? 0);
$bookings = (int)($analytics['booking'] ?? 0);
$shortlists = (int)($analytics['shortlist'] ?? 0);
$conversion = $views > 0
    ? round(($enquiries / $views) * 100, 1)
    : 0;

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">LAST 30 DAYS</span>
        <h1>Analytics</h1>
        <p>
            Track how profile discovery turns into shortlists, enquiries, conversations, quotes and bookings.
        </p>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Profile views</span>
        <strong><?= h((string)$views) ?></strong>
        <small>Public profile opens</small>
    </article>

    <article class="crm-stat">
        <span>Shortlists</span>
        <strong><?= h((string)$shortlists) ?></strong>
        <small>Customer save intent</small>
    </article>

    <article class="crm-stat">
        <span>Enquiries</span>
        <strong><?= h((string)$enquiries) ?></strong>
        <small>Marketplace leads</small>
    </article>

    <article class="crm-stat">
        <span>View → enquiry</span>
        <strong style="font-size:34px;"><?= h((string)$conversion) ?>%</strong>
        <small>Discovery conversion</small>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Funnel activity</h2>
            <p>Event counts recorded by Wedding Za marketplace activity.</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Count</th>
                    <th>What it means</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $labels = [
                    'profile_view' => 'Profile views',
                    'shortlist' => 'Shortlists',
                    'enquiry' => 'Enquiries',
                    'message' => 'Messages',
                    'quote' => 'Quotes',
                    'booking' => 'Bookings',
                ];
                ?>

                <?php foreach ($labels as $key => $label): ?>
                    <tr>
                        <td><strong><?= h($label) ?></strong></td>
                        <td><?= h((string)($analytics[$key] ?? 0)) ?></td>
                        <td>
                            <?= match ($key) {
                                'profile_view' => 'Customers opened your public profile.',
                                'shortlist' => 'Customers saved your business for later.',
                                'enquiry' => 'Customers requested pricing or availability.',
                                'message' => 'CRM conversation activity.',
                                'quote' => 'Commercial proposals created.',
                                'booking' => 'Confirmed marketplace outcomes.',
                            } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

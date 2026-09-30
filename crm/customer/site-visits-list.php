<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$siteVisitFilter = $siteVisitFilter ?? 'upcoming';
$crmPage = $crmPage ?? 'site-visits-upcoming';
$crmTitle = $siteVisitFilter === 'completed'
    ? 'Completed Site Visits'
    : 'Upcoming Site Visits';

$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$visits = [];
$pdo = wz_db();

if ($pdo) {
    $sql = 'SELECT
                ce.*,
                vp.venue_name,
                vp.city AS venue_city,
                vp.locality AS venue_locality
            FROM crm_enquiries ce
            LEFT JOIN venue_profiles vp
                ON vp.user_id = ce.business_user_id
            WHERE ce.customer_user_id = :customer_user_id
            AND ce.business_type = "venue"
            AND ce.site_visit_at IS NOT NULL';

    if ($siteVisitFilter === 'completed') {
        $sql .= ' AND ce.site_visit_status = "completed"';
    } else {
        $sql .= ' AND ce.site_visit_status = "scheduled"
                  AND ce.site_visit_at >= NOW()';
    }

    $sql .= ' ORDER BY ce.site_visit_at ASC';

    $statement = $pdo->prepare($sql);

    $statement->execute([
        'customer_user_id' => $userId,
    ]);

    $visits = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">SITE VISITS</span>
        <h1>
            <?= $siteVisitFilter === 'completed'
                ? 'Completed visits'
                : 'Upcoming visits' ?>
        </h1>
        <p>
            <?= $siteVisitFilter === 'completed'
                ? 'Review venues you have already visited.'
                : 'Keep your scheduled venue visits and event context in one place.' ?>
        </p>
    </div>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>
                <?= $siteVisitFilter === 'completed'
                    ? 'Visit history'
                    : 'Scheduled visits' ?>
            </h2>
            <p><?= h((string)count($visits)) ?> visit(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>Visit date</th>
                    <th>Event</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Enquiry</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($visits as $visit): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)($visit['venue_name'] ?: 'Venue')) ?>
                            </strong>
                        </td>
                        <td><?= h((string)$visit['site_visit_at']) ?></td>
                        <td>
                            <?= h((string)($visit['event_type'] ?: 'Event')) ?>
                            <?php if (!empty($visit['event_date'])): ?>
                                <br><small><?= h((string)$visit['event_date']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= h((string)($visit['venue_city'] ?: $visit['city'] ?: '—')) ?>
                            <?php if (!empty($visit['venue_locality'])): ?>
                                <br><small><?= h((string)$visit['venue_locality']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="crm-badge <?= h((string)$visit['site_visit_status']) ?>">
                                <?= h(ucfirst((string)$visit['site_visit_status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a
                                class="crm-button secondary small"
                                href="<?= h(
                                    wz_app_url(
                                        'crm/customer/enquiry.php?id=' .
                                        urlencode((string)$visit['id'])
                                    )
                                ) ?>"
                            >
                                Open
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$visits): ?>
                    <tr>
                        <td colspan="6">
                            <?= $siteVisitFilter === 'completed'
                                ? 'No completed site visits yet.'
                                : 'No upcoming site visits scheduled.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

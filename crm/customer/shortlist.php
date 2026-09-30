<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'shortlist-saved';
$crmTitle = 'Saved Venues';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$message = '';
$isSuccess = false;

$workspace = wz_customer_workspace($userId);
$shortlist = is_array($workspace['shortlist'] ?? null)
    ? $workspace['shortlist']
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $venueUserId = (int)($_POST['venue_user_id'] ?? 0);
        $removeKey = 'venue:' . $venueUserId;

        $shortlist = array_values(
            array_filter(
                $shortlist,
                fn ($item): bool =>
                    (string)$item !== $removeKey
            )
        );

        $workspace['shortlist'] = $shortlist;

        wz_customer_save_workspace(
            $userId,
            $workspace
        );

        $message = 'Venue removed from shortlist.';
        $isSuccess = true;
    }
}

$venueIds = [];

foreach ($shortlist as $item) {
    $item = (string)$item;

    if (str_starts_with($item, 'venue:')) {
        $venueId = (int)substr($item, 6);

        if ($venueId > 0) {
            $venueIds[] = $venueId;
        }
    }
}

$venues = [];
$pdo = wz_db();

if ($pdo && $venueIds) {
    $placeholders = implode(
        ',',
        array_fill(0, count($venueIds), '?')
    );

    $statement = $pdo->prepare(
        'SELECT
            vp.*,
            u.name AS contact_name,
            u.email AS contact_email
         FROM venue_profiles vp
         INNER JOIN users u
            ON u.id = vp.user_id
         WHERE vp.user_id IN (' . $placeholders . ')
         ORDER BY vp.venue_name'
    );

    $statement->execute($venueIds);
    $venues = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">MY SHORTLIST</span>
        <h1>Saved venues</h1>
        <p>Keep the serious venue options together before sending enquiries or scheduling site visits.</p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button"
            href="<?= h(wz_app_url('crm/customer/find-venues.php')) ?>"
        >
            Find more venues ↗
        </a>
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
            <h2>Your venue shortlist</h2>
            <p><?= h((string)count($venues)) ?> saved venue(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($venues as $venue): ?>
                    <tr>
                        <td><strong><?= h((string)$venue['venue_name']) ?></strong></td>
                        <td>
                            <?= h((string)($venue['city'] ?: '—')) ?>
                            <?php if (!empty($venue['locality'])): ?>
                                <br><small><?= h((string)$venue['locality']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h((string)($venue['venue_type'] ?: 'Venue')) ?></td>
                        <td>
                            <?= h((string)($venue['capacity_min'] ?: '—')) ?>
                            –
                            <?= h((string)($venue['capacity_max'] ?: '—')) ?>
                        </td>
                        <td><?= h((string)($venue['starting_price'] ?: 'On request')) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="venue_user_id" value="<?= h((string)$venue['user_id']) ?>">
                                <button class="crm-button secondary small" type="submit">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$venues): ?>
                    <tr>
                        <td colspan="6">
                            No venues saved yet.
                            <a href="<?= h(wz_app_url('crm/customer/find-venues.php')) ?>">
                                Find venues ↗
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'find-venues';
$crmTitle = 'Find Venues';
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
        $shortlistKey = 'venue:' . $venueUserId;

        if ($venueUserId > 0 && !in_array($shortlistKey, $shortlist, true)) {
            $shortlist[] = $shortlistKey;
            $workspace['shortlist'] = array_values($shortlist);

            wz_customer_save_workspace(
                $userId,
                $workspace
            );

            $message = 'Venue saved to your shortlist.';
            $isSuccess = true;
        } elseif ($venueUserId > 0) {
            $message = 'This venue is already in your shortlist.';
            $isSuccess = true;
        }
    }
}

$city = trim((string)($_GET['city'] ?? ''));
$guests = max(0, (int)($_GET['guests'] ?? 0));
$venues = [];
$pdo = wz_db();

if ($pdo) {
    $sql = 'SELECT
                vp.*,
                u.name AS contact_name,
                u.email AS contact_email
            FROM venue_profiles vp
            INNER JOIN users u
                ON u.id = vp.user_id
            WHERE vp.approval_status = "approved"
            AND u.status = "active"';

    $params = [];

    if ($city !== '') {
        $sql .= ' AND vp.city LIKE :city';
        $params['city'] = '%' . $city . '%';
    }

    if ($guests > 0) {
        $sql .= ' AND (
            vp.capacity_max IS NULL
            OR vp.capacity_max >= :guests
        )';
        $params['guests'] = $guests;
    }

    $sql .= ' ORDER BY
        vp.featured DESC,
        vp.venue_name ASC
        LIMIT 100';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $venues = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">VENUE DISCOVERY</span>
        <h1>Find venues</h1>
        <p>Search approved venues, compare capacity and save the strongest options to your shortlist.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-plan-wrap" aria-labelledby="venuePlanTitle">
    <article class="crm-plan-card">
        <div class="crm-plan-card-top">
            <span class="crm-plan-kicker">WEDDINGZA VENUE ASSIST</span>

            <h2 id="venuePlanTitle">
                Find Your Perfect Wedding Venue
            </h2>

            <div class="crm-plan-rule" aria-hidden="true"></div>

            <span class="crm-plan-name">ONE WEDDING</span>

            <strong class="crm-plan-price">
                ₹1,000
            </strong>
        </div>

        <ul class="crm-plan-features">
            <li>Venue search assistance</li>
            <li>Curated venue recommendations</li>
            <li>Shortlisted venues</li>
            <li>Venue enquiry support</li>
            <li>Site-visit assistance</li>
            <li>Dedicated Weddingza support</li>
        </ul>

        <a
            class="crm-plan-cta"
            href="<?= h(
                wz_app_url(
                    'crm/customer/venue-plan-payment.php'
                )
            ) ?>"
        >
            Continue to Payment
            <span aria-hidden="true">→</span>
        </a>

        <small class="crm-plan-note">
            One-time service fee for one wedding.
        </small>
    </article>
</section>

<section class="crm-panel">
    <form method="get" class="crm-form">
        <div class="crm-form-grid">
            <div class="crm-field">
                <label for="city">City</label>
                <input
                    id="city"
                    name="city"
                    value="<?= h($city) ?>"
                    placeholder="Jaipur"
                >
            </div>

            <div class="crm-field">
                <label for="guests">Minimum capacity</label>
                <input
                    id="guests"
                    type="number"
                    min="1"
                    name="guests"
                    value="<?= h($guests > 0 ? (string)$guests : '') ?>"
                    placeholder="250"
                >
            </div>
        </div>

        <div>
            <button class="crm-button" type="submit">Search venues</button>
        </div>
    </form>
</section>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Available venues</h2>
            <p><?= h((string)count($venues)) ?> venue(s) found</p>
        </div>

        <a
            class="crm-button secondary small"
            href="<?= h(wz_app_url('crm/customer/shortlist.php')) ?>"
        >
            My Shortlist
        </a>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>City</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Starting price</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($venues as $venue): ?>
                    <?php
                    $venueKey = 'venue:' . (int)$venue['user_id'];
                    $isSaved = in_array($venueKey, $shortlist, true);
                    ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$venue['venue_name']) ?></strong>
                            <?php if (!empty($venue['locality'])): ?>
                                <br><small><?= h((string)$venue['locality']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h((string)($venue['city'] ?: '—')) ?></td>
                        <td><?= h((string)($venue['venue_type'] ?: 'Venue')) ?></td>
                        <td>
                            <?= h((string)($venue['capacity_min'] ?: '—')) ?>
                            –
                            <?= h((string)($venue['capacity_max'] ?: '—')) ?>
                        </td>
                        <td><?= h((string)($venue['starting_price'] ?: 'On request')) ?></td>
                        <td>
                            <?php if ($isSaved): ?>
                                <span class="crm-badge active">Saved</span>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                    <input type="hidden" name="venue_user_id" value="<?= h((string)$venue['user_id']) ?>">
                                    <button class="crm-button small" type="submit">
                                        Save venue
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$venues): ?>
                    <tr>
                        <td colspan="6">No matching approved venues found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

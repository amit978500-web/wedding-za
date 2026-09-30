<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'requirements-wedding';
$crmTitle = 'Wedding Details';
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
        $eventType = trim((string)($_POST['event_type'] ?? ''));
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $guestCount = max(0, (int)($_POST['guest_count'] ?? 0));
        $notes = trim((string)($_POST['notes'] ?? ''));

        $pdo = wz_db();

        if ($pdo) {
            $statement = $pdo->prepare(
                'INSERT INTO customer_profiles (
                    user_id,
                    city,
                    event_type,
                    event_date,
                    guest_count,
                    notes
                ) VALUES (
                    :user_id,
                    :city,
                    :event_type,
                    :event_date,
                    :guest_count,
                    :notes
                )
                ON DUPLICATE KEY UPDATE
                    city = VALUES(city),
                    event_type = VALUES(event_type),
                    event_date = VALUES(event_date),
                    guest_count = VALUES(guest_count),
                    notes = VALUES(notes)'
            );

            $statement->execute([
                'user_id' => $userId,
                'city' => $city !== '' ? $city : null,
                'event_type' => $eventType !== '' ? $eventType : null,
                'event_date' => $eventDate !== '' ? $eventDate : null,
                'guest_count' => $guestCount > 0 ? $guestCount : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $workspace['brief']['event'] = $eventType;
            $workspace['brief']['date'] = $eventDate;
            $workspace['brief']['city'] = $city;
            $workspace['brief']['guests'] = $guestCount > 0
                ? (string)$guestCount
                : '';

            wz_customer_save_workspace(
                $userId,
                $workspace
            );

            $profile = wz_customer_profile_row($userId);
            $message = 'Wedding details saved.';
            $isSuccess = true;
        } else {
            $message = 'Database is not available.';
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">MY REQUIREMENTS</span>
        <h1>Wedding details</h1>
        <p>Keep the core event brief accurate so venue searches and enquiries have the right context.</p>
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
            <h2>Event brief</h2>
            <p>These details belong to your customer planning profile.</p>
        </div>
    </div>

    <form method="post" class="crm-form">
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <div class="crm-form-grid">
            <div class="crm-field">
                <label for="eventType">Wedding / event type</label>
                <input
                    id="eventType"
                    name="event_type"
                    value="<?= h((string)($profile['event_type'] ?? '')) ?>"
                    placeholder="Wedding, engagement, reception..."
                >
            </div>

            <div class="crm-field">
                <label for="eventDate">Main event date</label>
                <input
                    id="eventDate"
                    type="date"
                    name="event_date"
                    value="<?= h((string)($profile['event_date'] ?? '')) ?>"
                >
            </div>

            <div class="crm-field">
                <label for="eventCity">Preferred city</label>
                <input
                    id="eventCity"
                    name="city"
                    value="<?= h((string)($profile['city'] ?? '')) ?>"
                    placeholder="Jaipur"
                >
            </div>

            <div class="crm-field">
                <label for="guestCount">Guest count</label>
                <input
                    id="guestCount"
                    type="number"
                    min="1"
                    name="guest_count"
                    value="<?= h((string)($profile['guest_count'] ?? '')) ?>"
                    placeholder="250"
                >
            </div>

            <div class="crm-field full">
                <label for="notes">Requirements / notes</label>
                <textarea
                    id="notes"
                    name="notes"
                    placeholder="Rooms, outdoor preference, food, decor direction, special requirements..."
                ><?= h((string)($profile['notes'] ?? '')) ?></textarea>
            </div>
        </div>

        <div>
            <button class="crm-button" type="submit">
                Save wedding details
            </button>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

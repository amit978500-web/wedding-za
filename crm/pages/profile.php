<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/media.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'profile';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;
$pdo = wz_db();
$action = (string)($_POST['action'] ?? 'profile');

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } elseif (
        $action === 'media-upload'
        && in_array(
            $crmRole,
            ['vendor', 'venue'],
            true
        )
    ) {
        $upload = wz_media_upload(
            $_FILES['image'] ?? [],
            (string)($_POST['alt_text'] ?? '')
        );

        if ($upload['ok']) {
            if ($crmRole === 'vendor') {
                wz_vendor_append_media(
                    $userId,
                    (string)$upload['path']
                );
            } else {
                wz_venue_append_media(
                    $userId,
                    (string)$upload['path']
                );
            }

            $message = 'Portfolio image uploaded.';
            $isSuccess = true;
        } else {
            $message = (string)$upload['message'];
        }
    } elseif ($crmRole === 'host') {
        $statement = $pdo->prepare(
            'INSERT INTO customer_profiles (
                user_id,
                phone,
                city,
                event_type,
                event_date,
                guest_count,
                budget,
                notes
            ) VALUES (
                :user_id,
                :phone,
                :city,
                :event_type,
                :event_date,
                :guest_count,
                :budget,
                :notes
            )
            ON DUPLICATE KEY UPDATE
                phone = VALUES(phone),
                city = VALUES(city),
                event_type = VALUES(event_type),
                event_date = VALUES(event_date),
                guest_count = VALUES(guest_count),
                budget = VALUES(budget),
                notes = VALUES(notes)'
        );

        $statement->execute([
            'user_id' => $userId,
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'city' => trim((string)($_POST['city'] ?? '')),
            'event_type' => trim((string)($_POST['event_type'] ?? '')),
            'event_date' => !empty($_POST['event_date'])
                ? (string)$_POST['event_date']
                : null,
            'guest_count' => !empty($_POST['guest_count'])
                ? (int)$_POST['guest_count']
                : null,
            'budget' => trim((string)($_POST['budget'] ?? '')),
            'notes' => trim((string)($_POST['notes'] ?? '')),
        ]);

        wz_audit(
            'crm.customer_profile.updated',
            'customer_profile',
            $userId
        );

        $message = 'Customer profile updated.';
        $isSuccess = true;
    } elseif ($crmRole === 'vendor') {
        $events = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)($_POST['events'] ?? '')
                    )
                )
            )
        );

        $statement = $pdo->prepare(
            'INSERT INTO vendor_profiles (
                user_id,
                business_name,
                category,
                city,
                events_json,
                starting_price,
                portfolio_url,
                about
            ) VALUES (
                :user_id,
                :business_name,
                :category,
                :city,
                :events_json,
                :starting_price,
                :portfolio_url,
                :about
            )
            ON DUPLICATE KEY UPDATE
                business_name = VALUES(business_name),
                category = VALUES(category),
                city = VALUES(city),
                events_json = VALUES(events_json),
                starting_price = VALUES(starting_price),
                portfolio_url = VALUES(portfolio_url),
                about = VALUES(about)'
        );

        $statement->execute([
            'user_id' => $userId,
            'business_name' => trim((string)($_POST['business_name'] ?? '')),
            'category' => trim((string)($_POST['category'] ?? '')),
            'city' => trim((string)($_POST['city'] ?? '')),
            'events_json' => json_encode($events),
            'starting_price' => trim((string)($_POST['starting_price'] ?? '')),
            'portfolio_url' => trim((string)($_POST['portfolio_url'] ?? '')),
            'about' => trim((string)($_POST['about'] ?? '')),
        ]);

        wz_audit(
            'crm.vendor_profile.updated',
            'vendor_profile',
            $userId
        );

        $message = 'Vendor profile updated.';
        $isSuccess = true;
    } else {
        $events = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)($_POST['events'] ?? '')
                    )
                )
            )
        );

        $statement = $pdo->prepare(
            'INSERT INTO venue_profiles (
                user_id,
                venue_name,
                city,
                locality,
                address,
                capacity_min,
                capacity_max,
                rooms,
                venue_type,
                starting_price,
                portfolio_url,
                about,
                events_json
            ) VALUES (
                :user_id,
                :venue_name,
                :city,
                :locality,
                :address,
                :capacity_min,
                :capacity_max,
                :rooms,
                :venue_type,
                :starting_price,
                :portfolio_url,
                :about,
                :events_json
            )
            ON DUPLICATE KEY UPDATE
                venue_name = VALUES(venue_name),
                city = VALUES(city),
                locality = VALUES(locality),
                address = VALUES(address),
                capacity_min = VALUES(capacity_min),
                capacity_max = VALUES(capacity_max),
                rooms = VALUES(rooms),
                venue_type = VALUES(venue_type),
                starting_price = VALUES(starting_price),
                portfolio_url = VALUES(portfolio_url),
                about = VALUES(about),
                events_json = VALUES(events_json)'
        );

        $statement->execute([
            'user_id' => $userId,
            'venue_name' => trim((string)($_POST['venue_name'] ?? '')),
            'city' => trim((string)($_POST['city'] ?? '')),
            'locality' => trim((string)($_POST['locality'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'capacity_min' => !empty($_POST['capacity_min'])
                ? (int)$_POST['capacity_min']
                : null,
            'capacity_max' => !empty($_POST['capacity_max'])
                ? (int)$_POST['capacity_max']
                : null,
            'rooms' => !empty($_POST['rooms'])
                ? (int)$_POST['rooms']
                : null,
            'venue_type' => trim((string)($_POST['venue_type'] ?? '')),
            'starting_price' => trim((string)($_POST['starting_price'] ?? '')),
            'portfolio_url' => trim((string)($_POST['portfolio_url'] ?? '')),
            'about' => trim((string)($_POST['about'] ?? '')),
            'events_json' => json_encode($events),
        ]);

        wz_audit(
            'crm.venue_profile.updated',
            'venue_profile',
            $userId
        );

        $message = 'Venue profile updated.';
        $isSuccess = true;
    }
}

if ($crmRole === 'host') {
    $profile = wz_crm_customer_profile(
        $userId
    ) ?? [];
} else {
    $profile = wz_crm_business_profile(
        $userId,
        $crmRole
    ) ?? [];
}

$crmTitle = 'Profile';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            PROFILE
        </span>

        <h1>
            <?= $crmRole === 'host'
                ? 'Customer profile'
                : ($crmRole === 'venue'
                    ? 'Venue profile'
                    : 'Business profile') ?>
        </h1>

        <p>
            Keep the information used across your Wedding Za CRM current.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
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
            value="profile"
        >

        <div class="crm-form-grid">
            <?php if ($crmRole === 'host'): ?>
                <div class="crm-field">
                    <label for="customerPhone">
                        Phone
                    </label>

                    <input
                        id="customerPhone"
                        name="phone"
                        value="<?= h((string)($profile['phone'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerCity">
                        City
                    </label>

                    <input
                        id="customerCity"
                        name="city"
                        value="<?= h((string)($profile['city'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerEvent">
                        Event type
                    </label>

                    <input
                        id="customerEvent"
                        name="event_type"
                        value="<?= h((string)($profile['event_type'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerDate">
                        Event date
                    </label>

                    <input
                        id="customerDate"
                        type="date"
                        name="event_date"
                        value="<?= h((string)($profile['event_date'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerGuests">
                        Guest count
                    </label>

                    <input
                        id="customerGuests"
                        type="number"
                        min="0"
                        name="guest_count"
                        value="<?= h((string)($profile['guest_count'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerBudget">
                        Budget
                    </label>

                    <input
                        id="customerBudget"
                        name="budget"
                        value="<?= h((string)($profile['budget'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="customerNotes">
                        Planning notes
                    </label>

                    <textarea
                        id="customerNotes"
                        name="notes"
                    ><?= h((string)($profile['notes'] ?? '')) ?></textarea>
                </div>
            <?php elseif ($crmRole === 'vendor'): ?>
                <div class="crm-field">
                    <label for="vendorBusinessName">
                        Business name
                    </label>

                    <input
                        id="vendorBusinessName"
                        name="business_name"
                        value="<?= h((string)($profile['business_name'] ?? '')) ?>"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="vendorCategory">
                        Category
                    </label>

                    <input
                        id="vendorCategory"
                        name="category"
                        value="<?= h((string)($profile['category'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="vendorCity">
                        City
                    </label>

                    <input
                        id="vendorCity"
                        name="city"
                        value="<?= h((string)($profile['city'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="vendorPrice">
                        Starting price
                    </label>

                    <input
                        id="vendorPrice"
                        name="starting_price"
                        value="<?= h((string)($profile['starting_price'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="vendorEvents">
                        Event types
                    </label>

                    <input
                        id="vendorEvents"
                        name="events"
                        value="<?= h(
                            implode(
                                ', ',
                                json_decode(
                                    (string)($profile['events_json'] ?? '[]'),
                                    true
                                ) ?: []
                            )
                        ) ?>"
                        placeholder="Wedding, Engagement, Corporate"
                    >
                </div>

                <div class="crm-field full">
                    <label for="vendorPortfolio">
                        Portfolio URL
                    </label>

                    <input
                        id="vendorPortfolio"
                        name="portfolio_url"
                        value="<?= h((string)($profile['portfolio_url'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="vendorAbout">
                        About
                    </label>

                    <textarea
                        id="vendorAbout"
                        name="about"
                    ><?= h((string)($profile['about'] ?? '')) ?></textarea>
                </div>
            <?php else: ?>
                <div class="crm-field">
                    <label for="venueName">
                        Venue name
                    </label>

                    <input
                        id="venueName"
                        name="venue_name"
                        value="<?= h((string)($profile['venue_name'] ?? '')) ?>"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="venueType">
                        Venue type
                    </label>

                    <input
                        id="venueType"
                        name="venue_type"
                        value="<?= h((string)($profile['venue_type'] ?? '')) ?>"
                        placeholder="Hotel, Palace, Farmhouse..."
                    >
                </div>

                <div class="crm-field">
                    <label for="venueCity">
                        City
                    </label>

                    <input
                        id="venueCity"
                        name="city"
                        value="<?= h((string)($profile['city'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueLocality">
                        Locality
                    </label>

                    <input
                        id="venueLocality"
                        name="locality"
                        value="<?= h((string)($profile['locality'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venueAddress">
                        Address
                    </label>

                    <textarea
                        id="venueAddress"
                        name="address"
                    ><?= h((string)($profile['address'] ?? '')) ?></textarea>
                </div>

                <div class="crm-field">
                    <label for="venueCapacityMin">
                        Minimum capacity
                    </label>

                    <input
                        id="venueCapacityMin"
                        type="number"
                        min="0"
                        name="capacity_min"
                        value="<?= h((string)($profile['capacity_min'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueCapacityMax">
                        Maximum capacity
                    </label>

                    <input
                        id="venueCapacityMax"
                        type="number"
                        min="0"
                        name="capacity_max"
                        value="<?= h((string)($profile['capacity_max'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueRooms">
                        Rooms
                    </label>

                    <input
                        id="venueRooms"
                        type="number"
                        min="0"
                        name="rooms"
                        value="<?= h((string)($profile['rooms'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venuePrice">
                        Starting price
                    </label>

                    <input
                        id="venuePrice"
                        name="starting_price"
                        value="<?= h((string)($profile['starting_price'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venueEvents">
                        Event types
                    </label>

                    <input
                        id="venueEvents"
                        name="events"
                        value="<?= h(
                            implode(
                                ', ',
                                json_decode(
                                    (string)($profile['events_json'] ?? '[]'),
                                    true
                                ) ?: []
                            )
                        ) ?>"
                        placeholder="Wedding, Birthday, Corporate"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venuePortfolio">
                        Portfolio URL
                    </label>

                    <input
                        id="venuePortfolio"
                        name="portfolio_url"
                        value="<?= h((string)($profile['portfolio_url'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venueAbout">
                        About
                    </label>

                    <textarea
                        id="venueAbout"
                        name="about"
                    ><?= h((string)($profile['about'] ?? '')) ?></textarea>
                </div>
            <?php endif; ?>
        </div>

        <button
            class="crm-button"
            type="submit"
        >
            Save profile
        </button>
    </form>
</section>

<?php if (in_array($crmRole, ['vendor', 'venue'], true)): ?>
    <?php
    $gallery = json_decode(
        (string)($profile['gallery_json'] ?? '[]'),
        true
    );

    if (!is_array($gallery)) {
        $gallery = [];
    }
    ?>

    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>
                    Portfolio media
                </h2>

                <p>
                    Upload real work for your public Wedding Za profile.
                </p>
            </div>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
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
                value="media-upload"
            >

            <div class="crm-form-grid">
                <div class="crm-field">
                    <label for="crmProfileImage">
                        Image
                    </label>

                    <input
                        id="crmProfileImage"
                        type="file"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="crmProfileAlt">
                        Alt text
                    </label>

                    <input
                        id="crmProfileAlt"
                        name="alt_text"
                        maxlength="255"
                        placeholder="Describe the image"
                    >
                </div>
            </div>

            <button
                class="crm-button"
                type="submit"
            >
                Upload image
            </button>
        </form>

        <?php if ($gallery): ?>
            <div class="crm-calendar-grid">
                <?php foreach ($gallery as $imagePath): ?>
                    <article class="crm-day">
                        <img
                            src="<?= h(wz_app_url((string)$imagePath)) ?>"
                            alt="Portfolio"
                            style="width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 10px;"
                        >
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

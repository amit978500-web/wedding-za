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

$eventTypes = wz_data('event_types');
$cities = wz_data('cities');

$budgetOptions = [
    'Under ₹5 lakh',
    '₹5–15 lakh',
    '₹15–30 lakh',
    '₹30–60 lakh',
    '₹60 lakh+',
];

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
        $result = wz_crm_update_customer_profile(
            $userId,
            $_POST
        );

        $message = (string)$result['message'];
        $isSuccess = (bool)$result['ok'];

        if ($isSuccess) {
            $crmUser['name'] = (string)$result['name'];

            wz_set_user_session([
                'id' => $userId,
                'name' => (string)$result['name'],
                'email' => (string)$crmUser['email'],
                'role' => 'host',
            ]);

            wz_audit(
                'crm.customer_profile.updated',
                'customer_profile',
                $userId
            );
        }
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

        $serviceAreas = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)($_POST['service_areas'] ?? '')
                    )
                )
            )
        );

        $packages = array_values(
            array_filter(
                array_map(
                    'trim',
                    preg_split(
                        '/[\r\n]+/',
                        (string)($_POST['packages'] ?? '')
                    ) ?: []
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
                about,
                service_areas_json,
                packages_json,
                availability_enabled
            ) VALUES (
                :user_id,
                :business_name,
                :category,
                :city,
                :events_json,
                :starting_price,
                :portfolio_url,
                :about,
                :service_areas_json,
                :packages_json,
                :availability_enabled
            )
            ON DUPLICATE KEY UPDATE
                business_name = VALUES(business_name),
                category = VALUES(category),
                city = VALUES(city),
                events_json = VALUES(events_json),
                starting_price = VALUES(starting_price),
                portfolio_url = VALUES(portfolio_url),
                about = VALUES(about),
                service_areas_json = VALUES(service_areas_json),
                packages_json = VALUES(packages_json),
                availability_enabled = VALUES(availability_enabled)'
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
            'service_areas_json' => json_encode($serviceAreas),
            'packages_json' => json_encode($packages),
            'availability_enabled' => !empty($_POST['availability_enabled']) ? 1 : 0,
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

        $amenities = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)($_POST['amenities'] ?? '')
                    )
                )
            )
        );

        $spaces = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string)($_POST['spaces'] ?? '')
                    )
                )
            )
        );

        $policies = [
            'alcohol' => trim((string)($_POST['alcohol_policy'] ?? '')),
            'decor' => trim((string)($_POST['decor_policy'] ?? '')),
            'food' => trim((string)($_POST['food_policy'] ?? '')),
        ];

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
                events_json,
                price_per_plate_veg,
                price_per_plate_nonveg,
                rental_price,
                parking_capacity,
                video_url,
                spaces_json,
                amenities_json,
                policies_json
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
                :events_json,
                :price_per_plate_veg,
                :price_per_plate_nonveg,
                :rental_price,
                :parking_capacity,
                :video_url,
                :spaces_json,
                :amenities_json,
                :policies_json
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
                events_json = VALUES(events_json),
                price_per_plate_veg = VALUES(price_per_plate_veg),
                price_per_plate_nonveg = VALUES(price_per_plate_nonveg),
                rental_price = VALUES(rental_price),
                parking_capacity = VALUES(parking_capacity),
                video_url = VALUES(video_url),
                spaces_json = VALUES(spaces_json),
                amenities_json = VALUES(amenities_json),
                policies_json = VALUES(policies_json)'
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
            'price_per_plate_veg' => !empty($_POST['price_per_plate_veg'])
                ? (float)$_POST['price_per_plate_veg']
                : null,
            'price_per_plate_nonveg' => !empty($_POST['price_per_plate_nonveg'])
                ? (float)$_POST['price_per_plate_nonveg']
                : null,
            'rental_price' => !empty($_POST['rental_price'])
                ? (float)$_POST['rental_price']
                : null,
            'parking_capacity' => !empty($_POST['parking_capacity'])
                ? (int)$_POST['parking_capacity']
                : null,
            'video_url' => trim((string)($_POST['video_url'] ?? '')),
            'spaces_json' => json_encode($spaces),
            'amenities_json' => json_encode($amenities),
            'policies_json' => json_encode($policies),
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
                <div class="crm-field full">
                    <span class="crm-eyebrow">
                        PERSONAL DETAILS
                    </span>
                </div>

                <div class="crm-field">
                    <label for="customerName">
                        Full name
                    </label>

                    <input
                        id="customerName"
                        name="name"
                        value="<?= h((string)$crmUser['name']) ?>"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="customerEmail">
                        Email
                    </label>

                    <input
                        id="customerEmail"
                        type="email"
                        value="<?= h((string)$crmUser['email']) ?>"
                        readonly
                    >
                </div>

                <div class="crm-field">
                    <label for="customerPhone">
                        Phone
                    </label>

                    <input
                        id="customerPhone"
                        name="phone"
                        value="<?= h((string)($profile['phone'] ?? '')) ?>"
                        placeholder="+91..."
                    >
                </div>

                <div class="crm-field">
                    <label for="customerCity">
                        City
                    </label>

                    <select
                        id="customerCity"
                        name="city"
                    >
                        <option value="">
                            Choose city
                        </option>

                        <?php foreach ($cities as $city): ?>
                            <option
                                value="<?= h((string)$city) ?>"
                                <?= (string)($profile['city'] ?? '') === (string)$city ? 'selected' : '' ?>
                            >
                                <?= h((string)$city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="crm-field full">
                    <span class="crm-eyebrow">
                        EVENT DETAILS
                    </span>
                </div>

                <div class="crm-field">
                    <label for="customerEvent">
                        Event type
                    </label>

                    <select
                        id="customerEvent"
                        name="event_type"
                    >
                        <option value="">
                            Choose event
                        </option>

                        <?php foreach ($eventTypes as $event): ?>
                            <?php
                            $eventName = (string)($event['name'] ?? '');
                            ?>

                            <option
                                value="<?= h($eventName) ?>"
                                <?= (string)($profile['event_type'] ?? '') === $eventName ? 'selected' : '' ?>
                            >
                                <?= h($eventName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                        min="1"
                        name="guest_count"
                        value="<?= h((string)($profile['guest_count'] ?? '')) ?>"
                        placeholder="Example: 250"
                    >
                </div>

                <div class="crm-field">
                    <label for="customerBudget">
                        Budget
                    </label>

                    <select
                        id="customerBudget"
                        name="budget"
                    >
                        <option value="">
                            Choose budget
                        </option>

                        <?php foreach ($budgetOptions as $budgetOption): ?>
                            <option
                                value="<?= h($budgetOption) ?>"
                                <?= (string)($profile['budget'] ?? '') === $budgetOption ? 'selected' : '' ?>
                            >
                                <?= h($budgetOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="crm-field full">
                    <label for="customerNotes">
                        Planning notes
                    </label>

                    <textarea
                        id="customerNotes"
                        name="notes"
                        placeholder="Tell us anything useful about the event."
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
                <div class="crm-field full">
                    <label for="vendorServiceAreas">
                        Service areas
                    </label>

                    <input
                        id="vendorServiceAreas"
                        name="service_areas"
                        value="<?= h(
                            implode(
                                ', ',
                                json_decode(
                                    (string)($profile['service_areas_json'] ?? '[]'),
                                    true
                                ) ?: []
                            )
                        ) ?>"
                        placeholder="Jaipur, Udaipur, Delhi NCR"
                    >
                </div>

                <div class="crm-field full">
                    <label for="vendorPackages">
                        Packages
                    </label>

                    <textarea
                        id="vendorPackages"
                        name="packages"
                        placeholder="One package per line"
                    ><?= h(
                        implode(
                            "\n",
                            json_decode(
                                (string)($profile['packages_json'] ?? '[]'),
                                true
                            ) ?: []
                        )
                    ) ?></textarea>
                </div>

                <div class="crm-field full">
                    <label>
                        <input
                            type="checkbox"
                            name="availability_enabled"
                            value="1"
                            <?= !isset($profile['availability_enabled']) || !empty($profile['availability_enabled']) ? 'checked' : '' ?>
                        >
                        Show availability calendar in CRM
                    </label>
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

                <div class="crm-field">
                    <label for="venueVegPrice">Veg price / plate</label>
                    <input
                        id="venueVegPrice"
                        type="number"
                        min="0"
                        step="1"
                        name="price_per_plate_veg"
                        value="<?= h((string)($profile['price_per_plate_veg'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueNonVegPrice">Non-veg price / plate</label>
                    <input
                        id="venueNonVegPrice"
                        type="number"
                        min="0"
                        step="1"
                        name="price_per_plate_nonveg"
                        value="<?= h((string)($profile['price_per_plate_nonveg'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueRentalPrice">Venue rental</label>
                    <input
                        id="venueRentalPrice"
                        type="number"
                        min="0"
                        step="1"
                        name="rental_price"
                        value="<?= h((string)($profile['rental_price'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueParking">Parking capacity</label>
                    <input
                        id="venueParking"
                        type="number"
                        min="0"
                        name="parking_capacity"
                        value="<?= h((string)($profile['parking_capacity'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venueSpaces">Event spaces</label>
                    <input
                        id="venueSpaces"
                        name="spaces"
                        value="<?= h(
                            implode(
                                ', ',
                                json_decode(
                                    (string)($profile['spaces_json'] ?? '[]'),
                                    true
                                ) ?: []
                            )
                        ) ?>"
                        placeholder="Grand Ballroom, Pool Lawn, Rooftop"
                    >
                </div>

                <div class="crm-field full">
                    <label for="venueAmenities">Amenities</label>
                    <input
                        id="venueAmenities"
                        name="amenities"
                        value="<?= h(
                            implode(
                                ', ',
                                json_decode(
                                    (string)($profile['amenities_json'] ?? '[]'),
                                    true
                                ) ?: []
                            )
                        ) ?>"
                        placeholder="Parking, Valet, Rooms, Pool, Bridal room"
                    >
                </div>

                <?php
                $venuePolicies = json_decode(
                    (string)($profile['policies_json'] ?? '{}'),
                    true
                ) ?: [];
                ?>

                <div class="crm-field">
                    <label for="venueAlcoholPolicy">Alcohol policy</label>
                    <input
                        id="venueAlcoholPolicy"
                        name="alcohol_policy"
                        value="<?= h((string)($venuePolicies['alcohol'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueDecorPolicy">Decor policy</label>
                    <input
                        id="venueDecorPolicy"
                        name="decor_policy"
                        value="<?= h((string)($venuePolicies['decor'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueFoodPolicy">Food policy</label>
                    <input
                        id="venueFoodPolicy"
                        name="food_policy"
                        value="<?= h((string)($venuePolicies['food'] ?? '')) ?>"
                    >
                </div>

                <div class="crm-field">
                    <label for="venueVideo">Video URL</label>
                    <input
                        id="venueVideo"
                        type="url"
                        name="video_url"
                        value="<?= h((string)($profile['video_url'] ?? '')) ?>"
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

<section class="crm-panel wz-member-profile-section">
    <div class="crm-panel-head">
        <div>
            <h2>
                My Wedding Za card
            </h2>

            <p>
                Your digital CRM identity updates automatically from this profile.
            </p>
        </div>

        <button
            class="crm-button secondary small"
            type="button"
            data-wz-card-open
        >
            Open full card ↗
        </button>
    </div>

    <div class="crm-form">
        <?= wz_crm_member_card_html(
            $wzMemberCard,
            'profile'
        ) ?>
    </div>

    <div class="wz-member-inline-actions">
        <button
            class="wz-member-inline-action"
            type="button"
            data-wz-card-open
        >
            View premium card
        </button>
    </div>
</section>

<?php if (in_array($crmRole, ['vendor', 'venue'], true) && !empty($profile)): ?>
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

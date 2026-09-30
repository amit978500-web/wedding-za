<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';

$pageTitle = 'Wedding Venues';
$pageDescription = 'Compare wedding venues across India by city, venue type, guest capacity, rooms, rating and price.';
$pageKey = 'venues';

$city = trim((string)($_GET['city'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$minGuests = max(0, (int)($_GET['guests'] ?? 0));
$minRooms = max(0, (int)($_GET['rooms'] ?? 0));
$maxPrice = max(0, (int)($_GET['max_price'] ?? 0));
$minRating = max(0, min(5, (float)($_GET['rating'] ?? 0)));
$sort = (string)($_GET['sort'] ?? 'featured');

$venues = array_values(
    array_filter(
        wz_public_vendors(),
        function (array $venue) use (
            $city,
            $type,
            $minGuests,
            $minRooms,
            $maxPrice,
            $minRating
        ): bool {
            $isVenue =
                ($venue['business_type'] ?? '') === 'venue'
                || ($venue['category'] ?? '') === 'Venues';

            if (!$isVenue) {
                return false;
            }

            if (
                $city !== ''
                && strcasecmp(
                    (string)($venue['city'] ?? ''),
                    $city
                ) !== 0
            ) {
                return false;
            }

            if (
                $type !== ''
                && stripos(
                    (string)($venue['venue_type'] ?? ''),
                    $type
                ) === false
                && stripos(
                    implode(
                        ' ',
                        $venue['services'] ?? []
                    ),
                    $type
                ) === false
            ) {
                return false;
            }

            $capacityMax = (int)($venue['capacity_max'] ?? 0);

            if (
                $minGuests > 0
                && $capacityMax > 0
                && $capacityMax < $minGuests
            ) {
                return false;
            }

            if (
                $minRooms > 0
                && (int)($venue['rooms'] ?? 0) < $minRooms
            ) {
                return false;
            }

            if (
                $maxPrice > 0
                && wz_money_number(
                    (string)($venue['price'] ?? '0')
                ) > $maxPrice
            ) {
                return false;
            }

            if (
                $minRating > 0
                && (float)($venue['rating'] ?? 0) < $minRating
            ) {
                return false;
            }

            return true;
        }
    )
);

usort(
    $venues,
    function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'rating' =>
                (float)($b['rating'] ?? 0)
                <=> (float)($a['rating'] ?? 0),
            'price' =>
                wz_money_number((string)($a['price'] ?? '0'))
                <=> wz_money_number((string)($b['price'] ?? '0')),
            'capacity' =>
                (int)($b['capacity_max'] ?? 0)
                <=> (int)($a['capacity_max'] ?? 0),
            default =>
                (int)!empty($b['featured'])
                <=> (int)!empty($a['featured']),
        };
    }
);

$venueTypes = [];

foreach (wz_public_vendors() as $venue) {
    if (
        ($venue['business_type'] ?? '') === 'venue'
        || ($venue['category'] ?? '') === 'Venues'
    ) {
        $venueType = trim(
            (string)($venue['venue_type'] ?? '')
        );

        if ($venueType !== '') {
            $venueTypes[] = $venueType;
        }
    }
}

$venueTypes = array_values(
    array_unique($venueTypes)
);

sort($venueTypes);

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'VENUES',
        'Find the place that<br><em>shapes the wedding.</em>',
        'Search by city, scale, rooms, venue type, rating and starting price. Save the strongest options, then compare them side by side.'
    );
    ?>

    <section class="section marketplace-filter-shell">
        <div class="container">
            <form method="get" class="marketplace-filter-card">
                <div class="marketplace-filter-grid">
                    <label>
                        <span>City</span>
                        <select name="city">
                            <option value="">All cities</option>
                            <?php foreach (wz_data('cities') as $option): ?>
                                <option
                                    value="<?= h((string)$option) ?>"
                                    <?= $city === (string)$option ? 'selected' : '' ?>
                                >
                                    <?= h((string)$option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Venue type</span>
                        <select name="type">
                            <option value="">All venue types</option>
                            <?php foreach ($venueTypes as $option): ?>
                                <option
                                    value="<?= h($option) ?>"
                                    <?= $type === $option ? 'selected' : '' ?>
                                >
                                    <?= h($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <span>Guests</span>
                        <input
                            name="guests"
                            type="number"
                            min="0"
                            value="<?= h($minGuests > 0 ? (string)$minGuests : '') ?>"
                            placeholder="250"
                        >
                    </label>

                    <label>
                        <span>Rooms</span>
                        <input
                            name="rooms"
                            type="number"
                            min="0"
                            value="<?= h($minRooms > 0 ? (string)$minRooms : '') ?>"
                            placeholder="40"
                        >
                    </label>

                    <label>
                        <span>Max starting price</span>
                        <input
                            name="max_price"
                            type="number"
                            min="0"
                            value="<?= h($maxPrice > 0 ? (string)$maxPrice : '') ?>"
                            placeholder="500000"
                        >
                    </label>

                    <label>
                        <span>Min rating</span>
                        <select name="rating">
                            <option value="">Any rating</option>
                            <?php foreach ([4.5,4,3.5,3] as $rating): ?>
                                <option
                                    value="<?= h((string)$rating) ?>"
                                    <?= (float)$minRating === (float)$rating ? 'selected' : '' ?>
                                >
                                    <?= h((string)$rating) ?>+
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="vendor-filter-top" style="margin-top:16px;">
                    <label style="display:grid;gap:7px;">
                        <span class="eyebrow">SORT RESULTS</span>
                        <select name="sort">
                            <option value="featured" <?= $sort==='featured'?'selected':'' ?>>
                                Wedding Za edit
                            </option>
                            <option value="rating" <?= $sort==='rating'?'selected':'' ?>>
                                Highest rated
                            </option>
                            <option value="price" <?= $sort==='price'?'selected':'' ?>>
                                Lowest starting price
                            </option>
                            <option value="capacity" <?= $sort==='capacity'?'selected':'' ?>>
                                Highest capacity
                            </option>
                        </select>
                    </label>

                    <button class="pill-btn wine" type="submit">
                        Find venues ↗
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">VENUE RESULTS</span>
                    <h2>
                        <?= h((string)count($venues)) ?>
                        place<?= count($venues)===1?'':'s' ?>
                        worth opening.
                    </h2>
                </div>

                <a class="text-link" href="compare.php">
                    Compare venues ↗
                </a>
            </div>

            <div class="vendor-grid vendor-grid-v2">
                <?php foreach ($venues as $venue): ?>
                    <?php wz_vendor_card($venue); ?>
                <?php endforeach; ?>
            </div>

            <?php if (!$venues): ?>
                <div class="empty-state">
                    <h3>No exact venue match yet.</h3>
                    <p class="muted">
                        Relax one filter or search another city.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';

$pageTitle = 'Compare Wedding Venues';
$pageDescription = 'Compare wedding venues side by side by price, rating, capacity, rooms, parking and venue type.';
$pageKey = 'compare';

$ids = array_values(
    array_filter(
        array_map(
            'trim',
            explode(
                ',',
                (string)($_GET['ids'] ?? '')
            )
        )
    )
);

$ids = array_slice(
    array_unique($ids),
    0,
    4
);

$venues = [];

foreach (wz_public_vendors() as $venue) {
    $isVenue =
        ($venue['business_type'] ?? '') === 'venue'
        || ($venue['category'] ?? '') === 'Venues';

    if (
        $isVenue
        && in_array(
            (string)$venue['id'],
            $ids,
            true
        )
    ) {
        $venues[(string)$venue['id']] = $venue;
    }
}

$ordered = [];

foreach ($ids as $id) {
    if (isset($venues[$id])) {
        $ordered[] = $venues[$id];
    }
}

$venues = $ordered;

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'COMPARE',
        'Put the serious options<br><em>next to each other.</em>',
        'Compare up to four venues using the details that usually decide the shortlist.'
    );
    ?>

    <section class="section">
        <div class="container">
            <?php if ($venues): ?>
                <div class="marketplace-compare-table">
                    <table>
                        <tbody>
                            <tr>
                                <th>Venue</th>
                                <?php foreach ($venues as $venue): ?>
                                    <td>
                                        <img
                                            src="<?= h((string)$venue['image']) ?>"
                                            alt="<?= h((string)$venue['name']) ?>"
                                        >
                                        <h3><?= h((string)$venue['name']) ?></h3>
                                        <a
                                            class="text-link"
                                            href="vendor.php?id=<?= urlencode((string)$venue['id']) ?>"
                                        >
                                            Open profile ↗
                                        </a>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <?php
                            $rows = [
                                'Location' => fn (array $v): string =>
                                    trim(
                                        (string)($v['locality'] ?? '')
                                        . ', '
                                        . (string)($v['city'] ?? ''),
                                        ', '
                                    ),
                                'Rating' => fn (array $v): string =>
                                    (string)($v['rating'] ?? 0)
                                    . ' ★ · '
                                    . (string)($v['reviews'] ?? 0)
                                    . ' reviews',
                                'Starting price' => fn (array $v): string =>
                                    (string)($v['price'] ?? 'Ask venue'),
                                'Venue type' => fn (array $v): string =>
                                    (string)($v['venue_type'] ?? 'Venue'),
                                'Capacity' => fn (array $v): string =>
                                    (string)($v['capacity'] ?? 'Ask venue'),
                                'Rooms' => fn (array $v): string =>
                                    !empty($v['rooms'])
                                        ? (string)$v['rooms']
                                        : 'Ask venue',
                                'Parking' => fn (array $v): string =>
                                    !empty($v['parking_capacity'])
                                        ? (string)$v['parking_capacity'].' cars'
                                        : 'Ask venue',
                                'Veg / plate' => fn (array $v): string =>
                                    !empty($v['price_per_plate_veg'])
                                        ? '₹'.number_format((float)$v['price_per_plate_veg'])
                                        : 'Ask venue',
                                'Non-veg / plate' => fn (array $v): string =>
                                    !empty($v['price_per_plate_nonveg'])
                                        ? '₹'.number_format((float)$v['price_per_plate_nonveg'])
                                        : 'Ask venue',
                                'Rental' => fn (array $v): string =>
                                    !empty($v['rental_price'])
                                        ? '₹'.number_format((float)$v['rental_price'])
                                        : 'Ask venue',
                            ];
                            ?>

                            <?php foreach ($rows as $label => $resolver): ?>
                                <tr>
                                    <th><?= h($label) ?></th>
                                    <?php foreach ($venues as $venue): ?>
                                        <td><?= h($resolver($venue)) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No venues selected.</h3>
                    <p class="muted">
                        Open the Venues page and add up to four places to compare.
                    </p>
                    <a class="pill-btn wine" href="venues.php">
                        Find venues ↗
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/marketplace.php';

function wz_vendor_fallback_image(string $category): string
{
    $categoryData = wz_category_by_name($category);

    if (
        is_array($categoryData)
        && !empty($categoryData['image'])
    ) {
        return (string)$categoryData['image'];
    }

    return 'https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1400&q=92';
}

function wz_database_vendor_to_card(array $profile): array
{
    $events = json_decode(
        (string)($profile['events_json'] ?? '[]'),
        true
    );

    if (!is_array($events)) {
        $events = [];
    }

    $gallery = json_decode(
        (string)($profile['gallery_json'] ?? '[]'),
        true
    );

    if (!is_array($gallery)) {
        $gallery = [];
    }

    $heroImage = trim(
        (string)($profile['hero_image_url'] ?? '')
    );

    if ($heroImage === '') {
        $heroImage = $gallery[0]
            ?? wz_vendor_fallback_image(
                (string)($profile['category'] ?? '')
            );
    }

    $images = $gallery;

    if (!in_array(
        $heroImage,
        $images,
        true
    )) {
        array_unshift(
            $images,
            $heroImage
        );
    }

    $reviewSummary = wz_marketplace_review_summary(
        (int)$profile['user_id'],
        'vendor'
    );

    $serviceAreas = json_decode(
        (string)($profile['service_areas_json'] ?? '[]'),
        true
    );

    if (!is_array($serviceAreas)) {
        $serviceAreas = [];
    }

    $packages = json_decode(
        (string)($profile['packages_json'] ?? '[]'),
        true
    );

    if (!is_array($packages)) {
        $packages = [];
    }

    return [
        'id' => 'db-vendor-' . (string)$profile['id'],
        'name' => (string)$profile['business_name'],
        'category' => (string)($profile['category'] ?? 'Event Business'),
        'city' => (string)($profile['city'] ?? ''),
        'locality' => (string)($profile['city'] ?? ''),
        'rating' => $reviewSummary['rating'],
        'reviews' => $reviewSummary['count'],
        'price' => (string)(
            $profile['starting_price']
            ?: 'Ask for pricing'
        ),
        'tag' => wz_database_business_tag($profile),
        'image' => $heroImage,
        'images' => $images,
        'about' => (string)(
            $profile['about']
            ?: 'Event professional listed on Wedding Za.'
        ),
        'services' => [],
        'capacity' => 'Ask vendor',
        'experience' => 'Ask vendor',
        'policy' => 'Ask vendor',
        'verified' => true,
        'events' => array_values(
            array_filter(
                array_map(
                    'strval',
                    $events
                )
            )
        ),
        'featured' => wz_database_business_is_featured($profile),
        'plan' => (string)($profile['plan'] ?? 'free'),
        'billing_status' => (string)($profile['billing_status'] ?? 'inactive'),
        'database_profile_id' => (int)$profile['id'],
        'database_user_id' => (int)$profile['user_id'],
        'business_type' => 'vendor',
        'service_areas' => array_values($serviceAreas),
        'packages' => array_values($packages),
        'availability_enabled' => !isset($profile['availability_enabled'])
            || !empty($profile['availability_enabled']),
    ];
}

function wz_database_venue_to_card(array $profile): array
{
    $events = json_decode(
        (string)($profile['events_json'] ?? '[]'),
        true
    );

    if (!is_array($events)) {
        $events = [];
    }

    $gallery = json_decode(
        (string)($profile['gallery_json'] ?? '[]'),
        true
    );

    if (!is_array($gallery)) {
        $gallery = [];
    }

    $heroImage = trim(
        (string)($profile['hero_image_url'] ?? '')
    );

    if ($heroImage === '') {
        $heroImage = $gallery[0]
            ?? wz_vendor_fallback_image('Venues');
    }

    $images = $gallery;

    if (!in_array(
        $heroImage,
        $images,
        true
    )) {
        array_unshift(
            $images,
            $heroImage
        );
    }

    $capacity = 'Ask venue';

    if (
        !empty($profile['capacity_min'])
        || !empty($profile['capacity_max'])
    ) {
        $capacity = trim(
            (string)($profile['capacity_min'] ?: '')
            . '–'
            . (string)($profile['capacity_max'] ?: '')
            . ' guests',
            '– '
        );
    }

    $services = [];

    if (!empty($profile['venue_type'])) {
        $services[] = (string)$profile['venue_type'];
    }

    if (!empty($profile['rooms'])) {
        $services[] = (string)$profile['rooms'] . ' rooms';
    }

    $reviewSummary = wz_marketplace_review_summary(
        (int)$profile['user_id'],
        'venue'
    );

    $amenities = json_decode(
        (string)($profile['amenities_json'] ?? '[]'),
        true
    );

    if (!is_array($amenities)) {
        $amenities = [];
    }

    $policies = json_decode(
        (string)($profile['policies_json'] ?? '{}'),
        true
    );

    if (!is_array($policies)) {
        $policies = [];
    }

    return [
        'id' => 'db-venue-' . (string)$profile['id'],
        'name' => (string)$profile['venue_name'],
        'category' => 'Venues',
        'city' => (string)($profile['city'] ?? ''),
        'locality' => (string)($profile['locality'] ?? ''),
        'rating' => $reviewSummary['rating'],
        'reviews' => $reviewSummary['count'],
        'price' => (string)(
            $profile['starting_price']
            ?: 'Ask for pricing'
        ),
        'tag' => wz_database_business_tag($profile),
        'image' => $heroImage,
        'images' => $images,
        'about' => (string)(
            $profile['about']
            ?: 'Venue listed on Wedding Za.'
        ),
        'services' => $services,
        'capacity' => $capacity,
        'experience' => 'Venue CRM member',
        'policy' => 'Ask venue',
        'verified' => true,
        'events' => array_values(
            array_filter(
                array_map(
                    'strval',
                    $events
                )
            )
        ),
        'featured' => wz_database_business_is_featured($profile),
        'plan' => (string)($profile['plan'] ?? 'free'),
        'billing_status' => (string)($profile['billing_status'] ?? 'inactive'),
        'database_profile_id' => (int)$profile['id'],
        'database_user_id' => (int)$profile['user_id'],
        'business_type' => 'venue',
        'rooms' => (int)($profile['rooms'] ?? 0),
        'capacity_min' => (int)($profile['capacity_min'] ?? 0),
        'capacity_max' => (int)($profile['capacity_max'] ?? 0),
        'venue_type' => (string)($profile['venue_type'] ?? ''),
        'price_per_plate_veg' => (float)($profile['price_per_plate_veg'] ?? 0),
        'price_per_plate_nonveg' => (float)($profile['price_per_plate_nonveg'] ?? 0),
        'rental_price' => (float)($profile['rental_price'] ?? 0),
        'parking_capacity' => (int)($profile['parking_capacity'] ?? 0),
        'video_url' => (string)($profile['video_url'] ?? ''),
        'spaces' => array_values(
            json_decode(
                (string)($profile['spaces_json'] ?? '[]'),
                true
            ) ?: []
        ),
        'amenities' => array_values($amenities),
        'policies' => $policies,
    ];
}

function wz_database_business_tag(array $profile): string
{
    if (($profile['plan'] ?? 'free') === 'pro') {
        return 'Wedding Za Pro';
    }

    if (
        !empty($profile['featured'])
        || ($profile['plan'] ?? 'free') === 'featured'
    ) {
        return 'Wedding Za featured';
    }

    return 'Verified business';
}

function wz_database_business_is_featured(array $profile): bool
{
    return !empty($profile['featured'])
        || in_array(
            (string)($profile['plan'] ?? 'free'),
            [
                'featured',
                'pro',
            ],
            true
        );
}

function wz_approved_database_vendors(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    try {
        $statement = $pdo->query(
            "SELECT *
             FROM vendor_profiles
             WHERE approval_status = 'approved'
             ORDER BY featured DESC, updated_at DESC"
        );

        return array_map(
            'wz_database_vendor_to_card',
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        error_log(
            'Wedding Za vendor query failed: '
            . $exception->getMessage()
        );

        return [];
    }
}

function wz_approved_database_venues(): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    try {
        $statement = $pdo->query(
            "SELECT *
             FROM venue_profiles
             WHERE approval_status = 'approved'
             ORDER BY featured DESC, updated_at DESC"
        );

        return array_map(
            'wz_database_venue_to_card',
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        error_log(
            'Wedding Za venue query failed: '
            . $exception->getMessage()
        );

        return [];
    }
}

function wz_approved_database_businesses(): array
{
    return array_merge(
        wz_approved_database_venues(),
        wz_approved_database_vendors()
    );
}

function wz_public_vendors(): array
{
    $staticVendors = wz_data('vendors');
    $databaseVendors = wz_approved_database_businesses();

    if (!$databaseVendors) {
        return $staticVendors;
    }

    $databaseByName = [];

    foreach ($databaseVendors as $vendor) {
        $databaseByName[
            strtolower(
                trim(
                    (string)$vendor['name']
                )
            )
        ] = $vendor;
    }

    $merged = [];

    foreach ($staticVendors as $staticVendor) {
        $key = strtolower(
            trim(
                (string)$staticVendor['name']
            )
        );

        if (isset($databaseByName[$key])) {
            $databaseVendor = $databaseByName[$key];

            $databaseVendor['id'] = $staticVendor['id'];
            $databaseVendor['rating'] = $staticVendor['rating'] ?? 0;
            $databaseVendor['reviews'] = $staticVendor['reviews'] ?? 0;
            $databaseVendor['services'] = array_values(
                array_unique(
                    array_merge(
                        $databaseVendor['services'] ?? [],
                        $staticVendor['services'] ?? []
                    )
                )
            );

            if (
                empty($databaseVendor['capacity'])
                || $databaseVendor['capacity'] === 'Ask vendor'
            ) {
                $databaseVendor['capacity'] =
                    $staticVendor['capacity']
                    ?? 'Ask vendor';
            }

            $databaseVendor['experience'] =
                $staticVendor['experience']
                ?? $databaseVendor['experience'];

            $databaseVendor['policy'] =
                $staticVendor['policy']
                ?? $databaseVendor['policy'];

            if (!$databaseVendor['events']) {
                $databaseVendor['events'] =
                    $staticVendor['events']
                    ?? [];
            }

            $merged[] = $databaseVendor;

            unset(
                $databaseByName[$key]
            );

            continue;
        }

        $merged[] = $staticVendor;
    }

    foreach ($databaseByName as $vendor) {
        $merged[] = $vendor;
    }

    usort(
        $merged,
        function (array $left, array $right): int {
            $leftFeatured = !empty($left['featured'])
                ? 1
                : 0;

            $rightFeatured = !empty($right['featured'])
                ? 1
                : 0;

            return $rightFeatured <=> $leftFeatured;
        }
    );

    return $merged;
}

function wz_public_vendor(string $id): ?array
{
    foreach (wz_public_vendors() as $vendor) {
        if (
            (string)$vendor['id']
            === $id
        ) {
            return $vendor;
        }
    }

    return null;
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

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

    return [
        'id' => 'db-' . (string)$profile['id'],
        'name' => (string)$profile['business_name'],
        'category' => (string)($profile['category'] ?? 'Event Business'),
        'city' => (string)($profile['city'] ?? ''),
        'locality' => (string)($profile['city'] ?? ''),
        'rating' => 0,
        'reviews' => 0,
        'price' => (string)(
            $profile['starting_price']
            ?: 'Ask for pricing'
        ),
        'tag' => !empty($profile['featured'])
            ? 'Wedding Za featured'
            : 'Verified business',
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
        'featured' => !empty($profile['featured']),
        'database_profile_id' => (int)$profile['id'],
        'database_user_id' => (int)$profile['user_id'],
    ];
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
            'Wedding Za vendor query failed: ' .
            $exception->getMessage()
        );

        return [];
    }
}

function wz_public_vendors(): array
{
    $staticVendors = wz_data('vendors');
    $databaseVendors = wz_approved_database_vendors();

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
            $databaseVendor['services'] = $staticVendor['services'] ?? [];
            $databaseVendor['capacity'] = $staticVendor['capacity'] ?? 'Ask vendor';
            $databaseVendor['experience'] = $staticVendor['experience'] ?? 'Ask vendor';
            $databaseVendor['policy'] = $staticVendor['policy'] ?? 'Ask vendor';

            if (!$databaseVendor['events']) {
                $databaseVendor['events'] = $staticVendor['events'] ?? [];
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

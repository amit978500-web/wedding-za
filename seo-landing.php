<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';

$type = (string)($_GET['type'] ?? 'venues');
$citySlug = trim((string)($_GET['city'] ?? ''));
$categorySlug = trim((string)($_GET['category'] ?? ''));

$city = null;

foreach (wz_data('cities') as $candidate) {
    if (wz_slug((string)$candidate) === $citySlug) {
        $city = (string)$candidate;
        break;
    }
}

$category = null;

if ($type === 'vendors') {
    foreach (wz_data('categories') as $candidate) {
        $name = (string)($candidate['name'] ?? '');

        if (wz_slug($name) === $categorySlug) {
            $category = $name;
            break;
        }
    }
}

if (
    $city === null
    || (
        $type === 'vendors'
        && $category === null
    )
) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$isVenueLanding = $type === 'venues';

$pageTitle = $isVenueLanding
    ? 'Wedding Venues in ' . $city
    : $category . ' in ' . $city;

$pageDescription = $isVenueLanding
    ? 'Compare wedding venues in '
        . $city
        . ' by rating, starting price, guest capacity, rooms and venue type on Wedding Za.'
    : 'Discover '
        . strtolower((string)$category)
        . ' in '
        . $city
        . ' with portfolio, pricing context, reviews and enquiry tools on Wedding Za.';

$pageKey = $isVenueLanding
    ? 'venues'
    : 'vendors';

$useBaseHref = true;

$businesses = array_values(
    array_filter(
        wz_public_vendors(),
        function (array $business) use (
            $city,
            $category,
            $isVenueLanding
        ): bool {
            if (
                strcasecmp(
                    (string)($business['city'] ?? ''),
                    $city
                ) !== 0
            ) {
                return false;
            }

            $isVenue =
                ($business['business_type'] ?? '') === 'venue'
                || ($business['category'] ?? '') === 'Venues';

            if ($isVenueLanding) {
                return $isVenue;
            }

            return strcasecmp(
                (string)($business['category'] ?? ''),
                (string)$category
            ) === 0;
        }
    )
);

usort(
    $businesses,
    static function (
        array $a,
        array $b
    ): int {
        $ratingCompare =
            (float)($b['rating'] ?? 0)
            <=> (float)($a['rating'] ?? 0);

        if ($ratingCompare !== 0) {
            return $ratingCompare;
        }

        return (int)!empty($b['featured'])
            <=> (int)!empty($a['featured']);
    }
);

$faq = $isVenueLanding
    ? [
        [
            'question' => 'How should I shortlist wedding venues in ' . $city . '?',
            'answer' => 'Start with guest capacity, event spaces, rooms, food and rental pricing, location logistics, policies and date availability. Then compare only the venues that fit the same event brief.',
        ],
        [
            'question' => 'Can I compare venues on Wedding Za?',
            'answer' => 'Yes. Save or add up to four venue profiles to the Wedding Za comparison view and review price, capacity, rooms, parking and other available details side by side.',
        ],
        [
            'question' => 'Can Wedding Za help with venue enquiries and site visits?',
            'answer' => 'Wedding Za includes customer enquiry and site-visit workflows, and the One Wedding Venue Assistance plan adds curated venue search and dedicated Weddingza support.',
        ],
    ]
    : [
        [
            'question' => 'How do I choose ' . strtolower((string)$category) . ' in ' . $city . '?',
            'answer' => 'Compare event fit, portfolio consistency, starting price, reviews, packages, availability and communication before requesting a final quote.',
        ],
        [
            'question' => 'Can I save vendors before contacting them?',
            'answer' => 'Yes. Wedding Za shortlists keep serious options together so you can compare them against your wedding requirements before sending enquiries.',
        ],
        [
            'question' => 'Can vendors send quotes through Wedding Za?',
            'answer' => 'Businesses using Wedding Za CRM can send quotes tied to customer enquiries, and customers can review and respond from their account.',
        ],
    ];

$structuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $pageTitle,
        'numberOfItems' => count($businesses),
        'itemListElement' => array_map(
            static function (
                array $business,
                int $index
            ): array {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => wz_app_url(
                        'vendor.php?id='
                        . urlencode(
                            (string)$business['id']
                        )
                    ),
                    'name' => (string)$business['name'],
                ];
            },
            $businesses,
            array_keys($businesses)
        ),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(
            static fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ],
            $faq
        ),
    ],
];

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        $isVenueLanding
            ? strtoupper($city) . ' VENUES'
            : strtoupper((string)$category) . ' · ' . strtoupper($city),
        $isVenueLanding
            ? 'Wedding venues in<br><em>' . h($city) . '.</em>'
            : h((string)$category) . ' in<br><em>' . h($city) . '.</em>',
        $pageDescription
    );
    ?>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">LOCAL MARKETPLACE</span>
                    <h2>
                        <?= h((string)count($businesses)) ?>
                        <?= $isVenueLanding
                            ? 'venue'
                            : strtolower((string)$category) ?>
                        <?= count($businesses) === 1 ? '' : ' options' ?>
                    </h2>
                </div>

                <a
                    class="text-link"
                    href="<?= h(
                        wz_app_url(
                            $isVenueLanding
                                ? 'venues.php?city=' . urlencode($city)
                                : 'vendors.php?city='
                                    . urlencode($city)
                                    . '&category='
                                    . urlencode((string)$category)
                        )
                    ) ?>"
                >
                    Advanced filters ↗
                </a>
            </div>

            <div class="vendor-grid vendor-grid-v2">
                <?php foreach ($businesses as $business): ?>
                    <?php wz_vendor_card($business); ?>
                <?php endforeach; ?>
            </div>

            <?php if (!$businesses): ?>
                <div class="empty-state">
                    <h3>No approved profiles are live here yet.</h3>
                    <p class="muted">
                        Try the broader Wedding Za marketplace while this local category grows.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">PLANNING QUESTIONS</span>
                    <h2>Before you shortlist.</h2>
                </div>
            </div>

            <div class="service-grid-v2">
                <?php foreach ($faq as $index => $item): ?>
                    <div>
                        <span>
                            <?= str_pad(
                                (string)($index + 1),
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </span>
                        <strong><?= h($item['question']) ?></strong>
                        <p class="muted"><?= h($item['answer']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">KEEP EXPLORING</span>
                    <h2>More in <?= h($city) ?>.</h2>
                </div>
            </div>

            <div class="marketplace-chip-list">
                <a
                    class="pill-btn outline"
                    href="<?= h(
                        wz_app_url(
                            'wedding-venues/'
                            . wz_slug($city)
                            . '/'
                        )
                    ) ?>"
                >
                    Wedding Venues
                </a>

                <?php foreach (array_slice(wz_data('categories'), 0, 8) as $item): ?>
                    <?php
                    $name = (string)($item['name'] ?? '');

                    if ($name === '' || $name === 'Venues') {
                        continue;
                    }
                    ?>

                    <a
                        class="pill-btn outline"
                        href="<?= h(
                            wz_app_url(
                                'wedding-vendors/'
                                . wz_slug($city)
                                . '/'
                                . wz_slug($name)
                                . '/'
                            )
                        ) ?>"
                    >
                        <?= h($name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Wedding Za';
$pageDescription = $pageDescription
    ?? 'Discover venues, vendors, ideas and planning tools for every kind of celebration across India.';
$pageKey = $pageKey ?? '';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$canonicalUrl = wz_app_url(ltrim($requestUri, '/'));

$seoConfig = wz_config()['seo'] ?? [];
$analyticsConfig = wz_config()['analytics'] ?? [];

$pageImage = $pageImage
    ?? ($seoConfig['default_og_image'] ?? '');

$structuredData = $structuredData ?? [];

$baseStructuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => (string)($seoConfig['organization_name'] ?? 'Wedding Za'),
        'url' => wz_app_url(''),
        'email' => (string)($seoConfig['contact_email'] ?? ''),
        'telephone' => (string)($seoConfig['contact_phone'] ?? ''),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Wedding Za',
        'url' => wz_app_url(''),
    ],
];

$structuredData = array_merge(
    $baseStructuredData,
    is_array($structuredData)
        ? $structuredData
        : []
);

$analyticsId = trim(
    (string)($analyticsConfig['measurement_id'] ?? '')
);

$accountUrl = 'login.php?role=host';
$accountLabel = 'Log in';

if (wz_is_logged_in()) {
    if (wz_role() === 'vendor') {
        $accountUrl = 'vendor-dashboard.php';
        $accountLabel = 'Business workspace';
    } else {
        $accountUrl = 'account.php';
        $accountLabel = 'My account';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1,viewport-fit=cover"
    >

    <meta
        name="theme-color"
        content="#17120f"
    >

    <meta
        name="description"
        content="<?= h($pageDescription) ?>"
    >

    <link
        rel="canonical"
        href="<?= h($canonicalUrl) ?>"
    >

    <meta
        property="og:title"
        content="<?= h($pageTitle) ?> · Wedding Za"
    >

    <meta
        property="og:description"
        content="<?= h($pageDescription) ?>"
    >

    <meta
        property="og:type"
        content="website"
    >

    <meta
        property="og:url"
        content="<?= h($canonicalUrl) ?>"
    >

    <?php if ($pageImage !== ''): ?>
        <meta
            property="og:image"
            content="<?= h($pageImage) ?>"
        >
    <?php endif; ?>

    <?php foreach ($structuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode(
            array_filter(
                $schema,
                fn ($value): bool => $value !== ''
            ),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        ) ?></script>
    <?php endforeach; ?>

    <?php if ($analyticsId !== ''): ?>
        <script
            async
            src="https://www.googletagmanager.com/gtag/js?id=<?= h($analyticsId) ?>"
        ></script>

        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', <?= json_encode($analyticsId) ?>);
        </script>
    <?php endif; ?>

    <title><?= h($pageTitle) ?> · Wedding Za</title>

    <link
        rel="icon"
        href="assets/images/favicon.svg"
        type="image/svg+xml"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/app.css?v=4.0.0"
    >

    <link
        rel="stylesheet"
        href="assets/css/vision.css?v=4.0.0"
    >
</head>

<body
    data-page="<?= h($pageKey) ?>"
    class="vision-site"
>
    <div
        class="page-progress"
        id="pageProgress"
    ></div>

    <div
        class="page-wipe"
        id="pageWipe"
        aria-hidden="true"
    >
        <span>WZ</span>
    </div>

    <div
        class="preloader vision-preloader"
        id="preloader"
        aria-hidden="true"
    >
        <div class="vision-preloader-inner">
            <span class="vision-preloader-kicker">
                WEDDING ZA
            </span>

            <strong>
                For every reason
                <br>
                <em>to celebrate.</em>
            </strong>

            <i></i>
        </div>
    </div>

    <header
        class="vision-header"
        id="siteHeader"
    >
        <div class="container vision-nav">
            <a
                class="vision-brand"
                href="index.php"
                aria-label="Wedding Za home"
            >
                <span>W</span>
                <b>Wedding Za</b>
            </a>

            <nav
                class="vision-nav-links"
                aria-label="Primary navigation"
            >
                <a<?= wz_active('vendors.php') ?> href="vendors.php">
                    Vendors
                </a>

                <a<?= wz_active('inspiration.php') ?> href="inspiration.php">
                    Ideas
                </a>

                <a<?= wz_active('real-weddings.php') ?> href="real-weddings.php">
                    Celebrations
                </a>

                <a<?= wz_active('blog.php') ?> href="blog.php">
                    Journal
                </a>
            </nav>

            <div class="vision-nav-actions">
                <button
                    class="vision-discover"
                    type="button"
                    data-discovery-open
                >
                    Plan an event
                    <span>↗</span>
                </button>

                <a
                    class="vision-heart"
                    href="shortlist.php"
                    aria-label="Open shortlist"
                >
                    ♡
                    <b id="shortlistCount">0</b>
                </a>

                <button
                    class="vision-menu-btn"
                    id="menuToggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobileMenu"
                >
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <div
        class="vision-menu"
        id="mobileMenu"
        aria-hidden="true"
    >
        <div
            class="vision-menu-bg"
            aria-hidden="true"
        ></div>

        <div class="container vision-menu-grid">
            <div class="vision-menu-main">
                <small>EXPLORE</small>

                <a href="vendors.php">
                    <span>01</span>
                    Vendors
                </a>

                <a href="inspiration.php">
                    <span>02</span>
                    Ideas & inspiration
                </a>

                <a href="real-weddings.php">
                    <span>03</span>
                    Real celebrations
                </a>

                <a href="blog.php">
                    <span>04</span>
                    The journal
                </a>

                <a href="planner.php">
                    <span>05</span>
                    Planning studio
                </a>
            </div>

            <aside class="vision-menu-side">
                <div>
                    <small>POPULAR CITIES</small>

                    <a href="city.php?city=Jaipur">
                        Jaipur
                    </a>

                    <a href="city.php?city=Udaipur">
                        Udaipur
                    </a>

                    <a href="city.php?city=Goa">
                        Goa
                    </a>

                    <a href="city.php?city=Delhi%20NCR">
                        Delhi NCR
                    </a>
                </div>

                <div>
                    <small>YOUR SPACE</small>

                    <a href="shortlist.php">
                        Shortlist
                    </a>

                    <a href="invites.php">
                        E-invites
                    </a>

                    <a href="<?= h($accountUrl) ?>">
                        <?= h($accountLabel) ?>
                    </a>

                    <?php if (wz_role() !== 'vendor'): ?>
                        <a href="login.php?role=vendor">
                            Business login
                        </a>
                    <?php endif; ?>

                    <a href="register-vendor.php">
                        For event businesses ↗
                    </a>
                </div>
            </aside>
        </div>
    </div>

    <div
        class="discovery-panel"
        id="discoveryPanel"
        aria-hidden="true"
    >
        <button
            class="discovery-backdrop"
            type="button"
            data-discovery-close
            aria-label="Close vendor finder"
        ></button>

        <div
            class="discovery-dialog"
            role="dialog"
            aria-modal="true"
            aria-label="Find event vendors"
        >
            <div class="discovery-top">
                <span>BUILD YOUR EVENT TEAM</span>

                <button
                    type="button"
                    data-discovery-close
                >
                    Close ×
                </button>
            </div>

            <form
                action="vendors.php"
                method="get"
            >
                <label>
                    <span>
                        01 / What are you celebrating?
                    </span>

                    <select name="event">
                        <option value="">
                            Any celebration
                        </option>

                        <?php foreach (wz_data('event_types') as $event): ?>
                            <option>
                                <?= h($event['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>
                        02 / What do you need?
                    </span>

                    <select name="category">
                        <option value="">
                            All vendor categories
                        </option>

                        <?php foreach (wz_data('categories') as $category): ?>
                            <option>
                                <?= h($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>
                        03 / Where?
                    </span>

                    <select name="city">
                        <option value="">
                            All cities
                        </option>

                        <?php foreach (wz_data('cities') as $city): ?>
                            <option>
                                <?= h($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <button type="submit">
                    Build my shortlist
                    <span>↗</span>
                </button>
            </form>

            <div class="discovery-shortcuts">
                <span>Popular:</span>

                <a href="event.php?type=Wedding">
                    Wedding
                </a>

                <a href="event.php?type=Birthday">
                    Birthday
                </a>

                <a href="event.php?type=Corporate">
                    Corporate
                </a>

                <a href="city.php?city=Jaipur">
                    Jaipur
                </a>
            </div>
        </div>
    </div>

<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/vendors.php';

$pageTitle = 'Search';
$pageDescription = 'Search Wedding Za vendors, venues, planning guides and real celebrations.';
$pageKey = 'search';

$query = trim((string)($_GET['q'] ?? ''));
$needle = mb_strtolower($query);

$businesses = [];
$articles = [];
$weddings = [];

if ($needle !== '') {
    foreach (wz_public_vendors() as $business) {
        $haystack = mb_strtolower(
            implode(
                ' ',
                [
                    (string)($business['name'] ?? ''),
                    (string)($business['category'] ?? ''),
                    (string)($business['city'] ?? ''),
                    (string)($business['locality'] ?? ''),
                    implode(' ', $business['services'] ?? []),
                ]
            )
        );

        if (str_contains($haystack, $needle)) {
            $businesses[] = $business;
        }
    }

    foreach (wz_data('articles') as $article) {
        $haystack = mb_strtolower(
            (string)($article['title'] ?? '')
            . ' '
            . (string)($article['excerpt'] ?? '')
            . ' '
            . (string)($article['category'] ?? '')
        );

        if (str_contains($haystack, $needle)) {
            $articles[] = $article;
        }
    }

    foreach (wz_data('weddings') as $wedding) {
        $haystack = mb_strtolower(
            (string)($wedding['couple'] ?? '')
            . ' '
            . (string)($wedding['city'] ?? '')
            . ' '
            . (string)($wedding['theme'] ?? '')
            . ' '
            . (string)($wedding['title'] ?? '')
        );

        if (str_contains($haystack, $needle)) {
            $weddings[] = $wedding;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'SEARCH',
        'One search across<br><em>Wedding Za.</em>',
        'Find venues, vendors, guides and real celebrations without guessing which section to open.'
    );
    ?>

    <section class="section">
        <div class="container">
            <form class="marketplace-filter-card" method="get">
                <div class="field">
                    <label for="globalSearch">Search Wedding Za</label>
                    <input
                        id="globalSearch"
                        type="search"
                        name="q"
                        value="<?= h($query) ?>"
                        placeholder="Jaipur palace, photographer, decor ideas..."
                        autofocus
                    >
                </div>
                <button class="pill-btn wine" type="submit">
                    Search ↗
                </button>
            </form>
        </div>
    </section>

    <?php if ($query !== ''): ?>
        <section class="section paper-2">
            <div class="container marketplace-search-layout">
                <div class="marketplace-search-group">
                    <h2>Venues & vendors · <?= h((string)count($businesses)) ?></h2>

                    <?php foreach (array_slice($businesses,0,12) as $business): ?>
                        <article class="marketplace-search-result">
                            <img
                                src="<?= h((string)$business['image']) ?>"
                                alt="<?= h((string)$business['name']) ?>"
                            >
                            <div>
                                <strong><?= h((string)$business['name']) ?></strong>
                                <p class="muted">
                                    <?= h((string)$business['category']) ?>
                                    · <?= h((string)$business['city']) ?>
                                    · <?= h((string)$business['price']) ?>
                                </p>
                            </div>
                            <a
                                class="text-link"
                                href="vendor.php?id=<?= urlencode((string)$business['id']) ?>"
                            >
                                Open ↗
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="marketplace-search-group">
                    <h2>Planning guides · <?= h((string)count($articles)) ?></h2>

                    <div class="journal-grid">
                        <?php foreach (array_slice($articles,0,6) as $article): ?>
                            <?php wz_article_card($article); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="marketplace-search-group">
                    <h2>Real celebrations · <?= h((string)count($weddings)) ?></h2>

                    <div class="vendor-grid">
                        <?php foreach (array_slice($weddings,0,6) as $wedding): ?>
                            <?php wz_wedding_card($wedding); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

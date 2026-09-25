<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/content.php';

$id = (string)($_GET['id'] ?? 'jaipur-venues');
$article = wz_published_article($id);

if (!$article) {
    header('Location: 404.php');
    exit;
}

$pageTitle = (string)$article['title'];
$pageDescription = (string)$article['excerpt'];
$pageKey = 'article';

$bodyMap = [
    'jaipur-venues' => [
        'Start with the operational reality',
        'The prettiest venue can be the wrong venue if guest flow, access, parking, sound limits or service logistics do not work. Shortlist with a practical layer before you visit.',
        'Compare capacity function-by-function',
        'Ask for realistic seated and floating capacities for every space you plan to use. A dinner, conference, birthday or ceremony can consume the same room very differently.',
        'Treat logistics as part of the venue cost',
        'Transport, valet, rooms, loading access and overtime can change the economics quickly. Map the real event journey before comparing quotes.',
    ],
];

require __DIR__ . '/includes/header.php';

$isCmsArticle = ($article['source'] ?? '') === 'database';
$bodyBlocks = $isCmsArticle
    ? wz_article_body_blocks((string)($article['body'] ?? ''))
    : [];

$fallbackBody = $bodyMap[$id]
    ?? $bodyMap['jaipur-venues'];
?>

<main>
    <section class="article-hero">
        <div class="container article-hero-inner reveal">
            <span class="eyebrow">
                <?= h((string)$article['category']) ?>
            </span>

            <h1>
                <?= h((string)$article['title']) ?>
            </h1>

            <div class="article-meta">
                <span><?= h((string)$article['date']) ?></span>
                <span><?= h((string)$article['read']) ?></span>
                <span>Wedding Za Journal</span>
            </div>
        </div>

        <?php if (!empty($article['image'])): ?>
            <div class="container article-cover reveal">
                <img
                    src="<?= h((string)$article['image']) ?>"
                    alt="<?= h((string)$article['title']) ?>"
                >
            </div>
        <?php endif; ?>
    </section>

    <section class="section-sm">
        <div class="container article-layout">
            <aside class="article-aside">
                <div>
                    <small>SHARE THIS GUIDE</small>

                    <div class="article-share">
                        <button
                            type="button"
                            data-share
                        >
                            LINK
                        </button>
                    </div>
                </div>
            </aside>

            <article class="article-body">
                <p>
                    <strong>
                        <?= h((string)$article['excerpt']) ?>
                    </strong>
                </p>

                <?php if ($isCmsArticle): ?>
                    <?php foreach ($bodyBlocks as $block): ?>
                        <p>
                            <?= nl2br(h($block)) ?>
                        </p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <h2><?= h($fallbackBody[0]) ?></h2>
                    <p><?= h($fallbackBody[1]) ?></p>

                    <blockquote>
                        Make the practical decision first.
                        Then make it beautiful.
                    </blockquote>

                    <h2><?= h($fallbackBody[2]) ?></h2>
                    <p><?= h($fallbackBody[3]) ?></p>

                    <h2><?= h($fallbackBody[4]) ?></h2>
                    <p><?= h($fallbackBody[5]) ?></p>
                <?php endif; ?>
            </article>

            <aside class="article-related">
                <h3>Keep reading</h3>

                <?php
                $related = array_values(
                    array_filter(
                        wz_published_articles(),
                        fn (array $item): bool =>
                            (string)$item['id'] !== $id
                    )
                );
                ?>

                <?php foreach (array_slice($related, 0, 3) as $item): ?>
                    <a
                        class="related-mini"
                        href="article.php?id=<?= urlencode((string)$item['id']) ?>"
                    >
                        <span>
                            <?= h((string)$item['category']) ?>
                        </span>

                        <b>
                            <?= h((string)$item['title']) ?>
                        </b>
                    </a>
                <?php endforeach; ?>
            </aside>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

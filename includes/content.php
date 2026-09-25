<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function wz_published_articles(): array
{
    $fallback = wz_data('articles');
    $pdo = wz_db();

    if (!$pdo) {
        return $fallback;
    }

    try {
        $statement = $pdo->query(
            "SELECT
                slug,
                title,
                excerpt,
                body,
                image_url,
                published_at,
                created_at
             FROM content_entries
             WHERE content_type = 'article'
             AND status = 'published'
             ORDER BY
                COALESCE(published_at, created_at) DESC"
        );

        $databaseArticles = [];

        foreach ($statement->fetchAll() as $row) {
            $dateValue = (string)(
                $row['published_at']
                ?: $row['created_at']
            );

            $databaseArticles[] = [
                'id' => (string)$row['slug'],
                'category' => 'Journal',
                'title' => (string)$row['title'],
                'excerpt' => (string)$row['excerpt'],
                'date' => date(
                    'd M Y',
                    strtotime($dateValue)
                ),
                'read' => '6 min read',
                'image' => (string)$row['image_url'],
                'body' => (string)$row['body'],
                'source' => 'database',
            ];
        }

        $databaseSlugs = array_map(
            fn (array $item): string =>
                (string)$item['id'],
            $databaseArticles
        );

        $fallback = array_values(
            array_filter(
                $fallback,
                fn (array $item): bool =>
                    !in_array(
                        (string)$item['id'],
                        $databaseSlugs,
                        true
                    )
            )
        );

        return array_merge(
            $databaseArticles,
            $fallback
        );
    } catch (Throwable $exception) {
        error_log(
            'Wedding Za content query failed: ' .
            $exception->getMessage()
        );

        return $fallback;
    }
}

function wz_published_article(string $slug): ?array
{
    foreach (wz_published_articles() as $article) {
        if (
            (string)$article['id']
            === $slug
        ) {
            return $article;
        }
    }

    return null;
}

function wz_article_body_blocks(string $body): array
{
    $body = trim($body);

    if ($body === '') {
        return [];
    }

    $parts = preg_split(
        '/\R{2,}/',
        $body
    );

    if (!is_array($parts)) {
        return [];
    }

    return array_values(
        array_filter(
            array_map(
                'trim',
                $parts
            ),
            fn (string $part): bool =>
                $part !== ''
        )
    );
}

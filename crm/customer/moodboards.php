<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'host';
$crmPage = 'planning-moodboards';
$crmTitle = 'Moodboards';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$message = '';
$isSuccess = false;

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'board');

        if ($action === 'delete-idea') {
            $id = (int)($_POST['id'] ?? 0);

            $statement = $pdo->prepare(
                'DELETE FROM saved_ideas
                 WHERE id = :id
                 AND user_id = :user_id'
            );

            $statement->execute([
                'id' => $id,
                'user_id' => $userId,
            ]);

            $message = 'Idea removed.';
            $isSuccess = true;
        } else {
            $name = trim((string)($_POST['name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));

            if ($name === '') {
                $message = 'Enter a board name.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO saved_idea_boards (
                        user_id,
                        name,
                        description
                    ) VALUES (
                        :user_id,
                        :name,
                        :description
                    )'
                );

                $statement->execute([
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => $description !== '' ? $description : null,
                ]);

                $message = 'Moodboard created.';
                $isSuccess = true;
            }
        }
    }
}

$boards = [];

if ($pdo) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM saved_idea_boards
         WHERE user_id = :user_id
         ORDER BY updated_at DESC'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    $boards = $statement->fetchAll();

    foreach ($boards as &$board) {
        $ideas = $pdo->prepare(
            'SELECT *
             FROM saved_ideas
             WHERE board_id = :board_id
             AND user_id = :user_id
             ORDER BY created_at DESC'
        );

        $ideas->execute([
            'board_id' => $board['id'],
            'user_id' => $userId,
        ]);

        $board['ideas'] = $ideas->fetchAll();
    }

    unset($board);
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">IDEAS & INSPIRATION</span>
        <h1>Moodboards</h1>
        <p>
            Save inspiration into focused boards so decor, venue, styling and photography references stay coherent.
        </p>
    </div>

    <div class="crm-actions">
        <a class="crm-button" href="<?= h(wz_app_url('inspiration.php')) ?>">
            Browse inspiration ↗
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-layout">
    <section>
        <?php foreach ($boards as $board): ?>
            <article class="crm-panel">
                <div class="crm-panel-head">
                    <div>
                        <h2><?= h((string)$board['name']) ?></h2>
                        <p><?= h((string)($board['description'] ?: count($board['ideas']).' saved idea(s)')) ?></p>
                    </div>
                </div>

                <?php if ($board['ideas']): ?>
                    <div class="crm-calendar-grid">
                        <?php foreach ($board['ideas'] as $idea): ?>
                            <article class="crm-day" style="min-height:180px;">
                                <?php if (!empty($idea['image_url'])): ?>
                                    <img
                                        src="<?= h((string)$idea['image_url']) ?>"
                                        alt=""
                                        style="width:100%;height:110px;object-fit:cover;border-radius:10px;margin-bottom:10px;"
                                    >
                                <?php endif; ?>

                                <strong><?= h((string)$idea['title']) ?></strong>

                                <?php if (!empty($idea['source_url'])): ?>
                                    <a
                                        href="<?= h(wz_app_url((string)$idea['source_url'])) ?>"
                                        style="display:inline-block;margin-top:8px;font-size:9px;"
                                    >
                                        Open source ↗
                                    </a>
                                <?php endif; ?>

                                <form method="post" style="margin-top:10px;">
                                    <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete-idea">
                                    <input type="hidden" name="id" value="<?= h((string)$idea['id']) ?>">
                                    <button class="crm-button secondary small" type="submit">
                                        Remove
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="crm-empty">No ideas in this moodboard yet.</div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$boards): ?>
            <section class="crm-panel">
                <div class="crm-empty">
                    No moodboards yet. Save an idea from Inspiration or create your first board.
                </div>
            </section>
        <?php endif; ?>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Create board</h2>
                </div>
            </div>

            <form method="post" class="crm-form">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                <input type="hidden" name="action" value="board">

                <div class="crm-field">
                    <label for="boardName">Board name</label>
                    <input id="boardName" name="name" required placeholder="Reception Decor">
                </div>

                <div class="crm-field">
                    <label for="boardDescription">Description</label>
                    <textarea id="boardDescription" name="description"></textarea>
                </div>

                <button class="crm-button" type="submit">Create board</button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

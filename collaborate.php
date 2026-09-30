<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';

$pageTitle = 'Wedding Collaboration';
$pageDescription = 'Accept a Wedding Za collaboration invitation and join a shared wedding workspace.';
$pageKey = 'collaborate';

$token = trim((string)($_GET['token'] ?? ''));
$message = '';
$acceptedOwnerId = 0;
$pdo = wz_db();

if ($token !== '' && $pdo) {
    $statement = $pdo->prepare(
        'SELECT wc.*, u.name AS owner_name
         FROM wedding_collaborators wc
         INNER JOIN users u
            ON u.id = wc.owner_user_id
         WHERE wc.invite_token = :invite_token
         AND wc.status IN ("invited", "accepted")
         LIMIT 1'
    );

    $statement->execute([
        'invite_token' => $token,
    ]);

    $invite = $statement->fetch();

    if (!$invite) {
        $message = 'This collaboration invitation is no longer available.';
    } elseif (!wz_is_logged_in() || wz_role() !== 'host') {
        $message = 'Sign in with the invited customer email to accept this collaboration.';
    } else {
        $currentUser = wz_user() ?? [];
        $currentEmail = strtolower(
            trim((string)($currentUser['email'] ?? ''))
        );

        if (
            $currentEmail !== strtolower(
                trim((string)$invite['email'])
            )
        ) {
            $message = 'This invitation was sent to a different email address.';
        } else {
            $update = $pdo->prepare(
                'UPDATE wedding_collaborators
                 SET collaborator_user_id = :collaborator_user_id,
                     status = "accepted",
                     accepted_at = COALESCE(accepted_at, NOW())
                 WHERE id = :id'
            );

            $update->execute([
                'collaborator_user_id' => (int)$currentUser['id'],
                'id' => (int)$invite['id'],
            ]);

            $acceptedOwnerId = (int)$invite['owner_user_id'];
            $message = 'You now have access to '
                . (string)$invite['owner_name']
                . '’s shared wedding workspace.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'COLLABORATE',
        'Plan the same wedding,<br><em>not five versions of it.</em>',
        'Wedding Za collaboration keeps the event brief, budget direction and planning context visible to the people helping make decisions.'
    );
    ?>

    <section class="section">
        <div class="container" style="max-width:760px;">
            <div class="form-card">
                <h2><?= h($message) ?></h2>

                <?php if ($acceptedOwnerId > 0): ?>
                    <a
                        class="pill-btn wine"
                        href="<?= h(
                            wz_app_url(
                                'crm/customer/shared-wedding.php?owner_id='
                                . $acceptedOwnerId
                            )
                        ) ?>"
                    >
                        Open shared wedding ↗
                    </a>
                <?php elseif (!wz_is_logged_in()): ?>
                    <a
                        class="pill-btn wine"
                        href="login.php?role=host"
                    >
                        Sign in ↗
                    </a>
                <?php else: ?>
                    <a
                        class="pill-btn outline"
                        href="crm/customer/"
                    >
                        Return to CRM
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/components.php';

wz_require_role('host');

$user = wz_user();
$databaseReady = wz_database_ready();

$pageTitle = 'My Wedding Za';
$pageDescription = 'Your Wedding Za account workspace.';
$pageKey = 'account';

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="account-v2-hero">
        <div class="container account-v2-hero-grid">
            <div>
                <span class="eyebrow">
                    YOUR ACCOUNT
                </span>

                <h1>
                    Welcome back,
                    <br>
                    <em>
                        <?= h(explode(' ', (string)$user['name'])[0]) ?>.
                    </em>
                </h1>

                <p>
                    Your shortlist, event brief, checklist and budget
                    are connected through one planning workspace.
                </p>
            </div>

            <div class="account-v2-card">
                <span>ACCOUNT TYPE</span>

                <strong>
                    Host / Planner
                </strong>

                <p>
                    <?= h((string)$user['email']) ?>
                </p>

                <p>
                    <?= $databaseReady
                        ? 'Cloud workspace sync is active.'
                        : 'Local browser workspace is active.' ?>
                </p>

                <a href="logout.php">
                    Log out ↗
                </a>
            </div>
        </div>
    </section>

    <section class="section paper-2">
        <div class="container account-v2-grid">
            <a href="planner.php">
                <span>01</span>

                <h3>
                    Planning Studio
                </h3>

                <p>
                    Event brief, checklist, budget and progress.
                </p>

                <b>
                    Open workspace ↗
                </b>
            </a>

            <a href="shortlist.php">
                <span>02</span>

                <h3>
                    Shortlist
                </h3>

                <p>
                    Compare the vendors you are seriously considering.
                </p>

                <b>
                    Review saves ↗
                </b>
            </a>

            <a href="vendors.php">
                <span>03</span>

                <h3>
                    Discover
                </h3>

                <p>
                    Find venues and teams by occasion and city.
                </p>

                <b>
                    Find vendors ↗
                </b>
            </a>

            <a href="invites.php">
                <span>04</span>

                <h3>
                    Invitations
                </h3>

                <p>
                    Build and share the first guest-facing impression.
                </p>

                <b>
                    Open invites ↗
                </b>
            </a>
        </div>
    </section>

    <section class="section">
        <div class="container account-state-grid">
            <div>
                <span class="eyebrow">
                    WORKSPACE STATUS
                </span>

                <h2>
                    Your planning,
                    <br>
                    <em>kept together.</em>
                </h2>
            </div>

            <div>
                <?php if ($databaseReady): ?>
                    <p>
                        Your account is connected to MySQL.
                        Planning data is cached in your browser for speed
                        and synced to your Wedding Za account automatically.
                    </p>

                    <div class="account-tech-list">
                        <span>Persistent account</span>
                        <span>Workspace sync</span>
                        <span>CSRF protection</span>
                        <span>Secure password hash</span>
                    </div>
                <?php else: ?>
                    <p>
                        This installation is running without MySQL.
                        Your planning tools still work using local browser storage.
                        Configure the database to enable cross-device account sync.
                    </p>

                    <div class="account-tech-list">
                        <span>Local shortlist</span>
                        <span>Local event brief</span>
                        <span>Local checklist</span>
                        <span>Local budget</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

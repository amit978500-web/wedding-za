<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$role = (string)($_GET['role'] ?? $_POST['role'] ?? 'host');

if (!in_array($role, ['host', 'vendor'], true)) {
    $role = 'host';
}

$error = '';
$databaseReady = wz_database_ready();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please refresh and try again.';
    } elseif ($databaseReady) {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $result = wz_login_account(
            $email,
            $password,
            $role
        );

        if ($result['ok']) {
            $destination = $role === 'vendor'
                ? 'vendor-dashboard.php'
                : 'account.php';

            header('Location: ' . $destination);
            exit;
        }

        $error = (string)($result['message'] ?? 'Unable to sign in.');
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($name === '') {
            $error = 'Please enter your name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            wz_login_demo(
                $name,
                $email,
                $role
            );

            $destination = $role === 'vendor'
                ? 'vendor-dashboard.php'
                : 'account.php';

            header('Location: ' . $destination);
            exit;
        }
    }
}

$pageTitle = $role === 'vendor'
    ? 'Vendor Sign In'
    : 'Log In';

$pageDescription = 'Access your Wedding Za workspace.';
$pageKey = 'login';

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="auth-v2-shell">
        <div class="auth-v2-media">
            <img
                src="https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1500&q=92"
                alt="Indian celebration"
            >

            <div>
                <span class="eyebrow light">
                    WEDDING ZA ACCOUNT
                </span>

                <h2>
                    Keep the planning
                    <br>
                    <em>connected.</em>
                </h2>

                <p>
                    Shortlist, plan and manage enquiries without losing
                    the context behind the event.
                </p>
            </div>
        </div>

        <div class="auth-v2-panel">
            <div class="auth-v2-card">
                <span class="eyebrow">
                    <?= $role === 'vendor' ? 'BUSINESS ACCESS' : 'HOST ACCESS' ?>
                </span>

                <h1>
                    <?= $role === 'vendor' ? 'Vendor sign in' : 'Welcome back' ?>
                </h1>

                <?php if ($databaseReady): ?>
                    <p class="auth-v2-note">
                        Sign in with your Wedding Za account.
                    </p>
                <?php else: ?>
                    <p class="auth-v2-note">
                        MySQL is not configured on this installation yet,
                        so this local build is using session-only demo access.
                    </p>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="auth-error">
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="form-stack">
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= h(wz_csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="role"
                        value="<?= h($role) ?>"
                    >

                    <?php if (!$databaseReady): ?>
                        <div class="field">
                            <label for="loginName">
                                Name
                            </label>

                            <input
                                id="loginName"
                                name="name"
                                required
                                autocomplete="name"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="loginEmail">
                            Email
                        </label>

                        <input
                            id="loginEmail"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <?php if ($databaseReady): ?>
                        <div class="field">
                            <label for="loginPassword">
                                Password
                            </label>

                            <input
                                id="loginPassword"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    <?php endif; ?>

                    <button
                        class="pill-btn wine wide"
                        type="submit"
                    >
                        Enter workspace ↗
                    </button>
                </form>

                <div class="auth-role-switch">
                    <?php if ($role === 'vendor'): ?>
                        <a href="login.php?role=host">
                            I’m planning an event
                        </a>

                        <a href="register.php?role=vendor">
                            Create business account
                        </a>
                    <?php else: ?>
                        <a href="login.php?role=vendor">
                            I’m an event business
                        </a>

                        <a href="register.php?role=host">
                            Create account
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

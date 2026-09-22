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
    } elseif (!$databaseReady) {
        $error = 'Configure MySQL before creating persistent accounts.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($password !== $confirmPassword) {
            $error = 'The two passwords do not match.';
        } else {
            $result = wz_register_account(
                $name,
                $email,
                $password,
                $role
            );

            if ($result['ok']) {
                $destination = $role === 'vendor'
                    ? 'register-vendor.php?account=created'
                    : 'account.php';

                header('Location: ' . $destination);
                exit;
            }

            $error = (string)($result['message'] ?? 'Unable to create account.');
        }
    }
}

$pageTitle = $role === 'vendor'
    ? 'Create Business Account'
    : 'Create Account';

$pageDescription = 'Create your Wedding Za account.';
$pageKey = 'register';

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="auth-v2-shell">
        <div class="auth-v2-media">
            <img
                src="https://images.unsplash.com/photo-1769500810743-5e5dd4fd5848?auto=format&fit=crop&w=1500&q=92"
                alt="Indian celebration planning"
            >

            <div>
                <span class="eyebrow light">
                    CREATE YOUR WORKSPACE
                </span>

                <h2>
                    Save the decisions
                    <br>
                    <em>that matter.</em>
                </h2>

                <p>
                    One account for your shortlist, planning workspace
                    and event-business access.
                </p>
            </div>
        </div>

        <div class="auth-v2-panel">
            <div class="auth-v2-card">
                <span class="eyebrow">
                    <?= $role === 'vendor' ? 'BUSINESS ACCOUNT' : 'HOST ACCOUNT' ?>
                </span>

                <h1>
                    Create account
                </h1>

                <?php if (!$databaseReady): ?>
                    <div class="auth-error">
                        Persistent registration needs MySQL.
                        Follow database/README.md to configure it.
                    </div>
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

                    <div class="field">
                        <label for="registerName">
                            Name
                        </label>

                        <input
                            id="registerName"
                            name="name"
                            required
                            autocomplete="name"
                        >
                    </div>

                    <div class="field">
                        <label for="registerEmail">
                            Email
                        </label>

                        <input
                            id="registerEmail"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="field">
                        <label for="registerPassword">
                            Password
                        </label>

                        <input
                            id="registerPassword"
                            type="password"
                            name="password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="field">
                        <label for="registerPasswordConfirm">
                            Confirm password
                        </label>

                        <input
                            id="registerPasswordConfirm"
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <button
                        class="pill-btn wine wide"
                        type="submit"
                        <?= $databaseReady ? '' : 'disabled' ?>
                    >
                        Create account ↗
                    </button>
                </form>

                <div class="auth-role-switch">
                    <a href="login.php?role=<?= h($role) ?>">
                        Already have an account?
                    </a>

                    <?php if ($role === 'vendor'): ?>
                        <a href="register.php?role=host">
                            Create host account
                        </a>
                    <?php else: ?>
                        <a href="register.php?role=vendor">
                            Create business account
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

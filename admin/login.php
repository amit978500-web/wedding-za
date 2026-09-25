<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (wz_is_admin()) {
    header(
        'Location: index.php'
    );

    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Refresh and try again.';
    } else {
        $email = trim(
            (string)($_POST['email'] ?? '')
        );

        $password = (string)($_POST['password'] ?? '');

        $result = wz_login_account(
            $email,
            $password,
            'admin'
        );

        if ($result['ok']) {
            header(
                'Location: index.php'
            );

            exit;
        }

        $error = (string)$result['message'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title>Admin Login · Wedding Za</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css?v=1.0.0"
    >
</head>

<body>
    <main class="admin-login">
        <section class="admin-login-card">
            <h1>
                Wedding Za Admin
            </h1>

            <p>
                Restricted operations workspace.
                Admin accounts are created only through the command-line setup script.
            </p>

            <?php if ($error !== ''): ?>
                <div class="admin-notice">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                class="admin-form"
                style="padding: 0;"
            >
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= h(wz_csrf_token()) ?>"
                >

                <div class="admin-field">
                    <label for="adminEmail">
                        Email
                    </label>

                    <input
                        id="adminEmail"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="admin-field">
                    <label for="adminPassword">
                        Password
                    </label>

                    <input
                        id="adminPassword"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button
                    class="admin-button"
                    type="submit"
                >
                    Enter admin
                </button>
            </form>
        </section>
    </main>
</body>
</html>

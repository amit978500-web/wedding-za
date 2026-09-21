<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/auth.php';
    $role=(string)($_GET['role']??($_POST['role']??'host'));
    if(!in_array($role,['host','vendor'],true))$role='host';
    $error='';
    if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!wz_csrf_valid($_POST['csrf']??null)) {
    $error='Your session expired. Please refresh and try again.';
    } else {
    $name=trim((string)($_POST['name']??''));
    $email=trim((string)($_POST['email']??''));
    if($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    $error='Please enter your name and a valid email address.';
    } else {
    wz_login_demo($name,$email,$role);
    header('Location: '.($role==='vendor'?'vendor-dashboard.php':'account.php'));
    exit;
    }
    }
    }
    $pageTitle=$role==='vendor'?'Vendor Sign In':'Log In';
    $pageDescription='Access your Wedding Za workspace.';
    $pageKey='login';
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="auth-v2-shell">
        <div class="auth-v2-media">
            <img src="https://images.unsplash.com/photo-1744805624952-dab790f6b3bd?auto=format&fit=crop&w=1500&q=92" alt="Indian celebration">
            <div>
                <span class="eyebrow light">
                WEDDING ZA ACCOUNT
                </span>
                <h2>
                Keep the planning
                <br>
                <em>
                connected.
                </em>
                </h2>
                <p>
                Shortlist, plan and manage enquiries without losing the context behind the event.
                </p>
            </div>
        </div>
        <div class="auth-v2-panel">
            <div class="auth-v2-card">
                <span class="eyebrow">
                <?= $role==='vendor'?'BUSINESS ACCESS':'HOST ACCESS' ?>
                </span>
                <h1>
                <?= $role==='vendor'?'Vendor sign in':'Welcome back' ?>
                </h1>
                <p class="auth-v2-note">
                This build uses secure PHP session state in demo mode. Production passwords and account records will move to the database layer.
                </p>
                <?php
                    if($error):
                ?>
                    <div class="auth-error">
                        <?= h($error) ?>
                    </div>
                <?php
                    endif;
                ?>
                <form method="post" class="form-stack">
                    <input type="hidden" name="csrf" value="<?=h(wz_csrf_token())?>
                    ">
                    <input type="hidden" name="role" value="<?=h($role)?>
                    ">
                    <div class="field">
                        <label>
                            Name
                        </label>
                        <input name="name" required autocomplete="name">
                    </div>
                    <div class="field">
                        <label>
                            Email
                        </label>
                        <input type="email" name="email" required autocomplete="email">
                    </div>
                    <button class="pill-btn wine wide" type="submit">
                    Enter workspace ↗
                    </button>
                </form>
                <div class="auth-role-switch">
                    <?php
                        if($role==='vendor'):
                    ?>
                        <a href="login.php?role=host">
                        I’m planning an event
                        </a>
                        <a href="register-vendor.php">
                        Apply as a business
                        </a>
                    <?php
                        else:
                    ?>
                        <a href="login.php?role=vendor">
                        I’m an event business
                        </a>
                        <a href="register-vendor.php">
                        List my business
                        </a>
                    <?php
                        endif;
                    ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>

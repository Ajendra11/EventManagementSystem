<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin()) {
    redirect('admin/index.php');
}

if (is_logged_in()) {
    logout_user();
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $errors = attempt_admin_login($_POST['email'] ?? '', $_POST['password'] ?? '');

    if (!$errors) {
        flash('success', 'Welcome to the admin panel.');
        redirect('admin/index.php');
    }
}

render_header('Admin Portal');
?>

<div class="admin-login-page">
    <div class="admin-login-layout">

        <section class="admin-login-left">
            <img
                src="<?= APP_URL ?>/assets/css/images/admin.png"
                alt="Admin panel"
                class="admin-login-bg"
            >

            <div class="admin-login-overlay">
                <span class="admin-login-chip">Admin Access</span>

                <h1>Manage events with full control and confidence.</h1>

                <p>
                    Access the admin dashboard to manage events, bookings, users,
                    reviews, and platform activity in one place.
                </p>
            </div>
        </section>

        <section class="admin-login-right">
            <div class="admin-login-panel">
                <p class="admin-login-eyebrow">EVENTHUB ADMIN</p>

                <h2>Administrator sign in</h2>
                <p class="admin-login-subtext">
                    Participant accounts cannot access this portal.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="flash flash-error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="form-group">
                        <label for="email">Admin email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= old('email') ?>"
                            placeholder="Enter admin email"
                            autocomplete="email"
                            data-required="true"
                            data-label="Email"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            data-required="true"
                            data-label="Password"
                        >
                    </div>

                    <button class="btn btn-primary btn-block admin-login-btn" type="submit">
                        Sign in to admin panel
                    </button>

                    <p class="admin-login-back-link">
                        <a href="<?= APP_URL ?>/auth/login.php">&larr; Back to participant login</a>
                    </p>
                </form>
            </div>
        </section>

    </div>
</div>

<?php render_footer(); ?>

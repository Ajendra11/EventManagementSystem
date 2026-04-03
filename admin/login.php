<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin()) { redirect('admin/index.php'); }
if (is_logged_in()) { logout_user(); }

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
<div class="container">
    <form class="card form-card" method="post" data-validate="true" style="border-top:4px solid var(--primary);">
        <div class="status-row" style="margin-bottom:.5rem;">
            <span class="badge warning">Admin Portal</span>
        </div>
        <h2>Administrator sign in</h2>
        <p class="muted">Participant accounts cannot access this portal.</p>
        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label for="email">Admin email</label>
            <input id="email" type="email" name="email" data-required="true" data-label="Email"
                   value="<?= old('email') ?>" autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" data-required="true" data-label="Password"
                   autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in to admin panel</button>
        <p class="auth-note"><a href="<?= APP_URL ?>/auth/login.php">&larr; Back to participant login</a></p>
    </form>
</div>
<?php render_footer(); ?>
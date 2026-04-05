<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if (!$errors) {
        flash('success', 'Welcome back, ' . (auth_user()['name'] ?? '') . '!');
        redirect(is_admin() ? 'admin/index.php' : 'index.php');
    }
}

render_header('Sign In');
?>
<div class="container">
    <form class="card form-card" method="post" data-validate="true">
        <h2>Sign in</h2>
        <p class="muted">Access your account.</p>

        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" data-required="true" data-label="Email"
                   value="<?= old('email') ?>" autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" data-required="true" data-label="Password"
                   autocomplete="current-password">
        </div>

        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
        <p class="auth-note">Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Register here</a></p>
        <p class="auth-note"><a href="<?= APP_URL ?>/admin/login.php">Admin portal &rarr;</a></p>
    </form>
</div>
<?php render_footer(); ?>
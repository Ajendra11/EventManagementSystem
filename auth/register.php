<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = register_user($_POST);
    if (!$errors) {
        flash('success', 'Account created successfully! You can now sign in.');
        redirect('auth/login.php');
    }
}

render_header('Create Account');
?>
<div class="container">
    <form class="card form-card" method="post" data-validate="true">
        <h2>Create account</h2>
        <p class="muted">Join as a participant.</p>

        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="full_name">Full name</label>
            <input id="full_name" type="text" name="full_name" data-required="true" data-label="Full name"
                   value="<?= old('full_name') ?>" autocomplete="name">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" data-required="true" data-label="Email"
                   value="<?= old('email') ?>" autocomplete="email">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" data-required="true" data-label="Password"
                       autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" type="password" name="confirm_password"
                       data-required="true" data-label="Confirm password" autocomplete="new-password">
            </div>
        </div>
        <p class="muted" style="font-size:.88rem;">Must be 8–64 characters with uppercase, lowercase, number, and special character.</p>

        <button class="btn btn-primary btn-block" type="submit">Create account</button>
        <p class="auth-note">Already registered? <a href="<?= APP_URL ?>/auth/login.php">Sign in here</a></p>
    </form>
</div>
<?php render_footer(); ?>
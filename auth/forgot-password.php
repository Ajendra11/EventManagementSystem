<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_post()) {
    verify_csrf();
    request_password_reset((string)($_POST['email'] ?? ''));
    flash('success', 'If that email exists, a password reset link has been sent.');
    redirect('auth/login.php');
}

render_header('Forgot Password');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Forgot password</h2>
        <p class="muted">We will send a password reset link if your email exists in EventHub.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= old('email') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Send reset link</button>
    </form>
</div>
<?php render_footer(); ?>

<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

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
----------------------------
auth folder ma reset-password.php file create and paste the code below
<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];

if ($token === '') {
    flash('error', 'Missing password reset token.');
    redirect('auth/forgot-password.php');
}

if (is_post()) {
    verify_csrf();
    $errors = reset_password_with_token($token, (string)($_POST['password'] ?? ''), (string)($_POST['confirm_password'] ?? ''));
    if (!$errors) {
        flash('success', 'Password reset successfully. Please sign in.');
        redirect('auth/login.php');
    }
}

render_header('Reset Password');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Reset password</h2>
        <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
            <label>New password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <p class="muted">Password must include uppercase, lowercase, number, and special character.</p>
        <button class="btn btn-primary" type="submit">Reset password</button>
    </form>
</div>
<?php render_footer(); ?>
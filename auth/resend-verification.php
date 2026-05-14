<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = resend_verification_email((string)($_POST['email'] ?? ''));
    flash($errors ? 'error' : 'success', $errors[0] ?? 'If the account exists and is unverified, a new verification link has been sent.');
    redirect('auth/login.php');
}

render_header('Resend Verification');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Resend verification email</h2>
        <p class="muted">Enter your account email address.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= old('email') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Send verification link</button>
    </form>
</div>
<?php render_footer(); ?>

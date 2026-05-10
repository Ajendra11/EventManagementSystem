<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token !== '') {
    $errors = verify_email_token($token);
    flash($errors ? 'error' : 'success', $errors[0] ?? 'Email verified successfully. You can now sign in.');
    redirect('auth/login.php');
}

render_header('Verify Email');
?>
<div class="container section">
    <div class="panel" style="max-width:640px;">
        <h2>Verify email</h2>
        <p class="muted">Open the verification link sent to your email address. Need a new link?</p>
        <a class="btn btn-primary" href="<?= APP_URL ?>/auth/resend-verification.php">Resend verification email</a>
    </div>
</div>
<?php render_footer(); ?>
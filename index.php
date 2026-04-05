<?php
require_once __DIR__ . '/includes/layout.php';

render_header('Home');
?>
<div class="container section" style="text-align:center; padding:4rem 0;">
    <h1>Welcome to <?= APP_NAME ?></h1>
    <p>Please sign in or create an account to continue.</p>
    <div class="status-row" style="justify-content:center; margin-top:1.5rem;">
        <a class="btn btn-primary" href="<?= APP_URL ?>/auth/login.php">Sign In</a>
        <a class="btn btn-outline" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
    </div>
</div>
<?php render_footer(); ?>
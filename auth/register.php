<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('bookings/my-bookings.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = register_user($_POST);
    if (!$errors) {
        // Sprint 1: No email verification required — redirect directly to login
        flash('success', 'Account created successfully! You can now sign in.');
        redirect('auth/login.php');
    }
}

render_header('Create Account');
?>


<div class="container">
    <form class="card form-card" method="post">
        <h2>Create account</h2>

        <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <input type="text" name="full_name" value="<?= old('full_name') ?>">
        <input type="email" name="email" value="<?= old('email') ?>">

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label>Full name</label>
            <input type="text" name="full_name">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password">
        </div>

        <div class="form-group">
            <label>Confirm password</label>
            <input type="password" name="confirm_password">
        </div>

        <button type="submit">Create account</button>
    </form>
</div>

<?php render_footer(); ?>
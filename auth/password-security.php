<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user   = auth_user();
$errors = [];

if (is_post()) {
    verify_csrf();

    $errors = change_password(
        (int) $user['id'],
        $_POST['current_password'] ?? '',
        $_POST['new_password'] ?? '',
        $_POST['confirm_new_password'] ?? ''
    );

    if (!$errors) {
        flash('success', 'Password changed successfully.');
        redirect('auth/profile.php');
    }
}

render_header('Password & Security');
?>

<div class="container section">
    <div class="pm-shell">
        <div class="pm-card">
            <div class="pm-sub-header">
                <a class="pm-back-link" href="<?= APP_URL ?>/auth/profile.php">&larr; Back</a>
            </div>

            <div class="pm-form-wrap">
                <h1 class="pm-page-title">Password &amp; Security</h1>
                <p class="pm-page-subtitle">Change your password here.</p>

                <?php foreach ($errors as $err): ?>
                    <div class="flash flash-error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="pm-form-group">
                        <label for="current_password">Current password</label>
                        <div class="pm-password-field">
                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                data-required="true"
                                data-label="Current password"
                                autocomplete="current-password"
                            >
                            <button type="button" class="pm-toggle-pass" data-target="current_password">Show</button>
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label for="new_password">New password</label>
                        <div class="pm-password-field">
                            <input
                                id="new_password"
                                type="password"
                                name="new_password"
                                data-required="true"
                                data-label="New password"
                                autocomplete="new-password"
                            >
                            <button type="button" class="pm-toggle-pass" data-target="new_password">Show</button>
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label for="confirm_new_password">Confirm new password</label>
                        <div class="pm-password-field">
                            <input
                                id="confirm_new_password"
                                type="password"
                                name="confirm_new_password"
                                data-required="true"
                                data-label="Confirm new password"
                                autocomplete="new-password"
                            >
                            <button type="button" class="pm-toggle-pass" data-target="confirm_new_password">Show</button>
                        </div>
                    </div>

                    <p class="pm-helper-text">
                        Use 8 to 64 characters with uppercase, lowercase, number, and special character.
                    </p>

                    <button class="pm-primary-btn" type="submit">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.pm-toggle-pass').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = 'Hide';
        } else {
            input.type = 'password';
            this.textContent = 'Show';
        }
    });
});
</script>

<?php render_footer(); ?>
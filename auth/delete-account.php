<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user   = auth_user();
$errors = [];

if (is_post()) {
    verify_csrf();
    $errors = delete_user_account((int) $user['id'], $_POST['password'] ?? '');

    if (!$errors) {
        logout_user();
        redirect('auth/login.php?account_deleted=1');
    }
}

render_header('Delete Account');
?>

<div class="container section">
    <div class="pm-shell">
        <div class="pm-card">
            <div class="pm-sub-header">
                <a class="pm-back-link" href="<?= APP_URL ?>/auth/profile.php">&larr; Back</a>
            </div>

            <div class="pm-form-wrap">
                <h1 class="pm-page-title">Delete Account</h1>
                <p class="pm-page-subtitle">
                    This permanently removes your account.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="flash flash-error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="pm-form-group">
                        <label for="password">Enter password to confirm</label>
                        <div class="pm-password-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                data-required="true"
                                data-label="Password"
                                autocomplete="current-password"
                            >
                            <button type="button" class="pm-toggle-pass" data-target="password">Show</button>
                        </div>
                    </div>

                    <button class="pm-primary-btn pm-danger-btn" type="submit">
                        Delete account
                    </button>
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
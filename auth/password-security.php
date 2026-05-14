<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = auth_user();
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
        redirect('events/index.php');
    }
}

render_header('Password & Security');
?>

<div class="container section">
    <div class="pm-settings-shell pm-settings-shell-narrow">
        <a class="pm-inline-back" href="javascript:history.back()">
            <span>&larr;</span>
            <span>Back</span>
        </a>

        <div class="pm-settings-card panel">
            <div class="pm-settings-head">
                <h1 class="pm-settings-title">Password &amp; Security</h1>
                <p class="pm-settings-subtitle">
                    Update your password and keep your account protected with strong credentials.
                </p>
            </div>

            <?php if ($errors): ?>
                <div class="pm-settings-errors">
                    <?php foreach ($errors as $err): ?>
                        <div class="flash flash-error"><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="pm-settings-form" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="pm-settings-field">
                    <label for="current_password">Current password</label>

                    <div class="password-wrap pm-password-row">
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            data-required="true"
                            data-label="Current password"
                            autocomplete="current-password"
                            placeholder="Enter your current password"
                        >

                        <button
                            class="eye-btn"
                            type="button"
                            data-target="current_password"
                            aria-label="Show current password"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pm-settings-field">
                    <label for="new_password">New password</label>

                    <div class="password-wrap pm-password-row">
                        <input
                            id="new_password"
                            type="password"
                            name="new_password"
                            data-required="true"
                            data-label="New password"
                            autocomplete="new-password"
                            placeholder="Enter a new password"
                        >

                        <button
                            class="eye-btn"
                            type="button"
                            data-target="new_password"
                            aria-label="Show new password"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pm-settings-field">
                    <label for="confirm_new_password">Confirm new password</label>

                    <div class="password-wrap pm-password-row">
                        <input
                            id="confirm_new_password"
                            type="password"
                            name="confirm_new_password"
                            data-required="true"
                            data-label="Confirm new password"
                            autocomplete="new-password"
                            placeholder="Re-enter your new password"
                        >

                        <button
                            class="eye-btn"
                            type="button"
                            data-target="confirm_new_password"
                            aria-label="Show confirm new password"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pm-security-note">
                    <h3>Password tips</h3>
                    <ul>
                        <li>Use at least 8 characters.</li>
                        <li>Include uppercase, lowercase, a number, and a special character.</li>
                        <li>Avoid using your name, email, or common words.</li>
                    </ul>
                </div>

                <div class="pm-settings-actions">
                    <button class="btn btn-primary" type="submit">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.password-wrap,
.pm-password-row {
    position: relative;
}

.password-wrap input,
.pm-password-row input {
    width: 100%;
    padding-right: 56px;
}

.eye-btn {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #746f9a;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.eye-btn svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.eye-btn:hover {
    color: #7c3aed;
}
</style>

<script>
document.querySelectorAll('.eye-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);

        if (!input) {
            return;
        }

        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
</script>

<?php render_footer(); ?>
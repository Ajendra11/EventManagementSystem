<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = auth_user();
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
    <div class="pm-settings-shell pm-settings-shell-narrow">
        <a class="pm-inline-back" href="javascript:history.back()">
            <span>&larr;</span>
            <span>Back</span>
        </a>

        <div class="pm-settings-card pm-danger-card panel">
            <div class="pm-settings-head">
                <h1 class="pm-settings-title">Delete Account</h1>
                <p class="pm-settings-subtitle">
                    Permanently remove your account and end access to your profile, bookings, and account settings.
                </p>
            </div>

            <div class="pm-danger-note">
                <h3>Before you continue</h3>
                <ul>
                    <li>This action is permanent and cannot be undone.</li>
                    <li>You will lose access to your account immediately.</li>
                    <li>Please confirm using your current password.</li>
                </ul>
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
                    <label for="password">Confirm with password</label>

                    <div class="password-wrap pm-delete-password-wrap">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            data-required="true"
                            data-label="Password"
                            autocomplete="current-password"
                            placeholder="Enter your current password"
                        >

                        <button class="eye-btn" type="button" onclick="toggleDeletePassword()" aria-label="Show password">
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>

                    <p class="pm-settings-help">
                        Enter your password to confirm permanent account deletion.
                    </p>
                </div>

                <div class="pm-settings-actions">
                    <button class="btn btn-danger" type="submit">Delete account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.password-wrap,
.pm-delete-password-wrap {
    position: relative;
}

.password-wrap input,
.pm-delete-password-wrap input {
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
function toggleDeletePassword() {
    const input = document.getElementById('password');

    if (!input) {
        return;
    }

    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

<?php render_footer(); ?>
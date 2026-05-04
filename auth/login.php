<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'events/index.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $errors = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');

    if (!$errors) {
        flash('success', 'Welcome back, ' . (auth_user()['name'] ?? '') . '!');
        redirect(is_admin() ? 'admin/index.php' : 'events/index.php');
    }
}

render_header('Sign In');
?>

<div class="auth-login-page">
    <div class="auth-login-shell">
        <div class="auth-login-visual">
            <div class="auth-login-visual-content">
                <div class="auth-login-badge">EventHub Access</div>
                <h1>Step into events that feel bigger than ordinary.</h1>
                <p>
                    Sign in to manage your bookings, discover upcoming events,
                    and keep every ticket in one clean dashboard.
                </p>
            </div>
        </div>

        <div class="auth-login-panel">
            <div class="auth-login-card">
                <div class="auth-login-brand">EventHub</div>
                <h2>Welcome back</h2>
                <p class="auth-login-subtext">
                    Enter your credentials to continue to your account.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="auth-alert error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" class="auth-login-form" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="auth-login-group">
                        <label for="email">Email address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            data-required="true"
                            data-label="Email"
                            value="<?= old('email') ?>"
                            placeholder="Enter your email"
                            autocomplete="email"
                        >
                    </div>

                    <div class="auth-login-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                data-required="true"
                                data-label="Password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                aria-label="Show password"
                                title="Show password"
                                style="width:28px; height:28px;"
                            >
                                <span class="eye-open">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </span>

                                <span class="eye-closed" style="display:none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 34 Q32 12 54 34" />
                                        <path d="M16 28 L10 22" />
                                        <path d="M26 22 L23 14" />
                                        <path d="M38 22 L38 13" />
                                        <path d="M50 28 L56 22" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="auth-login-row">
                        <div></div>
                        <a href="<?= APP_URL ?>/auth/forgot-password.php">Forgot your password?</a>
                    </div>

                    <button class="auth-login-btn" type="submit">Sign in</button>

                    <div class="auth-login-divider">New here?</div>

                    <div class="auth-login-footer">
                        Don’t have an account?
                        <a href="<?= APP_URL ?>/auth/register.php">Create one</a>
                    </div>

                    <div class="auth-login-footer" style="margin-top:10px;">
                        <a href="<?= APP_URL ?>/admin/login.php">Admin portal &rarr;</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

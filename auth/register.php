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
        flash('success', 'Account created! Please check your email to verify your address before signing in.');
        redirect('auth/login.php');
    }
}

render_header('Create Account');
?>

<div class="auth-register-page">
    <div class="auth-register-shell">
        <div class="auth-register-visual">
            <div class="auth-register-visual-content">
                <div class="auth-register-badge">Join EventHub</div>
                <h1>Turn plans into unforgettable experiences.</h1>
                <p>
                    Create your account to explore upcoming events, manage bookings,
                    and keep every ticket in one beautiful place.
                </p>
            </div>
        </div>

        <div class="auth-register-panel">
            <div class="auth-register-card">
                <div class="auth-register-brand">EventHub</div>
                <h2>Create account</h2>
                <p class="auth-register-subtext">
                    Join as a participant and start discovering events.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="auth-alert error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" class="auth-register-form" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="auth-register-group">
                        <label for="full_name">Full name</label>
                        <input
                            id="full_name"
                            type="text"
                            name="full_name"
                            data-required="true"
                            data-label="Full name"
                            value="<?= e(old('full_name') ?? '') ?>"
                            autocomplete="name"
                            placeholder="Enter your full name"
                        >
                    </div>

                    <div class="auth-register-group">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            data-required="true"
                            data-label="Email"
                            value="<?= e(old('email') ?? '') ?>"
                            autocomplete="email"
                            placeholder="Enter your email"
                        >
                    </div>

                    <div class="auth-register-row">
                        <div class="auth-register-group">
                            <label for="password">Password</label>
                            <div class="password-field">
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    data-required="true"
                                    data-label="Password"
                                    autocomplete="new-password"
                                    placeholder="Create password"
                                >

                                <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                    <span class="eye-open">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>

                                    <span class="eye-closed" style="display:none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
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

                        <div class="auth-register-group">
                            <label for="confirm_password">Confirm password</label>
                            <div class="password-field">
                                <input
                                    id="confirm_password"
                                    type="password"
                                    name="confirm_password"
                                    data-required="true"
                                    data-label="Confirm password"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                >

                                <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                    <span class="eye-open">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>

                                    <span class="eye-closed" style="display:none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
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
                    </div>

                    <p class="auth-register-note">
                        Must be 8–64 characters with uppercase, lowercase, number, and special character.
                    </p>

                    <button class="auth-register-btn" type="submit">Create account</button>

                    <div class="auth-register-divider">Already registered?</div>

                    <div class="auth-register-footer">
                        Already registered?
                        <a href="<?= APP_URL ?>/auth/login.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

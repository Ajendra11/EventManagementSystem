<?php
// Include layout and authentication helper functions
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

// If the user is already an admin, redirect to admin dashboard
if (is_admin()) {
    redirect('admin/index.php');
}

// If a normal user is logged in, log them out (only admins allowed here)
if (is_logged_in()) {
    logout_user();
}

// Initialize an array to store validation or login errors
$errors = [];

// Check if the form is submitted via POST request
if (is_post()) {
    // Verify CSRF token for security
    verify_csrf();

    // Attempt admin login with provided email and password
    $errors = attempt_admin_login($_POST['email'] ?? '', $_POST['password'] ?? '');

    // If no errors, login successful
    if (!$errors) {
        // Set success flash message
        flash('success', 'Welcome to the admin panel.');

        // Redirect to admin dashboard
        redirect('admin/index.php');
    }
}

// Render page header with title
render_header('Admin Portal');
?>

<div class="admin-login-page">
    <div class="admin-login-layout">

        <!-- Left section: background image and branding -->
        <section class="admin-login-left">
            <img
                src="<?= APP_URL ?>/assets/css/images/admin.png"
                alt="Admin panel"
                class="admin-login-bg"
            >

            <div class="admin-login-overlay">
                <!-- Small label -->
                <span class="admin-login-chip">Admin Access</span>

                <!-- Main heading -->
                <h1>Manage events with full control and confidence.</h1>

                <!-- Description text -->
                <p>
                    Access the admin dashboard to manage events, bookings, users,
                    reviews, and platform activity in one place.
                </p>
            </div>
        </section>

        <!-- Right section: login form -->
        <section class="admin-login-right">
            <div class="admin-login-panel">
                <!-- Branding / panel title -->
                <p class="admin-login-eyebrow">EVENTHUB ADMIN</p>

                <!-- Form heading -->
                <h2>Administrator sign in</h2>

                <!-- Subtext -->
                <p class="admin-login-subtext">
                    Participant accounts cannot access this portal.
                </p>

                <!-- Display validation or login errors -->
                <?php foreach ($errors as $err): ?>
                    <div class="flash flash-error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <!-- Login form -->
                <form method="post" data-validate="true">
                    <!-- CSRF token for security -->
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <!-- Email input field -->
                    <div class="form-group">
                        <label for="email">Admin email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= old('email') ?>"
                            placeholder="Enter admin email"
                            autocomplete="email"
                            data-required="true"
                            data-label="Email"
                        >
                    </div>

                    <!-- Password input field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            data-required="true"
                            data-label="Password"
                        >
                    </div>

                    <!-- Submit button -->
                    <button class="btn btn-primary btn-block admin-login-btn" type="submit">
                        Sign in to admin panel
                    </button>

                    <!-- Link to go back to participant login -->
                    <p class="admin-login-back-link">
                        <a href="<?= APP_URL ?>/auth/login.php">&larr; Back to participant login</a>
                    </p>
                </form>
            </div>
        </section>

    </div>
</div>

<?php
// Render page footer
render_footer();
?>
<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';


function current_page_stylesheet(): ?string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $map = [
        '/index.php' => 'browse-events.css',
        '/events/index.php' => 'browse-events.css',
        '/events/show.php' => 'event-details.css',
        '/auth/login.php' => 'login.css',
        '/auth/register.php' => 'register.css',
        '/auth/forgot-password.php' => 'forgot-password.css',
        '/auth/reset-password.php' => 'reset-password.css',
        '/auth/resend-verification.php' => 'resend-verification.css',
        '/auth/verify-email.php' => 'verify-email.css',
        '/auth/profile.php' => 'profile.css',
        '/auth/manage-profile.php' => 'profile.css',
        '/auth/password-security.php' => 'profile.css',
        '/auth/delete-account.php' => 'profile.css',
        '/bookings/my-bookings.php' => 'my-bookings.css',
        '/bookings/khalti_simulator.php' => 'khalti-simulator.css',
        '/reviews/submit.php' => 'submit-review.css',
        '/reviews/my-reviews.php' => 'my-reviews.css',
        '/tickets/verify.php' => 'ticket-verify.css',
        '/admin/login.php' => 'admin-login.css',
        '/admin/index.php' => 'admin-dashboard.css',
        '/admin/events.php' => 'admin-events.css',
        '/admin/events_form.php' => 'admin-events-form.css',
        '/admin/bookings.php' => 'admin-bookings.css',
        '/admin/reviews.php' => 'admin-reviews.css',
        '/admin/users.php' => 'admin-users.css',
        '/privacy.php' => 'privacy.css',
    ];

    foreach ($map as $suffix => $stylesheet) {
        if (str_ends_with($script, $suffix)) {
            return $stylesheet;
        }
    }

    return null;
}

function current_page_class(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $script = trim($script, '/');
    if ($script === '') {
        return 'page-home';
    }

    $class = preg_replace('/[^a-z0-9]+/i', '-', $script) ?? 'page-generic';
    return 'page-' . trim(strtolower($class), '-');
}

function render_header(string $title = APP_NAME): void
{

    send_security_headers();
    $user    = auth_user();
    $flashes = get_flashes();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Discover and book events across Nepal. Secure online ticketing with Khalti.">
        <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/base-core.css">
        <?php if ($pageStylesheet = current_page_stylesheet()): ?>
            <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= e($pageStylesheet) ?>">
        <?php endif; ?>
    </head>
    <body class="<?= e(current_page_class()) ?>">
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="<?= APP_URL ?>/index.php"><?= e(APP_NAME) ?></a>
            <nav class="nav-links">
                <a href="<?= APP_URL ?>/events/index.php">Events</a>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?= APP_URL ?>/admin/index.php">Admin Panel</a>
                        <a href="<?= APP_URL ?>/admin/reviews.php">Reviews</a>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/bookings/my-bookings.php">My Bookings</a>
                        <a href="<?= APP_URL ?>/reviews/my-reviews.php">My Reviews</a>
                        <a href="<?= APP_URL ?>/auth/profile.php">Profile</a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/auth/logout.php">Logout</a>
                    <span class="nav-user">Hi, <?= e($user['name']) ?></span>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/auth/login.php">Login</a>
                    <a class="btn btn-sm btn-primary" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php if ($flashes): ?>
        <div class="container flash-stack">
            <?php foreach ($flashes as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3>EventHub</h3>
                <p class="muted">Secure, modern event management and online ticketing for Nepal.</p>
            </div>
            <div>
                <h4>Quick links</h4>
                <a href="<?= APP_URL ?>/events/index.php">Browse Events</a>
                <a href="<?= APP_URL ?>/auth/register.php">Join Now</a>
                <a href="<?= APP_URL ?>/auth/login.php">Sign In</a>
                <a href="<?= APP_URL ?>/privacy.php">Privacy Policy</a>
            </div>
            <div>
                <h4>Admin</h4>
                <a href="<?= APP_URL ?>/admin/login.php">Admin Portal</a>
            </div>
        </div>
        <div class="container" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
            <p class="muted" style="font-size:.85rem;">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Payments powered by Khalti.</p>
        </div>
    </footer>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}

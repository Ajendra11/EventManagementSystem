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
        '/admin/login.php' => 'admin-login.css',
        '/admin/index.php' => 'admin-dashboard.css',
        '/admin/events.php' => 'admin-events.css',
        '/admin/events_form.php' => 'admin-events-form.css',
        '/admin/bookings.php' => 'admin-bookings.css',
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

    $user = auth_user();
    $flashes = get_flashes();
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    $isLoggedInUser = $user && (($user['role'] ?? '') !== 'admin');
    $avatarLetter = strtoupper(mb_substr(trim((string)($user['name'] ?? 'U')), 0, 1));

    $currentRequestUri = $_SERVER['REQUEST_URI'] ?? '/events/index.php';
    $returnTo = urlencode($currentRequestUri);

    $hideUserSidebarToggle =
        str_contains($currentPath, '/auth/manage-profile.php') ||
        str_contains($currentPath, '/auth/password-security.php') ||
        str_contains($currentPath, '/auth/delete-account.php');
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
    <body class="<?= e(current_page_class()) ?> <?= $isLoggedInUser ? 'has-user-sidebar' : '' ?>">

    <?php if ($isLoggedInUser): ?>
        <div class="user-app-shell" id="userLayoutShell">
            <aside class="user-shell-sidebar" id="userShellSidebar">
                <div class="user-shell-top">
                    <div>
                        <a class="user-shell-brand" href="<?= APP_URL ?>/events/index.php"><?= e(APP_NAME) ?></a>
                        <p class="user-shell-subtitle">Your dashboard</p>
                    </div>

                    <button
                        type="button"
                        class="user-sidebar-close"
                        id="userSidebarClose"
                        aria-label="Hide sidebar"
                    >
                        ✕
                    </button>
                </div>

                <nav class="user-shell-nav" aria-label="User navigation">
                    <a
                        href="<?= APP_URL ?>/events/index.php"
                        class="user-shell-link <?= str_contains($currentPath, '/events/') ? 'active' : '' ?>"
                    >
                        <span>Events</span>
                    </a>

                    <a
                        href="<?= APP_URL ?>/bookings/my-bookings.php"
                        class="user-shell-link <?= str_contains($currentPath, '/bookings/my-bookings.php') ? 'active' : '' ?>"
                    >
                        <span>My Bookings</span>
                    </a>
                </nav>

                <div class="user-shell-profile-wrap">
                    <button
                        type="button"
                        class="user-shell-profile-trigger"
                        id="userProfileTrigger"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-controls="userProfileMenu"
                    >
                        <div class="user-shell-avatar"><?= e($avatarLetter) ?></div>

                        <div class="user-shell-profile-text">
                            <strong>Hi, <?= e((string) $user['name']) ?></strong>
                            <span>Manage your account</span>
                        </div>

                        <div class="user-shell-profile-arrow">▾</div>
                    </button>

                    <div
                        class="user-shell-profile-menu"
                        id="userProfileMenu"
                        role="menu"
                        aria-label="Profile menu"
                    >
                        <a href="<?= APP_URL ?>/auth/manage-profile.php?return_to=<?= $returnTo ?>" role="menuitem">Manage Account</a>
                        <a href="<?= APP_URL ?>/auth/password-security.php?return_to=<?= $returnTo ?>" role="menuitem">Password &amp; Security</a>
                        <a href="<?= APP_URL ?>/auth/delete-account.php?return_to=<?= $returnTo ?>" class="danger" role="menuitem">Delete Account</a>
                        <a href="<?= APP_URL ?>/auth/logout.php" class="js-logout-confirm" role="menuitem">Logout</a>
                    </div>
                </div>
            </aside>

            <div class="user-sidebar-backdrop" id="userSidebarBackdrop"></div>

            <main class="user-shell-main">
                <div class="user-main-topbar">
                    <?php if (!$hideUserSidebarToggle): ?>
                        <button
                            type="button"
                            class="user-sidebar-toggle"
                            id="userSidebarToggle"
                            aria-label="Toggle sidebar"
                            aria-expanded="true"
                        >
                            ☰
                        </button>
                    <?php endif; ?>

                    <div class="user-main-topbar-title"><?= e($title) ?></div>
                </div>

                <?php if ($flashes): ?>
                    <div class="user-shell-flashes">
                        <?php foreach ($flashes as $flash): ?>
                            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
    <?php else: ?>
        <header class="site-header">
            <div class="container nav-wrap">
                <a class="brand" href="<?= APP_URL ?>/index.php"><?= e(APP_NAME) ?></a>

                <nav class="nav-links">
                    <a href="<?= APP_URL ?>/events/index.php">Events</a>

                    <?php if ($user): ?>
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <a href="<?= APP_URL ?>/admin/index.php">Admin Panel</a>
                        <?php endif; ?>
                        <span class="nav-user">Hi, <?= e((string) $user['name']) ?></span>
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
    <?php endif; ?>
    <?php
}

function render_footer(): void
{
    $user = auth_user();
    $isLoggedInUser = $user && (($user['role'] ?? '') !== 'admin');
    ?>
    <?php if ($isLoggedInUser): ?>
            </main>
        </div>
    <?php else: ?>
        <footer class="site-footer">
            <div class="container footer-grid">
                <div>
                    <h3><?= e(APP_NAME) ?></h3>
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
    <?php endif; ?>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}

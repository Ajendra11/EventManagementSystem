<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

function render_admin_header(string $title = APP_NAME, array $pageStyles = []): void
{
    send_security_headers();
    $user = auth_user();

    if (!$user || ($user['role'] ?? '') !== 'admin') {
        redirect('/index.php');
    }

    $flashes = get_flashes();
    $currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $displayName = (string) ($user['name'] ?? $user['full_name'] ?? 'Administrator');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | Admin Portal</title>

        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/base-core.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin-core.css">

        <?php foreach ($pageStyles as $stylesheet): ?>
            <link rel="stylesheet" href="<?= APP_URL . '/assets/css/' . ltrim($stylesheet, '/') ?>">
        <?php endforeach; ?>
    </head>
    <body class="admin-body">

    <div class="admin-layout-shell" id="adminLayoutShell">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-header">
                <a class="admin-brand" href="<?= APP_URL ?>/admin/index.php" aria-label="EventHub Admin">
                    EventHub Admin
                </a>

                <button
                    type="button"
                    class="admin-sidebar-close"
                    id="adminSidebarClose"
                    aria-label="Hide sidebar"
                >
                    ✕
                </button>
            </div>

            <nav class="admin-sidebar-nav">
                <a href="<?= APP_URL ?>/admin/index.php" class="admin-nav-item <?= strpos($currentPath, '/admin/index.php') !== false ? 'active' : '' ?>">
                    <span>Dashboard</span>
                </a>

                <a href="<?= APP_URL ?>/admin/events.php" class="admin-nav-item <?= strpos($currentPath, '/admin/events') !== false ? 'active' : '' ?>">
                    <span>Events</span>
                </a>

                <a href="<?= APP_URL ?>/admin/users.php" class="admin-nav-item <?= strpos($currentPath, '/admin/users.php') !== false ? 'active' : '' ?>">
                    <span>Users</span>
                </a>

                <a href="<?= APP_URL ?>/admin/bookings.php" class="admin-nav-item <?= strpos($currentPath, '/admin/bookings.php') !== false ? 'active' : '' ?>">
                    <span>Bookings</span>
                </a>

                <div class="admin-sidebar-divider">
                    <a href="<?= APP_URL ?>/index.php" class="admin-nav-item">
                        <span>Public Site</span>
                    </a>
                </div>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-info">
                    <span class="admin-user-name"><?= e($displayName) ?></span>
                    <span class="admin-user-role">Administrator</span>
                </div>

                <a href="<?= APP_URL ?>/auth/logout.php" class="admin-logout-btn">Logout&rarr;</a>
            </div>
        </aside>

        <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

        <main class="admin-main">
            <div class="admin-main-topbar">
                <button
                    type="button"
                    class="admin-sidebar-toggle"
                    id="adminSidebarToggle"
                    aria-label="Toggle sidebar"
                    aria-expanded="true"
                >
                    ☰
                </button>

                <div class="admin-main-topbar-title"><?= e($title) ?></div>
            </div>

            <?php if ($flashes): ?>
                <div class="flash-stack admin-flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    <?php
}

function render_admin_footer(): void
{
    ?>
        </main>
    </div>

    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
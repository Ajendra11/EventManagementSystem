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
    $displayName = (string) ($user['name'] ?? $user['full_name'] ?? 'System Admin');

    $navItems = [
        [
            'label' => 'Dashboard',
            'href' => APP_URL . '/admin/index.php',
            'active' => str_contains($currentPath, '/admin/index.php') || str_ends_with($currentPath, '/admin/'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z"/></svg>',
        ],
        [
            'label' => 'Events',
            'href' => APP_URL . '/admin/events.php',
            'active' => str_contains($currentPath, '/admin/events'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M4 8h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/></svg>',
        ],
        [
            'label' => 'Users',
            'href' => APP_URL . '/admin/users.php',
            'active' => str_contains($currentPath, '/admin/users.php'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0ZM4 21a8 8 0 0 1 16 0M18 8a3 3 0 0 1 3 3M21 21a5.5 5.5 0 0 0-3.5-5.1"/></svg>',
        ],
        [
            'label' => 'Bookings',
            'href' => APP_URL . '/admin/bookings.php',
            'active' => str_contains($currentPath, '/admin/bookings.php'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-2 12H7L5 7Zm3 0a4 4 0 0 1 8 0M9 11h.01M15 11h.01"/></svg>',
        ],
        [
            'label' => 'Reviews',
            'href' => APP_URL . '/admin/reviews.php',
            'active' => str_contains($currentPath, '/admin/reviews.php'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5Zm5 5h6M9 13h3"/></svg>',
        ],
        [
            'label' => 'Verify Tickets',
            'href' => APP_URL . '/tickets/verify.php',
            'active' => str_contains($currentPath, '/tickets/verify.php'),
            'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Zm8-2v12"/></svg>',
        ],
    ];
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | EventHub Admin</title>

        <script>
            (function () {
                var theme = 'light';

                try {
                    var stored = localStorage.getItem('eventhub_theme');
                    var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    theme = stored === 'dark' || stored === 'light' ? stored : (systemDark ? 'dark' : 'light');
                } catch (error) {
                    theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.style.colorScheme = theme;
            })();
        </script>

        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/theme.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/base-core.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin-core.css">

        <?php foreach ($pageStyles as $stylesheet): ?>
            <link rel="stylesheet" href="<?= APP_URL . '/assets/css/' . ltrim($stylesheet, '/') ?>">
        <?php endforeach; ?>
    </head>

    <body class="admin-body">
    <div class="admin-layout-shell" id="adminLayoutShell">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-header">
                <a class="admin-brand" href="<?= APP_URL ?>/admin/index.php" aria-label="EventHub Admin Dashboard">
                    <span class="admin-brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 40 40" focusable="false">
                            <rect x="6" y="7" width="28" height="27" rx="9" fill="currentColor" opacity="0.14"/>
                            <path d="M14 13.5h12M14 18h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M20 30s6-5.1 6-10a6 6 0 1 0-12 0c0 4.9 6 10 6 10Z" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="20" cy="20" r="2" fill="currentColor"/>
                        </svg>
                    </span>

                    <span class="admin-brand-copy">
                        <span class="admin-brand-word">EventHub Admin</span>
                        <span class="admin-brand-sub">Management Panel</span>
                    </span>
                </a>

                <button
                    type="button"
                    class="admin-sidebar-close"
                    id="adminSidebarClose"
                    aria-label="Close admin sidebar"
                >
                    ×
                </button>
            </div>

            <nav class="admin-sidebar-nav" aria-label="Admin navigation">
                <?php foreach ($navItems as $item): ?>
                    <a
                        href="<?= e($item['href']) ?>"
                        class="admin-nav-item <?= $item['active'] ? 'active' : '' ?>"
                        <?= $item['active'] ? 'aria-current="page"' : '' ?>
                    >
                        <span class="admin-nav-icon"><?= $item['icon'] ?></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="admin-sidebar-bottom">
                <a href="<?= APP_URL ?>/index.php" class="admin-nav-item admin-nav-secondary">
                    <span class="admin-nav-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 4h6v6M20 4 10 14M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/>
                        </svg>
                    </span>
                    <span>Public Site</span>
                </a>

                <button type="button" class="admin-theme-toggle" data-theme-toggle aria-label="Switch color theme">
                    <span class="admin-theme-toggle-icon" data-theme-icon aria-hidden="true">🌙</span>
                    <span class="admin-theme-toggle-text" data-theme-label>Dark</span>
                </button>

                <div class="admin-user-info">
                    <span class="admin-user-name"><?= e($displayName) ?></span>
                    <span class="admin-user-role">Administrator</span>
                </div>

                <a href="<?= APP_URL ?>/auth/logout.php" class="admin-nav-item admin-logout-btn js-logout-confirm">
                    <span class="admin-nav-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10 17 15 12l-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/>
                        </svg>
                    </span>
                    <span>Logout</span>
                </a>
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

                <button
                    type="button"
                    class="admin-theme-toggle admin-theme-toggle-compact"
                    data-theme-toggle
                    aria-label="Switch color theme"
                >
                    <span class="admin-theme-toggle-icon" data-theme-icon aria-hidden="true">🌙</span>
                    <span class="admin-theme-toggle-text" data-theme-label>Dark</span>
                </button>
            </div>

            <?php if ($flashes): ?>
                <div class="flash-stack admin-flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>">
                            <?= e($flash['message']) ?>
                        </div>
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

    <script src="<?= APP_URL ?>/assets/js/theme.js"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
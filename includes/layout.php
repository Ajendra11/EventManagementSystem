<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function render_header(string $title = APP_NAME): void
{
    $flashes = get_flashes();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
    </head>
    <body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="<?= APP_URL ?>/index.php"><?= e(APP_NAME) ?></a>
            <nav class="nav-links">
                <a href="<?= APP_URL ?>/index.php">Home</a>
                 <a href="<?= APP_URL ?>/events/index.php">Events</a>
                <a href="<?= APP_URL ?>/auth/login.php">Login</a>
                <a class="btn btn-sm btn-primary" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
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
            <div><h3>EventHub</h3></div>
            <div>
                <h4>Account</h4>
                <a href="<?= APP_URL ?>/auth/login.php">Sign In</a><br>
                <a href="<?= APP_URL ?>/auth/register.php">Register</a>
            </div>
            <div>
                <h4>Admin</h4>
                <a href="<?= APP_URL ?>/admin/login.php">Admin Portal</a>
            </div>
        </div>
    </footer>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
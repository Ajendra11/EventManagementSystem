<?php

declare(strict_types=1);


require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

function current_page_stylesheet(): ?string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $map = [
        '/auth/login.php'    => 'login.css',
        '/auth/register.php' => 'register.css',
        '/admin/login.php'   => 'admin-login.css',
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

    if ($script === '') return 'page-home';

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
    <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>

    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">

    <?php if ($pageStylesheet = current_page_stylesheet()): ?>
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= e($pageStylesheet) ?>">
    <?php endif; ?>
</head>

<body class="<?= e(current_page_class()) ?>">

<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= APP_URL ?>/index.php"><?= e(APP_NAME) ?></a>

        <nav class="nav-links">

            <!-- ALWAYS VISIBLE BUT DISABLED -->
            <span class="nav-link disabled">Events</span>

            <?php if ($user): ?>

                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= APP_URL ?>/admin/index.php">Admin Panel</a>
                    <span class="nav-link disabled">Reviews</span>
                <?php else: ?>
                    <span class="nav-link disabled">My Bookings</span>
                    <span class="nav-link disabled">My Reviews</span>
                    <a href="<?= APP_URL ?>/auth/profile.php">Profile</a>
                <?php endif; ?>

                <a href="<?= APP_URL ?>/auth/logout.php">Logout</a>
                <span class="nav-user">Hi, <?= e($user['name']) ?></span>

            <?php else: ?>

                <a href="<?= APP_URL ?>/auth/login.php">Login</a>
                <a class="btn btn-sm btn-primary" href="<?= APP_URL ?>/auth/register.php">Create Account</a>
                <a href="<?= APP_URL ?>/admin/login.php">Admin</a>

            <?php endif; ?>

        </nav>
    </div>
</header>

<?php if ($flashes): ?>
    <div class="container flash-stack">
        <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php

require_once DIR . '/functions.php';

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

        <div>
            <h3><?= e(APP_NAME) ?></h3>
            <p class="muted">Modern event platform (features coming soon).</p>
        </div>

        <div>
            <h4>Quick links</h4>
            <span class="footer-link disabled">Browse Events</span>
            <a href="<?= APP_URL ?>/auth/register.php">Join Now</a>
            <a href="<?= APP_URL ?>/auth/login.php">Sign In</a>
        </div>

        <div>
            <h4>Admin</h4>
            <a href="<?= APP_URL ?>/admin/login.php">Admin Portal</a>
        </div>

    </div>

    <div class="container" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
        <p class="muted" style="font-size:.85rem;">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>
        </p>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
<?php
}

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
<?php

declare(strict_types=1);

/** Minimal .env loader used when vlucas/phpdotenv is unavailable. */
function eventhub_load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $_ENV[$key] ?? $value;
        $_SERVER[$key] = $_SERVER[$key] ?? $value;
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

$envPath = dirname(__DIR__) . '/.env';
$autoloadLoaded = eventhub_require_vendor_autoload();
if ($autoloadLoaded && class_exists(\Dotenv\Dotenv::class) && is_file($envPath)) {
    \Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
} else {
    eventhub_load_env_file($envPath);
}

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return (string) $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = strtolower((string) env_value($key, $default ? 'true' : 'false'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

define('APP_NAME', env_value('APP_NAME', 'EventHub'));
define('APP_URL', rtrim((string) env_value('APP_URL', 'http://localhost/eventhub'), '/'));
define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', true));

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_NAME', env_value('DB_NAME', 'eventhub_php_v1'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));

// ── Email / SMTP ──────────────────────────────────────────────────────────────
// MAIL_DRIVER: 'log' = write to file (dev), 'smtp' = real SMTP (production)
define('MAIL_DRIVER', strtolower((string) env_value('MAIL_DRIVER', 'smtp')));
define('MAIL_HOST', env_value('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
define('MAIL_USERNAME', env_value('MAIL_USERNAME', 'admineventhub@gmail.com'));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD', 'cmtkqwzslkbcfcmu'));
define('MAIL_FROM', env_value('MAIL_FROM', 'admineventhub@gmail.com'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', APP_NAME));
define('MAIL_ENCRYPTION', env_value('MAIL_ENCRYPTION', 'tls'));
define('ADMIN_EMAIL', env_value('ADMIN_EMAIL', 'admineventhub@gmail.com'));

define('KHALTI_MODE', strtolower((string) env_value('KHALTI_MODE', 'mock')));
define('KHALTI_SECRET_KEY', env_value('KHALTI_SECRET_KEY', ''));
define('KHALTI_BASE_URL', rtrim((string) env_value('KHALTI_BASE_URL', 'https://a.khalti.com/api/v2'), '/'));

// ── Uploads ───────────────────────────────────────────────────────────────────
const UPLOAD_BANNER_DIR     = __DIR__ . '/../uploads/banners/';
const UPLOAD_BANNER_URL     = APP_URL . '/uploads/banners/';
const UPLOAD_MAX_SIZE_BYTES = 5 * 1024 * 1024;  // 5 MB (FR-EC-05)
const UPLOAD_ALLOWED_TYPES  = ['image/jpeg', 'image/png'];

// ── Session ───────────────────────────────────────────────────────────────────
const SESSION_NAME          = 'eventhub_session';
const SESSION_TIMEOUT       = 1800;   // 30 minutes idle (FR-UM-11)
const ITEMS_PER_PAGE        = 9;
const MAX_SEATS_PER_BOOKING = 10;    // FR-BP-01

// ── Paths ────────────────────────────────────────────────────────────────────
define('LOG_FILE',    dirname(__DIR__) . '/logs/app.log');
define('MAIL_LOG',    dirname(__DIR__) . '/logs/mail.log');

// ── Bootstrap session ─────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Kathmandu');

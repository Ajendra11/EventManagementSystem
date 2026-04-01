<?php

declare(strict_types=1);

// ── Application ───────────────────────────────────────────────────────────
const APP_NAME = 'EventHub';
const APP_URL  = 'http://localhost/eventhub-sprint1';   // No trailing slash

// ── Database ─────────────────────────────────────────────────────────────────
const DB_HOST = '127.0.0.1';
const DB_NAME = 'eventhub_php_v1';
const DB_USER = 'root';
const DB_PASS = '';

// ── Email / SMTP ──────────────────────────────────────────────────────────────
// MAIL_DRIVER: 'log' = write to file (dev), 'smtp' = real SMTP (production)
const MAIL_DRIVER   = 'log';
const MAIL_HOST     = 'smtp.gmail.com';
const MAIL_PORT     = 587;
const MAIL_USERNAME = 'your-email@gmail.com';
const MAIL_PASSWORD = 'your-app-password';
const MAIL_FROM     = 'noreply@eventhub.local';
const MAIL_FROM_NAME= APP_NAME;
const MAIL_ENCRYPTION = 'tls';   // tls | ssl | ''

// ── Uploads ───────────────────────────────────────────────────────────────────
const UPLOAD_BANNER_DIR     = __DIR__ . '/../uploads/banners/';
const UPLOAD_BANNER_URL     = APP_URL . '/uploads/banners/';
const UPLOAD_MAX_SIZE_BYTES = 5 * 1024 * 1024;  // 5 MB
const UPLOAD_ALLOWED_TYPES  = ['image/jpeg', 'image/png'];

// ── Session ───────────────────────────────────────────────────────────────────
const SESSION_NAME          = 'eventhub_session';
const SESSION_TIMEOUT       = 1800;   // 30 minutes idle
const ITEMS_PER_PAGE        = 9;
const MAX_SEATS_PER_BOOKING = 10;

// ── Paths ────────────────────────────────────────────────────────────────────
define('LOG_FILE', dirname(__DIR__) . '/logs/app.log');
define('MAIL_LOG', dirname(__DIR__) . '/logs/mail.log');

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
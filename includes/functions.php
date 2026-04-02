<?php

declare(strict_types=1);
require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return rtrim($uri, '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function is_admin(): bool
{
    return (auth_user()['role'] ?? '') === 'admin';
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'name'  => $user['full_name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function check_session_timeout(): void
{
    if (!is_logged_in()) return;
    $last = $_SESSION['last_activity'] ?? null;
    if ($last !== null && (time() - $last) > SESSION_TIMEOUT) {
        logout_user();
        session_start();
        flash('warning', 'Your session expired due to inactivity. Please sign in again.');
        redirect('auth/login.php');
    }
    $_SESSION['last_activity'] = time();
}

function require_login(): void
{
    check_session_timeout();
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    check_session_timeout();
    if (!is_admin()) {
        flash('error', 'Administrator access is required.');
        redirect('index.php');
    }
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/', $password);
}

function generate_secure_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function client_ip(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function log_app_error(string $message, string $file = '', int $line = 0): void
{
    $uid   = auth_user()['id'] ?? 'guest';
    $entry = sprintf("[%s] user=%s | %s | %s:%d\n", date('Y-m-d H:i:s'), $uid, $message, $file, $line);
    $dir   = dirname(LOG_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

function send_security_headers(): void
{
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");
    }
}
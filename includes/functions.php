<?php

declare(strict_types=1);
require_once __DIR__ . '/db.php';

// ── Output & routing ─────────────────────────────────────────────────────────

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . rtrim(APP_URL, '/') . '/' . ltrim($path, '/'));
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

// ── CSRF ─────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
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

// ── Flash messages ────────────────────────────────────────────────────────────

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

// ── Session & auth helpers ────────────────────────────────────────────────────

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
    session_regenerate_id(true);   // SEC-03
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
    if (!is_logged_in()) {
        return;
    }
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

// ── Validation ────────────────────────────────────────────────────────────────

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password(string $password): bool
{
    // FR-UM-01: 8–64 chars, uppercase, lowercase, digit, special character
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/', $password);
}

// ── Pagination ────────────────────────────────────────────────────────────────

function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    return [
        'page'   => $page,
        'pages'  => $pages,
        'limit'  => $perPage,
        'offset' => ($page - 1) * $perPage,
    ];
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function generate_secure_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function event_status(array $event): string
{
    if ($event['status'] === 'Draft')    return 'Draft';
    if ($event['status'] === 'Archived') return 'Archived';
    if (strtotime($event['start_date'] . ' ' . $event['start_time']) < time()) return 'Completed';
    if ((int) $event['seats_left'] <= 0) return 'Sold Out';
    return 'Open';
}

function client_ip(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// ── Image upload (FR-EC-05) ───────────────────────────────────────────────────

function handle_banner_upload(array $fileInput): array
{
    if ($fileInput['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null];
    }
    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed (code ' . $fileInput['error'] . ').'];
    }

    if ($fileInput['size'] > UPLOAD_MAX_SIZE_BYTES) {
        return ['error' => 'Banner image must be under 5 MB.'];
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileInput['tmp_name']);
    if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES, true)) {
        return ['error' => 'Banner image must be a JPG or PNG file.'];
    }

    $ext      = $mimeType === 'image/png' ? 'png' : 'jpg';
    $filename = 'banner_' . generate_secure_token(8) . '.' . $ext;

    if (!is_dir(UPLOAD_BANNER_DIR)) {
        mkdir(UPLOAD_BANNER_DIR, 0755, true);
    }

    $dest = UPLOAD_BANNER_DIR . $filename;
    if (!move_uploaded_file($fileInput['tmp_name'], $dest)) {
        return ['error' => 'Could not save uploaded image. Please try again.'];
    }

    return ['path' => UPLOAD_BANNER_URL . $filename];
}

// ── Audit logging (FR-UM-10) ─────────────────────────────────────────────────

function audit_log(string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
    try {
        db()->prepare(
            'INSERT INTO audit_logs (admin_id, action, target_type, target_id, details, ip_address, created_at)
             VALUES (:aid, :action, :ttype, :tid, :details, :ip, NOW())'
        )->execute([
            'aid'     => auth_user()['id'] ?? null,
            'action'  => $action,
            'ttype'   => $targetType,
            'tid'     => $targetId,
            'details' => $details,
            'ip'      => client_ip(),
        ]);
    } catch (\Throwable $e) {
        log_app_error('audit_log: ' . $e->getMessage(), __FILE__, __LINE__);
    }
}

// ── Error logging ─────────────────────────────────────────────────────────────

function log_app_error(string $message, string $file = '', int $line = 0): void
{
    $uid   = auth_user()['id'] ?? 'guest';
    $entry = sprintf("[%s] user=%s | %s | %s:%d\n", date('Y-m-d H:i:s'), $uid, $message, $file, $line);
    $dir   = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

// ── Security headers (SEC-09) ─────────────────────────────────────────────────

function send_security_headers(): void
{
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; "
             . "img-src 'self' https://images.unsplash.com data:; "
             . "style-src 'self' 'unsafe-inline'; "
             . "script-src 'self' 'unsafe-inline';");
    }
}

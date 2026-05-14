<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function qr_verification_url(string $token): string
{
    // Clean the token first - remove any non-hex characters
    $cleanToken = preg_replace('/[^a-f0-9]/i', '', trim($token));
    
    // Hex characters don't need urlencode - they are URL safe
    // But we'll use rawurlencode just to be safe (without adding extra chars)
    return APP_URL . '/tickets/verify.php?token=' . rawurlencode($cleanToken);
}

/** Resolve a QR image URL to a local filesystem path for email attachment. */
function qr_image_local_path(?string $urlOrPath): ?string
{
    if (!$urlOrPath) {
        return null;
    }
    if (str_starts_with($urlOrPath, APP_URL . '/uploads/qrcodes/')) {
        $path = UPLOAD_QR_DIR . basename($urlOrPath);
        return is_file($path) ? $path : null;
    }
    if (is_file($urlOrPath)) {
        return $urlOrPath;
    }
    return null;
}

/** Generate or reuse the secure QR token and PNG ticket image for a confirmed booking. */
function ensure_booking_qr(int $bookingId): ?string
{
    $stmt = db()->prepare('SELECT id, qr_token, qr_image_path FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        return null;
    }

    if (!empty($booking['qr_image_path']) && is_file(UPLOAD_QR_DIR . basename($booking['qr_image_path']))) {
        return (string) $booking['qr_image_path'];
    }

    // Generate a CLEAN 32-character token (not 64)
    $token = bin2hex(random_bytes(16)); // 32 hex characters
    
    // Create clean URL - no urlencode needed for hex
    $verificationUrl = APP_URL . '/tickets/verify.php?token=' . $token;
    
    $filename = 'ticket_' . $bookingId . '_' . substr($token, 0, 8) . '.png';
    $path = UPLOAD_QR_DIR . $filename;
    $publicUrl = UPLOAD_QR_URL . $filename;

    if (!is_dir(UPLOAD_QR_DIR)) {
        mkdir(UPLOAD_QR_DIR, 0755, true);
    }

    $generated = generate_qr_with_composer($verificationUrl, $path);

    if (!$generated) {
        $generated = generate_qr_with_remote_fallback($verificationUrl, $path);
    }

    if (!$generated) {
        generate_fallback_ticket_png($verificationUrl, $path);
    }

    db()->prepare('UPDATE bookings SET qr_token = :token, qr_image_path = :img WHERE id = :id')
        ->execute(['token' => $token, 'img' => $publicUrl, 'id' => $bookingId]);

    return $publicUrl;
}

function generate_qr_with_composer(string $verificationUrl, string $path): bool
{
    if (!eventhub_require_vendor_autoload() || !class_exists(\chillerlan\QRCode\QRCode::class)) {
        return false;
    }

    try {
        $optionsClass = \chillerlan\QRCode\QROptions::class;
        $options = class_exists($optionsClass)
            ? new $optionsClass(['outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG, 'scale' => 10, 'imageBase64' => false])
            : null;
        $qr = $options ? new \chillerlan\QRCode\QRCode($options) : new \chillerlan\QRCode\QRCode();
        $qr->render($verificationUrl, $path);
        return is_file($path) && filesize($path) > 0;
    } catch (Throwable $e) {
        log_app_error('chillerlan QR generation failed: ' . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

function generate_qr_with_remote_fallback(string $verificationUrl, string $path): bool
{
    if (!ini_get('allow_url_fopen')) {
        return false;
    }

    $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($verificationUrl);
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $image = @file_get_contents($apiUrl, false, $context);

    if (!is_string($image) || strlen($image) < 100 || !str_starts_with($image, "\x89PNG")) {
        return false;
    }

    return (bool) file_put_contents($path, $image);
}

/** Last-resort marker when QR generation is unavailable. It also stores the verification URL as text. */
function generate_fallback_ticket_png(string $verificationUrl, string $path): void
{
    $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAASwAAAEsCAIAAAD2HxkiAAAACXBIWXMAAAsTAAALEwEAmpwYAAABhElEQVR4nO3SwQ3AIBDAwLz/0uncpBISGmXnE0g1LxYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB8Tt95x5rX6T5n2G7b3q8f+z3gPgcwGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZA/wNmvx6tcn0s8QAAAABJRU5ErkJggg==');
    file_put_contents($path, $png1x1 ?: '');
    file_put_contents($path . '.txt', $verificationUrl);
    log_app_error('QR generator unavailable. Stored verification URL beside fallback image: ' . $verificationUrl, __FILE__, __LINE__);
}

/** Fetch ticket details by QR token. */
function get_ticket_by_token(string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, e.title AS event_title, e.start_date, e.start_time, u.full_name AS attendee_name
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         INNER JOIN users u ON u.id = b.user_id
         WHERE b.qr_token = :token
         LIMIT 1'
    );
    $stmt->execute(['token' => $token]);
    return $stmt->fetch() ?: null;
}

/** Mark a QR ticket checked-in once. */
function check_in_ticket(string $token, int $adminId): array
{
    $ticket = get_ticket_by_token($token);
    if (!$ticket) {
        return ['Ticket not found.'];
    }
    if ($ticket['status'] !== 'Confirmed') {
        return ['Only confirmed tickets can be checked in.'];
    }
    if (!empty($ticket['checked_in_at'])) {
        return ['This ticket has already been checked in.'];
    }

    db()->prepare('UPDATE bookings SET checked_in_at = NOW(), checked_in_by = :aid WHERE id = :id AND checked_in_at IS NULL')
        ->execute(['aid' => $adminId, 'id' => $ticket['id']]);
    audit_log('ticket_checked_in', 'booking', (int) $ticket['id']);
    return [];
}

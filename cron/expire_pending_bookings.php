<?php
require_once DIR . '/../includes/booking.php';

// Restrict web access to localhost only - cron should only run via CLI or localhost
if (PHP_SAPI !== 'cli') {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Forbidden.');
    }
}

$count = expire_pending_bookings();

if (PHP_SAPI === 'cli') {
    echo '[' . date('Y-m-d H:i:s') . "] Expired {$count} pending booking(s).\n";
} else {
    header('Content-Type: text/plain');
    echo "Expired {$count} pending booking(s).";
}?>

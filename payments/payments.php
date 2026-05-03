<?php
declare(strict_types=1);

// Error display is controlled by config.php (APP_DEBUG).

function cb_html(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cb_plain_page(string $type, string $title, string $message, ?array $booking = null, ?string $qrUrl = null, ?string $debug = null): void
{
    $isSuccess = $type === 'success';
    $appUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : 'http://localhost/eventhub';
    http_response_code($isSuccess ? 200 : 500);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= cb_html($title) ?> | EventHub</title>
        <style>
            body{font-family:Arial,sans-serif;background:#f4f6fb;margin:0;color:#111827;line-height:1.5;}
            .wrap{max-width:760px;margin:48px auto;padding:0 16px;}
            .card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 12px 30px rgba(15,23,42,.12);}
            .badge{display:inline-block;padding:7px 12px;border-radius:999px;font-weight:700;margin-bottom:14px;}
            .success{background:#dcfce7;color:#166534;}.error{background:#fee2e2;color:#991b1b;}
            h1{margin:0 0 12px;font-size:28px;} p{margin:8px 0;color:#374151;}
            table{width:100%;border-collapse:collapse;margin-top:18px;} th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;} th{width:160px;color:#475569;}
            .actions{margin-top:22px;display:flex;gap:12px;flex-wrap:wrap}.btn{display:inline-block;text-decoration:none;border-radius:10px;padding:11px 16px;font-weight:700}.primary{background:#4f46e5;color:#fff}.secondary{border:1px solid #cbd5e1;color:#111827;background:#fff}
            .qr{margin-top:20px;display:flex;align-items:center;gap:18px;flex-wrap:wrap}.qr img{width:180px;height:180px;object-fit:contain;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:8px;}
            pre{white-space:pre-wrap;background:#111827;color:#f8fafc;padding:14px;border-radius:10px;overflow:auto;margin-top:16px;font-size:13px;}
        </style>
    </head>
    <body>
    <main class="wrap">
        <section class="card">
            <span class="badge <?= $isSuccess ? 'success' : 'error' ?>"><?= $isSuccess ? 'Success' : 'Error' ?></span>
            <h1><?= cb_html($title) ?></h1>
            <p><?= cb_html($message) ?></p>

            <?php if ($booking): ?>
                <table>
                    <tr><th>Booking ID</th><td>#<?= (int)($booking['id'] ?? 0) ?></td></tr>
                    <tr><th>Event</th><td><?= cb_html($booking['event_title'] ?? 'Event') ?></td></tr>
                    <tr><th>Quantity</th><td><?= (int)($booking['quantity'] ?? 1) ?></td></tr>
                    <tr><th>Amount</th><td>Rs. <?= number_format((float)($booking['amount'] ?? 0), 2) ?></td></tr>
                    <tr><th>Status</th><td><?= cb_html($booking['status'] ?? '') ?></td></tr>
                    <?php if (!empty($booking['khalti_ref_id'])): ?>
                        <tr><th>Khalti Ref</th><td><?= cb_html($booking['khalti_ref_id']) ?></td></tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>

            <?php if ($isSuccess): ?>
                <h2 style="margin-top:24px;">Event booked successfully</h2>
                <p>Your payment has been received and your booking is confirmed.</p>
                <?php if ($qrUrl): ?>
                    <div class="qr">
                        <img src="<?= cb_html($qrUrl) ?>" alt="Booking QR Ticket">
                        <div>
                            <strong>QR ticket generated.</strong>
                            <p>Show this QR code at the event entrance.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <p><strong>Note:</strong> Booking is confirmed, but QR generation could not complete. Check My Bookings or run Composer/database fixes.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($debug): ?>
                <pre><?= cb_html($debug) ?></pre>
            <?php endif; ?>

            <div class="actions">
                <a class="btn primary" href="<?= cb_html($appUrl) ?>/bookings/my-bookings.php">Go to My Bookings</a>
                <a class="btn secondary" href="<?= cb_html($appUrl) ?>/events/index.php">Browse Events</a>
            </div>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

try {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/booking.php';
    require_once __DIR__ . '/../includes/qr.php';
} catch (Throwable $e) {
    cb_plain_page('error', 'Callback boot failed', 'The callback could not load the project files.', null, null, $e->getMessage());
}

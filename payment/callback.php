<?php
/**
 * Emergency-safe Khalti callback for EventHub.
 * Replace /payments/callback.php with this file if Khalti redirects back with
 * GET pidx/status but the page still returns HTTP 500.
 */
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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
    require_once __DIR__ . '/../includes/qr.php';
} catch (Throwable $e) {
    cb_plain_page('error', 'Callback boot failed', 'The callback could not load the project files.', null, null, $e->getMessage());
}

function cb_payload(): array
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;
}

function cb_transaction_id(array $payload): string
{
    foreach (['transaction_id', 'tidx', 'txnId', 'idx'] as $key) {
        if (!empty($payload[$key])) {
            return trim((string)$payload[$key]);
        }
    }
    return '';
}

try {
    $payload = cb_payload();
    $pidx = trim((string)($payload['pidx'] ?? ''));
    $status = trim((string)($payload['status'] ?? ''));
    $purchaseOrderId = (int)($payload['purchase_order_id'] ?? 0);
    $transactionId = cb_transaction_id($payload);

    if ($pidx === '') {
        cb_plain_page('error', 'Missing Khalti PIDX', 'Khalti returned to the site without a pidx value. Please retry payment from My Bookings.', null, null, json_encode($payload, JSON_PRETTY_PRINT));
    }

    if (strcasecmp($status, 'Completed') !== 0) {
        cb_plain_page('error', 'Payment not completed', 'Khalti did not return Completed status. Current status: ' . ($status ?: 'Unknown'), null, null, json_encode($payload, JSON_PRETTY_PRINT));
    }

    $pdo = db();
    $booking = null;

    // First try exact Khalti PIDX match. If the old database has no khalti_pidx column, fall back to purchase_order_id and show a clear warning only if update fails later.
    try {
        $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.khalti_pidx = :pidx LIMIT 1');
        $stmt->execute(['pidx' => $pidx]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        // Continue; old DB may not have khalti_pidx yet.
    }

    if (!$booking && $purchaseOrderId > 0) {
        $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.id = :id LIMIT 1');
        $stmt->execute(['id' => $purchaseOrderId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$booking) {
        cb_plain_page('error', 'Booking not found', 'Payment was completed, but the booking record could not be found. Check purchase_order_id and booking table.', null, null, json_encode($payload, JSON_PRETTY_PRINT));
    }

    $expectedAmount = (int)round((float)$booking['amount'] * 100);
    $returnedAmount = isset($payload['amount']) && is_numeric($payload['amount']) ? (int)$payload['amount'] : null;
    if ($returnedAmount !== null && $expectedAmount > 0 && $returnedAmount !== $expectedAmount) {
        cb_plain_page('error', 'Payment amount mismatch', 'Khalti amount does not match the booking amount. Booking was not confirmed.', $booking, null, "Expected paisa: {$expectedAmount}\nReturned paisa: {$returnedAmount}");
    }

    if (($booking['status'] ?? '') !== 'Confirmed') {
        try {
            $stmt = $pdo->prepare("UPDATE bookings
                SET status = 'Confirmed', khalti_pidx = :pidx, khalti_ref_id = :ref
                WHERE id = :id");
            $stmt->execute([
                'pidx' => $pidx,
                'ref' => $transactionId ?: $pidx,
                'id' => (int)$booking['id'],
            ]);
        } catch (Throwable $e) {
            cb_plain_page(
                'error',
                'Database columns missing',
                'Payment was completed, but the booking could not be confirmed because the database does not have the Khalti/QR columns yet. Run the payment QR SQL fix, then retry.',
                $booking,
                null,
                $e->getMessage()
            );
        }
    }

    $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$booking['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: $booking;

    $qrUrl = null;
    try {
        $qrUrl = ensure_booking_qr((int)$booking['id']);
        $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$booking['id']]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: $booking;
    } catch (Throwable $e) {
        $qrUrl = null;
        $booking['_qr_error'] = $e->getMessage();
    }

    cb_plain_page('success', 'Payment successful', 'Event booked successfully.', $booking, $qrUrl, !empty($booking['_qr_error']) ? 'QR warning: ' . $booking['_qr_error'] : null);
} catch (Throwable $e) {
    cb_plain_page('error', 'Payment callback failed', 'The callback crashed while handling Khalti response. The exact error is shown below.', null, null, $e->getMessage());
}

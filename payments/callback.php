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
        // Use confirm_paid_booking() so confirmed_at, QR, and email are all handled.
        $confirmErrors = confirm_paid_booking(
            (int)$booking['id'],
            $transactionId ?: $pidx,
            $payload
        );
        if ($confirmErrors) {
            cb_plain_page('error', 'Booking confirmation failed',
                'Payment was received, but the booking could not be confirmed: ' . implode(' ', $confirmErrors),
                $booking, null, null);
        }
    }

    $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$booking['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: $booking;

    // Persist khalti_pidx if booking was looked up by purchase_order_id
    try {
        $pdo->prepare("UPDATE bookings SET khalti_pidx = :pidx WHERE id = :id AND (khalti_pidx IS NULL OR khalti_pidx = '')")
            ->execute(['pidx' => $pidx, 'id' => (int)$booking['id']]);
    } catch (Throwable $ignored) {}

    $qrUrl = !empty($booking['qr_image_path']) ? (string)$booking['qr_image_path'] : null;
    if (!$qrUrl) {
        try {
            $qrUrl = ensure_booking_qr((int)$booking['id']);
            $stmt = $pdo->prepare('SELECT b.*, e.title AS event_title FROM bookings b INNER JOIN events e ON e.id = b.event_id WHERE b.id = :id LIMIT 1');
            $stmt->execute(['id' => (int)$booking['id']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC) ?: $booking;
        } catch (Throwable $e) {
            $qrUrl = null;
            $booking['_qr_error'] = $e->getMessage();
        }
    }

        cb_plain_page('success', 'Payment successful', 'Event booked successfully.', $booking, $qrUrl, !empty($booking['_qr_error']) ? 'QR warning: ' . $booking['_qr_error'] : null);
} catch (Throwable $e) {
    cb_plain_page('error', 'Payment callback failed', 'The callback crashed while handling Khalti response. The exact error is shown below.', null, null, $e->getMessage());
}

<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/payment.php';
require_once __DIR__ . '/qr.php';

/** Fetch one booking, optionally restricted to a user to prevent IDOR. */
function get_booking_by_id(int $bookingId, ?int $userId = null): ?array
{
    $sql = 'SELECT b.*, e.title AS event_title, e.start_date, e.start_time,
                   e.location, e.price AS unit_price, e.status AS event_status,
                   u.full_name, u.email
            FROM bookings b
            INNER JOIN events e ON e.id = b.event_id
            INNER JOIN users u ON u.id = b.user_id
            WHERE b.id = :id';
    $params = ['id' => $bookingId];

    if ($userId !== null) {
        $sql .= ' AND b.user_id = :uid';
        $params['uid'] = $userId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

/** Fetch booking by Khalti PIDX. */
function get_booking_by_pidx(string $pidx): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, e.title AS event_title, e.start_date, e.start_time, e.location, e.price AS unit_price,
                u.full_name, u.email
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         INNER JOIN users u ON u.id = b.user_id
         WHERE b.khalti_pidx = :pidx
         LIMIT 1'
    );
    $stmt->execute(['pidx' => $pidx]);
    return $stmt->fetch() ?: null;
}

/** Fetch all bookings belonging to a participant. */
function get_user_bookings(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT b.*, e.title AS event_title, e.start_date, e.start_time,
                e.location, e.status AS event_status, e.price AS unit_price
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         WHERE b.user_id = :uid
         ORDER BY e.start_date ASC, e.start_time ASC, b.booking_date DESC'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

/** Fetch platform bookings for admin management. */
function get_all_bookings_admin(?string $statusFilter = null): array
{
    $sql = 'SELECT b.*, u.full_name, u.email, e.title AS event_title, e.start_date, e.start_time
            FROM bookings b
            INNER JOIN users u  ON u.id  = b.user_id
            INNER JOIN events e ON e.id  = b.event_id';
    $params = [];

    if ($statusFilter && in_array($statusFilter, ['Pending','Confirmed','Cancelled','Refunded'], true)) {
        $sql .= ' WHERE b.status = :status';
        $params['status'] = $statusFilter;
    }

    $sql .= ' ORDER BY b.booking_date DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Create a free or paid booking atomically using a row-level event lock. */
function create_booking(int $eventId, int $userId, int $quantity = 1): array
{
    if ($quantity < 1 || $quantity > MAX_SEATS_PER_BOOKING) {
        return ['error' => 'Quantity must be between 1 and ' . MAX_SEATS_PER_BOOKING . '.'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $evtStmt = $pdo->prepare(
            'SELECT id, title, status, start_date, start_time, capacity, price
             FROM events WHERE id = :id FOR UPDATE'
        );
        $evtStmt->execute(['id' => $eventId]);
        $event = $evtStmt->fetch();

        if (!$event || $event['status'] !== 'Published') {
            $pdo->rollBack();
            return ['error' => 'Event not found or not available.'];
        }
        if (strtotime($event['start_date'] . ' ' . $event['start_time']) <= time()) {
            $pdo->rollBack();
            return ['error' => 'This event has already taken place.'];
        }

        $dupStmt = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE event_id = :eid AND user_id = :uid AND status IN ('Confirmed','Pending')
             LIMIT 1"
        );
        $dupStmt->execute(['eid' => $eventId, 'uid' => $userId]);
        if ($dupStmt->fetch()) {
            $pdo->rollBack();
            return ['error' => 'You already have an active booking for this event.'];
        }

        $takenStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(quantity),0) AS taken
             FROM bookings WHERE event_id = :eid AND status IN ('Confirmed','Pending')"
        );
        $takenStmt->execute(['eid' => $eventId]);
        $taken = (int) $takenStmt->fetch()['taken'];
        $seatsLeft = (int) $event['capacity'] - $taken;

        if ($seatsLeft < $quantity) {
            $pdo->rollBack();
            return ['error' => "Only $seatsLeft seat(s) remaining for this event."];
        }

        $unitPrice = (float) $event['price'];
        $amount = $unitPrice * $quantity;
        $status = $amount > 0 ? 'Pending' : 'Confirmed';

        $insStmt = $pdo->prepare(
            'INSERT INTO bookings (user_id, event_id, quantity, amount, status, booking_date, confirmed_at)
             VALUES (:uid, :eid, :qty, :amount, :status, NOW(), :confirmed_at)'
        );
        $insStmt->execute([
            'uid' => $userId,
            'eid' => $eventId,
            'qty' => $quantity,
            'amount' => $amount,
            'status' => $status,
            'confirmed_at' => $status === 'Confirmed' ? date('Y-m-d H:i:s') : null,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        $pdo->commit();

        if ($status === 'Confirmed') {
            ensure_booking_qr($bookingId);
            send_free_booking_email($bookingId);
        }

        return [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'is_free' => $amount <= 0,
            'status' => $status,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_app_error('create_booking: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['error' => 'A system error occurred. Please try again.'];
    }
}

/** Send a free-booking confirmation email with a QR ticket when available. */
function send_free_booking_email(int $bookingId): void
{
    $qrUrl = ensure_booking_qr($bookingId);
    $booking = get_booking_by_id($bookingId);
    if (!$booking) {
        return;
    }

    $attachment = qr_image_local_path($qrUrl);

    send_email(
        (string) $booking['email'],
        'EventHub booking confirmed',
        "Your booking for {$booking['event_title']} is confirmed.\nSeats: {$booking['quantity']}\nDate: {$booking['start_date']} at " . substr((string) $booking['start_time'], 0, 5) . "\nYour QR ticket is attached and available in My Bookings.",
        $attachment ? [$attachment] : []
    );
}

/** Confirm a paid booking after successful Khalti verification and generate QR ticket. */
function confirm_paid_booking(int $bookingId, string $transactionId, array $payload = []): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id AND status = "Pending" FOR UPDATE');
        $stmt->execute(['id' => $bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $pdo->rollBack();
            return ['Booking is not pending or no longer exists.'];
        }

        $pdo->prepare(
            "UPDATE bookings
             SET status = 'Confirmed', khalti_ref_id = :ref, confirmed_at = NOW()
             WHERE id = :id"
        )->execute(['ref' => $transactionId, 'id' => $bookingId]);

        $pdo->commit();

        $qrUrl = ensure_booking_qr($bookingId);
        $fresh = get_booking_by_id($bookingId);
        if ($fresh) {
            $attachment = qr_image_local_path($qrUrl);
            send_email(
                (string) $fresh['email'],
                'EventHub paid booking confirmed',
                "Your paid booking for {$fresh['event_title']} is confirmed.\nSeats: {$fresh['quantity']}\nAmount: Rs. " . number_format((float) $fresh['amount'], 2) . "\nYour QR ticket is attached and available in My Bookings.",
                $attachment ? [$attachment] : []
            );
        }

        log_payment_event($bookingId, 'success', (float) ($fresh['amount'] ?? 0), 'confirmed', $payload);
        return [];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_app_error('confirm_paid_booking: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['Unable to confirm booking.'];
    }
}

/** Cancel eligibility rules for confirmed bookings. */
function is_cancellation_eligible(array $booking): array
{
    if (!in_array($booking['status'], ['Confirmed'], true)) {
        return [false, 'Only confirmed bookings can be cancelled.'];
    }

    $eventTime = strtotime($booking['start_date'] . ' ' . $booking['start_time']);
    $hoursLeft = ($eventTime - time()) / 3600;

    if ((float) $booking['amount'] > 0 && $hoursLeft < 24) {
        return [false, 'Paid booking cancellations are not allowed within 24 hours of the event.'];
    }

    return [true, ''];
}

/** Cancel a participant booking with owner verification and optional refund. */
function cancel_booking(int $bookingId, int $userId): array
{
    $booking = get_booking_by_id($bookingId, $userId);
    if (!$booking) {
        return ['Booking not found.'];
    }

    [$eligible, $reason] = is_cancellation_eligible($booking);
    if (!$eligible) {
        return [$reason];
    }

    try {
        $status = 'Cancelled';
        if ((float) $booking['amount'] > 0) {
            $refund = refund_khalti_payment($booking);
            $status = $refund['ok'] ? 'Refunded' : 'Cancelled';
        }

        db()->prepare("UPDATE bookings SET status = :status, cancelled_at = NOW() WHERE id = :id")
            ->execute(['status' => $status, 'id' => $bookingId]);

        return [];
    } catch (Throwable $e) {
        log_app_error('cancel_booking: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['A system error occurred during cancellation.'];
    }
}

/** Cancel pending payment-stage booking for its owner. */
function cancel_pending_booking(int $bookingId, int $userId): array
{
    $booking = get_booking_by_id($bookingId, $userId);
    if (!$booking || $booking['status'] !== 'Pending') {
        return ['Pending booking not found.'];
    }

    db()->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id = :id AND user_id = :uid")
        ->execute(['id' => $bookingId, 'uid' => $userId]);

    log_payment_event($bookingId, 'payment_cancelled', (float) $booking['amount'], 'cancelled_by_user');
    return [];
}

/** Expire all pending paid bookings older than 15 minutes. */
function expire_pending_bookings(): int
{
    $stmt = db()->prepare(
        "SELECT id, amount FROM bookings
         WHERE status = 'Pending' AND booking_date < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return 0;
    }

    $ids = array_map(fn($r) => (int) $r['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id IN ($placeholders)")
        ->execute($ids);

    foreach ($rows as $row) {
        log_payment_event((int) $row['id'], 'auto_expiry', (float) $row['amount'], 'cancelled');
    }

    return count($rows);
}

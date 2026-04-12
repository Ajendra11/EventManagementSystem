<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

// ── Booking queries ───────────────────────────────────────────────────────────

function get_booking_by_id(int $bookingId, ?int $userId = null): ?array
{
    $sql    = 'SELECT b.*, e.title AS event_title, e.start_date, e.start_time,
                      e.location, e.price AS unit_price, e.status AS event_status
               FROM bookings b
               INNER JOIN events e ON e.id = b.event_id
               WHERE b.id = :id';
    $params = ['id' => $bookingId];
    if ($userId !== null) {
        $sql   .= ' AND b.user_id = :uid';
        $params['uid'] = $userId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function get_user_bookings(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT b.*, e.title AS event_title, e.start_date, e.start_time,
                e.location, e.status AS event_status
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         WHERE b.user_id = :uid
         ORDER BY e.start_date ASC, e.start_time ASC, b.booking_date DESC'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function get_all_bookings_admin(?string $statusFilter = null): array
{
    $sql    = 'SELECT b.*, u.full_name, u.email, e.title AS event_title, e.start_date, e.start_time
               FROM bookings b
               INNER JOIN users u  ON u.id  = b.user_id
               INNER JOIN events e ON e.id  = b.event_id';
    $params = [];
    if ($statusFilter && in_array($statusFilter, ['Pending','Confirmed','Cancelled'], true)) {
        $sql   .= ' WHERE b.status = :status';
        $params['status'] = $statusFilter;
    }
    $sql .= ' ORDER BY b.booking_date DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ── Booking creation (atomic) — FR-BP-01..03, FR-BP-10 ───────────────────────
// Sprint 1: Free events only. Paid event booking deferred to Sprint 2 (Khalti).

function create_booking(int $eventId, int $userId, int $quantity = 1): array
{
    if ($quantity < 1 || $quantity > MAX_SEATS_PER_BOOKING) {
        return ['error' => 'Quantity must be between 1 and ' . MAX_SEATS_PER_BOOKING . '.'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock the event row to prevent race conditions (FR-BP-02)
        $evtStmt = $pdo->prepare(
            "SELECT id, status, start_date, start_time, capacity, price
             FROM events WHERE id = :id FOR UPDATE"
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

        // Sprint 1: Only free events can be booked
        if ((float) $event['price'] > 0) {
            $pdo->rollBack();
            return ['error' => 'Paid event booking is not available yet. Please check back for Sprint 2 when Khalti payment integration launches.'];
        }

        // Count seats already taken (Confirmed + Pending)
        $takenStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(quantity),0) AS taken
             FROM bookings WHERE event_id=:eid AND status IN ('Confirmed','Pending')"
        );
        $takenStmt->execute(['eid' => $eventId]);
        $taken     = (int) $takenStmt->fetch()['taken'];
        $seatsLeft = $event['capacity'] - $taken;

        if ($seatsLeft < $quantity) {
            $pdo->rollBack();
            return ['error' => "Only $seatsLeft seat(s) remaining for this event."];
        }

        // FR-BP-03: no duplicate active booking per user+event
        $dupStmt = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE event_id=:eid AND user_id=:uid AND status IN ('Confirmed','Pending') LIMIT 1"
        );
        $dupStmt->execute(['eid' => $eventId, 'uid' => $userId]);
        if ($dupStmt->fetch()) {
            $pdo->rollBack();
            return ['error' => 'You already have an active booking for this event.'];
        }

        $insStmt = $pdo->prepare(
            'INSERT INTO bookings (user_id, event_id, quantity, amount, status, booking_date)
             VALUES (:uid,:eid,:qty,:amount,"Pending",NOW())'
        );
        $insStmt->execute([
            'uid'    => $userId,
            'eid'    => $eventId,
            'qty'    => $quantity,
            'amount' => 0.00,
        ]);
        $bookingId = (int) $pdo->lastInsertId();

        $pdo->commit();
        return ['booking_id' => $bookingId, 'amount' => 0.00, 'is_free' => true];

    } catch (\Throwable $e) {
        $pdo->rollBack();
        log_app_error('create_booking: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['error' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Confirm a free-event booking immediately (FR-BP-10).
 * Sprint 2 will extend this for paid bookings after Khalti verification.
 */
function confirm_booking(int $bookingId): void
{
    try {
        db()->prepare(
            "UPDATE bookings SET status='Confirmed' WHERE id=:id AND status='Pending'"
        )->execute(['id' => $bookingId]);
    } catch (\Throwable $e) {
        log_app_error('confirm_booking: ' . $e->getMessage(), __FILE__, __LINE__);
    }
}

// ── Cancellation (US-006) ────────────────────────────────────────────────────
// Sprint 1: Cancel and release seat (no refund processing needed for free events)

function is_cancellation_eligible(array $booking): array
{
    if (!in_array($booking['status'], ['Confirmed'], true)) {
        return [false, 'Only confirmed bookings can be cancelled.'];
    }
    $eventTime = strtotime($booking['start_date'] . ' ' . $booking['start_time']);
    $hoursLeft = ($eventTime - time()) / 3600;
    if ($hoursLeft < 24) {
        return [false, 'Cancellations are not allowed within 24 hours of the event.'];
    }
    return [true, ''];
}

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
        db()->prepare("UPDATE bookings SET status='Cancelled', cancelled_at=NOW() WHERE id=:id")
            ->execute(['id' => $bookingId]);
        return [];
    } catch (\Throwable $e) {
        log_app_error('cancel_booking: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['A system error occurred during cancellation.'];
    }
}

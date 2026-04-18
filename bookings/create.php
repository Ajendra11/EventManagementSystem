<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/event.php';

require_login();

if (is_admin()) { flash('error', 'Administrators cannot book events.'); redirect('events/index.php'); }
if (!is_post()) { redirect('events/index.php'); }

verify_csrf();

$eventId  = (int)($_POST['event_id'] ?? 0);
$quantity = max(1, min((int)($_POST['quantity'] ?? 1), MAX_SEATS_PER_BOOKING));
$userId   = (int)auth_user()['id'];

$result = create_booking($eventId, $userId, $quantity);

if (isset($result['error'])) {
    flash('error', $result['error']);
    redirect('events/show.php?id=' . $eventId);
}

$bookingId = (int)$result['booking_id'];

//Confirm free-event bookings immediately
confirm_booking($bookingId);
flash('success', 'Your booking is confirmed! You can view it in My Bookings.');
redirect('bookings/my-bookings.php');

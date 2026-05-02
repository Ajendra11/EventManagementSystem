<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';

require_login();

$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId) {
    $errors = cancel_pending_booking($bookingId, (int)auth_user()['id']);
    flash($errors ? 'error' : 'success', $errors[0] ?? 'Payment cancelled and seats released.');
}
redirect('bookings/my-bookings.php');

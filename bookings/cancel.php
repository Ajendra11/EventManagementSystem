<?php
// Load layout and booking helpers
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';

// Redirect to login if not authenticated
require_login();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('bookings/my-bookings.php');
}

// Validate CSRF token to prevent cross-site request forgery
verify_csrf();

// Validate booking ID from POST data
$bookingId = (int) ($_POST['booking_id'] ?? 0);
if ($bookingId <= 0) {
    flash('error', 'Invalid booking.');
    redirect('bookings/my-bookings.php');
}

// Get current logged-in user ID
$userId = (int) auth_user()['id'];

// Attempt cancellation — returns array of errors or empty array on success
$errors = cancel_booking($bookingId, $userId);

if ($errors) {
    // Show first error message as flash
    flash('error', $errors[0]);
} else {
    flash('success', 'Your booking has been cancelled successfully.');
}

// Redirect back to bookings list
redirect('bookings/my-bookings.php');
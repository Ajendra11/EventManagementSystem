<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/payment.php';

require_login();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = get_booking_by_id($bookingId, (int)auth_user()['id']);

if (!$booking || $booking['status'] !== 'Pending' || (float)$booking['amount'] <= 0) {
    flash('error', 'Pending paid booking not found.');
    redirect('bookings/my-bookings.php');
}

$payment = initiate_khalti_payment($booking);
if (!$payment['ok']) {
    flash('error', $payment['message'] ?? 'Could not start payment.');
    redirect('bookings/my-bookings.php');
}

render_header('Khalti Payment');
?>
<div class="container section">
    <div class="panel" style="max-width:680px;">
        <h2>Complete Khalti payment</h2>
        <p class="muted">Your booking is reserved for 15 minutes while payment is pending.</p>

        <table class="info-table">
            <tr><th>Event</th><td><?= e($booking['event_title']) ?></td></tr>
            <tr><th>Seats</th><td><?= (int)$booking['quantity'] ?></td></tr>
            <tr><th>Amount</th><td>Rs. <?= number_format((float)$booking['amount'], 2) ?></td></tr>
            <tr><th>Payment mode</th><td><?= khalti_is_mock_mode() ? 'Local mock Khalti checkout' : 'Khalti ePayment' ?></td></tr>
        </table>

        <?php if (!empty($payment['requires_reinitiation'])): ?>
            <div class="flash flash-warning" style="margin-top:1rem;">
                <?= e($payment['message'] ?? 'This Khalti session was already initiated.') ?>
            </div>
            <a class="btn btn-outline" href="<?= APP_URL ?>/payments/cancel.php?booking_id=<?= (int)$booking['id'] ?>">Cancel pending booking</a>
        <?php elseif (!empty($payment['mock'])): ?>
            <form method="post" action="<?= APP_URL ?>/payments/callback.php" style="margin-top:1.25rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="pidx" value="<?= e($payment['pidx']) ?>">
                <button class="btn btn-primary" type="submit">Simulate successful Khalti payment</button>
                <a class="btn btn-outline" href="<?= APP_URL ?>/payments/cancel.php?booking_id=<?= (int)$booking['id'] ?>">Cancel payment</a>
            </form>
        <?php else: ?>
            <div class="status-row" style="margin-top:1.25rem;">
                <a class="btn btn-primary" href="<?= e($payment['payment_url']) ?>">Continue to Khalti</a>
                <a class="btn btn-outline" href="<?= APP_URL ?>/payments/cancel.php?booking_id=<?= (int)$booking['id'] ?>">Cancel payment</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php render_footer(); ?>

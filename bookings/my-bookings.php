<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';

require_login();

$userId   = (int) auth_user()['id'];
$bookings = get_user_bookings($userId);

render_header('My Bookings');
?>

<div class="container section bookings-shell">
    <div class="bookings-head">
        <div>
            <h2>My bookings</h2>
            <p class="muted">All your event bookings — past and upcoming.</p>
        </div>

        <a class="btn btn-outline bookings-head-btn" href="<?= APP_URL ?>/events/index.php">
            Find more events &rarr;
        </a>
    </div>

    <?php if (!$bookings): ?>
        <div class="panel empty bookings-empty">
            <p>You haven't booked any events yet.</p>
            <a class="btn btn-primary" style="margin-top:1rem;" href="<?= APP_URL ?>/events/index.php">Browse events</a>
        </div>
    <?php else: ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $b): ?>
                <?php
                [$eligible, $reason] = is_cancellation_eligible($b);
                $badgeClass = match ($b['status']) {
                    'Confirmed' => 'success',
                    'Pending'   => 'warning',
                    'Cancelled' => 'danger',
                    'Refunded'  => 'success',
                    default     => ''
                };
                $isPast = strtotime($b['start_date'] . ' ' . $b['start_time']) < time();
                ?>

                <article class="panel booking-card">
                    <div class="booking-card-top">
                        <div class="status-row booking-badges">
                            <span class="badge <?= $badgeClass ?>"><?= e($b['status']) ?></span>
                            <?php if ($isPast && $b['status'] === 'Confirmed'): ?>
                                <span class="badge">Past event</span>
                            <?php endif; ?>
                        </div>

                        <h3 class="booking-title">
                            <a href="<?= APP_URL ?>/events/show.php?id=<?= (int) $b['event_id'] ?>">
                                <?= e($b['event_title']) ?>
                            </a>
                        </h3>
                    </div>

                    <table class="info-table booking-info-table">
                        <tr><th>Date &amp; Time</th><td><?= e($b['start_date']) ?> at <?= e(substr($b['start_time'], 0, 5)) ?></td></tr>
                        <tr><th>Location</th><td><?= e($b['location']) ?></td>
                        <tr>
                            <th>Seats</th>
                            <td><?= (int) $b['quantity'] ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><?= (float) $b['amount'] > 0 ? 'Rs. ' . number_format((float) $b['amount'], 2) : 'Free' ?></td>
                        </tr>
                        <tr>
                            <th>Booked on</th>
                            <td><?= e(substr($b['booking_date'], 0, 16)) ?></td>
                        </tr>
                        <?php if (!empty($b['confirmed_at'])): ?>
                            <tr>
                                <th>Confirmed on</th>
                                <td><?= e(substr($b['confirmed_at'], 0, 16)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($b['status'] === 'Confirmed' && !empty($b['qr_image_path'])): ?>
                        <div class="panel" style="margin-top:1rem;">
                            <strong>QR ticket</strong>
                            <p class="muted">Show this QR at the venue for check-in.</p>
                            <img src="<?= e($b['qr_image_path']) ?>" alt="QR ticket for <?= e($b['event_title']) ?>" style="width:160px;height:160px;border-radius:12px;border:1px solid var(--border);">
                            <?php if (!empty($b['qr_token'])): ?>
                                <p class="muted" style="margin-top:.75rem;">
                                    Verification link: <a href="<?= e(qr_verification_url((string)$b['qr_token'])) ?>"><?= e(qr_verification_url((string)$b['qr_token'])) ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($b['status'] === 'Pending'): ?>
                        <div class="flash flash-warning" style="margin-top:1rem;">
                            Payment is pending. Complete Khalti payment within 15 minutes to keep your reservation.
                            <a href="<?= APP_URL ?>/payments/checkout.php?booking_id=<?= (int)$b['id'] ?>">Continue payment</a>
                        </div>
                    <?php endif; ?>

                    <div class="booking-actions">
                        <?php if ($eligible): ?>
                            <form method="post" action="<?= APP_URL ?>/bookings/cancel.php" onsubmit="return confirm('Cancel this booking?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit"><?= (float)$b['amount'] > 0 ? 'Cancel / refund' : 'Cancel booking' ?></button>
                            </form>
                        <?php elseif ($b['status'] === 'Confirmed' && !$isPast): ?>
                            <span class="muted booking-note" title="<?= e($reason) ?>">Not eligible for cancellation</span>
                        <?php endif; ?>

                        <?php if ($isPast && $b['status'] === 'Confirmed'): ?>
                            <a class="btn btn-outline btn-sm" href="<?= APP_URL ?>/reviews/create.php?event_id=<?= (int)$b['event_id'] ?>">Review event</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>

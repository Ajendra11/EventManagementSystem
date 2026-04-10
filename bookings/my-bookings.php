<?php
// Load layout and booking helpers
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';

// Redirect to login if not authenticated
require_login();

// Get current logged-in user ID
$userId = (int) auth_user()['id'];

// Fetch all bookings for this user
$bookings = get_user_bookings($userId);

render_header('My Bookings');
?>

<div class="container section bookings-shell">

    <div class="bookings-head">
        <div>
            <h2>My Bookings</h2>
            <p class="muted">All your event bookings — past and upcoming.</p>
        </div>
        <a class="btn btn-outline bookings-head-btn" href="<?= APP_URL ?>/events/index.php">
            Find more events &rarr;
        </a>
    </div>

    <?php if (!$bookings): ?>
        <!-- Empty state when user has no bookings -->
        <div class="panel empty bookings-empty">
            <p>You haven't booked any events yet.</p>
            <a class="btn btn-primary" style="margin-top:1rem;" href="<?= APP_URL ?>/events/index.php">
                Browse Events
            </a>
        </div>

    <?php else: ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $b): ?>
                <?php
                // Determine badge color based on booking status
                $badgeClass = match ($b['status']) {
                    'Confirmed' => 'success',
                    'Pending'   => 'warning',
                    'Cancelled' => 'danger',
                    default     => ''
                };

                // Check if the event has already passed
                $isPast = strtotime($b['start_date'] . ' ' . $b['start_time']) < time();
                ?>

                <article class="panel booking-card">

                    <div class="booking-card-top">
                        <!-- Status badge row -->
                        <div class="status-row booking-badges">
                            <span class="badge <?= $badgeClass ?>"><?= e($b['status']) ?></span>
                            <?php if ($isPast && $b['status'] === 'Confirmed'): ?>
                                <span class="badge">Past event</span>
                            <?php endif; ?>
                        </div>

                        <!-- Event title linking to event detail page -->
                        <h3 class="booking-title">
                            <a href="<?= APP_URL ?>/events/show.php?id=<?= (int) $b['event_id'] ?>">
                                <?= e($b['event_title']) ?>
                            </a>
                        </h3>
                    </div>

                    <!-- Booking detail table -->
                    <table class="info-table booking-info-table">
                        <tr>
                            <th>Date &amp; Time</th>
                            <td><?= e($b['start_date']) ?> at <?= e(substr($b['start_time'], 0, 5)) ?></td>
                        </tr>
                        <tr>
                            <th>Location</th>
                            <td><?= e($b['location']) ?></td>
                        </tr>
                        <tr>
                            <th>Seats</th>
                            <td><?= (int) $b['quantity'] ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>
                                <?= (float) $b['amount'] > 0
                                    ? 'Rs. ' . number_format((float) $b['amount'], 2)
                                    : 'Free' ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Booked on</th>
                            <td><?= e(substr($b['booking_date'], 0, 16)) ?></td>
                        </tr>
                    </table>

                </article>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php render_footer(); ?>
<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/event.php';

require_admin();

$stats = get_admin_stats();

function admin_dashboard_scalar(string $sql, array $params = [], mixed $fallback = 0): mixed
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return $value !== false ? $value : $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
}

function admin_dashboard_rows(string $sql, array $params = []): array
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

$publishedRate = ((int) $stats['events']) > 0
    ? (int) round(((int) $stats['published_events'] / (int) $stats['events']) * 100)
    : 0;

$pendingBookings = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'");
$confirmedBookings = (int) $stats['confirmed_bookings'];
$cancelledBookings = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM bookings WHERE status = 'Cancelled'");
$refundedBookings = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM bookings WHERE status = 'Refunded'");

$pendingReviews = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM reviews WHERE status = 'Pending'");
$approvedReviews = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM reviews WHERE status = 'Approved'");
$checkedInTickets = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM bookings WHERE checked_in_at IS NOT NULL");

$totalRevenue = (float) admin_dashboard_scalar(
    "SELECT COALESCE(SUM(amount), 0) FROM bookings WHERE status = 'Confirmed'"
);

$totalSeatsBooked = (int) admin_dashboard_scalar(
    "SELECT COALESCE(SUM(quantity), 0) FROM bookings WHERE status = 'Confirmed'"
);

$draftEvents = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM events WHERE status = 'Draft'");
$archivedEvents = (int) admin_dashboard_scalar("SELECT COUNT(*) FROM events WHERE status = 'Archived'");

$upcomingEvents = admin_dashboard_rows(
    "SELECT id, title, category, location, start_date, start_time, capacity, price, status
     FROM events
     WHERE status = 'Published'
       AND CONCAT(start_date, ' ', start_time) >= NOW()
     ORDER BY start_date ASC, start_time ASC
     LIMIT 5"
);

$recentBookings = admin_dashboard_rows(
    "SELECT b.id, b.quantity, b.amount, b.status, b.booking_date,
            e.title AS event_title,
            u.full_name,
            u.email
     FROM bookings b
     INNER JOIN events e ON e.id = b.event_id
     INNER JOIN users u ON u.id = b.user_id
     ORDER BY b.booking_date DESC
     LIMIT 6"
);

$reviewQueue = admin_dashboard_rows(
    "SELECT r.id, r.rating, r.comment, r.created_at,
            e.title AS event_title,
            u.full_name
     FROM reviews r
     INNER JOIN events e ON e.id = r.event_id
     INNER JOIN users u ON u.id = r.user_id
     WHERE r.status = 'Pending'
     ORDER BY r.created_at DESC
     LIMIT 6"
);

$bookingBreakdown = [
    'Confirmed' => $confirmedBookings,
    'Pending' => $pendingBookings,
    'Cancelled' => $cancelledBookings,
    'Refunded' => $refundedBookings,
];

$maxBookingValue = max(1, ...array_values($bookingBreakdown));

$eventBreakdown = [
    'Published' => (int) $stats['published_events'],
    'Draft' => $draftEvents,
    'Archived' => $archivedEvents,
];

$maxEventValue = max(1, ...array_values($eventBreakdown));

render_admin_header('Admin Dashboard', ['admin-dashboard.css']);
?>

<section class="admin-dashboard-page">
    <header class="dashboard-hero">
        <div>
            <p class="dashboard-kicker">EventHub Admin</p>
            <h1>Dashboard</h1>
            <p>Real-time overview of events, bookings, reviews, revenue, and ticket activity.</p>
        </div>

        <div class="dashboard-hero-actions">
            <a class="dashboard-btn dashboard-btn-muted" href="<?= APP_URL ?>/admin/bookings.php">View bookings</a>
            <a class="dashboard-btn dashboard-btn-primary" href="<?= APP_URL ?>/admin/events_form.php">+ Create event</a>
        </div>
    </header>

    <div class="dashboard-stats-grid">
        <article class="dashboard-stat-card">
            <span class="stat-label">Active Users</span>
            <strong><?= number_format((int) $stats['users']) ?></strong>
            <small>Total registered users</small>
        </article>

        <article class="dashboard-stat-card">
            <span class="stat-label">Total Events</span>
            <strong><?= number_format((int) $stats['events']) ?></strong>
            <small><?= $publishedRate ?>% currently published</small>
        </article>

        <article class="dashboard-stat-card">
            <span class="stat-label">Confirmed Bookings</span>
            <strong><?= number_format($confirmedBookings) ?></strong>
            <small><?= number_format($totalSeatsBooked) ?> confirmed seats</small>
        </article>

        <article class="dashboard-stat-card">
            <span class="stat-label">Revenue</span>
            <strong>Rs. <?= number_format($totalRevenue, 0) ?></strong>
            <small>From confirmed bookings</small>
        </article>
    </div>

    <div class="dashboard-mini-grid">
        <a class="dashboard-mini-card" href="<?= APP_URL ?>/admin/bookings.php?status=Pending">
            <span>Pending Bookings</span>
            <strong><?= number_format($pendingBookings) ?></strong>
        </a>

        <a class="dashboard-mini-card" href="<?= APP_URL ?>/admin/reviews.php?status=Pending">
            <span>Pending Reviews</span>
            <strong><?= number_format($pendingReviews) ?></strong>
        </a>

        <a class="dashboard-mini-card" href="<?= APP_URL ?>/tickets/verify.php">
            <span>Checked-in Tickets</span>
            <strong><?= number_format($checkedInTickets) ?></strong>
        </a>

        <a class="dashboard-mini-card" href="<?= APP_URL ?>/admin/reviews.php?status=Approved">
            <span>Approved Reviews</span>
            <strong><?= number_format($approvedReviews) ?></strong>
        </a>
    </div>

    <div class="dashboard-main-grid">
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Booking Status</h2>
                    <p>Actual booking distribution from your database.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/bookings.php">Open bookings</a>
            </div>

            <div class="dashboard-bars">
                <?php foreach ($bookingBreakdown as $label => $count): ?>
                    <?php $width = (int) round(($count / $maxBookingValue) * 100); ?>
                    <div class="dashboard-bar-row">
                        <div class="dashboard-bar-meta">
                            <span><?= e($label) ?></span>
                            <strong><?= number_format($count) ?></strong>
                        </div>
                        <div class="dashboard-bar-track">
                            <span style="width: <?= $width ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Event Catalog</h2>
                    <p>Published, draft, and archived event status.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/events.php">Manage events</a>
            </div>

            <div class="dashboard-bars">
                <?php foreach ($eventBreakdown as $label => $count): ?>
                    <?php $width = (int) round(($count / $maxEventValue) * 100); ?>
                    <div class="dashboard-bar-row">
                        <div class="dashboard-bar-meta">
                            <span><?= e($label) ?></span>
                            <strong><?= number_format($count) ?></strong>
                        </div>
                        <div class="dashboard-bar-track">
                            <span style="width: <?= $width ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="dashboard-main-grid dashboard-main-grid-wide">
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Upcoming Published Events</h2>
                    <p>The next events visible to users.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/events.php">View all</a>
            </div>

            <?php if (!$upcomingEvents): ?>
                <div class="dashboard-empty">
                    <strong>No upcoming published events.</strong>
                    <span>Create or publish an event to show it here.</span>
                    <a href="<?= APP_URL ?>/admin/events_form.php">Create event</a>
                </div>
            <?php else: ?>
                <div class="dashboard-event-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <?php
                        $eventDay = date('d', strtotime((string) $event['start_date']));
                        $eventMonth = date('M', strtotime((string) $event['start_date']));
                        $eventTime = substr((string) $event['start_time'], 0, 5);
                        $priceText = (float) $event['price'] > 0
                            ? 'Rs. ' . number_format((float) $event['price'], 0)
                            : 'Free';
                        ?>
                        <article class="dashboard-event-row">
                            <div class="event-date-chip">
                                <strong><?= e($eventDay) ?></strong>
                                <span><?= e($eventMonth) ?></span>
                            </div>

                            <div class="event-row-main">
                                <h3><?= e($event['title']) ?></h3>
                                <p><?= e($event['category']) ?> · <?= e($event['location']) ?> · <?= e($eventTime) ?></p>
                            </div>

                            <div class="event-row-side">
                                <span><?= e($priceText) ?></span>
                                <a href="<?= APP_URL ?>/admin/events_form.php?id=<?= (int) $event['id'] ?>">Edit</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Quick Admin Actions</h2>
                    <p>Useful shortcuts based on your app.</p>
                </div>
            </div>

            <div class="quick-action-list">
                <a class="quick-action-item" href="<?= APP_URL ?>/admin/events_form.php">
                    <span>+</span>
                    <div>
                        <strong>Create event</strong>
                        <small>Add a new event to the platform</small>
                    </div>
                </a>

                <a class="quick-action-item" href="<?= APP_URL ?>/admin/bookings.php">
                    <span>↗</span>
                    <div>
                        <strong>Manage bookings</strong>
                        <small>Check seats, payments, and status</small>
                    </div>
                </a>

                <a class="quick-action-item" href="<?= APP_URL ?>/admin/reviews.php?status=Pending">
                    <span>★</span>
                    <div>
                        <strong>Moderate reviews</strong>
                        <small>Approve or reject user reviews</small>
                    </div>
                </a>

                <a class="quick-action-item" href="<?= APP_URL ?>/tickets/verify.php">
                    <span>✓</span>
                    <div>
                        <strong>Verify ticket</strong>
                        <small>Check QR or ticket token</small>
                    </div>
                </a>
            </div>
        </aside>
    </div>

    <div class="dashboard-main-grid dashboard-bottom-grid">
        <section class="dashboard-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Recent Bookings</h2>
                    <p>Latest reservations made by users.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/bookings.php">View all</a>
            </div>

            <?php if (!$recentBookings): ?>
                <div class="dashboard-empty">
                    <strong>No bookings yet.</strong>
                    <span>Bookings will appear here once users reserve seats.</span>
                </div>
            <?php else: ?>
                <div class="dashboard-booking-list">
                    <?php foreach ($recentBookings as $booking): ?>
                        <?php
                        $badgeClass = match ((string) $booking['status']) {
                            'Confirmed' => 'success',
                            'Pending' => 'warning',
                            'Cancelled' => 'danger',
                            'Refunded' => 'info',
                            default => '',
                        };
                        ?>
                        <article class="dashboard-booking-row">
                            <div>
                                <h3><?= e($booking['full_name']) ?></h3>
                                <p><?= e($booking['event_title']) ?></p>
                                <small><?= e($booking['email']) ?></small>
                            </div>

                            <div class="booking-row-side">
                                <span class="badge <?= e($badgeClass) ?>"><?= e($booking['status']) ?></span>
                                <small>Rs. <?= number_format((float) $booking['amount'], 0) ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel review-queue-panel">
            <div class="dashboard-panel-head">
                <div>
                    <h2>Review Queue</h2>
                    <p>Pending feedback waiting for moderation.</p>
                </div>
                <a href="<?= APP_URL ?>/admin/reviews.php?status=Pending">Open queue</a>
            </div>

            <?php if (!$reviewQueue): ?>
                <div class="review-empty-compact">
                    <div class="review-empty-icon">★</div>
                    <strong>No pending reviews</strong>
                    <span>Your review queue is clear.</span>
                </div>
            <?php else: ?>
                <div class="review-queue-list">
                    <?php foreach ($reviewQueue as $index => $review): ?>
                        <details class="review-preview-card" <?= $index === 0 ? 'open' : '' ?>>
                            <summary>
                                <span class="review-avatar">
                                    <?= e(strtoupper(substr((string) $review['full_name'], 0, 1))) ?>
                                </span>

                                <span class="review-summary-copy">
                                    <strong><?= e($review['full_name']) ?></strong>
                                    <small><?= e($review['event_title']) ?></small>
                                </span>

                                <span class="review-rating">★ <?= (int) $review['rating'] ?></span>
                            </summary>

                            <div class="review-preview-body">
                                <?php if (!empty($review['comment'])): ?>
                                    <p><?= e((string) $review['comment']) ?></p>
                                <?php else: ?>
                                    <p class="review-muted">No written comment, rating only.</p>
                                <?php endif; ?>

                                <div class="review-preview-actions">
                                    <a href="<?= APP_URL ?>/admin/reviews.php?status=Pending">Moderate</a>
                                    <span><?= e(substr((string) $review['created_at'], 0, 10)) ?></span>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php render_admin_footer(); ?>
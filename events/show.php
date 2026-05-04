<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/event.php';

$event = get_event((int)($_GET['id'] ?? 0));
if (!$event) { flash('error', 'Event not found or no longer available.'); redirect('events/index.php'); }

$status    = event_status($event);
$seatsLeft = (int)$event['seats_left'];
$isPaid    = (float)$event['price'] > 0;
$canBook   = is_logged_in() && !is_admin() && $status === 'Open' && !$isPaid;
$maxQty    = min($seatsLeft, MAX_SEATS_PER_BOOKING);

render_header($event['title']);
?>
<div class="container section">
    <div class="grid-2">
        <article class="panel">
            <?php if (!empty($event['banner_image'])): ?>
                <img src="<?= e($event['banner_image']) ?>" alt="<?= e($event['title']) ?>"
                     style="border-radius:12px;margin-bottom:1.2rem;width:100%;" loading="lazy">
            <?php endif; ?>
            <div class="status-row">
                <span class="badge"><?= e($event['category']) ?></span>
                <span class="badge <?= $status === 'Open' ? 'success' : ($status === 'Sold Out' ? 'danger' : 'warning') ?>"><?= e($status) ?></span>
            </div>
            <h1 style="font-size:1.8rem;margin:.5rem 0 1rem;"><?= e($event['title']) ?></h1>
            <p style="line-height:1.8;"><?= nl2br(e($event['description'])) ?></p>
        </article>

        <aside style="align-self:start;position:sticky;top:80px;">
            <div class="panel">
                <h3>Event details</h3>
                <table class="info-table">
                    <tr><th>Date</th><td><?= e($event['start_date']) ?></td></tr>
                    <tr><th>Time</th><td><?= e(substr($event['start_time'], 0, 5)) ?></td></tr>
                    <tr><th>Location</th><td><?= e($event['location']) ?></td></tr>
                    <tr><th>Organizer</th><td><?= e($event['organizer_name']) ?></td></tr>
                    <tr><th>Price</th><td>
                        <?php if ($isPaid): ?>
                            <strong class="price-tag">Rs. <?= e(number_format((float)$event['price'], 2)) ?></strong> / seat
                        <?php else: ?>
                            <strong class="price-tag free">Free</strong>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Seats</th><td><?= $seatsLeft ?> / <?= (int)$event['capacity'] ?> remaining
                        <?php if ($seatsLeft === 0): ?>
                            <span class="badge danger" style="font-size:.75rem;">Sold Out</span>
                        <?php endif; ?>
                    </td></tr>
                </table>

                <?php if ($canBook): ?>
                    <form method="post" action="<?= APP_URL ?>/bookings/create.php" style="margin-top:1.2rem;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                        <?php if ($maxQty > 1): ?>
                            <div class="form-group">
                                <label for="quantity">Number of seats (max <?= $maxQty ?>)</label>
                                <input id="quantity" type="number" name="quantity" min="1" max="<?= $maxQty ?>" value="1">
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="quantity" value="1">
                        <?php endif; ?>
                        <button class="btn btn-primary btn-block" type="submit" style="margin-top:1rem;">
                            Book free seat
                        </button>
                    </form>

                <?php elseif ($isPaid && $status === 'Open'): ?>
                    <div class="flash flash-info" style="margin-top:1.2rem;">
                        <strong>Paid event booking coming in Sprint 2.</strong><br>
                        <span style="font-size:.9rem;">Khalti payment integration will be available soon. Check back later!</span>
                    </div>

                <?php elseif (!is_logged_in()): ?>
                    <a class="btn btn-primary btn-block" style="margin-top:1.2rem;" href="<?= APP_URL ?>/auth/login.php">Log in to book</a>
                    <p class="muted auth-note" style="text-align:center;">No account? <a href="<?= APP_URL ?>/auth/register.php">Register free</a></p>

                <?php elseif (is_admin()): ?>
                    <div class="flash flash-warning" style="margin-top:1.2rem;">Administrators cannot book events.</div>

                <?php elseif ($status === 'Sold Out'): ?>
                    <div class="flash flash-warning" style="margin-top:1.2rem;">This event is fully booked.</div>

                <?php elseif ($status === 'Completed'): ?>
                    <div class="flash" style="margin-top:1.2rem;">This event has already taken place.</div>

                <?php else: ?>
                    <div class="flash" style="margin-top:1.2rem;">Booking is not available for this event.</div>
                <?php endif; ?>

                <a class="btn btn-outline btn-block" style="margin-top:.8rem;" href="<?= APP_URL ?>/events/index.php">&larr; Back to events</a>
            </div>
        </aside>
    </div>
</div>
<?php render_footer(); ?>

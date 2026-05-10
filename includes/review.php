<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/** Determine whether a participant may review an event. */
function can_user_review_event(int $userId, int $eventId): array
{
    $stmt = db()->prepare(
        "SELECT b.id
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         WHERE b.user_id = :uid
           AND b.event_id = :eid
           AND b.status = 'Confirmed'
           AND TIMESTAMP(e.start_date, e.start_time) <= DATE_SUB(NOW(), INTERVAL 0 HOUR)
         LIMIT 1"
    );
    $stmt->execute(['uid' => $userId, 'eid' => $eventId]);

    if (!$stmt->fetch()) {
        return [false, 'You can review only events you attended after 24 hours have passed.'];
    }

    $existing = db()->prepare('SELECT id, status FROM reviews WHERE user_id = :uid AND event_id = :eid LIMIT 1');
    $existing->execute(['uid' => $userId, 'eid' => $eventId]);
    if ($existing->fetch()) {
        return [false, 'You have already reviewed this event.'];
    }

    return [true, ''];
}

/** Submit a participant review. */
function submit_review(int $userId, int $eventId, int $rating, string $comment): array
{
    [$allowed, $reason] = can_user_review_event($userId, $eventId);
    if (!$allowed) {
        return [$reason];
    }

    if ($rating < 1 || $rating > 5) {
        return ['Rating must be between 1 and 5.'];
    }

    $comment = trim($comment);
    if (mb_strlen($comment) > 1000) {
        return ['Review comment cannot exceed 1000 characters.'];
    }

    $status = REVIEW_AUTO_APPROVE ? 'Approved' : 'Pending';
    try {
        db()->prepare(
            'INSERT INTO reviews (user_id, event_id, rating, comment, status, created_at, updated_at)
             VALUES (:uid, :eid, :rating, :comment, :status, NOW(), NOW())'
        )->execute([
            'uid' => $userId,
            'eid' => $eventId,
            'rating' => $rating,
            'comment' => $comment ?: null,
            'status' => $status,
        ]);
        return [];
    } catch (Throwable $e) {
        log_app_error('submit_review: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['Unable to submit review.'];
    }
}

/** Update a pending review owned by the participant. */
function update_pending_review(int $reviewId, int $userId, int $rating, string $comment): array
{
    if ($rating < 1 || $rating > 5) {
        return ['Rating must be between 1 and 5.'];
    }
    if (mb_strlen(trim($comment)) > 1000) {
        return ['Review comment cannot exceed 1000 characters.'];
    }

    $stmt = db()->prepare("UPDATE reviews SET rating = :rating, comment = :comment, updated_at = NOW()
                           WHERE id = :id AND user_id = :uid AND status = 'Pending'");
    $stmt->execute([
        'rating' => $rating,
        'comment' => trim($comment) ?: null,
        'id' => $reviewId,
        'uid' => $userId,
    ]);

    return $stmt->rowCount() ? [] : ['Only pending reviews can be edited.'];
}

/** Delete a pending review owned by the participant. */
function delete_pending_review(int $reviewId, int $userId): array
{
    $stmt = db()->prepare("DELETE FROM reviews WHERE id = :id AND user_id = :uid AND status = 'Pending'");
    $stmt->execute(['id' => $reviewId, 'uid' => $userId]);
    return $stmt->rowCount() ? [] : ['Only pending reviews can be deleted.'];
}

/** Fetch the current participant's reviews. */
function get_user_reviews(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT r.*, e.title AS event_title, e.start_date, e.start_time
         FROM reviews r
         INNER JOIN events e ON e.id = r.event_id
         WHERE r.user_id = :uid
         ORDER BY r.created_at DESC'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

/** Fetch approved reviews for public event detail pages. */
function get_event_reviews(int $eventId, int $page = 1): array
{
    $countStmt = db()->prepare("SELECT COUNT(*) FROM reviews WHERE event_id = :eid AND status = 'Approved'");
    $countStmt->execute(['eid' => $eventId]);
    $total = (int) $countStmt->fetchColumn();
    $pager = paginate($total, $page, REVIEWS_PER_PAGE);

    $stmt = db()->prepare(
        "SELECT r.*, SUBSTRING_INDEX(u.full_name, ' ', 1) AS reviewer_first_name
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.event_id = :eid AND r.status = 'Approved'
         ORDER BY r.created_at DESC
         LIMIT :offset, :limit"
    );
    $stmt->bindValue(':eid', $eventId, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $pager['offset'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int) $pager['limit'], PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'pager' => $pager, 'total' => $total];
}

/** Fetch all reviews for the admin moderation queue. */
function get_all_reviews_admin(?string $status = null): array
{
    $sql = 'SELECT r.*, u.full_name, u.email, e.title AS event_title
            FROM reviews r
            INNER JOIN users u ON u.id = r.user_id
            INNER JOIN events e ON e.id = r.event_id';
    $params = [];
    if ($status && in_array($status, ['Pending','Approved','Rejected'], true)) {
        $sql .= ' WHERE r.status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY r.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

----------------------------------------------------------

reviews/my-reviews.php

<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/review.php';

require_login();

$userId = (int)auth_user()['id'];
if (is_post()) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $reviewId = (int)($_POST['review_id'] ?? 0);
    if ($action === 'update') {
        $errors = update_pending_review($reviewId, $userId, (int)($_POST['rating'] ?? 0), (string)($_POST['comment'] ?? ''));
        flash($errors ? 'error' : 'success', $errors[0] ?? 'Review updated.');
    } elseif ($action === 'delete') {
        $errors = delete_pending_review($reviewId, $userId);
        flash($errors ? 'error' : 'success', $errors[0] ?? 'Review deleted.');
    }
    redirect('reviews/my-reviews.php');
}

$reviews = get_user_reviews($userId);
render_header('My Reviews');
?>
<div class="container section">
    <h2>My reviews</h2>
    <p class="muted">You can edit or delete reviews while they are still pending moderation.</p>

    <?php if (!$reviews): ?>
        <div class="panel empty">No reviews submitted yet.</div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <article class="panel" style="margin-bottom:1rem;">
                <div class="status-row">
                    <h3><?= e($review['event_title']) ?></h3>
                    <span class="badge <?= $review['status'] === 'Approved' ? 'success' : ($review['status'] === 'Rejected' ? 'danger' : 'warning') ?>"><?= e($review['status']) ?></span>
                </div>

                <?php if ($review['status'] === 'Pending'): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">
                        <div class="form-group">
                            <label>Rating</label>
                            <select name="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= (int)$review['rating'] === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Comment</label>
                            <textarea name="comment" maxlength="1000"><?= e($review['comment'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm" name="action" value="update" type="submit">Save changes</button>
                        <button class="btn btn-danger btn-sm" name="action" value="delete" type="submit" onclick="return confirm('Delete this pending review?')">Delete</button>
                    </form>
                <?php else: ?>
                    <p><strong>Rating:</strong> <?= (int)$review['rating'] ?>/5</p>
                    <?php if (!empty($review['comment'])): ?>
                        <p><?= nl2br(e($review['comment'])) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_footer(); ?>

---------------------------------------------------------------------------
admin/reviews.php  replace the current code with the code below

<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/review.php';

require_admin();

if (is_post()) {
    verify_csrf();
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $errors = moderate_review($reviewId, $action);
    flash($errors ? 'error' : 'success', $errors[0] ?? 'Review updated.');
    redirect('admin/reviews.php' . (!empty($_GET['status']) ? '?status=' . urlencode((string)$_GET['status']) : ''));
}

$status = (string)($_GET['status'] ?? '');
if (!in_array($status, ['', 'Pending', 'Approved', 'Rejected'], true)) {
    $status = '';
}
$reviews = get_all_reviews_admin($status ?: null);
render_admin_header('Review Moderation', ['admin-bookings.css']);
?>
<div class="container section">
    <h2>Review moderation</h2>
    <p class="muted"><?= count($reviews) ?> review(s) <?= $status ? 'filtered by ' . e($status) : '' ?>.</p>

    <div class="status-row" style="margin:1rem 0;">
        <?php foreach (['', 'Pending', 'Approved', 'Rejected'] as $f): ?>
            <a class="btn btn-sm <?= $status === $f ? 'btn-primary' : 'btn-outline' ?>" href="?status=<?= urlencode($f) ?>"><?= $f === '' ? 'All' : e($f) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="panel table-wrap">
        <table>
            <thead>
                <tr><th>Event</th><th>Reviewer</th><th>Rating</th><th>Comment</th><th>Status</th><th>Submitted</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (!$reviews): ?>
                <tr><td colspan="7" class="empty">No reviews found.</td></tr>
            <?php endif; ?>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?= e($r['event_title']) ?></td>
                    <td><?= e($r['full_name']) ?><br><span class="muted"><?= e($r['email']) ?></span></td>
                    <td>★ <?= (int)$r['rating'] ?>/5</td>
                    <td><?= e(mb_strimwidth((string)($r['comment'] ?? ''), 0, 120, '…')) ?></td>
                    <td><span class="badge <?= $r['status'] === 'Approved' ? 'success' : ($r['status'] === 'Rejected' ? 'danger' : 'warning') ?>"><?= e($r['status']) ?></span></td>
                    <td><?= e(substr($r['created_at'], 0, 16)) ?></td>
                    <td>
                        <form method="post" class="status-row">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-primary btn-sm" name="action" value="approve" type="submit">Approve</button>
                            <button class="btn btn-outline btn-sm" name="action" value="reject" type="submit">Reject</button>
                            <button class="btn btn-danger btn-sm" name="action" value="delete" type="submit" onclick="return confirm('Delete this review?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_admin_footer(); ?>
------------------------------------------------------------
events/show.php replace the current code with the code below

<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/event.php';
require_once __DIR__ . '/../includes/review.php'; 

$event = get_event((int)($_GET['id'] ?? 0));
if (!$event) { flash('error', 'Event not found or no longer available.'); redirect('events/index.php'); }

$status    = event_status($event);
$seatsLeft = (int)$event['seats_left'];
$isPaid    = (float)$event['price'] > 0;
$canBook   = is_logged_in() && !is_admin() && $status === 'Open' && !$isPaid;
$maxQty    = min($seatsLeft, MAX_SEATS_PER_BOOKING);
$reviewPage = max(1, (int)($_GET['reviews_page'] ?? 1));
$reviews = get_event_reviews((int)$event['id'], $reviewPage);

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

            <?php if (!$reviews['items']): ?>
                <div class="panel empty">No approved reviews yet.</div>
            <?php else: ?>
                <?php foreach ($reviews['items'] as $review): ?>
                    <div class="panel" style="margin:.75rem 0;">
                        <div class="status-row">
                            <strong><?= e($review['reviewer_first_name']) ?></strong>
                            <span class="badge">★ <?= (int)$review['rating'] ?>/5</span>
                            <span class="muted"><?= e(substr($review['created_at'], 0, 10)) ?></span>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <p><?= nl2br(e($review['comment'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                    </td>
                    <tr><th>Seats</th><td><?= $seatsLeft ?> / <?= (int)$event['capacity'] ?> remaining
                        <?php if ($seatsLeft === 0): ?>
                            <span class="badge danger" style="font-size:.75rem;">Sold Out</span>
                        <?php endif; ?>
                    </td>
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

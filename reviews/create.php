<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/review.php';
require_once __DIR__ . '/../includes/event.php';

require_login();
if (is_admin()) {
    flash('error', 'Administrators cannot submit participant reviews.');
    redirect('events/index.php');
}

$userId = (int)auth_user()['id'];
$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$event = get_event($eventId, true);
if (!$event) {
    flash('error', 'Event not found.');
    redirect('bookings/my-bookings.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = submit_review($userId, $eventId, (int)($_POST['rating'] ?? 0), (string)($_POST['comment'] ?? ''));
    if (!$errors) {
        flash('success', REVIEW_AUTO_APPROVE ? 'Review published successfully.' : 'Review submitted and waiting for administrator approval.');
        redirect('reviews/my-reviews.php');
    }
}

render_header('Write Review');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:760px;">
        <h2>Review <?= e($event['title']) ?></h2>
        <p class="muted">Reviews are visible publicly only after moderation.</p>

        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

        <div class="form-group">
            <label>Rating *</label>
            <select name="rating" required>
                <option value="">Select rating</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= ((int)($_POST['rating'] ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Comment <span class="muted">(optional, max 1000 characters)</span></label>
            <textarea name="comment" maxlength="1000"><?= e($_POST['comment'] ?? '') ?></textarea>
        </div>

        <button class="btn btn-primary" type="submit">Submit review</button>
        <a class="btn btn-outline" href="<?= APP_URL ?>/bookings/my-bookings.php">Cancel</a>
    </form>
</div>
<?php render_footer(); ?>

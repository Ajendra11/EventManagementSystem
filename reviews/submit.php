<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/review.php';
require_once __DIR__ . '/../includes/event.php';

require_login();

if (is_admin()) { flash('error','Administrators cannot submit reviews.'); redirect('events/index.php'); }

$userId  = (int)auth_user()['id'];
$eventId = (int)($_GET['event_id']??0);
$event   = $eventId ? get_event($eventId,true) : null;

if (!$event) {
    $reviewable = get_reviewable_events($userId);
    render_header('Write a Review');
    ?>
    <div class="container section">
        <h2>Write a review</h2>
        <p class="muted">Select an event you attended to leave your review.</p>
        <?php if(!$reviewable): ?>
            <div class="panel empty" style="margin-top:1.5rem;">
                <p>You have no events eligible for review yet.</p>
                <p class="muted" style="font-size:.9rem;">Reviews can be submitted for confirmed bookings at least 24 hours after the event ends.</p>
                <a class="btn btn-primary" href="<?= APP_URL ?>/bookings/my-bookings.php" style="margin-top:1rem;">My bookings</a>
            </div>
        <?php else: ?>
            <div class="panel table-wrap" style="margin-top:1.5rem;">
                <table>
                    <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach($reviewable as $ev): ?>
                        <tr>
                            <td><strong><?= e($ev['title']) ?></strong></td>
                            <td><?= e($ev['start_date']) ?></td>
                            <td><?= e($ev['location']) ?></td>
                            <td><?php if($ev['already_reviewed']): ?><span class="badge success">Reviewed</span><?php else: ?><a class="btn btn-primary btn-sm" href="?event_id=<?= (int)$ev['id'] ?>">Write review</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
    render_footer();
    exit;
}

$errors = [];
[$canReview,$cantReason] = can_review_event($userId,$eventId);

if (is_post()) {
    verify_csrf();
    if (!$canReview) { $errors[]=$cantReason; }
    else {
        $errors = submit_review($userId,$eventId,$_POST);
        if (!$errors) {
            $msg = AUTO_APPROVE_REVIEWS ? 'Thank you! Your review has been published.' : 'Thank you! Your review has been submitted and is pending moderation.';
            flash('success',$msg);
            redirect('events/show.php?id='.$eventId);
        }
    }
}

render_header('Review: '.$event['title']);
?>
<div class="container section">
    <div style="max-width:640px;margin:0 auto;">
        <a class="muted" href="<?= APP_URL ?>/events/show.php?id=<?= (int)$eventId ?>" style="font-size:.9rem;">&larr; Back to event</a>
        <h2 style="margin-top:.8rem;">Write a review</h2>
        <p class="muted">Reviewing: <strong><?= e($event['title']) ?></strong></p>

        <?php if(!$canReview): ?>
            <div class="flash flash-warning"><?= e($cantReason) ?></div>
        <?php else: ?>
            <?php foreach($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
            <form class="panel" method="post" data-validate="true" style="margin-top:1.5rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="star-picker" id="star-picker">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <label class="star-label" title="<?= $i ?> star<?= $i>1?'s':'' ?>">
                                <input type="radio" name="rating" value="<?= $i ?>" <?= (isset($_POST['rating'])&&(int)$_POST['rating']===$i)?'checked':'' ?> required>
                                <span class="star">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                    <p class="muted" style="font-size:.85rem;margin-top:.3rem;">Click to select 1–5 stars</p>
                </div>
                <div class="form-group">
                    <label for="comment">Comment <span class="muted">(optional, max 1000 characters)</span></label>
                    <textarea id="comment" name="comment" maxlength="1000" placeholder="Share your experience..."><?= e($_POST['comment']??'') ?></textarea>
                    <small class="muted"><span id="comment-count">0</span> / 1000 characters</small>
                </div>
                <button class="btn btn-primary" type="submit">Submit review</button>
                <a class="btn btn-outline" href="<?= APP_URL ?>/events/show.php?id=<?= (int)$eventId ?>">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>
<style>
.star-picker{display:flex;gap:.3rem;direction:ltr;}
.star-picker input[type=radio]{display:none;}
.star-label{cursor:pointer;font-size:2rem;color:#d1d5db;transition:color .1s;}
.star-picker:has(input[value="1"]:checked) .star-label:nth-child(-n+1) .star,
.star-picker:has(input[value="2"]:checked) .star-label:nth-child(-n+2) .star,
.star-picker:has(input[value="3"]:checked) .star-label:nth-child(-n+3) .star,
.star-picker:has(input[value="4"]:checked) .star-label:nth-child(-n+4) .star,
.star-picker:has(input[value="5"]:checked) .star-label:nth-child(-n+5) .star{color:#f59e0b;}
</style>
<script>
(function(){var ta=document.getElementById('comment');var ct=document.getElementById('comment-count');if(ta&&ct){ct.textContent=ta.value.length;ta.addEventListener('input',function(){ct.textContent=this.value.length;});}})();
</script>
<?php render_footer(); ?>

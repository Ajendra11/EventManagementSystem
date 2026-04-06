<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/review.php';

require_login();

if (is_admin()) { redirect('admin/reviews.php'); }

$userId = (int)auth_user()['id'];
$errors = [];

if (is_post()) {
    verify_csrf();
    $action   = $_POST['action'] ?? '';
    $reviewId = (int)($_POST['review_id']??0);

    if ($action==='delete' && $reviewId) {
        if (delete_review($reviewId,$userId)) { flash('success','Review deleted.'); }
        else { flash('error','Could not delete that review (it may already be approved).'); }
        redirect('reviews/my-reviews.php');
    }
    if ($action==='update' && $reviewId) {
        $errors = update_review($reviewId,$userId,$_POST);
        if (!$errors) { flash('success','Review updated.'); redirect('reviews/my-reviews.php'); }
    }
}

$reviews = get_user_reviews($userId);

$editId   = (int)($_GET['edit']??0);
$editData = null;
if ($editId) {
    foreach($reviews as $r) {
        if ((int)$r['id']===$editId && $r['status']==='Pending') { $editData=$r; break; }
    }
}

render_header('My Reviews');
?>
<div class="container section">
    <div class="status-row" style="justify-content:space-between;">
        <div><h2>My reviews</h2><p class="muted">Reviews you have submitted for events you attended.</p></div>
        <a class="btn btn-outline" href="<?= APP_URL ?>/reviews/submit.php">+ Write a review</a>
    </div>

    <?php if($editData): ?>
        <div class="panel" style="margin-top:1.5rem;border-left:4px solid var(--primary);">
            <h3>Edit review — <?= e($editData['event_title']) ?></h3>
            <?php foreach($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="post" data-validate="true" style="margin-top:1rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="review_id" value="<?= (int)$editData['id'] ?>">
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="star-picker">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <label class="star-label" title="<?= $i ?> star<?= $i>1?'s':'' ?>">
                                <input type="radio" name="rating" value="<?= $i ?>" <?= ((isset($_POST['rating'])?(int)$_POST['rating']:(int)$editData['rating'])===$i)?'checked':'' ?> required>
                                <span class="star">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Comment <span class="muted">(optional)</span></label>
                    <textarea name="comment" maxlength="1000"><?= e($_POST['comment']??$editData['comment']??'') ?></textarea>
                </div>
                <div class="status-row">
                    <button class="btn btn-primary" type="submit">Save changes</button>
                    <a class="btn btn-outline" href="<?= APP_URL ?>/reviews/my-reviews.php">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if(!$reviews): ?>
        <div class="panel empty" style="margin-top:1.5rem;">
            <p>You haven't submitted any reviews yet.</p>
            <a class="btn btn-primary" href="<?= APP_URL ?>/reviews/submit.php" style="margin-top:1rem;">Write a review</a>
        </div>
    <?php else: ?>
        <div class="panel table-wrap" style="margin-top:1.5rem;">
            <table>
                <thead><tr><th>Event</th><th>Date</th><th>Rating</th><th>Comment</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($reviews as $r): ?>
                    <?php $statusClass=match($r['status']){'Approved'=>'success','Rejected'=>'danger',default=>'warning'}; ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/events/show.php?id=<?= (int)$r['event_id'] ?>"><?= e($r['event_title']) ?></a></td>
                        <td><?= e(substr($r['start_date'],0,10)) ?></td>
                        <td><span style="color:#f59e0b;font-size:1.1rem;"><?= str_repeat('★',(int)$r['rating']) ?></span><span style="color:#d1d5db;"><?= str_repeat('★',5-(int)$r['rating']) ?></span></td>
                        <td style="max-width:260px;"><span style="font-size:.9rem;"><?= $r['comment']?e(mb_strimwidth($r['comment'],0,80,'…')):'<span class="muted">—</span>' ?></span></td>
                        <td><span class="badge <?= $statusClass ?>"><?= e($r['status']) ?></span></td>
                        <td><?= e(substr($r['created_at'],0,10)) ?></td>
                        <td>
                            <?php if($r['status']==='Pending'): ?>
                                <div class="status-row">
                                    <a class="btn btn-outline btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this review permanently?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            <?php else: ?><span class="muted" style="font-size:.82rem;">—</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<style>
.star-picker{display:flex;gap:.3rem;}
.star-picker input[type=radio]{display:none;}
.star-label{cursor:pointer;font-size:1.8rem;color:#d1d5db;}
.star-picker:has(input[value="1"]:checked) .star-label:nth-child(-n+1) .star,
.star-picker:has(input[value="2"]:checked) .star-label:nth-child(-n+2) .star,
.star-picker:has(input[value="3"]:checked) .star-label:nth-child(-n+3) .star,
.star-picker:has(input[value="4"]:checked) .star-label:nth-child(-n+4) .star,
.star-picker:has(input[value="5"]:checked) .star-label:nth-child(-n+5) .star{color:#f59e0b;}
</style>
<?php render_footer(); ?>

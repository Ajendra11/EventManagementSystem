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

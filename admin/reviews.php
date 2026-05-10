<?php
require_once DIR . '/../includes/admin_layout.php';
require_once DIR . '/../includes/review.php';

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
$reviews = get_all_reviews_admin($status ?: null);
?>

<!-- Approve/Reject Buttons -->
 <?php foreach ($reviews as $r): ?>
<tr>
    <td><?= e($r['content']) ?></td>
    <td>
        <form method="post" class="status-row">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-primary btn-sm" name="action" value="approve">Approve</button>
            <button class="btn btn-outline btn-sm" name="action" value="reject">Reject</button>
            <button class="btn btn-danger btn-sm" name="action" value="delete" onclick="return confirm('Delete this review?')">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
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

/** Moderate one review from the admin panel. */
function moderate_review(int $reviewId, string $action): array
{
    if ($action === 'delete') {
        db()->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $reviewId]);
        audit_log('review_deleted', 'review', $reviewId);
        return [];
    }

    $status = match ($action) {
        'approve' => 'Approved',
        'reject' => 'Rejected',
        default => null,
    };

    if (!$status) {
        return ['Invalid review action.'];
    }

    db()->prepare('UPDATE reviews SET status = :status, updated_at = NOW() WHERE id = :id')
        ->execute(['status' => $status, 'id' => $reviewId]);
    audit_log('review_' . strtolower($status), 'review', $reviewId);
    return [];
}

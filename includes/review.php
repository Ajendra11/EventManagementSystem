<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

// ── Review eligibility (FR-RR-01, FR-RR-02) ──────────────────────────────────

/**
 * Check if a user can submit a review for an event.
 * Returns [true, ''] or [false, 'reason'].
 */
function can_review_event(int $userId, int $eventId): array
{
    // Must have a confirmed booking for an event that ended 24+ hours ago
    $stmt = db()->prepare(
        "SELECT b.id FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         WHERE b.user_id=:uid AND b.event_id=:eid AND b.status='Confirmed'
           AND TIMESTAMPADD(HOUR, 24, CONCAT(e.start_date,' ',e.start_time)) < NOW()
         LIMIT 1"
    );
    $stmt->execute(['uid' => $userId, 'eid' => $eventId]);
    if (!$stmt->fetch()) {
        return [false, 'You can only review events you attended, at least 24 hours after they have ended.'];
    }

    // Must not have already reviewed (FR-RR-02)
    $dup = db()->prepare(
        "SELECT id FROM reviews WHERE user_id=:uid AND event_id=:eid LIMIT 1"
    );
    $dup->execute(['uid' => $userId, 'eid' => $eventId]);
    if ($dup->fetch()) {
        return [false, 'You have already submitted a review for this event.'];
    }

    return [true, ''];
}

// ── Submit review (FR-RR-03, FR-RR-04) ───────────────────────────────────────

function submit_review(int $userId, int $eventId, array $data): array
{
    [$eligible, $reason] = can_review_event($userId, $eventId);
    if (!$eligible) {
        return [$reason];
    }

    $rating  = (int) ($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        return ['Please select a rating between 1 and 5 stars.'];
    }
    if (mb_strlen($comment) > 1000) {
        return ['Comment must be 1000 characters or fewer.'];
    }

    // FR-RR-10: auto-approve if configured, otherwise Pending
    $status = AUTO_APPROVE_REVIEWS ? 'Approved' : 'Pending';

    db()->prepare(
        'INSERT INTO reviews (user_id, event_id, rating, comment, status, created_at, updated_at)
         VALUES (:uid,:eid,:rating,:comment,:status,NOW(),NOW())'
    )->execute([
        'uid'     => $userId,
        'eid'     => $eventId,
        'rating'  => $rating,
        'comment' => $comment ?: null,
        'status'  => $status,
    ]);

    return [];
}

// ── Get reviews for event detail page (FR-RR-06, FR-RR-07) ───────────────────

function get_event_reviews(int $eventId, int $page = 1): array
{
    $countStmt = db()->prepare("SELECT COUNT(*) AS c FROM reviews WHERE event_id=:eid AND status='Approved'");
    $countStmt->execute(['eid' => $eventId]);
    $total = (int) $countStmt->fetch()['c'];

    $pager = paginate($total, $page, REVIEWS_PER_PAGE);

    $stmt = db()->prepare(
        "SELECT r.*, u.full_name AS reviewer_name
         FROM reviews r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.event_id=:eid AND r.status='Approved'
         ORDER BY r.created_at DESC
         LIMIT :offset, :limit"
    );
    $stmt->bindValue(':eid',    $eventId, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pager['offset'], PDO::PARAM_INT);
    $stmt->bindValue(':limit',  $pager['limit'],  PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'pager' => $pager, 'total' => $total];
}

// ── User's own reviews (FR-RR-09) ─────────────────────────────────────────────

function get_user_reviews(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT r.*, e.title AS event_title, e.start_date
         FROM reviews r
         INNER JOIN events e ON e.id = r.event_id
         WHERE r.user_id=:uid
         ORDER BY r.created_at DESC"
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function get_user_review_for_event(int $userId, int $eventId): ?array
{
    $stmt = db()->prepare(
        "SELECT r.*, e.title AS event_title
         FROM reviews r
         INNER JOIN events e ON e.id = r.event_id
         WHERE r.user_id=:uid AND r.event_id=:eid LIMIT 1"
    );
    $stmt->execute(['uid' => $userId, 'eid' => $eventId]);
    return $stmt->fetch() ?: null;
}

/**
 * Update own Pending review (FR-RR-09).
 */
function update_review(int $reviewId, int $userId, array $data): array
{
    $review = db()->prepare(
        "SELECT * FROM reviews WHERE id=:id AND user_id=:uid LIMIT 1"
    );
    $review->execute(['id' => $reviewId, 'uid' => $userId]);
    $existing = $review->fetch();

    if (!$existing) {
        return ['Review not found.'];
    }
    if ($existing['status'] !== 'Pending') {
        return ['Only pending reviews can be edited.'];
    }

    $rating  = (int) ($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        return ['Please select a rating between 1 and 5 stars.'];
    }
    if (mb_strlen($comment) > 1000) {
        return ['Comment must be 1000 characters or fewer.'];
    }

    db()->prepare(
        "UPDATE reviews SET rating=:r, comment=:c, updated_at=NOW() WHERE id=:id"
    )->execute(['r' => $rating, 'c' => $comment ?: null, 'id' => $reviewId]);

    return [];
}

/**
 * Delete own Pending review (FR-RR-09).
 */
function delete_review(int $reviewId, int $userId): bool
{
    $stmt = db()->prepare(
        "DELETE FROM reviews WHERE id=:id AND user_id=:uid AND status='Pending'"
    );
    $stmt->execute(['id' => $reviewId, 'uid' => $userId]);
    return $stmt->rowCount() > 0;
}

// ── Admin moderation (FR-RR-05) ───────────────────────────────────────────────

function get_all_reviews_admin(string $statusFilter = ''): array
{
    $sql    = "SELECT r.*, u.full_name AS reviewer_name, e.title AS event_title
               FROM reviews r
               INNER JOIN users u  ON u.id  = r.user_id
               INNER JOIN events e ON e.id  = r.event_id";
    $params = [];
    if ($statusFilter && in_array($statusFilter, ['Pending','Approved','Rejected'], true)) {
        $sql   .= ' WHERE r.status=:status';
        $params['status'] = $statusFilter;
    }
    $sql .= ' ORDER BY r.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function moderate_review(int $reviewId, string $action): bool
{
    $validActions = ['Approved', 'Rejected'];
    if (!in_array($action, $validActions, true)) {
        return false;
    }
    $stmt = db()->prepare(
        "UPDATE reviews SET status=:status, updated_at=NOW() WHERE id=:id"
    );
    $stmt->execute(['status' => $action, 'id' => $reviewId]);
    audit_log('review_' . strtolower($action), 'review', $reviewId);
    return $stmt->rowCount() > 0;
}

function admin_delete_review(int $reviewId): bool
{
    $stmt = db()->prepare("DELETE FROM reviews WHERE id=:id");
    $stmt->execute(['id' => $reviewId]);
    audit_log('review_deleted', 'review', $reviewId);
    return $stmt->rowCount() > 0;
}

// ── List events eligible for review (helper for submit page) ─────────────────

function get_reviewable_events(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT e.id, e.title, e.start_date, e.start_time, e.location,
                (SELECT COUNT(*) FROM reviews r WHERE r.event_id=e.id AND r.user_id=:uid2) AS already_reviewed
         FROM bookings b
         INNER JOIN events e ON e.id = b.event_id
         WHERE b.user_id=:uid AND b.status='Confirmed'
           AND TIMESTAMPADD(HOUR, 24, CONCAT(e.start_date,' ',e.start_time)) < NOW()
         ORDER BY e.start_date DESC"
    );
    $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
    return $stmt->fetchAll();
}

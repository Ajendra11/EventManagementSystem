<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Moderate one review from the admin panel. */
function moderate_review(int $reviewId, string $action): array
{
    if ($action === 'delete') {
        db()->prepare('DELETE FROM reviews WHERE id = :id')
            ->execute(['id' => $reviewId]);

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
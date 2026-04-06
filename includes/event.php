```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// ─────────────────────────────────────────────────────────────────────────────
// Base query used across event listing functions
// ─────────────────────────────────────────────────────────────────────────────
function event_base_query(): string
{
    return "
        SELECT
            e.*,
            u.full_name AS organizer_name,

            COALESCE((
                SELECT SUM(b.quantity)
                FROM bookings b
                WHERE b.event_id = e.id
                  AND b.status IN ('Confirmed', 'Pending')
            ), 0) AS registered_count,

            GREATEST(
                e.capacity - COALESCE((
                    SELECT SUM(b.quantity)
                    FROM bookings b
                    WHERE b.event_id = e.id
                      AND b.status IN ('Confirmed', 'Pending')
                ), 0),
                0
            ) AS seats_left,

            COALESCE((
                SELECT ROUND(AVG(r.rating), 1)
                FROM reviews r
                WHERE r.event_id = e.id
                  AND r.status = 'Approved'
            ), 0) AS avg_rating,

            COALESCE((
                SELECT COUNT(*)
                FROM reviews r
                WHERE r.event_id = e.id
                  AND r.status = 'Approved'
            ), 0) AS review_count

        FROM events e
        INNER JOIN users u ON u.id = e.created_by
    ";
}

// ─────────────────────────────────────────────────────────────────────────────
// Get featured upcoming published events
// ─────────────────────────────────────────────────────────────────────────────
function get_featured_events(int $limit = 4): array
{
    $sql = event_base_query() . "
        WHERE e.status = 'Published'
          AND CONCAT(e.start_date, ' ', e.start_time) >= NOW()
        ORDER BY e.start_date ASC, e.start_time ASC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ─────────────────────────────────────────────────────────────────────────────
// Search and filter events
// ─────────────────────────────────────────────────────────────────────────────
function search_events(array $filters = [], bool $admin = false): array
{
    $conditions = [];
    $params     = [];

    if (!$admin) {
        $conditions[] = "e.status = 'Published'";
        $conditions[] = "CONCAT(e.start_date, ' ', e.start_time) >= NOW()";
    }

    // Search keyword
    if (!empty($filters['q'])) {
        $conditions[] = "(
            e.title LIKE :q
            OR e.category LIKE :q
            OR e.location LIKE :q
            OR e.description LIKE :q
        )";

        $params['q'] = '%' . trim($filters['q']) . '%';
    }

    // Category filter
    if (!empty($filters['category'])) {
        $conditions[] = 'e.category = :category';
        $params['category'] = trim($filters['category']);
    }

    // Date filter
    if (!empty($filters['start_date'])) {
        $conditions[] = 'e.start_date >= :start_date';
        $params['start_date'] = $filters['start_date'];
    }

    // Location filter
    if (!empty($filters['location'])) {
        $conditions[] = 'e.location LIKE :location';
        $params['location'] = '%' . trim($filters['location']) . '%';
    }

    // Admin status filter
    if ($admin && !empty($filters['status'])) {
        $conditions[] = 'e.status = :status';
        $params['status'] = trim($filters['status']);
    }

    $where = '';
    if (!empty($conditions)) {
        $where = ' WHERE ' . implode(' AND ', $conditions);
    }

    // Count total events for pagination
    $countSql = "SELECT COUNT(*) FROM events e" . $where;
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Pagination values
    $page  = max(1, (int) ($filters['page'] ?? 1));
    $pager = paginate($total, $page);

    // Main query
    $sql = event_base_query() . $where . "
        ORDER BY e.start_date ASC, e.start_time ASC
        LIMIT :offset, :limit
    ";

    $stmt = db()->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->bindValue(':offset', (int) $pager['offset'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int) $pager['limit'], PDO::PARAM_INT);

    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pager' => $pager,
        'total' => $total,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Get single event by ID
// ─────────────────────────────────────────────────────────────────────────────
function get_event(int $id, bool $admin = false): ?array
{
    $sql = event_base_query() . ' WHERE e.id = :id';

    if (!$admin) {
        $sql .= " AND e.status = 'Published'";
    }

    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $id]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    return $event ?: null;
}

// ─────────────────────────────────────────────────────────────────────────────
// Validate event form data
// ─────────────────────────────────────────────────────────────────────────────
function validate_event_data(array $data, bool $isNew = true): array
{
    $errors = [];

    $title = trim($data['title'] ?? '');
    if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        $errors[] = 'Title must be between 3 and 150 characters.';
    }

    $category = trim($data['category'] ?? '');
    if ($category === '') {
        $errors[] = 'Category is required.';
    }

    $location = trim($data['location'] ?? '');
    if (mb_strlen($location) < 3 || mb_strlen($location) > 150) {
        $errors[] = 'Location must be between 3 and 150 characters.';
    }

    $description = trim($data['description'] ?? '');
    if (mb_strlen($description) < 20 || mb_strlen($description) > 2000) {
        $errors[] = 'Description must be between 20 and 2000 characters.';
    }

    $startDate = $data['start_date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $errors[] = 'Invalid start date.';
    } elseif ($isNew && strtotime($startDate) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Start date cannot be in the past.';
    }

    $startTime = $data['start_time'] ?? '';
    if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        $errors[] = 'Invalid start time.';
    }

    $capacity = (int) ($data['capacity'] ?? 0);
    if ($capacity < 1 || $capacity > 1000) {
        $errors[] = 'Capacity must be between 1 and 1000.';
    }

    $price = (float) ($data['price'] ?? 0);
    if ($price < 0) {
        $errors[] = 'Price cannot be negative.';
    }

    $validStatuses = ['Draft', 'Published', 'Archived'];
    if (!in_array($data['status'] ?? '', $validStatuses, true)) {
        $errors[] = 'Invalid status selected.';
    }

    return $errors;
}

// ─────────────────────────────────────────────────────────────────────────────
// Create or update an event
// ─────────────────────────────────────────────────────────────────────────────
function save_event(
    array $data,
    int $userId,
    ?int $eventId = null,
    ?array $fileInput = null
): array {
    $isNew = $eventId === null;

    $errors = validate_event_data($data, $isNew);
    if (!empty($errors)) {
        return $errors;
    }

    $bannerImage = trim($data['banner_image'] ?? '') ?: null;

    // Handle uploaded image
    if (
        $fileInput !== null
        && isset($fileInput['error'])
        && $fileInput['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $upload = handle_banner_upload($fileInput);

        if (!empty($upload['error'])) {
            return [$upload['error']];
        }

        if (!empty($upload['path'])) {
            $bannerImage = $upload['path'];
        }
    }

    $payload = [
        'title'        => trim($data['title']),
        'category'     => trim($data['category']),
        'location'     => trim($data['location']),
        'description'  => trim($data['description']),
        'start_date'   => $data['start_date'],
        'start_time'   => $data['start_time'] . ':00',
        'capacity'     => (int) $data['capacity'],
        'price'        => (float) $data['price'],
        'banner_image' => $bannerImage,
        'status'       => $data['status'],
    ];

    // Create new event
    if ($isNew) {
        $slug = strtolower(trim($payload['title']));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $payload['slug'] = $slug;
        $payload['created_by'] = $userId;

        $sql = "
            INSERT INTO events (
                title,
                slug,
                category,
                location,
                description,
                start_date,
                start_time,
                capacity,
                price,
                banner_image,
                status,
                created_by,
                created_at,
                updated_at
            ) VALUES (
                :title,
                :slug,
                :category,
                :location,
                :description,
                :start_date,
                :start_time,
                :capacity,
                :price,
                :banner_image,
                :status,
                :created_by,
                NOW(),
                NOW()
            )
        ";
    } else {
        $existing = get_event($eventId, true);

        if (!$existing) {
            return ['Event not found.'];
        }

        $payload['id'] = $eventId;

        $sql = "
            UPDATE events SET
                title = :title,
                category = :category,
                location = :location,
                description = :description,
                start_date = :start_date,
                start_time = :start_time,
                capacity = :capacity,
                price = :price,
                banner_image = :banner_image,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ";
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($payload);

    return [];
}

// ─────────────────────────────────────────────────────────────────────────────
// Archive event
// ─────────────────────────────────────────────────────────────────────────────
function archive_event(int $id): void
{
    $stmt = db()->prepare("
        UPDATE events
        SET status = 'Archived', updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Check if event has active bookings
// ─────────────────────────────────────────────────────────────────────────────
function event_has_active_bookings(int $eventId): bool
{
    $stmt = db()->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE event_id = :id
          AND status IN ('Confirmed', 'Pending')
    ");

    $stmt->execute(['id' => $eventId]);

    return (int) $stmt->fetchColumn() > 0;
}

// ─────────────────────────────────────────────────────────────────────────────
// Permanently delete event
// ─────────────────────────────────────────────────────────────────────────────
function delete_event(int $id): void
{
    $stmt = db()->prepare('DELETE FROM events WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Admin dashboard statistics
// ─────────────────────────────────────────────────────────────────────────────
function get_admin_stats(): array
{
    $pdo = db();

    return [
        'users' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE status = 'active'"
        )->fetchColumn(),

        'events' => (int) $pdo->query(
            'SELECT COUNT(*) FROM events'
        )->fetchColumn(),

        'published_events' => (int) $pdo->query(
            "SELECT COUNT(*) FROM events WHERE status = 'Published'"
        )->fetchColumn(),

        'confirmed_bookings' => (int) $pdo->query(
            "SELECT COUNT(*) FROM bookings WHERE status = 'Confirmed'"
        )->fetchColumn(),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Get all distinct event categories
// ─────────────────────────────────────────────────────────────────────────────
function get_event_categories(): array
{
    $stmt = db()->query(
        'SELECT DISTINCT category FROM events ORDER BY category ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
```

### Fixed / Improved ✅

* Added `PDO::FETCH_ASSOC` for safer array access
* Improved readability and formatting
* Fixed search keyword to also search description
* Simplified count query using `fetchColumn()`
* Improved category filtering
* Safer image upload checks
* Better slug generation
* Cleaner SQL formatting and spacing
* Added comments for every section 🚀

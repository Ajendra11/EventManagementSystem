<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

// ── Core query ────────────────────────────────────────────────────────────────

function event_base_query(): string
{
    return "
        SELECT e.*,
            u.full_name AS organizer_name,
            COALESCE((
                SELECT SUM(b.quantity)
                FROM bookings b
                WHERE b.event_id = e.id AND b.status IN ('Confirmed','Pending')
            ), 0) AS registered_count,
            GREATEST(e.capacity - COALESCE((
                SELECT SUM(b.quantity)
                FROM bookings b
                WHERE b.event_id = e.id AND b.status IN ('Confirmed','Pending')
            ), 0), 0) AS seats_left,
            COALESCE((
                SELECT ROUND(AVG(r.rating), 1)
                FROM reviews r
                WHERE r.event_id = e.id AND r.status = 'Approved'
            ), 0) AS avg_rating,
            COALESCE((
                SELECT COUNT(*)
                FROM reviews r
                WHERE r.event_id = e.id AND r.status = 'Approved'
            ), 0) AS review_count
        FROM events e
        INNER JOIN users u ON u.id = e.created_by
    ";
}

// ── Public queries ────────────────────────────────────────────────────────────

function get_featured_events(int $limit = 4): array
{
    $sql  = event_base_query()
          . " WHERE e.status = 'Published' AND CONCAT(e.start_date,' ',e.start_time) >= NOW()"
          . ' ORDER BY e.start_date ASC, e.start_time ASC LIMIT :lim';
    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function search_events(array $filters, bool $admin = false): array
{
    $conditions = [];
    $params     = [];

    if (!$admin) {
        $conditions[] = "e.status = 'Published'";
        $conditions[] = "CONCAT(e.start_date,' ',e.start_time) >= NOW()";
    }

    if (!empty($filters['q'])) {
        $conditions[] = '(e.title LIKE :q1 OR e.category LIKE :q2 OR e.location LIKE :q3)';
        $searchValue = '%' . trim($filters['q']) . '%';
        $params['q1'] = $searchValue;
        $params['q2'] = $searchValue;
        $params['q3'] = $searchValue;
    }

    if (!empty($filters['category'])) {
        $conditions[] = 'e.category LIKE :category';
        $params['category'] = '%' . trim($filters['category']) . '%';
    }

    if (!empty($filters['start_date'])) {
        $conditions[] = 'e.start_date >= :start_date';
        $params['start_date'] = $filters['start_date'];
    }

    if (!empty($filters['location'])) {
        $conditions[] = 'e.location LIKE :location';
        $params['location'] = '%' . trim($filters['location']) . '%';
    }

    if ($admin && !empty($filters['status'])) {
        $conditions[] = 'e.status = :status';
        $params['status'] = $filters['status'];
    }

    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    $countStmt = db()->prepare('SELECT COUNT(*) AS total FROM events e' . $where);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['total'];

    $page  = max(1, (int) ($filters['page'] ?? 1));
    $pager = paginate($total, $page);

    $sql  = event_base_query() . $where . '
             ORDER BY e.start_date ASC, e.start_time ASC 
             LIMIT :offset, :limit';

    $stmt = db()->prepare($sql);

    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }

    $stmt->bindValue(':offset', (int)$pager['offset'], PDO::PARAM_INT);
    $stmt->bindValue(':limit',  (int)$pager['limit'],  PDO::PARAM_INT);

    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'pager' => $pager,
        'total' => $total
    ];
}

function get_event(int $id, bool $admin = false): ?array
{
    $sql = event_base_query() . ' WHERE e.id = :id';
    if (!$admin) {
        $sql .= " AND e.status = 'Published'";
    }
    $stmt = db()->prepare($sql);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// ── Admin CRUD ────────────────────────────────────────────────────────────────

function validate_event_data(array $data, bool $isNew): array
{
    $errors = [];

    $title    = trim($data['title'] ?? '');
    $titleLen = mb_strlen($title);
    if ($titleLen < 3 || $titleLen > 150) {
        $errors[] = 'Title must be between 3 and 150 characters.';
    }
    if (trim($data['category'] ?? '') === '') {
        $errors[] = 'Category is required.';
    }
    $locLen = mb_strlen(trim($data['location'] ?? ''));
    if ($locLen < 3 || $locLen > 150) {
        $errors[] = 'Location must be between 3 and 150 characters.';
    }
    $descLen = mb_strlen(trim($data['description'] ?? ''));
    if ($descLen < 20 || $descLen > 2000) {
        $errors[] = 'Description must be between 20 and 2000 characters.';
    }

    $dateStr = $data['start_date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        $errors[] = 'Start date is invalid.';
    } elseif ($isNew && strtotime($dateStr) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Start date cannot be in the past.';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $data['start_time'] ?? '')) {
        $errors[] = 'Start time is invalid.';
    }

    $capacity = (int) ($data['capacity'] ?? 0);
    if ($capacity < 1 || $capacity > 1000) {
        $errors[] = 'Capacity must be between 1 and 1000.';
    }
    $price = (float) ($data['price'] ?? 0);
    if ($price < 0 || $price > 1000000) {
        $errors[] = 'Price must be 0 or a positive number.';
    }

    $validStatuses = ['Draft', 'Published', 'Archived'];
    if (!in_array($data['status'] ?? '', $validStatuses, true)) {
        $errors[] = 'Invalid status selected.';
    }

    return $errors;
}

function save_event(array $data, int $userId, ?int $eventId = null, ?array $fileInput = null): array
{
    $isNew  = $eventId === null;
    $errors = validate_event_data($data, $isNew);
    if ($errors) {
        return $errors;
    }

    $bannerImage = trim($data['banner_image'] ?? '') ?: null;
    if ($fileInput && isset($fileInput['error']) && $fileInput['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = handle_banner_upload($fileInput);
        if (isset($upload['error'])) {
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

    if ($isNew) {
        $slug              = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($payload['title'])));
        $slug              = trim($slug, '-') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $payload['slug']   = $slug;
        $payload['created_by'] = $userId;

        $sql = 'INSERT INTO events
                    (title, slug, category, location, description, start_date, start_time,
                     capacity, price, banner_image, status, created_by, created_at, updated_at)
                VALUES
                    (:title,:slug,:category,:location,:description,:start_date,:start_time,
                     :capacity,:price,:banner_image,:status,:created_by,NOW(),NOW())';
    } else {
        $existing = get_event($eventId, true);
        if (!$existing) {
            return ['Event not found.'];
        }
        $payload['slug'] = $existing['slug'];
        $payload['id']   = $eventId;

        $sql = 'UPDATE events SET
                    title=:title, category=:category, location=:location, description=:description,
                    start_date=:start_date, start_time=:start_time, capacity=:capacity,
                    price=:price, banner_image=:banner_image, status=:status, updated_at=NOW()
                WHERE id=:id';
    }

    db()->prepare($sql)->execute($payload);
    return [];
}

function archive_event(int $id): void
{
    db()->prepare("UPDATE events SET status='Archived', updated_at=NOW() WHERE id=:id")
        ->execute(['id' => $id]);
}

function event_has_active_bookings(int $eventId): bool
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM bookings WHERE event_id=:id AND status IN ('Confirmed','Pending')");
    $stmt->execute(['id' => $eventId]);
    return (int) $stmt->fetchColumn() > 0;
}

function delete_event(int $id): void
{
    db()->prepare('DELETE FROM events WHERE id=:id')->execute(['id' => $id]);
}

// ── Admin dashboard stats ─────────────────────────────────────────────────────

function get_admin_stats(): array
{
    $pdo = db();
    return [
        'users'              => (int)   $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
        'events'             => (int)   $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
        'published_events'   => (int)   $pdo->query("SELECT COUNT(*) FROM events WHERE status='Published'")->fetchColumn(),
        'confirmed_bookings' => (int)   $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Confirmed'")->fetchColumn(),
    ];
}

// ── Distinct category list ────────────────────────────────────────────────────

function get_event_categories(): array
{
    return db()->query("SELECT DISTINCT category FROM events ORDER BY category ASC")
               ->fetchAll(PDO::FETCH_COLUMN);
}

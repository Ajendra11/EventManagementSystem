<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/event.php';

require_admin();

if (is_post()) {
    verify_csrf();

    $action  = $_POST['action'] ?? '';
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($action === 'archive' && $eventId > 0) {
        archive_event($eventId);
        audit_log('event_archived', 'event', $eventId);
        flash('success', 'Event archived successfully.');
        redirect('admin/events.php');
    }

    if ($action === 'delete' && $eventId > 0) {
        if (event_has_active_bookings($eventId)) {
            flash('error', 'This event cannot be deleted because it has active bookings. Archive it instead.');
        } else {
            delete_event($eventId);
            audit_log('event_deleted', 'event', $eventId);
            flash('success', 'Event deleted successfully.');
        }

        redirect('admin/events.php');
    }
}

$filters = $_GET;

if (($filters['status'] ?? '') === '') {
    unset($filters['status']);
}

$results = search_events($filters, true);

render_admin_header('Manage Events', ['admin-events.css']);
?>

<div class="container section">
    <div class="events-page-head">
        <h2>Manage events</h2>
        <p>Easily manage and organize your events.</p>
    </div>

    <form class="events-toolbar" method="get">
        <div class="events-search-wrap">
            <input
                type="text"
                name="q"
                value="<?= e($_GET['q'] ?? '') ?>"
                placeholder="Search events by title or location"
            >
        </div>

        <div class="events-filter-wrap">
            <select name="status">
                <option value="" <?= (($_GET['status'] ?? '') === '') ? 'selected' : '' ?>>All</option>
                <option value="Published" <?= (($_GET['status'] ?? '') === 'Published') ? 'selected' : '' ?>>Open</option>
                <option value="Draft" <?= (($_GET['status'] ?? '') === 'Draft') ? 'selected' : '' ?>>Draft</option>
                <option value="Archived" <?= (($_GET['status'] ?? '') === 'Archived') ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <button class="filter-btn" type="submit">Filter</button>

        <a class="create-event-btn" href="<?= APP_URL ?>/admin/events_form.php">
            + Create Event
        </a>
    </form>

    <div class="events-cards">
        <?php if (empty($results['items'])): ?>
            <div class="empty-state-card">
                <h3>No events found</h3>
                <p>Try searching with a different title or location.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($results['items'] as $event): ?>
            <?php
            $displayStatus = event_status($event);
            $hasBookings   = event_has_active_bookings((int) $event['id']);

            $statusClass = match ($displayStatus) {
                'Open'     => 'open',
                'Sold Out' => 'sold-out',
                'Draft'    => 'draft',
                'Archived' => 'archived',
                default    => 'default'
            };

            $formattedDate = 'Date not set';
            if (!empty($event['start_date'])) {
                $formattedDate = date('d M Y', strtotime($event['start_date']));
                if (!empty($event['start_time'])) {
                    $formattedDate .= ', ' . date('g:i A', strtotime($event['start_time']));
                }
            }

            $priceText = !empty($event['price']) && (float) $event['price'] > 0
                ? 'Rs. ' . number_format((float) $event['price'], 0)
                : 'Free';
            ?>

            <div class="event-card">
                <div class="event-card-top">
                    <div class="event-title-wrap">
                        <h3><?= e($event['title']) ?></h3>
                        <p class="event-category"><?= e($event['category']) ?></p>
                    </div>

                    <span class="status-badge <?= e($statusClass) ?>">
                        <?= e($displayStatus) ?>
                    </span>
                </div>

                <div class="event-card-body">
                    <div class="event-meta-grid">
                        <div class="meta-row">
                            <span class="meta-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/>
                                </svg>
                            </span>
                            <span class="meta-text"><?= e($formattedDate) ?></span>
                        </div>

                        <div class="meta-row">
                            <span class="meta-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a7 7 0 0 1 7 7c0 4.97-5.12 10.18-6.46 11.45a.75.75 0 0 1-1.08 0C10.12 19.18 5 13.97 5 9a7 7 0 0 1 7-7Zm0 4.5A2.5 2.5 0 1 0 12 11.5a2.5 2.5 0 0 0 0-5Z"/>
                                </svg>
                            </span>
                            <span class="meta-text"><?= e($event['location']) ?></span>
                        </div>

                        <div class="meta-row">
                            <span class="meta-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 11a3 3 0 1 0-2.999-3A3 3 0 0 0 16 11Zm-8 0A3 3 0 1 0 5 8a3 3 0 0 0 3 3Zm8 2c-2.67 0-8 1.34-8 4v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.66-5.33-4-8-4Zm-8 0c-.29 0-.62.02-.97.05C4.7 13.27 1 14.43 1 17v1a1 1 0 0 0 1 1h5.5a2.5 2.5 0 0 1-.5-1.5V17c0-1.46.8-2.78 2.18-3.85A14.9 14.9 0 0 0 8 13Z"/>
                                </svg>
                            </span>
                            <span class="meta-text"><?= (int) $event['registered_count'] ?> / <?= (int) $event['capacity'] ?> seats</span>
                        </div>

                        <div class="meta-row">
                            <span class="meta-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm.75 15.5v1a.75.75 0 0 1-1.5 0v-.98a4.52 4.52 0 0 1-3.157-1.607.75.75 0 1 1 1.214-.882A3.03 3.03 0 0 0 11.75 16c1.22 0 2.25-.68 2.25-1.75 0-.97-.77-1.46-2.51-1.88-1.86-.45-3.49-1.12-3.49-3.12 0-1.63 1.16-2.86 2.75-3.19V5.5a.75.75 0 0 1 1.5 0v.55a4.1 4.1 0 0 1 2.52 1.34.75.75 0 1 1-1.14.98A2.67 2.67 0 0 0 11.87 7.5c-1.12 0-1.87.62-1.87 1.52 0 .9.75 1.31 2.62 1.77 1.87.46 3.38 1.21 3.38 3.24 0 1.72-1.28 3.03-3.25 3.47Z"/>
                                </svg>
                            </span>
                            <span class="meta-text"><?= e($priceText) ?></span>
                        </div>
                    </div>

                    <div class="event-actions">
                        <a class="action-btn edit-btn" href="<?= APP_URL ?>/admin/events_form.php?id=<?= (int) $event['id'] ?>">
                            Edit
                        </a>

                        <?php if (($event['status'] ?? '') === 'Published'): ?>
                            <form method="post" onsubmit="return confirm('Archive this event?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <button class="action-btn archive-btn" type="submit">Archive</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$hasBookings): ?>
                            <form method="post" onsubmit="return confirm('Permanently delete this event?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <button class="action-btn delete-btn" type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($results['pager']) && ($results['pager']['pages'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $results['pager']['pages']; $i++): ?>
                <a
                    class="btn <?= $i === (int) $results['pager']['page'] ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                    href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php render_admin_footer(); ?>
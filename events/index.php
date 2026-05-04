<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/event.php';

// Get filtered search results using query parameters
$results    = search_events($_GET);

// Fetch all event categories for dropdown
$categories = get_event_categories();

// Render page header
render_header('Browse Events');
?>
<div class="container section">
    <h2>Browse events</h2>

    <!-- Page description -->
    <p class="muted">All upcoming published events. Search by keyword, category, or location.</p>

    <!-- Search and filter form -->
    <form class="panel filters" method="get" style="margin-bottom:1.5rem;">
        
        <!-- Search input field -->
        <div>
            <label>Search</label>
            <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Title, category, location…">
        </div>

        <!-- Category selection dropdown -->
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All categories</option>

                <!-- Loop through categories -->
                <?php foreach ($categories as $cat): ?>
                    <!-- Keep selected category after search -->
                    <option value="<?= e($cat) ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date filter -->
        <div>
            <label>Date from</label>
            <input type="date" name="start_date" value="<?= e($_GET['start_date'] ?? '') ?>">
        </div>

        <!-- Location filter -->
        <div>
            <label>Location</label>
            <input type="text" name="location" value="<?= e($_GET['location'] ?? '') ?>" placeholder="e.g. Kathmandu">
        </div>

        <!-- Submit button -->
        <div style="align-self:flex-end;">
            <button class="btn btn-primary btn-block" type="submit">Search</button>
        </div>
    </form>

    <!-- Display total number of events found -->
    <p class="muted"><?= $results['total'] ?> event(s) found.</p>

    <!-- If no events match search -->
    <?php if (!$results['items']): ?>
        <div class="panel empty">No events matched your search. Try different keywords.</div>

    <?php else: ?>
        <!-- Event grid container -->
        <div class="event-grid section">

            <!-- Loop through each event -->
            <?php foreach ($results['items'] as $event): ?>

                // Get current event status (Open, Sold Out, etc.)
                <?php $status = event_status($event); ?>

                <article class="card event-card">

                    <!-- Show event banner image if available -->
                    <?php if (!empty($event['banner_image'])): ?>
                        <img src="<?= e($event['banner_image']) ?>" alt="<?= e($event['title']) ?>" loading="lazy">
                    
                    <!-- Otherwise show placeholder -->
                    <?php else: ?>
                        <div style="height:180px;background:linear-gradient(135deg,#f3eeff,#ede9fe);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                            &#127917;
                        </div>
                    <?php endif; ?>

                    <div class="content">

                        <!-- Category and status badges -->
                        <div class="status-row">
                            <span class="badge"><?= e($event['category']) ?></span>

                            <!-- Status badge with conditional styling -->
                            <span class="badge <?= $status === 'Open' ? 'success' : ($status === 'Sold Out' ? 'danger' : 'warning') ?>">
                                <?= e($status) ?>
                            </span>
                        </div>

                        <!-- Event title -->
                        <h3><?= e($event['title']) ?></h3>

                        <!-- Short description (limited to 90 characters) -->
                        <p class="muted"><?= e(mb_strimwidth($event['description'], 0, 90, '…')) ?></p>

                        <!-- Event date and time -->
                        <p>
                            <strong><?= e($event['start_date']) ?></strong> 
                            &middot; <?= e(substr($event['start_time'], 0, 5)) ?>
                        </p>

                        <!-- Location and remaining seats -->
                        <p class="muted" style="font-size:.9rem;">
                            <?= e($event['location']) ?> 
                            &middot; <?= (int)$event['seats_left'] ?> seats left
                        </p>

                        <!-- Price display -->
                        <?php if ((float)$event['price'] > 0): ?>
                            <p class="price-tag">Rs. <?= e(number_format((float)$event['price'], 0)) ?></p>
                        <?php else: ?>
                            <p class="price-tag free">Free entry</p>
                        <?php endif; ?>

                        <!-- Link to event details page -->
                        <a class="btn btn-outline btn-block" href="<?= APP_URL ?>/events/show.php?id=<?= (int)$event['id'] ?>">
                            View details
                        </a>
                    </div>
                </article>

            <?php endforeach; ?>
        </div>

        <!-- Pagination if multiple pages exist -->
        <?php if ($results['pager']['pages'] > 1): ?>
            <div class="pagination">

                <!-- Loop through page numbers -->
                <?php for ($i = 1; $i <= $results['pager']['pages']; $i++): ?>

                    <!-- Highlight current page -->
                    <a class="btn <?= $i === $results['pager']['page'] ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                       href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>">
                       <?= $i ?>
                    </a>

                <?php endfor; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php 
// Render footer
render_footer(); 
?>
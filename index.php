<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/event.php';

$results    = search_events($_GET);
$categories = get_event_categories();
render_header('Browse Events');
?>
<div class="container section">
    <h2>Browse events</h2>
    <p class="muted">All upcoming published events. Search by keyword, category, or location.</p>

    <form class="panel filters" method="get" style="margin-bottom:1.5rem;">
        <div><label>Search</label><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Title, category, location…"></div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Date from</label><input type="date" name="start_date" value="<?= e($_GET['start_date'] ?? '') ?>"></div>
        <div><label>Location</label><input type="text" name="location" value="<?= e($_GET['location'] ?? '') ?>" placeholder="e.g. Kathmandu"></div>
        <div style="align-self:flex-end;"><button class="btn btn-primary btn-block" type="submit">Search</button></div>
    </form>

    <p class="muted"><?= $results['total'] ?> event(s) found.</p>

    <?php if (!$results['items']): ?>
        <div class="panel empty">No events matched your search. Try different keywords.</div>
    <?php else: ?>
        <div class="event-grid section">
            <?php foreach ($results['items'] as $event): ?>
                <?php $status = event_status($event); ?>
                <article class="card event-card">
                    <?php if (!empty($event['banner_image'])): ?>
                        <img src="<?= e($event['banner_image']) ?>" alt="<?= e($event['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div style="height:180px;background:linear-gradient(135deg,#f3eeff,#ede9fe);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">&#127917;</div>
                    <?php endif; ?>
                    <div class="content">
                        <div class="status-row">
                            <span class="badge"><?= e($event['category']) ?></span>
                            <span class="badge <?= $status === 'Open' ? 'success' : ($status === 'Sold Out' ? 'danger' : 'warning') ?>"><?= e($status) ?></span>
                        </div>
                        <h3><?= e($event['title']) ?></h3>
                        <p class="muted"><?= e(mb_strimwidth($event['description'], 0, 90, '…')) ?></p>
                        <p><strong><?= e($event['start_date']) ?></strong> &middot; <?= e(substr($event['start_time'], 0, 5)) ?></p>
                        <p class="muted" style="font-size:.9rem;"><?= e($event['location']) ?> &middot; <?= (int)$event['seats_left'] ?> seats left</p>
                        <?php if ((float)$event['price'] > 0): ?>
                            <p class="price-tag">Rs. <?= e(number_format((float)$event['price'], 0)) ?></p>
                        <?php else: ?>
                            <p class="price-tag free">Free entry</p>
                        <?php endif; ?>
                        <a class="btn btn-outline btn-block" href="<?= APP_URL ?>/events/show.php?id=<?= (int)$event['id'] ?>">View details</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($results['pager']['pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $results['pager']['pages']; $i++): ?>
                    <a class="btn <?= $i === $results['pager']['page'] ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                       href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php render_footer(); ?>

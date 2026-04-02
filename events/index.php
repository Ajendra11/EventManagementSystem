<?php
require_once __DIR__ . '/../includes/layout.php';

// Read filter inputs from GET 
$q        = trim($_GET['q']        ?? '');  // keyword search
$category = trim($_GET['category'] ?? '');  // category dropdown
$date     = trim($_GET['date']     ?? '');  // date picker
$location = trim($_GET['location'] ?? '');  // city or venue

render_header('Events');
?>

<div class="container section">

    <!-- Page heading -->
    <div class="status-row" style="justify-content:space-between; margin-bottom:1.5rem;">
        <div>
            <h2>Upcoming Events</h2>
            <p class="muted">Browse and filter events happening near you.</p>
        </div>
    </div>

    <!-- Filter form -->
    <form method="get" class="panel" style="margin-bottom:2rem;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; align-items:flex-end;">

            <!-- Keyword search -->
            <div class="form-group" style="margin:0;">
                <label for="q">Search</label>
                <input id="q" type="text" name="q"
                       value="<?= e($q) ?>"
                       placeholder="Event name or keyword…">
            </div>

            <!-- Category dropdown -->
            <div class="form-group" style="margin:0;">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php
                    // Hardcoded category list 
                    $categories = ['Music', 'Technology', 'Sports', 'Arts', 'Food & Drink', 'Networking'];
                    foreach ($categories as $cat):
                        $selected = $category === $cat ? 'selected' : '';
                    ?>
                        <option value="<?= e($cat) ?>" <?= $selected ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date picker -->
            <div class="form-group" style="margin:0;">
                <label for="date">Date</label>
                <input id="date" type="date" name="date" value="<?= e($date) ?>">
            </div>

            <!-- Location text input -->
            <div class="form-group" style="margin:0;">
                <label for="location">Location</label>
                <input id="location" type="text" name="location"
                       value="<?= e($location) ?>"
                       placeholder="City or venue…">
            </div>

            <!-- Submit and clear buttons -->
            <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>

        </div>
    </form>

    <!-- Event cards grid — hardcoded placeholders -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:1.5rem;">

        <!-- Card 1: Technology event -->
        <div class="panel" style="display:flex; flex-direction:column; gap:.75rem;">
            <div class="status-row" style="justify-content:space-between; margin:0;">
                <span class="badge">Technology</span>
                <span class="muted" style="font-size:.85rem;">12 seats left</span>
            </div>
            <h3 style="margin:0;">Tech Nepal Summit 2025</h3>
            <div class="muted" style="font-size:.9rem; display:flex; flex-direction:column; gap:.25rem;">
                <span>Date: April 15, 2025</span>
                <span>Location: Kathmandu, Nepal</span>
                <span>Price: NPR 500</span>
            </div>
            <p style="margin:0; font-size:.93rem; color:var(--text);">
                A premier gathering of tech enthusiasts, startups, and innovators
                shaping Nepal's digital future.
            </p>

            <!-- View Details links to event detail page (not yet built) -->
            <a class="btn btn-primary" href="#" style="margin-top:auto;">View Details</a>
        </div>

        <!-- Card 2: Music event -->
        <div class="panel" style="display:flex; flex-direction:column; gap:.75rem;">
            <div class="status-row" style="justify-content:space-between; margin:0;">
                <span class="badge">Music</span>
                <span class="muted" style="font-size:.85rem;">38 seats left</span>
            </div>
            <h3 style="margin:0;">Himalayan Music Festival</h3>
            <div class="muted" style="font-size:.9rem; display:flex; flex-direction:column; gap:.25rem;">
                <span>Date: May 3, 2025</span>
                <span>Location: Pokhara, Nepal</span>
                <span>Price: NPR 1,200</span>
            </div>
            <p style="margin:0; font-size:.93rem; color:var(--text);">
                Three days of live music under the Annapurna skyline — featuring
                local and international artists across multiple stages.
            </p>
            <a class="btn btn-primary" href="#" style="margin-top:auto;">View Details</a>
        </div>

        <!-- Card 3: Networking event — badge danger used because seats are nearly full -->
        <div class="panel" style="display:flex; flex-direction:column; gap:.75rem;">
            <div class="status-row" style="justify-content:space-between; margin:0;">
                <span class="badge">Networking</span>
                <span class="badge danger" style="font-size:.85rem;">3 seats left</span>
            </div>
            <h3 style="margin:0;">Startup Founders Meetup</h3>
            <div class="muted" style="font-size:.9rem; display:flex; flex-direction:column; gap:.25rem;">
                <span>Date: April 28, 2025</span>
                <span>Location: Lalitpur, Nepal</span>
                <span>Price: Free</span>
            </div>
            <p style="margin:0; font-size:.93rem; color:var(--text);">
                Connect with fellow founders, share your journey, and explore
                collaboration opportunities in Nepal's growing startup ecosystem.
            </p>
            <a class="btn btn-primary" href="#" style="margin-top:auto;">View Details</a>
        </div>

    </div>

</div>

<?php render_footer(); ?>
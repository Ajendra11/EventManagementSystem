<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/event.php';
require_admin();

$event = null;
$isEdit = false;

if (!empty($_GET['id'])) {
    $event = get_event((int)$_GET['id'], true);

    if (!$event) {
        flash('error', 'Event not found.');
        redirect('admin/events.php');
    }

    $isEdit = true;
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $fileInput = $_FILES['banner_file'] ?? null;
    $errors = save_event($_POST, (int)auth_user()['id'], $isEdit ? (int)$event['id'] : null, $fileInput);

    if (!$errors) {
        flash('success', $isEdit ? 'Event updated successfully.' : 'Event created successfully.');
        redirect('admin/events.php');
    }
}

$v = fn(string $key, string $fallback = '') => e($_POST[$key] ?? $event[$key] ?? $fallback);

/* THIS IS THE IMPORTANT FIX */
render_admin_header($isEdit ? 'Edit Event' : 'Create Event', ['admin-events-form.css']);
?>
<div class="container section">
    <form class="panel" method="post" enctype="multipart/form-data" data-validate="true" style="max-width:860px;">
        <h2><?= $isEdit ? 'Edit event' : 'Create new event' ?></h2>
        <p class="muted">Fields marked * are required.</p>

        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" data-required="true" data-label="Title" value="<?= $v('title') ?>" maxlength="150">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" data-required="true" data-label="Category" value="<?= $v('category') ?>" placeholder="e.g. Conference, Workshop">
            </div>

            <div class="form-group">
                <label>Location *</label>
                <input type="text" name="location" data-required="true" data-label="Location" value="<?= $v('location') ?>" placeholder="e.g. Kathmandu">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Start date *</label>
                <input type="date" name="start_date" data-required="true" data-label="Start date" value="<?= $v('start_date') ?>" <?= !$isEdit ? 'min="' . date('Y-m-d') . '"' : '' ?>>
            </div>

            <div class="form-group">
                <label>Start time *</label>
                <input type="time" name="start_time" data-required="true" data-label="Start time" value="<?= e(substr($_POST['start_time'] ?? $event['start_time'] ?? '', 0, 5)) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Capacity (1–1000) *</label>
                <input type="number" min="1" max="1000" name="capacity" data-required="true" data-label="Capacity" value="<?= $v('capacity', '50') ?>">
            </div>

            <div class="form-group">
                <label>Ticket price (NPR) *</label>
                <input type="number" step="0.01" min="0" name="price" data-required="true" data-label="Price" value="<?= $v('price', '0') ?>">
                <p class="muted" style="font-size:.82rem;margin-top:.3rem;">Set to 0 for free events. Paid event booking requires Sprint 2 (Khalti integration).</p>
            </div>
        </div>

        <div class="form-group">
            <label>Status *</label>
            <select name="status">
                <?php foreach (['Draft', 'Published', 'Archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($v('status', 'Draft') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <p class="muted" style="font-size:.85rem;margin-top:.3rem;">Only <strong>Published</strong> events appear in the public listing.</p>
        </div>

        <div class="form-group">
            <label>Banner image <span class="muted">(optional)</span></label>
            <input type="file" name="banner_file" accept="image/jpeg,image/png" style="padding:.5rem .75rem;">
            <p class="muted" style="font-size:.82rem;margin-top:.3rem;">Upload JPG or PNG, max 5 MB. Or paste an image URL below.</p>
        </div>

        <div class="form-group">
            <label>Banner image URL <span class="muted">(alternative to upload)</span></label>
            <input type="url" name="banner_image" value="<?= $v('banner_image') ?>" placeholder="https://example.com/banner.jpg">

            <?php if (!empty($event['banner_image'])): ?>
                <div style="margin-top:.5rem;">
                    <img src="<?= e($event['banner_image']) ?>" alt="Current banner" style="height:80px;border-radius:8px;object-fit:cover;">
                    <span class="muted" style="font-size:.82rem;display:block;margin-top:.3rem;">Current banner</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Description * <span class="muted">(20–2000 characters)</span></label>
            <textarea name="description" data-required="true" data-label="Description" id="desc-field"><?= $v('description') ?></textarea>
            <small class="muted"><span id="desc-count">0</span> / 2000 characters</small>
        </div>

        <div class="status-row">
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save changes' : 'Create event' ?></button>
            <a class="btn btn-outline" href="<?= APP_URL ?>/admin/events.php">Cancel</a>
        </div>
    </form>
</div>

<script>
(function () {
    var ta = document.getElementById('desc-field');
    var ct = document.getElementById('desc-count');

    if (ta && ct) {
        ct.textContent = ta.value.length;
        ta.addEventListener('input', function () {
            ct.textContent = this.value.length;
        });
    }
})();
</script>

<?php render_admin_footer(); ?>
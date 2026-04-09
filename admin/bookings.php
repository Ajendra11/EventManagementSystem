<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/booking.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $action    = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if ($action === 'remove' && $bookingId) {
        db()->prepare('DELETE FROM bookings WHERE id = :id')->execute(['id' => $bookingId]);
        audit_log('booking_removed', 'booking', $bookingId);
        flash('success', 'Booking record removed.');
    }
    redirect('admin/bookings.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$statusFilter = $_GET['status'] ?? '';
$validFilters = ['', 'Pending', 'Confirmed', 'Cancelled'];
if (!in_array($statusFilter, $validFilters, true)) $statusFilter = '';

$bookings = get_all_bookings_admin($statusFilter ?: null);
render_header('Manage Bookings');
?>
<div class="container section">
    <h2>Manage bookings</h2>
    <p class="muted"><?= count($bookings) ?> record(s) <?= $statusFilter ? "— filtered by <strong>{$statusFilter}</strong>" : '' ?>.</p>

    <div class="status-row" style="margin:1rem 0;">
        <?php foreach ($validFilters as $f): ?>
            <a class="btn btn-sm <?= $statusFilter === $f ? 'btn-primary' : 'btn-outline' ?>"
               href="?status=<?= urlencode($f) ?>"><?= $f === '' ? 'All' : e($f) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="panel table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Participant</th><th>Event</th><th>Date</th><th>Qty</th><th>Amount (NPR)</th><th>Status</th><th>Booked On</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (!$bookings): ?><tr><td colspan="9" class="empty">No bookings found.</td></tr><?php endif; ?>
            <?php foreach ($bookings as $b): ?>
                <?php $badgeClass = match($b['status']) { 'Confirmed' => 'success', 'Pending' => 'warning', 'Cancelled' => 'danger', default => '' }; ?>
                <tr>
                    <td><?= (int)$b['id'] ?></td>
                    <td><?= e($b['full_name']) ?><br><span class="muted"><?= e($b['email']) ?></span></td>
                    <td><?= e($b['event_title']) ?></td>
                    <td><?= e($b['start_date']) ?><br><span class="muted"><?= e(substr($b['start_time'], 0, 5)) ?></span></td>
                    <td><?= (int)$b['quantity'] ?></td>
                    <td><?= (float)$b['amount'] > 0 ? 'Rs. ' . number_format((float)$b['amount'], 2) : 'Free' ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= e($b['status']) ?></span></td>
                    <td><?= e(substr($b['booking_date'], 0, 10)) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Permanently remove this booking record?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_footer(); ?>

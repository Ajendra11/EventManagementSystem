<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/qr.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$ticket = $token !== '' ? get_ticket_by_token($token) : null;
$errors = [];

if (is_post() && ($_POST['action'] ?? '') === 'check_in') {
    require_login();
    if (!is_admin()) {
        flash('error', 'Administrator access is required for ticket check-in.');
        redirect('tickets/verify.php?token=' . urlencode($token));
    }

    verify_csrf();
    $errors = check_in_ticket($token, (int)auth_user()['id']);
    flash($errors ? 'error' : 'success', $errors[0] ?? 'Ticket checked in successfully.');
    redirect('tickets/verify.php?token=' . urlencode($token));
}

render_header('Verify Ticket');
?>
<div class="container section">
    <div class="panel" style="max-width:760px;">
        <h2>Ticket verification</h2>
        <p class="muted">Scan a QR code or enter a token manually to verify a booking.</p>

        <form method="get" class="status-row" style="margin:1rem 0;">
            <input type="text" name="token" value="<?= e($token) ?>" placeholder="Enter QR token" style="flex:1;">
            <button class="btn btn-primary" type="submit">Verify</button>
        </form>

        <?php if ($token !== '' && !$ticket): ?>
            <div class="flash flash-error">No ticket found for this token.</div>
        <?php endif; ?>

        <?php if ($ticket): ?>
            <table class="info-table">
                <tr><th>Event</th><td><?= e($ticket['event_title']) ?></td></tr>
                <tr><th>Date &amp; Time</th><td><?= e($ticket['start_date']) ?> at <?= e(substr($ticket['start_time'], 0, 5)) ?></td></tr>
                <tr><th>Attendee</th><td><?= e($ticket['attendee_name']) ?></td></tr>
                <tr><th>Seats</th><td><?= (int)$ticket['quantity'] ?></td></tr>
                <tr><th>Booking status</th><td><span class="badge"><?= e($ticket['status']) ?></span></td></tr>
                <tr><th>Check-in status</th><td>
                    <?php if (!empty($ticket['checked_in_at'])): ?>
                        Checked in at <?= e(substr($ticket['checked_in_at'], 0, 16)) ?>
                    <?php else: ?>
                        Not checked in
                    <?php endif; ?>
                </td></tr>
            </table>

            <?php if (is_logged_in() && is_admin() && $ticket['status'] === 'Confirmed' && empty($ticket['checked_in_at'])): ?>
                <form method="post" style="margin-top:1rem;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="action" value="check_in">
                    <button class="btn btn-primary" type="submit">Mark as checked in</button>
                </form>
            <?php elseif (!is_logged_in() || !is_admin()): ?>
                <p class="muted" style="margin-top:1rem;">Only administrators can mark tickets as checked in.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php render_footer(); ?>

<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user       = auth_user();
$nameErrors = [];
$passErrors = [];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_name') {
        $nameErrors = update_profile_name((int) $user['id'], $_POST['full_name'] ?? '');
        if (!$nameErrors) { flash('success', 'Your name has been updated.'); redirect('auth/profile.php'); }
    } elseif ($action === 'change_password') {
        $passErrors = change_password((int) $user['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '', $_POST['confirm_new_password'] ?? '');
        if (!$passErrors) { flash('success', 'Password changed successfully.'); redirect('auth/profile.php'); }
    }
}

render_header('My Profile');
?>
<div class="container section">
    <h2>My profile</h2>
    <p class="muted">Update your display name or change your password.</p>
    <div class="grid-2" style="margin-top:1.5rem;">
        <div class="panel">
            <h3>Display name</h3>
            <?php foreach ($nameErrors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="post" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_name">
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input id="full_name" type="text" name="full_name" data-required="true" data-label="Full name"
                           value="<?= e(!empty($nameErrors) ? ($_POST['full_name'] ?? '') : $user['name']) ?>">
                </div>
                <button class="btn btn-primary" type="submit">Save name</button>
            </form>
        </div>
        <div class="panel">
            <h3>Change password</h3>
            <?php foreach ($passErrors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="post" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" data-required="true" data-label="Current password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="new_password">New password</label>
                    <input id="new_password" type="password" name="new_password" data-required="true" data-label="New password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirm new password</label>
                    <input id="confirm_new_password" type="password" name="confirm_new_password" data-required="true" data-label="Confirm new password" autocomplete="new-password">
                </div>
                <p class="muted" style="font-size:.88rem;">8–64 chars · uppercase · lowercase · number · special character.</p>
                <button class="btn btn-primary" type="submit">Change password</button>
            </form>
        </div>
    </div>
    <div class="panel" style="margin-top:1.5rem;">
        <h3>Account details</h3>
        <p><strong>Email:</strong> <?= e($user['email']) ?></p>
        <p><strong>Role:</strong> <?= e(ucfirst($user['role'])) ?></p>
    </div>
</div>
<?php render_footer(); ?>
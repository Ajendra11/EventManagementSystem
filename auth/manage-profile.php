<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = auth_user();
$errors = [];

if (is_post()) {
    verify_csrf();

    $errors = update_profile_name((int) $user['id'], $_POST['full_name'] ?? '');

    if (!$errors) {
        flash('success', 'Profile updated successfully.');
        redirect('events/index.php');
    }
}

render_header('Manage Profile');
?>

<div class="container section">
    <div class="pm-settings-shell">
        <a class="pm-inline-back" href="javascript:history.back()">
            <span>&larr;</span>
            <span>Back</span>
        </a>

        <div class="pm-settings-card panel">
            <div class="pm-settings-head">
                <h1 class="pm-settings-title">Manage Profile</h1>
                <p class="pm-settings-subtitle">
                    Update the display name shown across your account.
                </p>
            </div>

            <?php if ($errors): ?>
                <div class="pm-settings-errors">
                    <?php foreach ($errors as $err): ?>
                        <div class="flash flash-error"><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="pm-settings-form" data-validate="true">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="pm-settings-field">
                    <label for="full_name">Display name</label>
                    <input
                        id="full_name"
                        type="text"
                        name="full_name"
                        data-required="true"
                        data-label="Display name"
                        value="<?= e(!empty($errors) ? ($_POST['full_name'] ?? '') : $user['name']) ?>"
                        placeholder="Enter your display name"
                    >
                    <p class="pm-settings-help">
                        This is the name that will appear in your account.
                    </p>
                </div>

                <div class="pm-settings-actions">
                    <button class="btn btn-primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_footer(); ?>

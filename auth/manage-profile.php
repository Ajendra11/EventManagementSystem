<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user   = auth_user();
$errors = [];

if (is_post()) {
    verify_csrf();
    $errors = update_profile_name((int) $user['id'], $_POST['full_name'] ?? '');

    if (!$errors) {
        flash('success', 'Profile updated successfully.');
        redirect('auth/profile.php');
    }
}

render_header('Manage Profile');
?>

<div class="container section">
    <div class="pm-shell">
        <div class="pm-card">
            <div class="pm-sub-header">
                <a class="pm-back-link" href="<?= APP_URL ?>/auth/profile.php">&larr; Back</a>
            </div>

            <div class="pm-form-wrap">
                <h1 class="pm-page-title">Manage Profile</h1>
                <p class="pm-page-subtitle">
                    Update the display name shown across your account only.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="flash flash-error"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" data-validate="true">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="pm-form-group">
                        <label for="full_name">Display name</label>
                        <input
                            id="full_name"
                            type="text"
                            name="full_name"
                            data-required="true"
                            data-label="Display name"
                            value="<?= e(!empty($errors) ? ($_POST['full_name'] ?? '') : $user['name']) ?>"
                        >
                    </div>

                    <button class="pm-primary-btn" type="submit">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
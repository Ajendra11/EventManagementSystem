<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login();

$user = auth_user();
$displayName = (string)($user['name'] ?? $user['full_name'] ?? 'User');
$email = (string)($user['email'] ?? '');
$role = (string)($user['role'] ?? 'participant');
$avatarLetter = strtoupper(mb_substr(trim($displayName), 0, 1));

render_header('My Profile');
?>

<div class="container section profile-page">
    <div class="profile-hero panel">
        <div class="profile-hero-main">
            <div class="profile-avatar-large"><?= e($avatarLetter) ?></div>

            <div class="profile-hero-text">
                <h1><?= e($displayName) ?></h1>
                <p class="muted"><?= e($email) ?></p>
                <span class="badge <?= $role === 'admin' ? 'warning' : 'success' ?>">
                    <?= e(ucfirst($role)) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="profile-actions-wrap">
        <div class="profile-actions-popout panel">
            <h2>Account</h2>
            <p class="muted">Manage your account settings and security.</p>

            <div class="profile-actions-list">
                <a class="profile-action-item" href="<?= APP_URL ?>/auth/manage-profile.php">
                    <div>
                        <strong>Manage Account</strong>
                        <span>Update your profile details</span>
                    </div>
                    <span class="profile-action-arrow">›</span>
                </a>

                <a class="profile-action-item" href="<?= APP_URL ?>/auth/password-security.php">
                    <div>
                        <strong>Password &amp; Security</strong>
                        <span>Change your password and protect your account</span>
                    </div>
                    <span class="profile-action-arrow">›</span>
                </a>

                <a class="profile-action-item danger" href="<?= APP_URL ?>/auth/delete-account.php">
                    <div>
                        <strong>Delete Account</strong>
                        <span>Permanently remove your account</span>
                    </div>
                    <span class="profile-action-arrow">›</span>
                </a>

                <a class="profile-action-item" href="<?= APP_URL ?>/auth/logout.php">
                    <div>
                        <strong>Logout</strong>
                        <span>Sign out of your account</span>
                    </div>
                    <span class="profile-action-arrow">›</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
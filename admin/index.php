<?php
require_once __DIR__ . '/../includes/layout.php';
require_admin();

render_header('Admin Dashboard');
?>
<div class="container section">
    <div class="status-row" style="justify-content:space-between;">
        <div>
            <h2>Admin dashboard</h2>
            <p class="muted">User management and system overview.</p>
        </div>
    </div>

    <div class="grid-2" style="margin-top:1.5rem;">
        <a class="panel nav-card" href="<?= APP_URL ?>/admin/users.php">
            <h3>👤 Manage users</h3>
            <p class="muted">View, block, activate, or change roles of registered users.</p>
        </a>
        <a class="panel nav-card" href="<?= APP_URL ?>/auth/profile.php">
            <h3>🔐 My profile</h3>
            <p class="muted">Update your name or password.</p>
        </a>
    </div>
</div>
<?php render_footer(); ?>
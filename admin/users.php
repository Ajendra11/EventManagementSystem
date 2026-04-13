<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = db();
$selfId = (int) (auth_user()['id'] ?? 0);

if (is_post()) {
    verify_csrf();

    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($userId > 0 && $userId !== $selfId) {
        if ($action === 'toggle_status') {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET status = IF(status = 'active', 'blocked', 'active')
                 WHERE id = :id"
            );
            $stmt->execute(['id' => $userId]);

            audit_log('user_status_toggle', 'user', $userId);
            flash('success', 'User status updated.');
        } elseif ($action === 'toggle_role') {
            $stmt = $pdo->prepare(
                "UPDATE users
                 SET role = IF(role = 'participant', 'admin', 'participant')
                 WHERE id = :id"
            );
            $stmt->execute(['id' => $userId]);

            audit_log('user_role_toggle', 'user', $userId);
            flash('success', 'User role updated.');
        }
    }

    redirect('admin/users.php');
}

$search = trim((string) ($_GET['q'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$sql = "
    SELECT id, full_name, email, role, status, failed_login_attempts, locked_until, created_at
    FROM users
";

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE :q_name OR email LIKE :q_email)";
    $params['q_name'] = '%' . $search . '%';
    $params['q_email'] = '%' . $search . '%';
}

if ($roleFilter !== '') {
    $where[] = "role = :role";
    $params['role'] = strtolower($roleFilter);
}

if ($statusFilter !== '') {
    $where[] = "status = :status";
    $params['status'] = strtolower($statusFilter);
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

render_admin_header('Manage Users', ['admin-users.css']);
?>

<div class="container section">
    <div class="users-header-row">
        <div class="users-page-head">
            <h2>Manage users</h2>
            <p>Easily manage and organize your users.</p>
        </div>

       
    </div>

    <form class="users-toolbar" method="get">
        <div class="users-search-wrap">
            <input
                type="text"
                name="q"
                value="<?= e($search) ?>"
                placeholder="Search users by name or email"
            >
        </div>

        <div class="users-filter-wrap">
            <select name="role">
                <option value="" <?= $roleFilter === '' ? 'selected' : '' ?>>All Roles</option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="participant" <?= $roleFilter === 'participant' ? 'selected' : '' ?>>Participant</option>
            </select>
        </div>

        <div class="users-filter-wrap">
            <select name="status">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="blocked" <?= $statusFilter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
            </select>
        </div>

        <button class="filter-btn" type="submit">Filter</button>
    </form>

    <div class="users-cards">
        <?php if (!$users): ?>
            <div class="empty-state-card">
                <h3>No users found</h3>
                <p>Try searching with a different name, email, role, or status.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($users as $user): ?>
            <?php
            $isSelf = (int) $user['id'] === $selfId;
            $isLocked = !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time();

            $roleClass = match (strtolower((string) $user['role'])) {
                'admin' => 'admin',
                'participant' => 'participant',
                default => 'default',
            };

            $statusClass = match (strtolower((string) $user['status'])) {
                'active' => 'active',
                'blocked' => 'blocked',
                default => 'default',
            };

            $statusActionLabel = strtolower((string) $user['status']) === 'active' ? 'Block' : 'Activate';
            $statusActionClass = strtolower((string) $user['status']) === 'active'
                ? 'action-btn danger-btn'
                : 'action-btn success-btn';

            $roleActionLabel = strtolower((string) $user['role']) === 'admin'
                ? 'Make participant'
                : 'Make admin';

            $joinedText = '';
            if (!empty($user['created_at'])) {
                $joinedText = date('d M Y', strtotime((string) $user['created_at']));
            }
            ?>

            <div class="user-card">
                <div class="user-card-top">
                    <div class="user-info-block">
                        <h3 class="user-name"><?= e($user['full_name']) ?></h3>
                        <p class="user-email"><?= e($user['email']) ?></p>
                    </div>

                    <div class="user-badges">
                        <span class="role-badge <?= e($roleClass) ?>">
                            <?= e(ucfirst((string) $user['role'])) ?>
                        </span>

                        <span class="state-badge <?= e($statusClass) ?>">
                            <?= e(ucfirst((string) $user['status'])) ?>
                        </span>
                    </div>
                </div>

                <div class="user-meta">
                    <div class="user-meta-row">
                        <span class="user-meta-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                        </span>
                        <span class="user-meta-text">Joined <?= e($joinedText) ?></span>
                    </div>

                    <div class="user-meta-row">
                        <span class="user-meta-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <span class="user-meta-text"><?= (int) $user['failed_login_attempts'] ?> failed login(s)</span>
                    </div>

                    <?php if ($isLocked): ?>
                        <div class="user-meta-row">
                            <span class="user-meta-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 2h12"></path>
                                    <path d="M6 22h12"></path>
                                    <path d="M8 2v6a4 4 0 0 0 1.172 2.828L12 13.657l2.828-2.829A4 4 0 0 0 16 8V2"></path>
                                    <path d="M16 22v-6a4 4 0 0 0-1.172-2.828L12 10.343l-2.828 2.829A4 4 0 0 0 8 16v6"></path>
                                </svg>
                            </span>
                            <span class="user-meta-text">
                                Locked until <?= e(substr((string) $user['locked_until'], 0, 16)) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="user-actions">
                    <?php if ($isSelf): ?>
                        <span class="self-pill">You</span>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button class="<?= e($statusActionClass) ?>" type="submit">
                                <?= e($statusActionLabel) ?>
                            </button>
                        </form>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <input type="hidden" name="action" value="toggle_role">
                            <button class="action-btn role-btn" type="submit">
                                <?= e($roleActionLabel) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php render_admin_footer(); ?>

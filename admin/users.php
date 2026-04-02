<?php
require_once __DIR__ . '/../includes/layout.php';
require_admin();

$pdo    = db();
$selfId = (int)auth_user()['id'];

if (is_post()) {
    verify_csrf();
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($uid && $uid !== $selfId) {
        if ($action === 'toggle_status') {
            $pdo->prepare("UPDATE users SET status=IF(status='active','blocked','active') WHERE id=:id")->execute(['id' => $uid]);
            flash('success', 'User status updated.');
        } elseif ($action === 'toggle_role') {
            $pdo->prepare("UPDATE users SET role=IF(role='participant','admin','participant') WHERE id=:id")->execute(['id' => $uid]);
            flash('success', 'User role updated.');
        }
    }
    redirect('admin/users.php');
}

$search = trim($_GET['q'] ?? '');
$sql    = 'SELECT id, full_name, email, role, status, failed_login_attempts, locked_until, created_at FROM users';
$params = [];
if ($search) {
    $sql .= ' WHERE full_name LIKE :q OR email LIKE :q';
    $params['q'] = '%' . $search . '%';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
render_header('Manage Users');
?>
<div class="container section">
    <h2>Manage users</h2>
    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1.25rem;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or email…">
        </div>
        <button class="btn btn-primary" type="submit">Search</button>
        <?php if ($search): ?><a class="btn btn-outline" href="?">Clear</a><?php endif; ?>
    </form>
    <p class="muted"><?= count($users) ?> account(s).</p>
    <div class="panel table-wrap" style="margin-top:1rem;">
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Failed logins</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <?php $isLocked = !empty($user['locked_until']) && strtotime($user['locked_until']) > time(); ?>
                <tr>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge <?= $user['role'] === 'admin' ? 'warning' : '' ?>"><?= e($user['role']) ?></span></td>
                    <td>
                        <span class="badge <?= $user['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($user['status']) ?></span>
                        <?php if ($isLocked): ?><br><small class="muted">Locked until <?= e(substr($user['locked_until'], 0, 16)) ?></small><?php endif; ?>
                    </td>
                    <td><?= (int)$user['failed_login_attempts'] ?></td>
                    <td><?= e(substr($user['created_at'], 0, 10)) ?></td>
                    <td>
                        <?php if ((int)$user['id'] === $selfId): ?>
                            <span class="muted">You</span>
                        <?php else: ?>
                            <div class="status-row">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <button class="btn btn-outline btn-sm" type="submit"><?= $user['status'] === 'active' ? 'Block' : 'Activate' ?></button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <button class="btn btn-outline btn-sm" type="submit">Switch role</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_footer(); ?>
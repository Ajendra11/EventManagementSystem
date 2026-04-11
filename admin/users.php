<?php
// Include layout and required helper functions
require_once __DIR__ . '/../includes/layout.php';

// Ensure only admin users can access this page
require_admin();

// Get database connection
$pdo= db();

// Get currently logged-in user ID (to prevent self-modification)
$selfId = (int)auth_user()['id'];

// Handle POST request (form submissions)
if (is_post()) {
    // Verify CSRF token for security
    verify_csrf();

    // Get user ID and requested action from form
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Prevent admin from modifying their own account
    if ($uid && $uid !== $selfId) {

        // Toggle user status (active <-> blocked)
        if ($action === 'toggle_status') {
            $pdo->prepare("
                UPDATE users 
                SET status = IF(status='active','blocked','active') 
                WHERE id = :id
            ")->execute(['id' => $uid]);

            // Log action for auditing
            audit_log('user_status_toggle', 'user', $uid);

            // Show success message
            flash('success', 'User status updated.');
        }

        // Toggle user role (participant <-> admin)
        elseif ($action === 'toggle_role') {
            $pdo->prepare("
                UPDATE users 
                SET role = IF(role='participant','admin','participant') 
                WHERE id = :id
            ")->execute(['id' => $uid]);

            // Log action
            audit_log('user_role_toggle', 'user', $uid);

            flash('success', 'User role updated.');
        }
    }

    // Redirect to avoid form resubmission (PRG pattern)
    redirect('admin/users.php');
}

// Get search query from URL
$search = trim($_GET['q'] ?? '');

// Base SQL query to fetch users
$sql    = 'SELECT id, full_name, email, role, status, failed_login_attempts, locked_until, created_at FROM users';
$params = [];

// Apply search filter if query exists
if ($search) {
    $sql .= ' WHERE full_name LIKE :q1 OR email LIKE :q2';
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
}

// Sort users by newest first
$sql .= ' ORDER BY created_at DESC';

// Prepare and execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Fetch all users
$users = $stmt->fetchAll();

// Render page header
render_header('Manage Users');
?>

<div class="container section">
    <h2>Manage users</h2>

    <!-- Search Form -->
    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1.25rem;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;">
            <label>Search</label>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or email…">
        </div>
        <button class="btn btn-primary" type="submit">Search</button>

        <!-- Show clear button only if search is active -->
        <?php if ($search): ?>
            <a class="btn btn-outline" href="?">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Display total user count -->
    <p class="muted"><?= count($users) ?> account(s).</p>

    <div class="panel table-wrap" style="margin-top:1rem;">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Failed logins</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($users as $user): ?>

                <?php
                // Check if account is temporarily locked
                $isLocked = !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
                ?>

                <tr>
                    <!-- User basic info -->
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['email']) ?></td>

                    <!-- Role badge -->
                    <td>
                        <span class="badge <?= $user['role'] === 'admin' ? 'warning' : '' ?>">
                            <?= e($user['role']) ?>
                        </span>
                    </td>

                    <!-- Status + lock info -->
                    <td>
                        <span class="badge <?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                            <?= e($user['status']) ?>
                        </span>

                        <!-- Show lock info if applicable -->
                        <?php if ($isLocked): ?>
                            <br>
                            <small class="muted">
                                Locked until <?= e(substr($user['locked_until'], 0, 16)) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <!-- Failed login attempts -->
                    <td><?= (int)$user['failed_login_attempts'] ?></td>

                    <!-- Join date -->
                    <td><?= e(substr($user['created_at'], 0, 10)) ?></td>

                    <td>
                        <?php if ((int)$user['id'] === $selfId): ?>
                            <!-- Prevent admin from modifying their own account -->
                            <span class="muted">You</span>
                        <?php else: ?>

                            <div class="status-row">

                                <!-- Toggle status form -->
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_status">

                                    <button class="btn btn-outline btn-sm" type="submit">
                                        <?= $user['status'] === 'active' ? 'Block' : 'Activate' ?>
                                    </button>
                                </form>

                                <!-- Toggle role form -->
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_role">

                                    <button class="btn btn-outline btn-sm" type="submit">
                                        Switch role
                                    </button>
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

<?php
// Render page footer
render_footer();
?>
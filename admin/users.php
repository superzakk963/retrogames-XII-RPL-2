<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/trash.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Manage Users';
$cssPath = '../assets/style.css';
$jsPath  = '../assets/main.js';
$homePath = '../';
$pdo = getDB();
$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = in_array($_POST['role'], ['user','admin']) ? $_POST['role'] : 'user';

            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) $errors[] = 'Invalid username.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email.';
            if (strlen($password) < 6)                               $errors[] = 'Password too short.';

            if (empty($errors)) {
                $dup = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND deleted_at IS NULL");
                $dup->execute([$username, $email]);
                if ($dup->fetch()) {
                    $errors[] = 'Username or email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)")
                        ->execute([$username, $email, $hash, $role]);
                    setFlash('success', "User '$username' created.");
                    header('Location: users.php'); exit;
                }
            }
        }

        if ($action === 'update') {
            $id       = (int)$_POST['id'];
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $role     = in_array($_POST['role'], ['user','admin']) ? $_POST['role'] : 'user';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $newPw    = $_POST['new_password'] ?? '';

            // Prevent self-demotion
            if ($id == $_SESSION['user_id'] && $role !== 'admin') {
                $errors[] = 'Cannot change your own role.';
            }
            if ($id == $_SESSION['user_id'] && !$is_active) {
                $errors[] = 'Cannot deactivate your own account.';
            }

            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) $errors[] = 'Invalid username.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email.';

            if (empty($errors)) {
                // Check dup excluding self (active users only)
                $dup = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=? AND deleted_at IS NULL");
                $dup->execute([$username, $email, $id]);
                if ($dup->fetch()) {
                    $errors[] = 'Username or email already taken.';
                } else {
                    $pdo->prepare("UPDATE users SET username=?,email=?,role=?,is_active=?,updated_at=NOW() WHERE id=?")
                        ->execute([$username, $email, $role, $is_active, $id]);
                    if (!empty($newPw) && strlen($newPw) >= 6) {
                        $hash = password_hash($newPw, PASSWORD_BCRYPT);
                        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $id]);
                    }
                    setFlash('success', "User updated.");
                    header('Location: users.php'); exit;
                }
            }
        }

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $errors[] = 'Cannot delete your own account.';
            } else {
                softDelete('users', $id);
                setFlash('success', 'User moved to Trash. You can restore it from the Trash page.');
                header('Location: users.php'); exit;
            }
        }

        if ($action === 'toggle_status') {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $errors[] = 'Cannot ban yourself.';
            } else {
                $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
                setFlash('success', 'User status toggled.');
                header('Location: users.php'); exit;
            }
        }
    }
}

// Fetch with filters/search
$search    = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$where = ['deleted_at IS NULL']; // exclude recycle-bin users
$params = [];
if ($search) { $where[] = "(username LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter) { $where[] = "role = ?"; $params[] = $roleFilter; }
$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereStr");
$total->execute($params);
$total = $total->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM users WHERE $whereStr ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $eu = $pdo->prepare("SELECT * FROM users WHERE id=? AND deleted_at IS NULL");
    $eu->execute([(int)$_GET['edit']]);
    $editUser = $eu->fetch();
}

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>👥 Manage Users</h2>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php" class="active">Users</a>
            <a href="games.php">Games</a>
            <a href="scores.php">Scores</a>
            <a href="trash.php">🗑 Trash</a>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Create / Edit Form -->
    <div class="admin-card">
        <h3><?= $editUser ? 'Edit User: ' . sanitize($editUser['username']) : 'Create New User' ?></h3>
        <form method="POST" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= sanitize($editUser['username'] ?? '') ?>"
                           required pattern="[a-zA-Z0-9_]{3,50}" maxlength="50">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= sanitize($editUser['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= $editUser ? 'New Password (leave blank to keep)' : 'Password' ?></label>
                    <input type="password" name="<?= $editUser ? 'new_password' : 'password' ?>"
                           <?= $editUser ? '' : 'required' ?> minlength="6" placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user" <?= ($editUser['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <?php if ($editUser): ?>
                <div class="form-group">
                    <label>Status</label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= $editUser['is_active'] ? 'checked' : '' ?>>
                        Active (uncheck to ban)
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editUser ? 'Update User' : 'Create User' ?></button>
                <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Search & Filter -->
    <div class="admin-card">
        <form method="GET" action="users.php" class="filter-form">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search username or email...">
            <select name="role">
                <option value="">All Roles</option>
                <option value="user" <?= $roleFilter==='user'?'selected':'' ?>>Users</option>
                <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admins</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="users.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Users Table -->
    <div class="admin-card">
        <h3>All Users (<?= $total ?>)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
                    <th>Status</th><th>Last Login</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="<?= !$u['is_active'] ? 'row-inactive' : '' ?>">
                    <td><?= $u['id'] ?></td>
                    <td><?= sanitize($u['username']) ?> <?= $u['id'] == $_SESSION['user_id'] ? '<span class="badge badge-you">You</span>' : '' ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Banned' ?></span></td>
                    <td><?= $u['last_login'] ? date('M d Y', strtotime($u['last_login'])) : 'Never' ?></td>
                    <td><?= date('M d Y', strtotime($u['created_at'])) ?></td>
                    <td class="action-cell">
                        <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-xs btn-primary">Edit</a>

                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-warning"
                                    onclick="return confirm('Toggle status for <?= sanitize($u['username']) ?>?')">
                                <?= $u['is_active'] ? 'Ban' : 'Unban' ?>
                            </button>
                        </form>

                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete user <?= sanitize($u['username']) ?>? It will be moved to the Trash and can be restored.')">
                                Delete
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="users.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>"
                   class="btn btn-xs <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

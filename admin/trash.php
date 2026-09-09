<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/trash.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Recycle Bin';
$cssPath = '../assets/style.css';
$jsPath  = '../assets/main.js';
$homePath = '../';
$pdo = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        $table  = in_array($_POST['table'] ?? '', ['users', 'scores'], true) ? $_POST['table'] : '';

        if ($action === 'restore' && $table && $id) {
            restoreFromTrash($table, $id);
            setFlash('success', ucfirst($table) . ' restored from Trash.');
            header('Location: trash.php'); exit;
        }

        if ($action === 'purge' && $table && $id) {
            try {
                purgeFromTrash($table, $id);
                setFlash('success', ucfirst($table) . ' permanently deleted.');
            } catch (PDOException $e) {
                // FK constraint: e.g. a user still has scores referencing it
                error_log('Trash purge failed: ' . $e->getMessage());
                setFlash('danger', 'Cannot permanently delete: this item still has related records (e.g. scores). Remove those first.');
            }
            header('Location: trash.php'); exit;
        }

        if ($action === 'empty') {
            $r = emptyTrash();
            setFlash('success', sprintf(
                'Trash emptied: %d user(s) and %d score(s) permanently deleted.',
                $r['users'], $r['scores']
            ));
            header('Location: trash.php'); exit;
        }
    }
}

// Automatic cleanup: permanently purge trash items older than 30 days.
// Throttled to at most once per hour per admin session.
$autoPurged = autoPurgeTrash();
if (array_sum($autoPurged) > 0) {
    setFlash('info', sprintf(
        'Auto-cleanup: %d user(s) and %d score(s) older than %d days were permanently removed from the Trash.',
        $autoPurged['users'], $autoPurged['scores'], TRASH_RETENTION_DAYS
    ));
    header('Location: trash.php'); exit;
}

$counts = trashCounts();
$totalTrash = $counts['users'] + $counts['scores'];

$trashedUsers = $pdo->query(
    "SELECT id, username, email, role, created_at, deleted_at
     FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC"
)->fetchAll();

$trashedScores = $pdo->query("
    SELECT s.id, s.score, s.level, s.created_at, s.deleted_at,
           g.name AS game_name
    FROM scores s
    JOIN games g ON s.game_id = g.id
    WHERE s.deleted_at IS NOT NULL
    ORDER BY s.deleted_at DESC
    LIMIT 100
")->fetchAll();

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>🗑 Recycle Bin</h2>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="games.php">Games</a>
            <a href="scores.php">Scores</a>
            <a href="trash.php" class="active">Trash</a>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="alert alert-info">
        Items in the Trash are <strong>not deleted yet</strong> — restore them anytime.
        Items older than <strong><?= TRASH_RETENTION_DAYS ?> days</strong> are removed automatically.
        <strong>Delete forever</strong> (and its cascade to related scores) is permanent and cannot be undone!
        (<?= $totalTrash ?> item<?= $totalTrash == 1 ? '' : 's' ?> in Trash)
    </div>

    <?php if ($totalTrash > 0): ?>
    <div class="trash-actions">
        <form method="POST" onsubmit="return confirm('EMPTY THE ENTIRE TRASH?<?= "\\n" ?>All <?= $totalTrash ?> item(s) — users and scores — will be PERMANENTLY deleted. This CANNOT be undone!')">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="empty">
            <button type="submit" class="btn btn-danger">🗑 Empty Trash (<?= $totalTrash ?>)</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Trashed Users -->
    <div class="admin-card">
        <h3>👤 Deleted Users (<?= count($trashedUsers) ?>)</h3>
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Deleted At</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($trashedUsers as $u): ?>
                <tr class="row-deleted">
                    <td><?= $u['id'] ?></td>
                    <td><?= sanitize($u['username']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><?= date('M d Y', strtotime($u['created_at'])) ?></td>
                    <td><?= date('M d Y H:i', strtotime($u['deleted_at'])) ?></td>
                    <td class="action-cell">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="table" value="users">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-primary">↩ Restore</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="purge">
                            <input type="hidden" name="table" value="users">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('PERMANENTLY delete user <?= sanitize($u['username']) ?> AND all of their scores? This CANNOT be undone!')">
                                ✕ Delete Forever
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($trashedUsers)): ?>
                <tr><td colspan="7">Trash is empty for users.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Trashed Scores -->
    <div class="admin-card">
        <h3>🏆 Deleted Scores (<?= $counts['scores'] ?>)</h3>
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Game</th><th>Score</th><th>Level</th><th>Date</th><th>Deleted At</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($trashedScores as $s): ?>
                <tr class="row-deleted">
                    <td><?= $s['id'] ?></td>
                    <td><?= sanitize($s['game_name']) ?></td>
                    <td><?= number_format($s['score']) ?></td>
                    <td><?= $s['level'] ?? '-' ?></td>
                    <td><?= date('M d Y H:i', strtotime($s['created_at'])) ?></td>
                    <td><?= date('M d Y H:i', strtotime($s['deleted_at'])) ?></td>
                    <td class="action-cell">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="table" value="scores">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-primary">↩ Restore</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="purge">
                            <input type="hidden" name="table" value="scores">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('PERMANENTLY delete this score? This CANNOT be undone!')">
                                ✕ Delete Forever
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($trashedScores)): ?>
                <tr><td colspan="7">Trash is empty for scores.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($counts['scores'] > 100): ?>
        <p><em>Showing the 100 most recent. Total in Trash: <?= $counts['scores'] ?>.</em></p>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Manage Games';
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

        if ($action === 'toggle') {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE games SET is_active = 1 - is_active WHERE id=? AND deleted_at IS NULL")->execute([$id]);
            setFlash('success', 'Game status toggled.');
            header('Location: games.php'); exit;
        }
        // NOTE: no delete action here on purpose — games are the app's built-in
        // content and their PHP files live in /games regardless of the DB row.
        // Deleting a DB row would leave a broken link. Use Hide/Show instead.
        // The Trash page still supports restoring/purging games if any ever
        // end up there (e.g. legacy data).
    }
}

$games = $pdo->query("SELECT g.*, (SELECT COUNT(*) FROM scores s WHERE s.game_id=g.id AND s.deleted_at IS NULL) AS total_scores FROM games g WHERE g.deleted_at IS NULL ORDER BY sort_order ASC")->fetchAll();

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>🎮 Manage Games</h2>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="games.php" class="active">Games</a>
            <a href="scores.php">Scores</a>
            <a href="trash.php">🗑 Trash</a>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Games Table -->
    <div class="admin-card">
        <h3>All Games (<?= count($games) ?>)</h3>
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Slug</th><th>Category</th><th>Status</th><th>Scores</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($games as $g): ?>
                <tr class="<?= !$g['is_active'] ? 'row-inactive' : '' ?>">
                    <td><?= $g['sort_order'] ?></td>
                    <td>
                        <a href="../games/<?= sanitize($g['slug']) ?>.php" target="_blank"><?= sanitize($g['name']) ?></a>
                    </td>
                    <td><code><?= sanitize($g['slug']) ?></code></td>
                    <td><span class="badge badge-<?= sanitize($g['category']) ?>"><?= ucfirst(sanitize($g['category'])) ?></span></td>
                    <td><span class="badge badge-<?= $g['is_active'] ? 'active' : 'inactive' ?>"><?= $g['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                    <td><?= number_format($g['total_scores']) ?></td>
                    <td class="action-cell">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-warning">
                                <?= $g['is_active'] ? 'Hide' : 'Show' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
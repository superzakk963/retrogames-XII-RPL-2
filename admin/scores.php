<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/trash.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Manage Scores';
$cssPath = '../assets/style.css';
$jsPath  = '../assets/main.js';
$homePath = '../';
$pdo = getDB();
$errors = [];

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $n = softDelete('scores', $id);
            setFlash('success', $n ? 'Score moved to Trash.' : 'Score not found.');
            header('Location: scores.php'); exit;
        }

        if ($action === 'delete_user_game') {
            $uid = (int)$_POST['user_id'];
            $gid = (int)$_POST['game_id'];
            $n = softDeleteWhere('scores', ['user_id' => $uid, 'game_id' => $gid]);
            setFlash('success', $n . ' score(s) for that user/game moved to Trash.');
            header('Location: scores.php'); exit;
        }

        if ($action === 'delete_all_game') {
            $gid = (int)$_POST['game_id'];
            $n = softDeleteWhere('scores', ['game_id' => $gid]);
            setFlash('success', $n . ' score(s) for that game moved to Trash.');
            header('Location: scores.php'); exit;
        }
    }
}

// Filters
$gameFilter = (int)($_GET['game_id'] ?? 0);
$userSearch = trim($_GET['user'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 30;
$offset     = ($page - 1) * $perPage;

$where  = ['s.deleted_at IS NULL', 'u.deleted_at IS NULL', 'g.deleted_at IS NULL']; // exclude recycle-bin rows
$params = [];
if ($gameFilter) { $where[] = 's.game_id = ?'; $params[] = $gameFilter; }
if ($userSearch) { $where[] = 'u.username LIKE ?'; $params[] = "%$userSearch%"; }
$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM scores s JOIN users u ON s.user_id=u.id JOIN games g ON s.game_id=g.id WHERE $whereStr");
$total->execute($params);
$total = $total->fetchColumn();
$totalPages = ceil($total / $perPage);

$scores = $pdo->prepare("
    SELECT s.id, s.score, s.level, s.duration, s.created_at,
           u.id AS user_id, u.username,
           g.id AS game_id, g.name AS game_name
    FROM scores s
    JOIN users u ON s.user_id = u.id
    JOIN games g ON s.game_id = g.id
    WHERE $whereStr
    ORDER BY s.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$scores->execute($params);
$scores = $scores->fetchAll();

$games = $pdo->query("SELECT id, name FROM games WHERE deleted_at IS NULL ORDER BY sort_order")->fetchAll();

// Stats per game (trash-aware: count only live scores, list only live games)
$gameStats = $pdo->query("
    SELECT g.name, g.id,
           COUNT(s.id) AS total_scores,
           MAX(s.score) AS max_score,
           AVG(s.score) AS avg_score,
           COUNT(DISTINCT s.user_id) AS unique_players
    FROM games g
    LEFT JOIN scores s ON s.game_id = g.id AND s.deleted_at IS NULL
    WHERE g.deleted_at IS NULL
    GROUP BY g.id
    ORDER BY g.sort_order
")->fetchAll();

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>🏆 Manage Scores</h2>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="games.php">Games</a>
            <a href="scores.php" class="active">Scores</a>
            <a href="trash.php">🗑 Trash</a>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Stats per game -->
    <div class="admin-card">
        <h3>Score Statistics by Game</h3>
        <table class="data-table">
            <thead>
                <tr><th>Game</th><th>Total Played</th><th>Unique Players</th><th>Highest Score</th><th>Avg Score</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($gameStats as $gs): ?>
                <tr>
                    <td><?= sanitize($gs['name']) ?></td>
                    <td><?= number_format($gs['total_scores']) ?></td>
                    <td><?= number_format($gs['unique_players']) ?></td>
                    <td><?= $gs['max_score'] ? number_format($gs['max_score']) : '-' ?></td>
                    <td><?= $gs['avg_score'] ? number_format($gs['avg_score']) : '-' ?></td>
                    <td>
                        <a href="scores.php?game_id=<?= $gs['id'] ?>" class="btn btn-xs btn-primary">Filter</a>
                        <?php if ($gs['total_scores'] > 0): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete_all_game">
                            <input type="hidden" name="game_id" value="<?= $gs['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete ALL <?= number_format($gs['total_scores']) ?> scores for <?= sanitize($gs['name']) ?>?')">
                                Clear All
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Filter Form -->
    <div class="admin-card">
        <form method="GET" action="scores.php" class="filter-form">
            <input type="text" name="user" value="<?= sanitize($userSearch) ?>" placeholder="Search by username...">
            <select name="game_id">
                <option value="0">All Games</option>
                <?php foreach ($games as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $gameFilter == $g['id'] ? 'selected' : '' ?>><?= sanitize($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="scores.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Scores Table -->
    <div class="admin-card">
        <h3>Score Records (<?= number_format($total) ?> total)</h3>
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Player</th><th>Game</th><th>Score</th><th>Level</th><th>Duration</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($scores as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td>
                        <a href="users.php?search=<?= urlencode($s['username']) ?>"><?= sanitize($s['username']) ?></a>
                    </td>
                    <td><?= sanitize($s['game_name']) ?></td>
                    <td><?= number_format($s['score']) ?></td>
                    <td><?= $s['level'] ?? '-' ?></td>
                    <td><?= $s['duration'] ? gmdate('i:s', $s['duration']) : '-' ?></td>
                    <td><?= date('M d Y H:i', strtotime($s['created_at'])) ?></td>
                    <td class="action-cell">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Delete this score record? It will be moved to the Trash and can be restored.')">Del</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($scores)): ?>
                <tr><td colspan="8">No scores found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= min($totalPages, 20); $i++): ?>
                <a href="scores.php?page=<?= $i ?>&game_id=<?= $gameFilter ?>&user=<?= urlencode($userSearch) ?>"
                   class="btn btn-xs <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($totalPages > 20): ?>
                <span>... <?= $totalPages ?> pages</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

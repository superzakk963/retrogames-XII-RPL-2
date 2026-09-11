<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/trash.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Admin Dashboard';
$cssPath = '../assets/style.css';
$jsPath  = '../assets/main.js';
$homePath = '../';
$pdo = getDB();

// Stats (exclude recycle-bin rows)
$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND deleted_at IS NULL")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND deleted_at IS NULL")->fetchColumn();
$totalScores = $pdo->query("SELECT COUNT(*) FROM scores WHERE deleted_at IS NULL")->fetchColumn();
$totalGames  = $pdo->query("SELECT COUNT(*) FROM games WHERE is_active=1 AND deleted_at IS NULL")->fetchColumn();

// Recycle bin counter for the nav badge.
// Also runs the throttled auto-purge (items older than 30 days) at most once
// per hour, then re-counts so the badge stays accurate.
autoPurgeTrash();
$trashTotal = array_sum(trashCounts());

$recentUsers = $pdo->query("
    SELECT id, username, email, role, is_active, created_at
    FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5
")->fetchAll();

$recentScores = $pdo->query("
    SELECT s.score, s.created_at, u.username, g.name AS game_name
    FROM scores s
    JOIN users u ON s.user_id = u.id
    JOIN games g ON s.game_id = g.id
    WHERE s.deleted_at IS NULL AND u.deleted_at IS NULL AND g.deleted_at IS NULL
    ORDER BY s.created_at DESC LIMIT 10
")->fetchAll();

$topPerGame = $pdo->query("
    SELECT l.game_name, l.username, l.best_score
    FROM leaderboard l
    ORDER BY l.game_id, l.best_score DESC
")->fetchAll();

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>⚙️ Admin Dashboard</h2>
        <nav class="admin-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="games.php">Games</a>
            <a href="scores.php">Scores</a>
            <a href="trash.php">🗑 Trash (<?= $trashTotal ?>)</a>
        </nav>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= number_format($totalUsers) ?></span>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($totalAdmins) ?></span>
            <span class="stat-label">Admins</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($totalGames) ?></span>
            <span class="stat-label">Active Games</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= number_format($totalScores) ?></span>
            <span class="stat-label">TOTAL GAMES PLAYED BY ADMIN/USER</span>
        </div>
    </div>

    <!-- Recent Registrations & Recent Scores - two column layout with better responsiveness -->
    <div class="admin-grid-2col">
        <!-- Recent Registrations -->
        <div class="admin-card">
            <h3>Recent Registrations</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= sanitize($u['username']) ?></td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                            <td><span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Banned' ?></span></td>
                            <td><?= date('M d', strtotime($u['created_at'])) ?></td>
                            <td><a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-xs">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="users.php" class="btn btn-sm">Manage All Users →</a>
        </div>

        <!-- Recent Scores -->
        <div class="admin-card">
            <h3>Recent Scores</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>User</th><th>Game</th><th>Score</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentScores as $s): ?>
                        <tr>
                            <td><?= sanitize($s['username']) ?></td>
                            <td><?= sanitize($s['game_name']) ?></td>
                            <td><?= number_format($s['score']) ?></td>
                            <td><?= date('M d H:i', strtotime($s['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="scores.php" class="btn btn-sm">View All Scores →</a>
        </div>
    </div>

    <!-- Current Leaderboard -->
    <div class="admin-card">
        <h3>Current Leaderboard (Top per Game)</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Game</th><th>Top Player</th><th>Best Score</th></tr></thead>
                <tbody>
                    <?php
                    $shown = [];
                    foreach ($topPerGame as $t):
                        if (in_array($t['game_name'], $shown)) continue;
                        $shown[] = $t['game_name'];
                    ?>
                    <tr>
                        <td><?= sanitize($t['game_name']) ?></td>
                        <td><?= sanitize($t['username']) ?></td>
                        <td><?= number_format($t['best_score']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
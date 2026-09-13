<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
startSession();
requireLogin('login.php');

$pageTitle = 'My Profile';
$pdo = getDB();
$errors = [];
$success = '';
$currentUser = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'];

        if ($action === 'update_profile') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required.';
            } else {
                // Check email not taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already in use.';
                } else {
                    $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $_SESSION['user_id']]);
                    $success = 'Profile updated successfully.';
                    $currentUser['email'] = $email;
                }
            }
        }

        if ($action === 'change_password') {
            $currentPw  = $_POST['current_password'] ?? '';
            $newPw      = $_POST['new_password'] ?? '';
            $confirmPw  = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPw, $currentUser['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPw) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPw !== $confirmPw) {
                $errors[] = 'New passwords do not match.';
            } else {
                $hash = password_hash($newPw, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
                $success = 'Password changed successfully.';
            }
        }
    }
}

// Fetch user scores grouped by game
$playtimeStmt = $pdo->prepare("
    SELECT g.name AS game_name, g.slug,
           SUM(gs.duration) AS playtime
    FROM game_sessions gs
    JOIN games g ON gs.game_id = g.id
    WHERE gs.user_id = ? AND gs.duration IS NOT NULL AND g.deleted_at IS NULL
    GROUP BY gs.game_id
");
$playtimeStmt->execute([$_SESSION['user_id']]);
$playtimeByGame = array_column($playtimeStmt->fetchAll(), 'playtime', 'slug');

/** Format seconds as e.g. "2h 05m", "45m 30s", "42s" */
function formatPlaytime(?float $seconds): string {
    if ($seconds === null || $seconds <= 0) return '0s';
    $s = (int)$seconds;
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    $sec = $s % 60;
    if ($h > 0) return $h . 'h ' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . 'm';
    if ($m > 0) return $m . 'm ' . str_pad((string)$sec, 2, '0', STR_PAD_LEFT) . 's';
    return $sec . 's';
}

// Fetch user scores grouped by game
$scores = $pdo->prepare("
    SELECT g.name AS game_name, g.slug,
           MAX(s.score) AS best_score,
           COUNT(s.id) AS total_plays,
           AVG(s.score) AS avg_score,
           SUM(s.duration) AS total_time
    FROM scores s
    JOIN games g ON s.game_id = g.id
    WHERE s.user_id = ? AND s.deleted_at IS NULL AND g.deleted_at IS NULL
    GROUP BY s.game_id
    ORDER BY best_score DESC
");
$scores->execute([$_SESSION['user_id']]);
$userScores = $scores->fetchAll();

// Fetch recent scores
$recentScores = $pdo->prepare("
    SELECT s.score, s.level, s.duration, s.created_at, g.name AS game_name, g.slug
    FROM scores s
    JOIN games g ON s.game_id = g.id
    WHERE s.user_id = ? AND s.deleted_at IS NULL AND g.deleted_at IS NULL
    ORDER BY s.created_at DESC
    LIMIT 20
");
$recentScores->execute([$_SESSION['user_id']]);
$recentScores = $recentScores->fetchAll();

// Fetch rank per game
$ranks = $pdo->prepare("
    SELECT lb.game_slug,
           (SELECT COUNT(*) + 1 FROM leaderboard lb2
            WHERE lb2.game_id = lb.game_id AND lb2.best_score > lb.best_score) AS rank,
           lb.best_score, lb.game_name
    FROM leaderboard lb
    WHERE lb.user_id = ?
");
$ranks->execute([$_SESSION['user_id']]);
$userRanks  = $ranks->fetchAll(PDO::FETCH_ASSOC);
$rankByGame = array_column($userRanks, null, 'game_slug');

include 'includes/header.php';
?>

<section id="profile-section">
    <div class="profile-header">
        <div class="profile-avatar">
            <span class="avatar-placeholder">
                <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
            </span>
        </div>
        <div class="profile-info">
            <h2><?= sanitize($currentUser['username']) ?></h2>
            <p><?= sanitize($currentUser['email']) ?></p>
            <p>Member since: <?= date('F Y', strtotime($currentUser['created_at'])) ?></p>
            <?php if ($currentUser['last_login']): ?>
                <p>Last login: <?= date('M d, Y H:i', strtotime($currentUser['last_login'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Stats Summary -->
    <section class="profile-stats">
        <h3>My Stats</h3>
        <?php
        $totalPlays = array_sum(array_column($userScores, 'total_plays'));
        $bestOverall = !empty($userScores) ? max(array_column($userScores, 'best_score')) : 0;
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value"><?= number_format($totalPlays) ?></span>
                <span class="stat-label">Total Games Played</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= formatPlaytime(array_sum($playtimeByGame)) ?></span>
                <span class="stat-label">Total Playtime</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= count($userScores) ?></span>
                <span class="stat-label">Games Tried</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= number_format($bestOverall) ?></span>
                <span class="stat-label">Best Score (All)</span>
            </div>
        </div>
    </section>

    <!-- Per-game scores -->
    <section class="profile-game-scores">
        <h3>Best Scores by Game</h3>
        <?php if (empty($userScores)): ?>
            <p>No scores yet. <a href="index.php">Start playing!</a></p>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Best Score</th>
                    <th>Avg Score</th>
                    <th>Plays</th>
                    <th>Playtime</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userScores as $s): ?>
                <tr>
                    <td><a href="games/<?= sanitize($s['slug']) ?>.php"><?= sanitize($s['game_name']) ?></a></td>
                    <td><?= number_format($s['best_score']) ?></td>
                    <td><?= number_format($s['avg_score']) ?></td>
                    <td><?= number_format($s['total_plays']) ?></td>
                    <td><?= formatPlaytime($playtimeByGame[$s['slug']] ?? null) ?></td>
                    <td>
                        <?php if (isset($rankByGame[$s['slug']])): ?>
                            #<?= $rankByGame[$s['slug']]['rank'] ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- Recent scores -->
    <section class="profile-recent-scores">
        <h3>Recent Scores</h3>
        <?php if (empty($recentScores)): ?>
            <p>No recent scores.</p>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th>Game</th><th>Score</th><th>Level</th><th>Duration</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentScores as $s): ?>
                <tr>
                    <td><a href="games/<?= sanitize($s['slug']) ?>.php"><?= sanitize($s['game_name']) ?></a></td>
                    <td><?= number_format($s['score']) ?></td>
                    <td><?= $s['level'] ?? '-' ?></td>
                    <td><?= $s['duration'] ? gmdate('i:s', $s['duration']) : '-' ?></td>
                    <td><?= date('M d, Y H:i', strtotime($s['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- Edit profile + change password (2 columns on desktop) -->
    <div class="profile-forms-row">
    <section class="profile-edit">
        <h3>Edit Profile</h3>
        <form method="POST" action="profile.php" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= sanitize($currentUser['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </section>

    <!-- Change password -->
    <section class="profile-password">
        <h3>Change Password</h3>
        <form method="POST" action="profile.php" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-warning">Change Password</button>
        </form>
    </section>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

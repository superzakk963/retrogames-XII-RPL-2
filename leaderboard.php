<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
startSession();

// ── Handle AJAX score submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_slug'])) {
    header('Content-Type: application/json');

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'msg' => 'not_logged_in']);
        exit;
    }

    $pdo      = getDB();
    $gameSlug = $_POST['game_slug'] ?? '';
    $score    = (int)($_POST['score'] ?? 0);
    $level    = (int)($_POST['level'] ?? 1);

    if ($gameSlug === '' || $score <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'invalid_data']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM games WHERE slug = ? AND is_active = 1");
    $stmt->execute([$gameSlug]);
    $game = $stmt->fetch();

    if (!$game) {
        echo json_encode(['ok' => false, 'msg' => 'game_not_found']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO scores (user_id, game_id, score, level)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $game['id'], $score, $level]);

    echo json_encode(['ok' => true]);
    exit;
}
// ── End AJAX handler ──────────────────────────────────────────────

$pageTitle = 'Leaderboard';
$pdo = getDB();

$selectedGame = $_GET['game'] ?? 'all';
$games = $pdo->query("SELECT * FROM games WHERE is_active = 1 ORDER BY sort_order")->fetchAll();

if ($selectedGame !== 'all') {
    $game = $pdo->prepare("SELECT * FROM games WHERE slug = ?");
    $game->execute([$selectedGame]);
    $game = $game->fetch();
    if (!$game) $selectedGame = 'all';
}

if ($selectedGame === 'all') {
    // Compatible with MySQL 5.7 and 8+
    $entries = $pdo->query("
        SELECT l.game_id, l.user_id, l.best_score, l.username, l.game_name, l.game_slug,
               @rn := IF(@prev_game = l.game_id, @rn + 1, 1) AS game_rank,
               @prev_game := l.game_id
        FROM leaderboard l,
             (SELECT @rn := 0, @prev_game := NULL) AS vars
        ORDER BY l.game_id, l.best_score DESC
    ")->fetchAll();
    // Strip the helper column
    foreach ($entries as &$e) {
        unset($e['@prev_game := l.game_id']);
    }
    unset($e);
    $grouped = [];
    foreach ($entries as $e) {
        $grouped[$e['game_slug']][] = $e;
    }
} else {
    $stmt = $pdo->prepare("
        SELECT l.game_id, l.user_id, l.best_score, l.username, l.game_name, l.game_slug,
               @rn := @rn + 1 AS game_rank
        FROM leaderboard l,
             (SELECT @rn := 0) AS vars
        WHERE l.game_slug = ?
        ORDER BY l.best_score DESC
        LIMIT 50
    ");
    $stmt->execute([$selectedGame]);
    $grouped = [$selectedGame => $stmt->fetchAll()];
}

include 'includes/header.php';
?>

<section id="leaderboard-section">
    <h2>🏆 Leaderboard</h2>

    <nav class="game-filter-tabs">
        <a href="leaderboard.php?game=all"
           class="tab <?= $selectedGame === 'all' ? 'active' : '' ?>">All Games</a>
        <?php foreach ($games as $g): ?>
        <a href="leaderboard.php?game=<?= sanitize($g['slug']) ?>"
           class="tab <?= $selectedGame === $g['slug'] ? 'active' : '' ?>">
           <?= sanitize($g['name']) ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($grouped as $gameSlug => $entries): ?>
    <div class="leaderboard-game-section">
        <?php if ($selectedGame === 'all' && !empty($entries)): ?>
            <h3><?= sanitize($entries[0]['game_name']) ?></h3>
        <?php endif; ?>

        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Best Score</th>
                    <?php if ($selectedGame === 'all'): ?>
                    <th>Game</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($entries, 0, 10) as $i => $entry): ?>
                <tr class="<?= $entry['user_id'] == ($_SESSION['user_id'] ?? 0) ? 'my-row' : '' ?>
                           <?= $entry['game_rank'] == 1 ? 'rank-gold' : ($entry['game_rank'] == 2 ? 'rank-silver' : ($entry['game_rank'] == 3 ? 'rank-bronze' : '')) ?>">
                    <td>
                        <?php
                        echo '#'.$entry['game_rank'];
                        ?>
                    </td>
                    <td>
                        <?= sanitize($entry['username']) ?>
                        <?= $entry['user_id'] == ($_SESSION['user_id'] ?? 0) ? '<span class="you-badge">You</span>' : '' ?>
                    </td>
                    <td><?= number_format($entry['best_score']) ?></td>
                    <?php if ($selectedGame === 'all'): ?>
                    <td><?= sanitize($entry['game_name']) ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($entries)): ?>
                <tr><td colspan="4">No scores yet for this game.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($selectedGame === 'all'): ?>
            <a href="leaderboard.php?game=<?= sanitize($gameSlug) ?>" class="btn btn-sm">View All →</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($grouped)): ?>
        <p>No scores recorded yet. <a href="index.php">Start playing!</a></p>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>

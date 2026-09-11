<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
startSession();

$pageTitle = 'Home';
$pdo = getDB();

// Fetch active games
$games = $pdo->query("SELECT * FROM games WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Games that currently have a preview video (assets/videos/<slug>.mp4)
$previewVideos = [
    'snake'      => 'snake.mp4',
    'pacman'     => 'pacman.mp4',
    'car'        => 'car.mp4',
    'flappybird' => 'flappybird.mp4',
    'pingpong'   => 'pingpong.mp4',
    'tetris'     => 'tetris.mp4',
];

// Fetch top scores per game
$topScores = $pdo->query("
    SELECT l.*, l.best_score, l.username, l.game_name, l.game_slug
    FROM leaderboard l
    ORDER BY l.game_id, l.best_score DESC
")->fetchAll();

$topByGame = [];
foreach ($topScores as $s) {
    if (!isset($topByGame[$s['game_slug']])) {
        $topByGame[$s['game_slug']] = $s;
    }
}

include 'includes/header.php';
?>

<section id="hero">
    <h1>Welcome to RetroGames</h1>
    <p>Play classic arcade games, compete on the leaderboard, and relive the golden age of gaming!</p>
    <?php if (!isLoggedIn()): ?>
        <div class="hero-actions">
            <a href="register.php" class="btn btn-primary">Get Started</a>
            <a href="login.php" class="btn btn-secondary">Login</a>
        </div>
    <?php else: ?>
        <p class="hero-welcome">Welcome back, <strong><?= sanitize($_SESSION['username']) ?></strong>! Ready to play?</p>
    <?php endif; ?>
</section>

<section id="game-list">
    <h2>Games</h2>
    <div class="games-grid">
        <?php foreach ($games as $game): ?>
        <div class="game-card" data-category="<?= sanitize($game['category']) ?>">
            <div class="game-card-thumb">
                <?php if ($game['thumbnail']): ?>
                    <img src="assets/thumbnails/<?= sanitize($game['thumbnail']) ?>" alt="<?= sanitize($game['name']) ?>">
                <?php else: ?>
                    <div class="game-thumb-placeholder">
                        <?php
                        $icons = [
                            'snake' => '🐍', 'pacman' => '🥠', 'car' => '🚗',
                            'flappybird' => '🐦', 'pingpong' => '🏓', 'tetris' => '🟦'
                        ];
                        echo $icons[$game['slug']] ?? '🎮';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="game-card-body">
                <h3><?= sanitize($game['name']) ?></h3>
                <p><?= sanitize($game['description']) ?></p>
                <div class="game-card-meta">
                    <span class="badge badge-<?= sanitize($game['category']) ?>"><?= ucfirst(sanitize($game['category'])) ?></span>
                </div>
                <div class="game-card-actions">
                    <?php if (isset($previewVideos[$game['slug']])): ?>
                    <button type="button" class="btn btn-preview"
                        data-preview-video="assets/videos/<?= sanitize($previewVideos[$game['slug']]) ?>"
                        data-preview-poster="assets/videos/posters/<?= sanitize($game['slug']) ?>.jpg"
                        data-preview-title="<?= sanitize($game['name']) ?>"
                        data-preview-desc="<?= sanitize($game['description']) ?>"
                        data-preview-category="<?= sanitize($game['category']) ?>"
                        data-preview-play="games/<?= sanitize($game['slug']) ?>.php">
                        👁 Preview
                    </button>
                    <?php endif; ?>
                    <a href="games/<?= sanitize($game['slug']) ?>.php" class="btn btn-play">▶ Play Now</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="mini-leaderboard">
    <h2>Top Scores</h2>
    <table class="leaderboard-table">
        <thead>
            <tr><th>Game</th><th>Player</th><th>Score</th></tr>
        </thead>
        <tbody>
            <?php
            $topAll = $pdo->query("
                SELECT l.game_name, l.username, l.best_score
                FROM leaderboard l
                ORDER BY l.best_score DESC
                LIMIT 10
            ")->fetchAll();
            foreach ($topAll as $row): ?>
            <tr>
                <td><?= sanitize($row['game_name']) ?></td>
                <td><?= sanitize($row['username']) ?></td>
                <td><?= number_format($row['best_score']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topAll)): ?>
            <tr><td colspan="3">No scores yet. Be the first to play!</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="leaderboard.php" class="btn btn-secondary">View Full Leaderboard</a>
</section>

<div id="preview-modal" class="preview-modal" aria-hidden="true" style="display:none;">
    <div class="preview-modal-backdrop" data-preview-close></div>
    <div class="preview-modal-content">
        <button type="button" class="preview-modal-close" data-preview-close aria-label="Close">&times;</button>

        <div class="preview-header">
            <h3 id="preview-modal-title" class="preview-modal-title"></h3>
            <span id="preview-modal-badge" class="badge"></span>
        </div>
        <p id="preview-modal-desc" class="preview-modal-desc"></p>

        <div class="preview-screen">
            <div class="preview-screen-inner">
                <video id="preview-modal-video" playsinline preload="metadata"></video>
                <button type="button" id="preview-play-btn" class="preview-play-btn" aria-label="Play preview"></button>
                <button type="button" id="preview-mute-btn" class="preview-mute-btn" aria-label="Mute/unmute" aria-pressed="false"></button>
            </div>
        </div>

        <div class="preview-modal-actions">
            <a id="preview-modal-playnow" href="#" class="btn btn-play">▶ Play Now</a>
            <button type="button" class="btn btn-secondary" data-preview-close>Close</button>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
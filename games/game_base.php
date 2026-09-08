<?php
// games/game_base.php - include this at top of each game file
require_once '../includes/auth.php';
require_once '../includes/db.php';
startSession();

if (!isset($gameSlug)) die('gameSlug not set');

$pdo = getDB();
$gameData = $pdo->prepare("SELECT * FROM games WHERE slug = ? AND is_active = 1");
$gameData->execute([$gameSlug]);
$gameData = $gameData->fetch();
if (!$gameData) {
    header('Location: ../index.php');
    exit;
}

// Get top 5 scores for this game
$topScores = $pdo->prepare("
    SELECT l.username, l.best_score
    FROM leaderboard l
    WHERE l.game_slug = ?
    ORDER BY l.best_score DESC
    LIMIT 5
");
$topScores->execute([$gameSlug]);
$topScores = $topScores->fetchAll();

// Get current user's best score
$myBest = null;
if (isLoggedIn()) {
    $myBestStmt = $pdo->prepare("
        SELECT MAX(s.score) AS best
        FROM scores s
        JOIN games g ON s.game_id = g.id
        WHERE s.user_id = ? AND g.slug = ?
    ");
    $myBestStmt->execute([$_SESSION['user_id'], $gameSlug]);
    $myBest = $myBestStmt->fetchColumn();
}

$pageTitle = $gameData['name'];
$cssPath = '../assets/style.css';
$jsPath  = '../assets/main.js';
$homePath = '../';
?>
<?php include '../includes/header.php'; ?>

<section class="game-section">
    <div class="game-header">
        <h2><?= sanitize($gameData['name']) ?></h2>
        <p class="game-description"><?= sanitize($gameData['description']) ?></p>
    </div>

    <div class="game-layout">
        <div class="game-main">
            <!-- GAME CANVAS AREA - defined in each game file -->

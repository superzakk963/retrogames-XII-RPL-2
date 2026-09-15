<?php
// api/save_score.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
startSession();

// Always output JSON; send header before any output
header('Content-Type: application/json; charset=utf-8');

// Disable output buffering accidents
if (ob_get_level()) ob_end_clean();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw   = file_get_contents('php://input');
$input = $raw ? json_decode($raw, true) : null;
if (!is_array($input)) {
    $input = $_POST;
}

$gameSlug = trim($input['game'] ?? '');
$score    = filter_var($input['score'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999999]]);
$level    = isset($input['level'])    ? max(0, (int)$input['level'])    : null;
// Durasi ronde (detik). Di-cap 24 jam agar request nakal tidak merusak total.
$duration = isset($input['duration']) ? min(86400, max(0, (int)$input['duration'])) : null;

// Validate score (filter_var returns false on failure)
if (empty($gameSlug) || $score === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$pdo = getDB();

$gameStmt = $pdo->prepare('SELECT id FROM games WHERE slug = ? AND is_active = 1 AND deleted_at IS NULL');
$gameStmt->execute([$gameSlug]);
$game = $gameStmt->fetch();

if (!$game) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Game not found']);
    exit;
}

$pdo->prepare('INSERT INTO scores (user_id, game_id, score, level, duration) VALUES (?, ?, ?, ?, ?)')
    ->execute([$_SESSION['user_id'], $game['id'], $score, $level, $duration]);

// Sinkronkan lifetime total (gabungan sessions + scores). Idempoten —
// hasilnya sama walau trigger MySQL juga aktif.
$pdo->prepare('
    UPDATE users u SET u.total_playtime = (
        SELECT COALESCE(SUM(gs.duration), 0) FROM game_sessions gs
        WHERE gs.user_id = u.id AND gs.duration IS NOT NULL
    ) + (
        SELECT COALESCE(SUM(s.duration), 0) FROM scores s
        WHERE s.user_id = u.id AND s.duration IS NOT NULL AND s.deleted_at IS NULL
    ) WHERE u.id = ?
')->execute([$_SESSION['user_id']]);

// Get user's best score for this game (abaikan skor di recycle bin)
$bestStmt = $pdo->prepare('SELECT MAX(score) AS best FROM scores WHERE user_id = ? AND game_id = ? AND deleted_at IS NULL');
$bestStmt->execute([$_SESSION['user_id'], $game['id']]);
$best = (int)$bestStmt->fetchColumn();

// Get leaderboard rank (how many users have a better best score)
$rankStmt = $pdo->prepare('SELECT COUNT(*) + 1 AS rank FROM leaderboard WHERE game_id = ? AND best_score > ?');
$rankStmt->execute([$game['id'], $best]);
$rank = (int)$rankStmt->fetchColumn();

echo json_encode([
    'success'    => true,
    'score'      => $score,
    'best_score' => $best,
    'rank'       => $rank,
    'message'    => 'Score saved!',
]);

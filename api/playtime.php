<?php
// api/playtime.php — playtime tracking (user activity)
// Records how long a logged-in user actually plays a game.
// Sends JSON; expects JSON (or form) POST.
//
// Actions:
//   start     → open a new game_sessions row, returns its id
//   heartbeat → accumulate seconds (paused time excluded by client),
//               capped by the server so a tampered client cannot
//               inflate playtime
//   end       → close the session and finalize total_playtime
require_once '../includes/auth.php';
require_once '../includes/db.php';
startSession();

header('Content-Type: application/json; charset=utf-8');
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

$action   = $input['action'] ?? '';
$gameSlug = trim($input['game'] ?? '');
// Cap a single heartbeat at 120s (client heartbeats every 30s).
// The server is the source of truth: a tampered client cannot
// inflate playtime beyond this rate, and the caller's wall-clock
// is checked against session start below.
$seconds  = max(0, min(120, (int)($input['seconds'] ?? 0)));
$sessionId = (int)($input['session_id'] ?? 0);

if ($action === '' || $gameSlug === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing action or game']);
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
$gameId = (int)$game['id'];
$userId = (int)$_SESSION['user_id'];

// A session belongs to this user + game, and is still open.
// The started_at guard makes the max accumulatable playtime
// roughly (now - started_at), even if heartbeats are forged.
function fetchOpenSession(PDO $pdo, int $sessionId, int $userId, int $gameId): array {
    $stmt = $pdo->prepare(
        'SELECT id, started_at, COALESCE(duration, 0) AS duration
           FROM game_sessions
          WHERE id = ? AND user_id = ? AND game_id = ? AND ended_at IS NULL'
    );
    $stmt->execute([$sessionId, $userId, $gameId]);
    return $stmt->fetch() ?: [];
}

switch ($action) {
    case 'start':
        // Close any stale open sessions of this user (e.g. tab closed
        // without an "end" call), so rows don't pile up forever.
        $pdo->prepare(
            "UPDATE game_sessions
                SET ended_at = NOW(),
                    duration = COALESCE(duration, 0)
              WHERE user_id = ? AND ended_at IS NULL"
        )->execute([$userId]);

        // Keep the lifetime counter in sync (covers the stale sessions
        // just closed and any sessions whose 'end' beacon was lost).
        $pdo->prepare(
            'UPDATE users
                SET total_playtime = (
                    SELECT COALESCE(SUM(duration), 0)
                      FROM game_sessions
                     WHERE user_id = ? AND duration IS NOT NULL
                )
              WHERE id = ?'
        )->execute([$userId, $userId]);

        $pdo->prepare('INSERT INTO game_sessions (user_id, game_id) VALUES (?, ?)')
            ->execute([$userId, $gameId]);
        $sessionId = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'session_id' => $sessionId]);
        break;

    case 'heartbeat':
        if ($sessionId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing session_id']);
            exit;
        }
        $session = fetchOpenSession($pdo, $sessionId, $userId, $gameId);
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'Session not found or closed']);
            exit;
        }

        // Cap: never allow more than elapsed wall-clock since start.
        $elapsed = max(0, time() - strtotime($session['started_at']));
        $newDur  = min($session['duration'] + $seconds, $elapsed);

        $pdo->prepare('UPDATE game_sessions SET duration = ? WHERE id = ?')
            ->execute([$newDur, $sessionId]);
        echo json_encode(['success' => true, 'session_id' => $sessionId, 'duration' => (int)$newDur]);
        break;

    case 'end':
        if ($sessionId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing session_id']);
            exit;
        }
        $session = fetchOpenSession($pdo, $sessionId, $userId, $gameId);
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'Session not found or closed']);
            exit;
        }

        $elapsed = max(0, time() - strtotime($session['started_at']));
        $newDur  = min($session['duration'] + $seconds, $elapsed);

        $pdo->prepare('UPDATE game_sessions SET ended_at = NOW(), duration = ? WHERE id = ?')
            ->execute([$newDur, $sessionId]);

        // Keep the user's lifetime counter in sync.
        $pdo->prepare(
            'UPDATE users
                SET total_playtime = (
                    SELECT COALESCE(SUM(duration), 0)
                      FROM game_sessions
                     WHERE user_id = ? AND duration IS NOT NULL
                )
              WHERE id = ?'
        )->execute([$userId, $userId]);

        echo json_encode(['success' => true, 'session_id' => $sessionId, 'duration' => (int)$newDur]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

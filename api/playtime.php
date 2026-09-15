<?php
// api/playtime.php — Playtime Tracker backend (matches assets/playtime.js).
//
// Kontrak dengan playtime.js:
//   POST {action:'start', game:'slug'}                          -> {success, session_id}
//   POST {action:'heartbeat', game, session_id, seconds}        -> {success, total}
//   POST {action:'end', game, session_id, seconds}              -> {success, total}
// Aksi lawas (backward compat): ping/update/stop + `duration` dipetakan
// ke heartbeat/end agar game lama tetap tercatat.
//
// Setiap heartbeat/end:
//   1. game_sessions.duration ditambah `seconds` (di-cap anti-cheat).
//   2. users.total_playtime di-recalc = SUM(game_sessions) + SUM(scores)
//      untuk user tsb (sumber "gabungan", idempoten — aman walau trigger
//      MySQL juga aktif, karena hasilnya sama).

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
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

$action = strtolower(trim($input['action'] ?? ''));
// Backward compat: ping/update -> heartbeat, stop -> end
if ($action === 'ping' || $action === 'update') $action = 'heartbeat';
if ($action === 'stop') $action = 'end';

$gameSlug  = trim($input['game'] ?? $input['game_slug'] ?? '');
$sessionId = (int)($input['session_id'] ?? 0);
// Frontend mengirim `seconds`; API lama memakai `duration`. Dukung keduanya.
$seconds   = (int)($input['seconds'] ?? $input['duration'] ?? 0);
if ($seconds < 0) $seconds = 0;
// Cap anti-cheat: satu request maksimal 5 menit. Sesi normal hanya
// mengirim 30 detik (heartbeat) atau sisa detik (end), jadi cap ini
// tidak mengganggu permainan jujur tapi menahan manipulasi request.
if ($seconds > 300) $seconds = 300;

if (!in_array($action, ['start', 'heartbeat', 'end'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

$pdo = getDB();

// Auto-migrasi: DB lama yang belum punya kolom duration/total_playtime
// langsung di-upgrade (idempoten) agar API tidak 500.
try {
    require_once __DIR__ . '/../includes/schema.php';
    if (function_exists('ensureSchema')) {
        ensureSchema($pdo);
    }
} catch (Throwable $e) {
    // Lanjut saja — query di bawah akan error_log bila skema tetap invalid.
}

// Resolve game_id dari slug (kecuali heartbeat/end yang bawa session_id valid,
// slug tetap divalidasi bila dikirim).
$gameId = null;
if ($gameSlug !== '') {
    $gs = $pdo->prepare('SELECT id FROM games WHERE slug = ? AND is_active = 1 AND deleted_at IS NULL');
    $gs->execute([$gameSlug]);
    $g = $gs->fetch();
    if ($g) {
        $gameId = (int)$g['id'];
    } elseif ($action === 'start') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Game not found']);
        exit;
    }
}

$userId = (int)$_SESSION['user_id'];

/** Recalc users.total_playtime = SUM(sessions) + SUM(scores) untuk 1 user. */
$recalcTotal = function () use ($pdo, $userId) {
    $pdo->prepare('
        UPDATE users u SET u.total_playtime = (
            SELECT COALESCE(SUM(gs.duration), 0) FROM game_sessions gs
            WHERE gs.user_id = u.id AND gs.duration IS NOT NULL
        ) + (
            SELECT COALESCE(SUM(s.duration), 0) FROM scores s
            WHERE s.user_id = u.id AND s.duration IS NOT NULL AND s.deleted_at IS NULL
        ) WHERE u.id = ?
    ')->execute([$userId]);
};

try {
    if ($action === 'start') {
        if ($gameId === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Game not found']);
            exit;
        }
        // Pakai ulang sesi terbuka yang sama (user refresh / double start
        // tidak boleh bikin 2 baris sesi).
        $open = $pdo->prepare('
            SELECT id FROM game_sessions
            WHERE user_id = ? AND game_id = ? AND ended_at IS NULL
            ORDER BY started_at DESC LIMIT 1
        ');
        $open->execute([$userId, $gameId]);
        $existing = $open->fetchColumn();
        if ($existing) {
            echo json_encode(['success' => true, 'session_id' => (int)$existing, 'reused' => true]);
            exit;
        }
        $ins = $pdo->prepare('
            INSERT INTO game_sessions (user_id, game_id, started_at, duration)
            VALUES (?, ?, NOW(), 0)
        ');
        $ins->execute([$userId, $gameId]);
        echo json_encode(['success' => true, 'session_id' => (int)$pdo->lastInsertId()]);
        exit;
    }

    // heartbeat / end: butuh session_id milik user yang masih terbuka.
    if ($sessionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing session_id']);
        exit;
    }
    $chk = $pdo->prepare('SELECT id, game_id, COALESCE(duration, 0) AS duration, ended_at
                          FROM game_sessions WHERE id = ? AND user_id = ?');
    $chk->execute([$sessionId, $userId]);
    $sess = $chk->fetch();
    if (!$sess) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    if ($sess['ended_at'] !== null) {
        // Frontend (playtime.js) memakai pesan ini untuk reset session.id = 0.
        echo json_encode(['success' => false, 'message' => 'Session closed']);
        exit;
    }

    if ($seconds > 0) {
        $pdo->prepare('UPDATE game_sessions SET duration = COALESCE(duration, 0) + ?
                       WHERE id = ? AND user_id = ? AND ended_at IS NULL')
            ->execute([$seconds, $sessionId, $userId]);
    }
    if ($action === 'end') {
        $pdo->prepare('UPDATE game_sessions SET ended_at = NOW()
                       WHERE id = ? AND user_id = ? AND ended_at IS NULL')
            ->execute([$sessionId, $userId]);
    }

    // Sinkronkan lifetime total (gabungan sessions + scores).
    $recalcTotal();

    $tot = $pdo->prepare('SELECT COALESCE(duration, 0) FROM game_sessions WHERE id = ?');
    $tot->execute([$sessionId]);
    echo json_encode([
        'success'    => true,
        'session_id' => $sessionId,
        'total'      => (int)$tot->fetchColumn(),
    ]);
    exit;
} catch (PDOException $e) {
    error_log('playtime API failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

<?php
require_once __DIR__ . '/includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$game = $data['game'] ?? '';

$db = getDB();

if ($action === 'start') {
    // Simpan session awal jika perlu
    echo json_encode(['status' => 'success', 'message' => 'Session started']);
    exit;
}

if ($action === 'ping' || $action === 'update' || $action === 'stop') {
    $duration = intval($data['duration'] ?? 10); // default tambah 10 detik jika tidak dikirim
    
    // Update atau buat session bermain baru
    $stmt = $db->prepare("INSERT INTO game_sessions (user_id, game_id, duration, created_at) VALUES (?, 1, ?, NOW())");
    $stmt->execute([$userId, $duration]);
    
    echo json_encode(['status' => 'success', 'updated_seconds' => $duration]);
    exit;
}

echo json_encode(['status' => 'ignored']);

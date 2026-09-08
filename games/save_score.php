<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

session_start(); // startSession() ada di auth.php, tapi amankan dengan session_start()

// Harus login
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$gameSlug = $_POST['game_slug'] ?? '';
$score    = (int)($_POST['score'] ?? 0);
$level    = (int)($_POST['level'] ?? 1);

if ($gameSlug === '' || $score <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$pdo = getDB();

// Cari game_id dari slug
$stmt = $pdo->prepare("SELECT id FROM games WHERE slug = ? AND is_active = 1");
$stmt->execute([$gameSlug]);
$game = $stmt->fetch();

if (!$game) {
    http_response_code(404);
    exit('Game not found');
}

// Simpan score (pastikan tabel scores punya kolom level, jika tidak hapus , level)
$stmt = $pdo->prepare("
    INSERT INTO scores (user_id, game_id, score, level)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$_SESSION['user_id'], $game['id'], $score, $level]);

http_response_code(200);
echo 'OK';
<?php
// Run this file once to set up the database
// Access: http://yourserver/retrogames/install.php

// Prevent re-running if already installed (basic guard)
if (file_exists(__DIR__ . '/includes/db.php')) {
    // Use the same constants from db.php
    require_once __DIR__ . '/includes/db.php';
} else {
    // Fallback: define constants here if db.php not present
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'retrogames');
    define('DB_PORT', '3306');
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
            `avatar` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `last_login` DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Games table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `games` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `instructions` TEXT,
            `category` VARCHAR(50) DEFAULT 'arcade',
            `thumbnail` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Scores table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `scores` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `game_id` INT UNSIGNED NOT NULL,
            `score` INT UNSIGNED NOT NULL DEFAULT 0,
            `level` INT UNSIGNED DEFAULT NULL,
            `duration` INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_game` (`user_id`, `game_id`),
            INDEX `idx_game_score` (`game_id`, `score` DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Leaderboard view (top score per user per game)
    $pdo->exec("
        CREATE OR REPLACE VIEW `leaderboard` AS
        SELECT s.game_id, s.user_id, MAX(s.score) AS best_score,
               u.username, g.name AS game_name, g.slug AS game_slug
        FROM scores s
        JOIN users u ON s.user_id = u.id
        JOIN games g ON s.game_id = g.id
        GROUP BY s.game_id, s.user_id;
    ");

    // Game sessions / activity log
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `game_sessions` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `game_id` INT UNSIGNED NOT NULL,
            `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ended_at` DATETIME DEFAULT NULL,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Announcements table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `announcements` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `content` TEXT NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert default admin user (password: admin123)
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $adminStmt = $pdo->prepare(
        "INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')"
    );
    $adminStmt->execute(['admin', 'admin@retrogames.com', $adminPassword]);

    // Insert default games
    $games = [
        ['snake',      'Snake',       'Classic snake game. Eat food, grow longer, avoid walls and yourself!', 'Arrow keys or WASD to move. Eat the food to grow. Avoid hitting walls or your tail.', 'arcade', 1],
        ['pacman',     'Pac-Man',     'Guide Pac-Man through the maze, eat all dots while avoiding ghosts!', 'Arrow keys to move. Eat all dots to win. Power pellets let you eat ghosts!', 'arcade', 2],
        ['car',        'Car Racing',  'Dodge incoming traffic and survive as long as possible at high speed!', 'Arrow keys or A/D to move left/right. Avoid other cars. Speed increases over time.', 'racing', 3],
        ['flappybird', 'Flappy Bird', 'Tap to flap through pipes. How far can you go?', 'Press Space or click to flap. Avoid the pipes. Each pipe passed = 1 point.', 'arcade', 4],
        ['pingpong',   'Ping Pong',   'Classic table tennis. Play against the computer or a friend!', 'Player 1: W/S keys. Player 2: Up/Down arrows. First to 7 wins!', 'sports', 5],
        ['tetris',     'Tetris',      'Arrange falling blocks to complete lines. Classic puzzle action!', 'Arrow keys to move/rotate. Down to drop faster. Space for hard drop.', 'puzzle', 6],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO `games` (`slug`, `name`, `description`, `instructions`, `category`, `sort_order`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($games as $g) {
        $stmt->execute($g);
    }

    echo "<h2>Installation Successful!</h2>";
    echo "<p>Database <strong>" . DB_NAME . "</strong> created with all tables.</p>";
    echo "<p>Default admin: <strong>admin / admin123</strong></p>";
    echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/index.php'>Go to Admin Panel</a></p>";
    echo "<p style='color:red'><strong>SECURITY: Delete this install.php file after setup!</strong></p>";

} catch (PDOException $e) {
    echo "<h2>Installation Failed</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

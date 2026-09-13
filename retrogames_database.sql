-- ============================================================
--  RetroGames — Database Setup Script (MySQL 5.7+ / MariaDB 10.3+)
--  Run once to create the database, tables, views, and seed data.
--  Usage:  mysql -u root -p < retrogames_database.sql
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ── 1. Database ──────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `retrogames`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `retrogames`;

-- ── 2. Tables ────────────────────────────────────────────────

-- 2.1 users
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)      NOT NULL,
  `email`      VARCHAR(100)     NOT NULL,
  `password`   VARCHAR(255)     NOT NULL,
  `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
  `avatar`     VARCHAR(255)     DEFAULT NULL,
  `is_active`  TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` DATETIME         DEFAULT NULL,
  `total_playtime` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'lifetime seconds played',
  `deleted_at` DATETIME         DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 games
CREATE TABLE IF NOT EXISTS `games` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(50)   NOT NULL,
  `name`         VARCHAR(100)  NOT NULL,
  `description`  TEXT,
  `instructions` TEXT,
  `category`     VARCHAR(50)   DEFAULT 'arcade',
  `thumbnail`    VARCHAR(255)  DEFAULT NULL,
  `is_active`    TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`   INT           NOT NULL DEFAULT 0,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`   DATETIME      DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_games_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 scores
CREATE TABLE IF NOT EXISTS `scores` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `game_id`    INT UNSIGNED NOT NULL,
  `score`      INT UNSIGNED NOT NULL DEFAULT 0,
  `level`      INT UNSIGNED DEFAULT NULL,
  `duration`   INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME     DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  KEY `idx_user_game`  (`user_id`, `game_id`),
  KEY `idx_game_score` (`game_id`, `score` DESC),
  KEY `idx_scores_deleted` (`deleted_at`),
  CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_scores_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 game_sessions
CREATE TABLE IF NOT EXISTS `game_sessions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `game_id`    INT UNSIGNED NOT NULL,
  `started_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at`   DATETIME     DEFAULT NULL,
  `duration`   INT UNSIGNED DEFAULT NULL COMMENT 'seconds actually played',
  PRIMARY KEY (`id`),
  KEY `idx_gs_user` (`user_id`),
  KEY `idx_gs_duration` (`duration`),
  KEY `idx_gs_game` (`game_id`),
  CONSTRAINT `fk_gs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gs_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. View ──────────────────────────────────────────────────

-- leaderboard: best score per user per game
-- (excludes soft-deleted users, games, and scores)
CREATE OR REPLACE VIEW `leaderboard` AS
  SELECT
    s.game_id,
    s.user_id,
    MAX(s.score)  AS best_score,
    u.username,
    g.name        AS game_name,
    g.slug        AS game_slug
  FROM   `scores` s
  JOIN   `users`  u ON s.user_id = u.id
  JOIN   `games`  g ON s.game_id = g.id
  WHERE  s.deleted_at IS NULL
    AND  u.deleted_at IS NULL
    AND  g.deleted_at IS NULL
  GROUP  BY s.game_id, s.user_id;

-- ── 4. Seed data ─────────────────────────────────────────────

-- 4.1 Default admin user  (password: admin123)
--     bcrypt hash generated with PASSWORD_BCRYPT cost 10
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
VALUES (
  'admin',
  'admin@retrogames.com',
  '$2y$10$Ta.3t4mxnDzf03De6culF.uU/Ho1JfA0/eApvVoDit7h9JslK/zRe', -- admin123
  'admin'
);

-- 4.2 Six built-in games
INSERT IGNORE INTO `games`
  (`slug`, `name`, `description`, `instructions`, `category`, `sort_order`)
VALUES
  ('snake',      'Snake',
   'Classic snake game. Eat food, grow longer, avoid walls and yourself!',
   'Arrow keys or WASD to move. Eat the food to grow. Avoid hitting walls or your tail.',
   'arcade', 1),

  ('pacman',     'Pac-Man',
   'Guide Pac-Man through the maze, eat all dots while avoiding ghosts!',
   'Arrow keys to move. Eat all dots to win. Power pellets let you eat ghosts!',
   'arcade', 2),

  ('car',        'Car Racing',
   'Dodge incoming traffic and survive as long as possible at high speed!',
   'Arrow keys or A/D to move left/right. Avoid other cars. Speed increases over time.',
   'racing', 3),

  ('flappybird', 'Flappy Bird',
   'Tap to flap through pipes. How far can you go?',
   'Press Space or click to flap. Avoid the pipes. Each pipe passed = 1 point.',
   'arcade', 4),

  ('pingpong',   'Ping Pong',
   'Classic table tennis. Play against the computer or a friend!',
   'Player 1: W/S keys. Player 2: Up/Down arrows. First to 7 wins!',
   'sports', 5),

  ('tetris',     'Tetris',
   'Arrange falling blocks to complete lines. Classic puzzle action!',
   'Arrow keys to move/rotate. Down to drop faster. Space for hard drop.',
   'puzzle', 6);

SET foreign_key_checks = 1;

-- ── Done ─────────────────────────────────────────────────────
-- Default admin credentials:  admin / admin123
-- IMPORTANT: Change the admin password immediately after first login!
-- IMPORTANT: Delete install.php from your server after setup!

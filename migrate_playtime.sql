-- ============================================================
--  RetroGames — Migration: Playtime Timer / User Activity
--  Run ONCE on an EXISTING database (the one already installed).
--  For fresh installs, just use retrogames_database.sql instead.
--  Usage:  mysql -u root -p < migrate_playtime.sql
--  NOTE: if you run this twice you will get "Duplicate column
--        name" errors — that is harmless, it means it's done.
-- ============================================================

USE `retrogames`;

-- ── 1. game_sessions: record how long each session lasted ───
--     duration = seconds actually played (pause time excluded),
--     accumulated from client heartbeats and capped by the server.
ALTER TABLE `game_sessions`
  ADD COLUMN `duration` INT UNSIGNED DEFAULT NULL COMMENT 'seconds actually played' AFTER `ended_at`,
  ADD KEY `idx_gs_duration` (`duration`);

-- ── 2. users: cumulative lifetime playtime (all games) ──────
--     Kept denormalized so the profile page never has to SUM
--     the whole sessions table on every visit.
ALTER TABLE `users`
  ADD COLUMN `total_playtime` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'lifetime seconds played' AFTER `last_login`;

-- ── 3. Backfill from existing data (best effort) ────────────
--     Sessions that already have ended_at get their real length;
--     open sessions (no ended_at) are counted as zero.
UPDATE `game_sessions`
   SET `duration` = TIMESTAMPDIFF(SECOND, `started_at`, `ended_at`)
 WHERE `ended_at` IS NOT NULL
   AND `duration` IS NULL;

UPDATE `users` u
   SET u.`total_playtime` = (
     SELECT COALESCE(SUM(gs.`duration`), 0)
       FROM `game_sessions` gs
      WHERE gs.`user_id` = u.`id`
        AND gs.`duration` IS NOT NULL
   )
 WHERE u.`deleted_at` IS NULL;

-- ── Done ─────────────────────────────────────────────────────

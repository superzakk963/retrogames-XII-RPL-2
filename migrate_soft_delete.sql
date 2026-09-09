-- ============================================================
--  RetroGames — Migration: Soft Delete / Recycle Bin
--  Run ONCE on an EXISTING database (the one already installed).
--  For fresh installs, just use retrogames_database.sql instead.
--  Usage:  mysql -u root -p < migrate_soft_delete.sql
--  NOTE: if you run this twice you will get "Duplicate column
--        name" errors — that is harmless, it means it's done.
-- ============================================================

USE `retrogames`;

-- ── 1. Add deleted_at columns ────────────────────────────────
ALTER TABLE `users`
  ADD COLUMN `deleted_at` DATETIME DEFAULT NULL COMMENT 'soft delete / recycle bin' AFTER `last_login`,
  ADD KEY `idx_users_deleted` (`deleted_at`);

ALTER TABLE `games`
  ADD COLUMN `deleted_at` DATETIME DEFAULT NULL COMMENT 'soft delete / recycle bin' AFTER `updated_at`,
  ADD KEY `idx_games_deleted` (`deleted_at`);

ALTER TABLE `scores`
  ADD COLUMN `deleted_at` DATETIME DEFAULT NULL COMMENT 'soft delete / recycle bin' AFTER `created_at`,
  ADD KEY `idx_scores_deleted` (`deleted_at`);

-- ── 2. Scores no longer cascade-delete with users/games ──────
--     (a soft-deleted user's scores must survive in the trash,
--      so CASCADE has to go. FK names differ between databases
--      created via install.php (auto names) vs the .sql file,
--      so we drop whatever foreign keys exist dynamically.)
DROP PROCEDURE IF EXISTS `drop_scores_fks`;
DELIMITER $$
CREATE PROCEDURE `drop_scores_fks`()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE fkname VARCHAR(64);
  DECLARE cur CURSOR FOR
    SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scores'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  OPEN cur;
  fk_loop: LOOP
    FETCH cur INTO fkname;
    IF done THEN LEAVE fk_loop; END IF;
    SET @sql = CONCAT('ALTER TABLE `scores` DROP FOREIGN KEY `', fkname, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE cur;
END$$
DELIMITER ;
CALL `drop_scores_fks`();
DROP PROCEDURE `drop_scores_fks`;

ALTER TABLE `scores`
  ADD CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_scores_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

-- ── 3. Recreate the leaderboard view (now trash-aware) ───────
DROP VIEW IF EXISTS `leaderboard`;
CREATE VIEW `leaderboard` AS
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

-- ── 4. Drop the unused announcements feature ─────────────────
DROP TABLE IF EXISTS `announcements`;

-- ── Done ─────────────────────────────────────────────────────

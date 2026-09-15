<?php
// includes/schema.php — idempotent schema upgrades for RetroGames.
//
// Root cause it fixes:
//   install.php historically used CREATE TABLE IF NOT EXISTS, which is a
//   no-op on databases created by an older version. So a DB created before
//   the playtime feature kept missing `game_sessions.duration` and
//   `users.total_playtime`, and profile.php (which legitimately needs those
//   columns) died with HTTP 500 / SQLSTATE[42S22].
//
// What this file does:
//   ensureSchema() brings ANY existing database up to the current schema.
//   It performs the same steps as migrate_soft_delete.sql (first) and
//   migrate_playtime.sql (second), but each step checks INFORMATION_SCHEMA
//   first, so it is safe to run on every install.php visit and on re-runs.
//
// Order matters: the playtime backfill assumes the soft-delete columns
// exist (it filters on users.deleted_at), hence soft-delete steps run first.

function schemaColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function schemaIndexExists(PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function schemaTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Do the scores FKs still cascade (pre-soft-delete behaviour)?
 * Soft-deleted users' scores must survive in the trash, so CASCADE has to go.
 */
function schemaScoresHasCascadeDelete(PDO $pdo): bool {
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
          WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'scores'
            AND DELETE_RULE = 'CASCADE'"
    );
    return (int)$stmt->fetchColumn() > 0;
}

/** Drop whatever FKs exist on scores (names differ per install method). */
function schemaDropScoresForeignKeys(PDO $pdo): void {
    $names = $pdo->query(
        "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scores'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($names as $fk) {
        $pdo->exec('ALTER TABLE `scores` DROP FOREIGN KEY `' . str_replace('`', '', $fk) . '`');
    }
}

/** Is the leaderboard view missing or still the pre-trash definition? */
function schemaLeaderboardNeedsRefresh(PDO $pdo): bool {
    $stmt = $pdo->query(
        "SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leaderboard'"
    );
    $def = $stmt->fetchColumn();
    if ($def === false || $def === null) return true;
    return stripos((string)$def, 'deleted_at') === false;
}

function schemaRefreshLeaderboardView(PDO $pdo): void {
    $pdo->exec(
        'CREATE OR REPLACE VIEW `leaderboard` AS
         SELECT s.game_id, s.user_id, MAX(s.score) AS best_score,
                u.username, g.name AS game_name, g.slug AS game_slug
         FROM scores s
         JOIN users u ON s.user_id = u.id
         JOIN games g ON s.game_id = g.id
         WHERE s.deleted_at IS NULL
           AND u.deleted_at IS NULL
           AND g.deleted_at IS NULL
         GROUP BY s.game_id, s.user_id'
    );
}

function schemaTriggerExists(PDO $pdo, string $trigger): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TRIGGERS
          WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ?'
    );
    $stmt->execute([$trigger]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Trigger jaga users.total_playtime = SUM(game_sessions.duration)
 * + SUM(scores.duration) per user. Idempoten (DROP lalu CREATE).
 * Dipakai fresh install (install.php / retrogames_database.sql) maupun
 * upgrade DB lama via ensureSchema().
 */
function schemaEnsurePlaytimeTriggers(PDO $pdo): bool {
    $created = false;
    $recalcGs = "UPDATE `users` u SET u.`total_playtime` = "
        . "(SELECT COALESCE(SUM(gs.`duration`), 0) FROM `game_sessions` gs "
        . "WHERE gs.`user_id` = NEW.`user_id` AND gs.`duration` IS NOT NULL) + "
        . "(SELECT COALESCE(SUM(s.`duration`), 0) FROM `scores` s "
        . "WHERE s.`user_id` = NEW.`user_id` AND s.`duration` IS NOT NULL AND s.`deleted_at` IS NULL) "
        . "WHERE u.`id` = NEW.`user_id`";
    $recalcGsOld = str_replace('NEW.`user_id`', 'OLD.`user_id`', $recalcGs);
    // Untuk AFTER UPDATE game_sessions: sinkronkan NEW dan (bila user berubah) OLD.
    $recalcGsBoth = $recalcGs . '; '
        . 'IF NOT (OLD.`user_id` <=> NEW.`user_id`) THEN ' . $recalcGsOld . '; END IF';
    $recalcScores = $recalcGs;
    $recalcScoresOld = $recalcGsOld;
    $recalcScoresBoth = $recalcGsBoth;

    $triggers = [
        'trg_gs_after_insert' => "CREATE TRIGGER `trg_gs_after_insert` AFTER INSERT ON `game_sessions` "
            . "FOR EACH ROW BEGIN {$recalcGs}; END",
        'trg_gs_after_update' => "CREATE TRIGGER `trg_gs_after_update` AFTER UPDATE ON `game_sessions` "
            . "FOR EACH ROW BEGIN {$recalcGsBoth}; END",
        'trg_gs_after_delete' => "CREATE TRIGGER `trg_gs_after_delete` AFTER DELETE ON `game_sessions` "
            . "FOR EACH ROW BEGIN "
            . str_replace('NEW.`user_id`', 'OLD.`user_id`', $recalcGs) . "; END",
        'trg_scores_after_insert' => "CREATE TRIGGER `trg_scores_after_insert` AFTER INSERT ON `scores` "
            . "FOR EACH ROW BEGIN {$recalcScores}; END",
        'trg_scores_after_update' => "CREATE TRIGGER `trg_scores_after_update` AFTER UPDATE ON `scores` "
            . "FOR EACH ROW BEGIN {$recalcScoresBoth}; END",
        'trg_scores_after_delete' => "CREATE TRIGGER `trg_scores_after_delete` AFTER DELETE ON `scores` "
            . "FOR EACH ROW BEGIN "
            . str_replace('NEW.`user_id`', 'OLD.`user_id`', $recalcScores) . "; END",
    ];

    foreach ($triggers as $name => $sql) {
        // Selalu DROP + CREATE agar definisi lama (hanya SUM sessions)
        // ikut ter-upgrade ke definisi gabungan yang baru.
        $pdo->exec("DROP TRIGGER IF EXISTS `{$name}`");
        $pdo->exec($sql);
        $created = true;
    }
    return $created;
}

/**
 * Bring the current database up to date. Safe to call repeatedly.
 *
 * @return string[] Human-readable list of upgrades actually applied
 *                  (empty = already up to date).
 */
function ensureSchema(PDO $pdo): array {
    $applied = [];

    // ── 1. Soft-delete columns (must come before the playtime backfill) ──
    if (!schemaColumnExists($pdo, 'users', 'deleted_at')) {
        $pdo->exec(
            "ALTER TABLE `users`
               ADD COLUMN `deleted_at` DATETIME DEFAULT NULL
               COMMENT 'soft delete / recycle bin' AFTER `last_login`"
        );
        $applied[] = 'users.deleted_at';
    }
    if (!schemaIndexExists($pdo, 'users', 'idx_users_deleted')) {
        $pdo->exec('ALTER TABLE `users` ADD KEY `idx_users_deleted` (`deleted_at`)');
    }

    if (!schemaColumnExists($pdo, 'games', 'deleted_at')) {
        $pdo->exec(
            "ALTER TABLE `games`
               ADD COLUMN `deleted_at` DATETIME DEFAULT NULL
               COMMENT 'soft delete / recycle bin' AFTER `updated_at`"
        );
        $applied[] = 'games.deleted_at';
    }
    if (!schemaIndexExists($pdo, 'games', 'idx_games_deleted')) {
        $pdo->exec('ALTER TABLE `games` ADD KEY `idx_games_deleted` (`deleted_at`)');
    }

    if (!schemaColumnExists($pdo, 'scores', 'deleted_at')) {
        $pdo->exec(
            "ALTER TABLE `scores`
               ADD COLUMN `deleted_at` DATETIME DEFAULT NULL
               COMMENT 'soft delete / recycle bin' AFTER `created_at`"
        );
        $applied[] = 'scores.deleted_at';
    }
    if (!schemaIndexExists($pdo, 'scores', 'idx_scores_deleted')) {
        $pdo->exec('ALTER TABLE `scores` ADD KEY `idx_scores_deleted` (`deleted_at`)');
    }

    // Scores FKs must not cascade (soft-deleted rows stay in the trash).
    if (schemaScoresHasCascadeDelete($pdo)) {
        schemaDropScoresForeignKeys($pdo);
        $pdo->exec(
            'ALTER TABLE `scores`
               ADD CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
               ADD CONSTRAINT `fk_scores_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`)'
        );
        $applied[] = 'scores foreign keys (CASCADE removed)';
    }

    if (schemaLeaderboardNeedsRefresh($pdo)) {
        schemaRefreshLeaderboardView($pdo);
        $applied[] = 'leaderboard view (trash-aware)';
    }

    if (schemaTableExists($pdo, 'announcements')) {
        $pdo->exec('DROP TABLE IF EXISTS `announcements`');
        $applied[] = 'announcements table removed';
    }

    // ── 2. Playtime columns (same as migrate_playtime.sql) ──
    if (!schemaColumnExists($pdo, 'game_sessions', 'duration')) {
        $pdo->exec(
            "ALTER TABLE `game_sessions`
               ADD COLUMN `duration` INT UNSIGNED DEFAULT NULL
               COMMENT 'seconds actually played' AFTER `ended_at`"
        );
        $applied[] = 'game_sessions.duration';
    }
    if (!schemaIndexExists($pdo, 'game_sessions', 'idx_gs_duration')) {
        $pdo->exec('ALTER TABLE `game_sessions` ADD KEY `idx_gs_duration` (`duration`)');
    }
    if (!schemaIndexExists($pdo, 'game_sessions', 'idx_gs_user')) {
        $pdo->exec('ALTER TABLE `game_sessions` ADD KEY `idx_gs_user` (`user_id`)');
    }
    if (!schemaIndexExists($pdo, 'game_sessions', 'idx_gs_game')) {
        $pdo->exec('ALTER TABLE `game_sessions` ADD KEY `idx_gs_game` (`game_id`)');
    }

    if (!schemaColumnExists($pdo, 'users', 'total_playtime')) {
        $pdo->exec(
            "ALTER TABLE `users`
               ADD COLUMN `total_playtime` INT UNSIGNED NOT NULL DEFAULT 0
               COMMENT 'lifetime seconds played' AFTER `last_login`"
        );
        $applied[] = 'users.total_playtime';
    }

    // ── 2b. Trigger gabungan sessions + scores ──
    try {
        schemaEnsurePlaytimeTriggers($pdo);
        $applied[] = 'playtime triggers (sessions + scores -> users.total_playtime)';
    } catch (Throwable $t) {
        // Hak TRIGGER tidak ada di sebagian hosting: API tetap me-recalc
        // manual tiap request, jadi ini non-fatal.
        error_log('ensureSchema triggers skipped: ' . $t->getMessage());
    }

    // ── 3. Backfill (gabungan sessions + scores) ──
    // Only rows that still need it are touched, so re-runs are no-ops.
    $filled = (int)$pdo->exec(
        'UPDATE `game_sessions`
            SET `duration` = TIMESTAMPDIFF(SECOND, `started_at`, `ended_at`)
          WHERE `ended_at` IS NOT NULL AND `duration` IS NULL'
    );
    if ($filled > 0) {
        $applied[] = "backfilled {$filled} game session duration(s)";
    }

    $synced = (int)$pdo->exec(
        'UPDATE `users` u
            SET u.`total_playtime` = (
              SELECT COALESCE(SUM(gs.`duration`), 0)
                FROM `game_sessions` gs
               WHERE gs.`user_id` = u.`id`
                 AND gs.`duration` IS NOT NULL
            ) + (
              SELECT COALESCE(SUM(s.`duration`), 0)
                FROM `scores` s
               WHERE s.`user_id` = u.`id`
                 AND s.`duration` IS NOT NULL
                 AND s.`deleted_at` IS NULL
            )
          WHERE u.`deleted_at` IS NULL'
    );
    if ($synced > 0) {
        $applied[] = "recalculated lifetime playtime for {$synced} user(s)";
    }

    return $applied;
}

<?php
// includes/trash.php — Soft delete (recycle bin) helpers.
// All deletes in the app go through here instead of raw DELETE queries.
// Requires db.php to be loaded first.

// How long items stay in the Trash before being purged automatically.
// Override by defining this constant before including this file.
if (!defined('TRASH_RETENTION_DAYS')) {
    define('TRASH_RETENTION_DAYS', 30);
}

// Minimum seconds between two automatic purge runs (throttle).
if (!defined('TRASH_AUTOPURGE_INTERVAL')) {
    define('TRASH_AUTOPURGE_INTERVAL', 3600); // 1 hour
}

/**
 * Soft delete a single row by marking deleted_at = NOW().
 * Returns the number of affected rows.
 */
function softDelete(string $table, int $id): int {
    $allowed = ['users', 'games', 'scores']; // whitelist — never build SQL from user input
    if (!in_array($table, $allowed, true)) return 0;

    $pdo = getDB();

    if ($table === 'users') {
        // users.username / users.email are UNIQUE. Temporarily rename them so a
        // new user can register with the freed username/email while the old row
        // sits in the trash. The original values are restored on restoreFromTrash().
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) return 0;

        $suffix = '_deleted_' . time();
        $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?")
            ->execute([
                substr($u['username'], 0, 50 - strlen($suffix)) . $suffix,
                substr($u['email'], 0, 100 - strlen($suffix)) . $suffix,
                $id,
            ]);
    }

    $stmt = $pdo->prepare("UPDATE `$table` SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->rowCount();
}

/**
 * Soft delete many rows by WHERE condition.
 * $conds: list of ["col" => value] pairs (ANDed together). Values are bound, not interpolated.
 */
function softDeleteWhere(string $table, array $conds): int {
    $allowed = ['users', 'games', 'scores'];
    if (!in_array($table, $allowed, true) || empty($conds)) return 0;

    $set = [];
    $params = [];
    foreach ($conds as $col => $val) {
        $set[] = "`$col` = ?";
        $params[] = $val;
    }
    $where = implode(' AND ', $set) . ' AND deleted_at IS NULL';

    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE `$table` SET deleted_at = NOW() WHERE $where");
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Restore a soft-deleted row (deleted_at = NULL).
 * For users it also restores the original username/email if they are free.
 */
function restoreFromTrash(string $table, int $id): bool {
    $allowed = ['users', 'games', 'scores'];
    if (!in_array($table, $allowed, true)) return false;

    $pdo = getDB();

    if ($table === 'users') {
        // Original username/email are stored with a "_deleted_<timestamp>" suffix.
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) return false;

        $origUser = preg_replace('/_deleted_\d+$/', '', $u['username']);
        $origMail = preg_replace('/_deleted_\d+$/', '', $u['email']);

        // If another user has taken the name in the meantime, keep the suffixed
        // version (still unique) so the restore never fails.
        $chk = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? AND deleted_at IS NULL");
        $chk->execute([$origUser, $origMail, $id]);
        if (!$chk->fetch()) {
            $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?")
                ->execute([$origUser, $origMail, $id]);
        }
    }

    $stmt = $pdo->prepare("UPDATE `$table` SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

/**
 * Permanently delete a row from the trash. This is the only place in the app
 * that runs a real DELETE statement.
 *
 * Cascades: purging a user or game also permanently deletes all of its scores
 * (inside a transaction), so the delete can never fail on FK constraints.
 * game_sessions rows are cascade-deleted by the database itself.
 */
function purgeFromTrash(string $table, int $id): bool {
    $allowed = ['users', 'games', 'scores'];
    if (!in_array($table, $allowed, true)) return false;

    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        if ($table === 'users') {
            $pdo->prepare("DELETE FROM scores WHERE user_id = ?")->execute([$id]);
        } elseif ($table === 'games') {
            $pdo->prepare("DELETE FROM scores WHERE game_id = ?")->execute([$id]);
        }
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$id]);
        $ok = $stmt->rowCount() > 0;
        $pdo->commit();
        return $ok;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Permanently empty the entire trash. Users/games cascade their scores.
 * Returns the number of purged rows per table: ['users'=>n, 'games'=>n, 'scores'=>n].
 */
function emptyTrash(): array {
    $pdo = getDB();
    $purged = ['users' => 0, 'games' => 0, 'scores' => 0];

    // 1. All trashed scores
    $purged['scores'] = (int)$pdo->exec("DELETE FROM scores WHERE deleted_at IS NOT NULL");

    // 2. All trashed users (cascade removes their remaining scores)
    $ids = $pdo->query("SELECT id FROM users WHERE deleted_at IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try { if (purgeFromTrash('users', (int)$id)) $purged['users']++; }
        catch (PDOException $e) { error_log('Empty-trash user purge failed: ' . $e->getMessage()); }
    }

    // 3. All trashed games (cascade removes their remaining scores)
    $ids = $pdo->query("SELECT id FROM games WHERE deleted_at IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try { if (purgeFromTrash('games', (int)$id)) $purged['games']++; }
        catch (PDOException $e) { error_log('Empty-trash game purge failed: ' . $e->getMessage()); }
    }

    return $purged;
}

/**
 * Permanently delete every trash item older than $days days (default: 30).
 * Order matters: scores first, then users/games (each purge cascades its scores).
 * Returns the number of purged rows per table: ['users'=>n, 'games'=>n, 'scores'=>n].
 */
function purgeExpiredTrash(int $days = TRASH_RETENTION_DAYS): array {
    if ($days < 0) $days = 0;
    $pdo = getDB();
    $purged = ['users' => 0, 'games' => 0, 'scores' => 0];

    // 1. Expired scores
    $purged['scores'] = $pdo->exec("DELETE FROM scores
        WHERE deleted_at IS NOT NULL
          AND deleted_at < NOW() - INTERVAL " . (int)$days . " DAY");

    // 2. Expired users (cascade removes their remaining scores)
    $ids = $pdo->query("SELECT id FROM users
        WHERE deleted_at IS NOT NULL
          AND deleted_at < NOW() - INTERVAL " . (int)$days . " DAY")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try { if (purgeFromTrash('users', (int)$id)) $purged['users']++; }
        catch (PDOException $e) { error_log('Auto-purge user failed: ' . $e->getMessage()); }
    }

    // 3. Expired games (cascade removes their remaining scores)
    $ids = $pdo->query("SELECT id FROM games
        WHERE deleted_at IS NOT NULL
          AND deleted_at < NOW() - INTERVAL " . (int)$days . " DAY")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try { if (purgeFromTrash('games', (int)$id)) $purged['games']++; }
        catch (PDOException $e) { error_log('Auto-purge game failed: ' . $e->getMessage()); }
    }

    return $purged;
}

/**
 * Auto-purge wrapper, throttled to once per TRASH_AUTOPURGE_INTERVAL seconds
 * (tracked in the session). Call it on admin pages so old trash items get
 * cleaned up without any cron job. Returns the purged counts array, or an
 * empty array when the throttle skipped this run.
 */
function autoPurgeTrash(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return []; // no session (e.g. CLI) — call purgeExpiredTrash() directly
    }
    $last = $_SESSION['trash_last_autopurge'] ?? 0;
    if (time() - $last < TRASH_AUTOPURGE_INTERVAL) {
        return [];
    }
    $_SESSION['trash_last_autopurge'] = time();
    return purgeExpiredTrash();
}

/**
 * Count of items currently in the recycle bin, per table.
 * Returns ['users' => int, 'games' => int, 'scores' => int].
 */
function trashCounts(): array {
    $pdo = getDB();
    return [
        'users'  => (int)$pdo->query("SELECT COUNT(*) FROM users  WHERE deleted_at IS NOT NULL")->fetchColumn(),
        'games'  => (int)$pdo->query("SELECT COUNT(*) FROM games  WHERE deleted_at IS NOT NULL")->fetchColumn(),
        'scores' => (int)$pdo->query("SELECT COUNT(*) FROM scores WHERE deleted_at IS NOT NULL")->fetchColumn(),
    ];
}

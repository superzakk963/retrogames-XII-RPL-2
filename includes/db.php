<?php
// DB constants can be pre-defined (e.g. by tests) to override the defaults.
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    
    // Otomatis ganti user berdasarkan lingkungan (Termux / Laptop)
    $is_termux = is_dir('/data/data/com.termux') || (isset($_SERVER['HOME']) && str_istr($_SERVER['HOME'], 'termux'));
    define('DB_USER', $is_termux ? 'admin' : 'root');
    
    define('DB_PASS', '');
    define('DB_NAME', 'retrogames');
    define('DB_PORT', '3306');
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            // Try fallback to 'root' or 'admin' if connection fails
            try {
                $fallbackUser = (DB_USER === 'admin') ? 'root' : 'admin';
                $pdo = new PDO($dsn, $fallbackUser, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $ex) {
                error_log('DB connection failed: ' . $ex->getMessage());
                die('Database connection failed. Please check your configuration.');
            }
        }
    }
    return $pdo;
}

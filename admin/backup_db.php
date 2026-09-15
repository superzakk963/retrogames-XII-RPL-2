<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/trash.php';
startSession();
requireAdmin('../index.php');

$pageTitle = 'Backup DB';
$cssPath   = '../assets/style.css';
$jsPath    = '../assets/main.js';
$homePath  = '../';

$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Keamanan Tambahan: Buat file .htaccess agar folder backups tidak bisa diakses langsung via URL browser
$htaccessFile = $backupDir . '.htaccess';
if (!file_exists($htaccessFile)) {
    file_put_contents($htaccessFile, "Deny from all");
}

$message = '';
$messageType = '';

// Path Eksekusi MySQL & MySQLDump XAMPP Windows
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';
$mysql     = 'C:\xampp\mysql\bin\mysql.exe';
$host      = 'localhost';
$user      = 'root';
$pass      = '';
$dbname    = 'retrogames';

// --- FITUR 1: Auto Cleanup (Maksimal Menyimpan 10 File Backup Terbaru) ---
function autoCleanupBackups($dir, $maxFiles = 10) {
    $files = array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), array('..', '.', '.htaccess'));
    $sqlFiles = array();
    foreach ($files as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
            $sqlFiles[] = $f;
        }
    }
    if (count($sqlFiles) > $maxFiles) {
        $filesToDelete = array_slice($sqlFiles, $maxFiles);
        foreach ($filesToDelete as $fileToDelete) {
            @unlink($dir . $fileToDelete);
        }
    }
}

// --- 1. PROSES GENERATE BACKUP BARU ---
if (isset($_POST['create_backup'])) {
    $filename   = $dbname . '_' . date("Y-m-d_H-i-s") . '.sql';
    $backupFile = $backupDir . $filename;

    $command = "\"{$mysqldump}\" --user={$user} --host={$host} {$dbname} > \"{$backupFile}\"";
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        autoCleanupBackups($backupDir, 10); // Jalankan auto cleanup
        $message = "Backup database berhasil dibuat: " . htmlspecialchars($filename);
        $messageType = "success";
    } else {
        $message = "Gagal melakukan backup database. Pastikan mysqldump terinstall.";
        $messageType = "danger";
    }
}

// --- 2. PROSES RESTORE DATABASE (FITUR UTAMA BARU) ---
if (isset($_GET['restore'])) {
    $file = basename($_GET['restore']);
    $targetPath = $backupDir . $file;
    if (file_exists($targetPath)) {
        // Impor/Restore data dari file .sql kembali ke MySQL database
        $command = "\"{$mysql}\" --user={$user} --host={$host} {$dbname} < \"{$targetPath}\"";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $message = "Database berhasil dipulihkan (Restore) dari file: " . htmlspecialchars($file);
            $messageType = "success";
        } else {
            $message = "Gagal memulihkan database. Silakan periksa file salinan.";
            $messageType = "danger";
        }
    }
}

// --- 3. PROSES DOWNLOAD FILE ---
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $targetPath = $backupDir . $file;
    if (file_exists($targetPath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($targetPath));
        readfile($targetPath);
        exit;
    }
}

// --- 4. PROSES HAPUS FILE BACKUP ---
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $targetPath = $backupDir . $file;
    if (file_exists($targetPath)) {
        unlink($targetPath);
        $message = "File backup berhasil dihapus: " . htmlspecialchars($file);
        $messageType = "success";
    }
}

// Ambil daftar file backup .sql saja
$rawFiles = array_diff(scandir($backupDir, SCANDIR_SORT_DESCENDING), array('..', '.', '.htaccess'));
$backups = array();
foreach ($rawFiles as $rf) {
    if (pathinfo($rf, PATHINFO_EXTENSION) === 'sql') {
        $backups[] = $rf;
    }
}

autoPurgeTrash();
$trashTotal = array_sum(trashCounts());

include '../includes/header.php';
?>

<section id="admin-section">
    <div class="admin-header">
        <h2>⚙️ Admin Dashboard</h2>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="games.php">Games</a>
            <a href="scores.php">Scores</a>
            <a href="trash.php">🗑 Trash (<?= $trashTotal ?>)</a>
            <a href="backup_db.php" class="active">Backup Database</a>
        </nav>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>" style="padding: 12px 20px; margin-bottom: 20px; border-radius: 8px; background-color: <?= $messageType === 'success' ? '#059669' : '#dc2626' ?>; color: white;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="admin-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0;">Database Backup & Restore</h3>
                <p style="color: #a0aec0; margin-top: 5px; font-size: 0.9em;">Ekspor, unduh, pulihkan (restore), atau kelola salinan database MySQL.</p>
            </div>
            <form method="POST" action="backup_db.php">
                <button type="submit" name="create_backup" class="btn btn-primary" style="padding: 8px 16px; font-weight: bold; cursor: pointer;">
                    💾 Backup Sekarang
                </button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <h3>Riwayat Backup Database (Max 10 File Simpanan)</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #a0aec0;">Belum ada file backup.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($backups as $b): 
                            $filePath = $backupDir . $b;
                            if (!is_file($filePath)) continue;
                            $size = round(filesize($filePath) / 1024, 2);
                            $date = date("Y-m-d H:i:s", filemtime($filePath));
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($b) ?></code></td>
                            <td><?= $size ?> KB</td>
                            <td><?= $date ?></td>
                            <td>
                                <a href="backup_db.php?restore=<?= urlencode($b) ?>" class="btn btn-xs" style="background-color: #eab308; color: black; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-decoration: none; margin-right: 4px;" onclick="return confirm('Peringatan! Mengembalikan (restore) database akan menimpa seluruh data saat ini dengan data dari file backup ini. Lanjutkan?');">Restore</a>
                                <a href="backup_db.php?download=<?= urlencode($b) ?>" class="btn btn-xs btn-primary">Download</a>
                                <a href="backup_db.php?delete=<?= urlencode($b) ?>" class="btn btn-xs btn-danger" onclick="return confirm('Yakin ingin menghapus file backup ini?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
<?php
// Konfigurasi Database
$host     = 'localhost';
$user     = 'root';
$pass     = '';
$dbname   = 'retrogames';

// Format Nama File Backup (misal: backup_retrogames_2026-09-11_10-45-00.sql)
$backup_file = '../backups/' . $dbname . '_' . date("Y-m-d_H-i-s") . '.sql';

// Buat folder 'backups' otomatis jika belum ada
if (!is_dir('../backups')) {
    mkdir('../backups', 0777, true);
}

// Perintah mysqldump untuk ekspor database
$command = "mysqldump --user={$user} --password={$pass} --host={$host} {$dbname} > {$backup_file}";

// Eksekusi Perintah
$output = array();
$return_var = NULL;
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Backup database berhasil disimpan di: " . $backup_file;
} else {
    echo "Gagal melakukan backup database. Pastikan mysqldump terinstall.";
}
?>
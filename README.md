# 🎮 RetroGames — XII RPL 2

A retro arcade web app (PHP + MySQL) with 6 classic games, user accounts, leaderboards, an admin panel, and a **Recycle Bin (soft delete)** system.

> Versi Bahasa Indonesia ada di bagian bawah file ini.

---

## ✨ Features

- **6 built-in games**: Snake, Pac-Man, Car Racing, Flappy Bird, Ping Pong, Tetris
- **User system**: register, login, profile, password change
- **Leaderboards**: per-game and global, live-updating via AJAX score submission
- **Admin panel**: dashboard stats, manage users / games / scores
- **🗑 Recycle Bin (soft delete)**:
  - Deleting a user or score **marks it** (`deleted_at`) instead of erasing it (built-in games cannot be deleted)
  - Restorable anytime from **Admin → Trash**
  - **Delete Forever** for permanent removal (cascades to related scores)
  - **Empty Trash** button to purge everything at once
  - **Auto-purge**: items older than 30 days are permanently removed automatically
    (runs at most once per hour per admin session; no cron needed)

## 🚀 Setup (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\retrogames`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Create / upgrade the database — pick ONE:
   - **Recommended (fresh install AND existing old database)**: open
     `http://localhost/retrogames/install.php`.
     It creates missing tables AND auto-applies pending upgrades
     (`deleted_at` soft-delete columns, `game_sessions.duration`,
     `users.total_playtime`, trash-aware `leaderboard` view).
     Safe to re-open after `git pull` — already-up-to-date DBs report
     "no upgrades needed".
   - **Manual fresh install**: import `retrogames_database.sql` via phpMyAdmin
     (already contains all columns + view + seed data).
   - **Manual upgrade of an existing database**: import IN THIS ORDER
     `migrate_soft_delete.sql`, then `migrate_playtime.sql`.
     Safe to run once; re-running prints harmless "Duplicate column" errors.
4. Default admin: **admin / admin123** — change the password immediately.
5. Open `http://localhost/retrogames/`.

DB credentials live in `includes/db.php` (defaults: `root`, no password, DB `retrogames`).

## 🗑 Using the Recycle Bin

| Action | Where | Effect |
|---|---|---|
| Delete | Users / Scores pages | Moves item to Trash (recoverable) |
| Hide / Show | Games page | Built-in games cannot be deleted (their PHP files must exist) — hide them from the public instead |
| ↩ Restore | Admin → Trash | Brings the item back (users get their original username/email back if still free) |
| ✕ Delete Forever | Admin → Trash | Permanent; also removes that user's scores |
| 🗑 Empty Trash | Admin → Trash | Permanently purges the entire Trash |
| Auto-purge | automatic | Trash items older than `TRASH_RETENTION_DAYS` (30) are purged when an admin visits the Dashboard or Trash page |

Retention is configurable — define `TRASH_RETENTION_DAYS` before `includes/trash.php` is loaded (e.g. in `includes/auth.php`):

```php
define('TRASH_RETENTION_DAYS', 7); // purge trash older than 7 days
```

Soft-deleted users are renamed internally (`name_deleted_<timestamp>`) so their
username/email become free for new registrations; restoring returns the original
name when available.

## 📁 Structure

```
admin/        admin panel (dashboard, users, games, scores, trash.php)
api/          JSON score-saving endpoint
assets/       CSS + JS
games/        game pages + game_base.php (shared game page logic)
includes/     auth, db, schema.php (idempotent upgrades), trash.php (soft-delete helpers), header/footer
install.php   installer + auto-upgrader (safe to re-run, delete after use on production!)
*.sql         schema+seed (retrogames_database.sql) and migrations
              (migrate_soft_delete.sql, then migrate_playtime.sql)
```

## ⚠️ Notes / Limitations

- `install.php` is safe to re-run (seed inserts use `INSERT IGNORE` and schema
  upgrades are idempotent), but still delete it on a public server after setup.
- "Delete Forever" on a user also permanently removes their scores — the
  confirmation dialog warns about this.
- Game sessions (`game_sessions` table) cascade with users via foreign keys.

---

# 🎮 RetroGames — Versi Indonesia

Aplikasi web arcade retro (PHP + MySQL) dengan 6 game klasik, akun pengguna, leaderboard, panel admin, dan fitur **Recycle Bin (soft delete)**.

## ✨ Fitur

- **6 game bawaan**: Snake, Pac-Man, Car Racing, Flappy Bird, Ping Pong, Tetris
- **Sistem user**: daftar, login, profil, ganti password
- **Leaderboard**: per game dan global, skor tersimpan langsung via AJAX
- **Panel admin**: statistik dashboard, kelola users / games / scores
- **🗑 Recycle Bin (soft delete)**:
  - Menghapus user/skor hanya **menandai data** (`deleted_at`), tidak menghapus permanen (game bawaan tidak bisa dihapus)
  - Bisa dipulihkan kapan saja lewat **Admin → Trash**
  - **Delete Forever** untuk hapus permanen (ikut menghapus skor terkait)
  - Tombol **Empty Trash** untuk mengosongkan seluruh sampah sekaligus
  - **Auto-purge**: item berumur lebih dari 30 hari dihapus permanen otomatis
    (maksimal sekali per jam per sesi admin; tidak butuh cron)

## 🚀 Cara Instalasi (XAMPP)

1. Salin folder ini ke `C:\xampp\htdocs\retrogames`.
2. Nyalakan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buat / upgrade database — pilih salah satu:
   - **Disarankan (install baru MAUPUN database lama)**: buka
     `http://localhost/retrogames/install.php`.
     File ini membuat tabel yang belum ada DAN otomatis menerapkan upgrade
     yang kurang (kolom soft-delete `deleted_at`, `game_sessions.duration`,
     `users.total_playtime`, view `leaderboard` yang sadar trash).
     Aman dibuka ulang setelah `git pull` — DB yang sudah terbaru akan
     melaporkan "no upgrades needed".
   - **Install baru manual**: import `retrogames_database.sql` lewat phpMyAdmin
     (sudah berisi semua kolom + view + data awal).
   - **Upgrade database lama manual**: import DENGAN URUTAN
     `migrate_soft_delete.sql`, lalu `migrate_playtime.sql`.
     Cukup dijalankan sekali; kalau dijalankan dua kali akan muncul error
     "Duplicate column" yang tidak berbahaya.
4. Admin bawaan: **admin / admin123** — segera ganti passwordnya.
5. Buka `http://localhost/retrogames/`.

Kredensial database ada di `includes/db.php` (bawaan: `root`, tanpa password, DB `retrogames`).

## 🗺 Cara Pakai Recycle Bin

| Aksi | Lokasi | Efek |
|---|---|---|
| Delete | Halaman Users / Scores | Memindahkan item ke Trash (masih bisa dipulihkan) |
| Hide / Show | Halaman Games | Game bawaan tidak bisa dihapus (file PHP-nya harus tetap ada) — sembunyikan dari publik saja |
| ↩ Restore | Admin → Trash | Mengembalikan data (username/email asli user kembali kalau belum dipakai orang lain) |
| ✕ Delete Forever | Admin → Trash | Hapus permanen; skor milik user itu ikut terhapus |
| 🗑 Empty Trash | Admin → Trash | Menghapus permanen seluruh isi Trash |
| Auto-purge | otomatis | Item sampah lebih tua dari `TRASH_RETENTION_DAYS` (30) dihapus saat admin membuka Dashboard atau Trash |

Durasi retensi bisa diubah — definisikan `TRASH_RETENTION_DAYS` sebelum `includes/trash.php` dimuat (misal di `includes/auth.php`):

```php
define('TRASH_RETENTION_DAYS', 7); // sampah dihapus permanen setelah 7 hari
```

User yang di-soft-delete otomatis di-rename internal (`nama_deleted_<timestamp>`)
agar username/email-nya bisa dipakai pendaftar baru; saat di-restore, nama asli
kembali jika masih tersedia.

## 📁 Struktur Folder

```
admin/        panel admin (dashboard, users, games, scores, trash.php)
api/          endpoint JSON penyimpanan skor
assets/       CSS + JS
games/        halaman game + game_base.php (logika bersama)
includes/     auth, db, schema.php (upgrade idempoten), trash.php (helper soft delete), header/footer
install.php   installer + auto-upgrader (aman dijalankan ulang, hapus setelah dipakai di production!)
*.sql         skema+seed (retrogames_database.sql) dan migrasi
              (migrate_soft_delete.sql, lalu migrate_playtime.sql)
```

## ⚠️ Catatan / Keterbatasan

- `install.php` aman dijalankan ulang (seed pakai `INSERT IGNORE` dan upgrade
  skema idempoten), tapi tetap hapus di server publik setelah instalasi.
- "Delete Forever" pada user juga menghapus permanen semua skornya —
  dialog konfirmasi sudah memperingatkan hal ini.
- Data `game_sessions` ikut terhapus otomatis via foreign key saat user dihapus permanen.

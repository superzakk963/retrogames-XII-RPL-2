<?php
require_once __DIR__ . '/auth.php';
startSession();
$flash = getFlash();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - RetroGames' : 'RetroGames' ?></title>
    <link rel="stylesheet" href="<?= $cssPath ?? 'assets/style.css' ?>">
</head>
<body>

<header id="site-header">
    <div class="container">
        <div id="logo">
            <a href="<?= $homePath ?? 'index.php' ?>">🎮 RetroGames</a>
        </div>
        <nav id="main-nav">
            <?php 
            $isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
            $isRootIndex = $currentPage === 'index.php' && !$isAdminPage;
            ?>
            <a href="<?= $homePath ?? 'index.php' ?>" class="<?= $isRootIndex ? 'active' : '' ?>">Home</a>
            <a href="<?= ($homePath ?? '') ?>leaderboard.php" class="<?= $currentPage === 'leaderboard.php' ? 'active' : '' ?>">Leaderboard</a>
            <?php if (isAdmin()): ?>
                <a href="<?= ($homePath ?? '') ?>admin/index.php" class="<?= $isAdminPage ? 'active' : '' ?>">Admin Panel</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <a href="<?= ($homePath ?? '') ?>profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    👤 <?= sanitize($_SESSION['username']) ?>
                </a>
                <a href="<?= ($homePath ?? '') ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= ($homePath ?? '') ?>login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="<?= ($homePath ?? '') ?>register.php" class="<?= $currentPage === 'register.php' ? 'active' : '' ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main-content">
<div class="container">

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" role="alert">
        <?= sanitize($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
<?php endif; ?>

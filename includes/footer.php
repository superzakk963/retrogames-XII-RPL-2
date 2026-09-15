<?php
// includes/footer.php
?>
</div><!-- /container -->
</main><!-- /main-content -->

<footer id="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> RetroGames. All rights reserved.</p>
        <p>
            <a href="<?= $homePath ?? '' ?>index.php">Home</a> |
            <a href="<?= $homePath ?? '' ?>leaderboard.php">Leaderboard</a> |
            <a href="<?= $homePath ?? '' ?>about.php">About</a>
        </p>
    </div>
</footer>

<?php
// Cache-busting: paksa browser ambil main.js baru setelah merge (fix tombol Preview yang "mati" karena cache lama).
// Resolve href relatif terhadap direktori entry script, bukan includes/ —
// lihat komentar yang sama di includes/header.php.
$jsSrc = $jsPath ?? 'assets/main.js';
$entryDir = dirname($_SERVER['SCRIPT_FILENAME'] ?? (__DIR__ . '/index.php'));
if (is_file($entryDir . '/' . $jsSrc)) {
    $jsSrc .= '?v=' . filemtime($entryDir . '/' . $jsSrc);
}
?>
<script src="<?= $jsSrc ?>"></script>
</body>
</html>

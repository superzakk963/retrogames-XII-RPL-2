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
$jsSrc = $jsPath ?? 'assets/main.js';
$jsFile = __DIR__ . '/../' . ltrim($jsSrc, '/');
if (is_file($jsFile)) $jsSrc .= '?v=' . filemtime($jsFile);
?>
<script src="<?= $jsSrc ?>"></script>
</body>
</html>

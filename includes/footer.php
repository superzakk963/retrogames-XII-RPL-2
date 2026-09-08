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

<script src="<?= $jsPath ?? 'assets/main.js' ?>"></script>
</body>
</html>

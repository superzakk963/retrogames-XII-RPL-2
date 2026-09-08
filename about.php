<?php
require_once 'includes/auth.php';
startSession();
$pageTitle = 'About';
include 'includes/header.php';
?>
<section id="about-section">
    <h2>About RetroGames</h2>
    <p>RetroGames is a collection of classic arcade games playable in your browser. Relive the golden age of gaming with Snake, Pac-Man, Car Racing, Flappy Bird, Ping Pong, and Tetris.</p>

    <h3>Games Available</h3>
    <ul>
        <li><strong>🐍 Snake</strong> – Grow your snake by eating food. Avoid walls and yourself!</li>
        <li><strong>👾 Pac-Man</strong> – Eat all dots while avoiding ghosts in the maze.</li>
        <li><strong>🚗 Car Racing</strong> – Dodge traffic and survive as long as possible.</li>
        <li><strong>🐦 Flappy Bird</strong> – Tap to fly through pipes without crashing.</li>
        <li><strong>🏓 Ping Pong</strong> – Classic table tennis, 1P vs CPU or 2P local.</li>
        <li><strong>🧩 Tetris</strong> – Stack falling blocks to clear lines.</li>
    </ul>

    <h3>Features</h3>
    <ul>
        <li>User accounts with registration and login</li>
        <li>Score tracking and leaderboards</li>
        <li>Personal profile and stats</li>
        <li>Admin panel for full management</li>
    </ul>
</section>
<?php include 'includes/footer.php'; ?>

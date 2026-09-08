<?php
require_once 'includes/auth.php';
startSession();

// Regenerate ID before destroying to prevent session fixation
session_regenerate_id(true);
$_SESSION = [];
session_destroy();

// Restart a fresh session so we can flash the goodbye message
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out successfully.'];

header('Location: index.php');
exit;

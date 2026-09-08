<?php
require_once 'includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login';
$errors = [];
$formData = ['login' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $loginInput = trim($_POST['login'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($loginInput)) $errors[] = 'Username or email is required.';
        if (empty($password))   $errors[] = 'Password is required.';

        if (empty($errors)) {
            if (login($loginInput, $password)) {
                $redirect = $_GET['redirect'] ?? 'index.php';
                // Security: only allow relative redirects
                if (strpos($redirect, '//') !== false || strpos($redirect, ':') !== false) {
                    $redirect = 'index.php';
                }
                setFlash('success', 'Welcome back, ' . sanitize($_SESSION['username']) . '!');
                header("Location: $redirect");
                exit;
            } else {
                $errors[] = 'Invalid username/email or password.';
                $formData['login'] = $loginInput;
            }
        } else {
            $formData['login'] = $loginInput;
        }
    }
}

include 'includes/header.php';
?>

<section id="auth-section">
    <div class="auth-card">
        <h2>Login to RetroGames</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login"
                       value="<?= sanitize($formData['login']) ?>"
                       placeholder="Enter username or email"
                       required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="Enter password"
                           required autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">👁</button>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </div>
        </form>

        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

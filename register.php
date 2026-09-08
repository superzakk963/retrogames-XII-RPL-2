<?php
require_once 'includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Register';
$errors = [];
$formData = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3-50 characters, letters/numbers/underscore only.';
        }

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        $formData = ['username' => $username, 'email' => $email];

        if (empty($errors)) {
            $result = register($username, $email, $password);
            if ($result['success']) {
                setFlash('success', $result['message']);
                header('Location: login.php');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

include 'includes/header.php';
?>

<section id="auth-section">
    <div class="auth-card">
        <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= sanitize($formData['username']) ?>"
                       placeholder="Choose a username (3-50 chars, letters/numbers/_)"
                       required maxlength="50" autocomplete="username">
                <small>Letters, numbers, underscore. 3-50 characters.</small>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= sanitize($formData['email']) ?>"
                       placeholder="Enter your email"
                       required maxlength="100" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 characters"
                           required minlength="6" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">👁</button>
                </div>
                <div id="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat password"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁</button>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-full">Create Account</button>
            </div>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

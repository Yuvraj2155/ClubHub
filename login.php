<?php
/*
 * login.php
 * Handles user login.
 * - Includes config for DB and session.
 * - Processes login form POST data.
 * - Displays the login form.
 */

// 1. CONFIG & SESSION
require_once 'config.php';

// 2. PAGE PROTECTION
// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 3. BACKEND LOGIC (Handles form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-Side Validation (Req #7)
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        // PHP for Searching Records (Req #2) & Data Sanitization (Req #6)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Web Authentication (Req #4) & Password Security
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Session Management (Req #5)
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // e.g., 'member' or 'superadmin'

            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit;
            
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

// 4. HTML VIEW
// Include the header template
require_once 'header.php';
?>

<!-- This wrapper centers the auth card -->
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Log In</h1>

        <?php
        // Display any errors
        if (!empty($errors)) {
            echo '<ul class="message-box errors">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
        ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <div class="auth-switch">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
</div>

<?php
// Include the footer template
require_once 'footer.php';
?>

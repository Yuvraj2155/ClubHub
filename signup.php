<?php
/*
 * signup.php
 * Handles new user registration.
 * - Includes config for DB and session.
 * - Processes signup form POST data.
 * - Displays the signup form.
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
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username)) $errors[] = 'Username is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    // Check if user already exists
    if (empty($errors)) {
        // Data Sanitization (Req #6) - Use Prepared Statement
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        }
    }

    // If no errors, create the user
    if (empty($errors)) {
        // Password Security (Req #3)
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // PHP for Adding Records (Req #2) & Data Sanitization (Req #6)
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'member')");
        
        try {
            $stmt->execute([$username, $email, $password_hash]);
            
            // Get the new user's ID
            $user_id = $pdo->lastInsertId();
            
            // Session Management (Req #5) - Log them in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'member'; // Default role

            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database error. Could not register user.';
        }
    }
}

// 4. HTML VIEW
// Include the header template
require_once 'header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Create Account</h1>

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

        <form action="signup.php" method="POST">
            <!-- Client-Side Validation (Req #7) -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password (min. 8 characters)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>
        <div class="auth-switch">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<?php
// Include the footer template
require_once 'footer.php';
?>

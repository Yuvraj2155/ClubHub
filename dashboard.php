<?php
/*
 * dashboard.php
 * The main page for logged-in users.
 * - Includes config for DB and session.
 * - Protects the page from non-logged-in users.
 */

// 1. CONFIG & SESSION
require_once 'config.php';

// 2. PAGE PROTECTION (Req #5: Session Management)
// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 3. HTML VIEW
// Include the header template
require_once 'header.php';
?>

<div class="dashboard-card">
    <!-- Welcome message using Session data (Req #5) -->
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    
    <p>You are logged in to the Club Hub dashboard. From here, you will be able to:</p>
    <ul>
        <li><a href="browse_clubs.php">Browse Clubs</a> (Coming soon)</li>
        <li><a href="create_club.php">Create a New Club</a> (Coming soon)</li>
        <li><a href="#">View Your Profile</a> (Coming soon)</li>
    </ul>

    <?php
    // Program Documentation (Req #8)
    // This block demonstrates role-based access control.
    // It checks the session 'role' variable.
    if ($_SESSION['role'] === 'superadmin'):
    ?>
        <div class="super-admin-notice">
            <strong>Super Admin Panel:</strong>
            <a href="superadmin_users.php">Manage All Users</a> | <a href="superadmin_clubs.php">Manage All Clubs</a>
        </div>
    <?php endif; ?>

    <a href="logout.php" class="logout">Log Out</a>
</div>

<?php
// Include the footer template
require_once 'footer.php';
?>

<?php
// This header assumes config.php has already been included
// and $isLoggedIn variable has been set by the page.
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub Platform</title>
    <!-- Link to our new external stylesheet -->
    <link rel="stylesheet" href="../Resources/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <!-- Brand link goes to dashboard if logged in, or login if not -->
        <a class="nav-brand" href="<?php echo $isLoggedIn ? '/dashboard.php' : '/login.php'; ?>">
            Club Hub
        </a>
        <div class="nav-menu">
            <?php if ($isLoggedIn): ?>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a href="/signup.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- This container holds all page content -->
<div class="container">

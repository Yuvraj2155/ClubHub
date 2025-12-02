<?php
// We don't need to include config.php here because the file
// that includes header.php will have already included it.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub</title>
    <!-- We need to ensure the CSS path is correct regardless of which subfolder we are in.
         A simple trick is to check if we are in a subfolder. -->
    <?php
    $path_prefix = "";
    if (basename(getcwd()) == 'club' || basename(getcwd()) == 'events' || basename(getcwd()) == 'posts' || basename(getcwd()) == 'members' || basename(getcwd()) == 'profile') {
        $path_prefix = "../";
    }
    ?>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>Resources/css/style.css">
    <!-- If you don't have style.css in Resources/css, change above to just style.css or where you kept it -->
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $path_prefix; ?>dashboard.php" class="nav-logo">Club Hub</a>
            <ul class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <!-- UPDATED NAV ITEMS -->
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>club/create_club.php" class="nav-link">Create Club</a></li>
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>profile/index.php" class="nav-link">View Profile</a></li>
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>logout.php" class="nav-link nav-link-button">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>login.php" class="nav-link">Login</a></li>
                    <li class="nav-item"><a href="<?php echo $path_prefix; ?>signup.php" class="nav-link nav-link-button">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="main-container">
        <!-- Content from other pages will go here -->
<?php
/*
 * index.php
 * This is the entry point of the application.
 * It prevents the "Directory Listing" view and redirects users.
 */

// Include config to start the session
require_once 'config.php';

// Check if user is logged in using the helper function from config.php
if (isLoggedIn()) {
    // User is logged in -> Go to Dashboard
    header("Location: dashboard.php");
} else {
    // User is NOT logged in -> Go to Login
    header("Location: login.php");
}

exit; // Always call exit after a header redirect
?>
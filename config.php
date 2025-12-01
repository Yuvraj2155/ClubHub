<?php
/*
 * config.php
 *
 * This file handles two critical tasks:
 * 1. Starts the session for user authentication.
 * 2. Connects to the MySQL database using PDO.
 *
 * It will be included at the top of every other PHP file.
 */

// ------------------------------------------------------------------
// 1. SESSION MANAGEMENT (Req #5)
// ------------------------------------------------------------------
// Session must be the very first thing started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// 2. DATABASE CONFIGURATION (Req #1)
// ------------------------------------------------------------------
// --- !!! IMPORTANT !!! ---
// Fill in your MySQL database credentials below.
define('DB_HOST', '127.0.0.1');  // Or 'localhost'
define('DB_NAME', 'club_hub_db'); // The database name you created
define('DB_USER', 'root');        // Your database username
define('DB_PASS', '');            // Your database password
define('DB_CHARSET', 'utf8mb4');

// Establish PDO Database Connection
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Global variables for other files to use
$pdo = null;
$errors = []; // Array to hold validation errors
$messages = []; // Array to hold success messages

try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
     // If connection fails, stop the script and show an error.
     // In a real-world app, you'd log this, not show the user.
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// ------------------------------------------------------------------
// 3. GLOBAL HELPER FUNCTIONS
// ------------------------------------------------------------------
// These are the functions used by other pages to check login status.

// Check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get the current logged-in user's ID (or null if not logged in)
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>
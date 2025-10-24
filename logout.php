<?php
/*
 * logout.php
 * Destroys the user's session and logs them out.
 */

// 1. CONFIG & SESSION
require_once 'config.php';

// 2. SESSION MANAGEMENT (Req #5)
session_unset();
session_destroy();

// 3. REDIRECT
header('Location: login.php');
exit;

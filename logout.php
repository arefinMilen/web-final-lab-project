<?php
/**
 * Logout functionality
 * Destroys session and redirects to home page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store user name for goodbye message
$user_name = $_SESSION['user_name'] ?? 'User';

// Destroy all session data
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for the flash message
session_start();

// Set goodbye message
$_SESSION['flash_message'] = "Goodbye, $user_name! You have been logged out successfully.";
$_SESSION['flash_type'] = 'success';

// Redirect to home page
header("Location: " . (defined('BASE_URL') ? BASE_URL : 'http://localhost/campusmart/') . "index.php");
exit;
?>
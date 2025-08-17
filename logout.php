<?php
/**
 * User Logout Script
 * ------------------
 * This script is responsible for securely logging out a user:
 *  1. Ends the current session.
 *  2. Removes all session variables and cookies.
 *  3. Stores a temporary flash message to confirm logout.
 *  4. Redirects the user back to the homepage.
 */

// Start session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Save the current user's name (for a friendly goodbye message)
// If no name is found in the session, default to "User"
$user_name = $_SESSION['user_name'] ?? 'User';

// Clear all data stored in the session
$_SESSION = array();

// If sessions are stored in cookies, delete the session cookie as well
// This ensures the session cannot be reused
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// Completely destroy the session on the server side
session_destroy();

// Start a new session (used only to show the flash message after logout)
session_start();

// Add a flash message to confirm logout for the user
$_SESSION['flash_message'] = "Goodbye, $user_name! You have been logged out successfully.";
$_SESSION['flash_type'] = 'success';

// Redirect user to the homepage (BASE_URL if defined, otherwise default localhost path)
// This ensures the logout process feels seamless to the user
header("Location: " . (defined('BASE_URL') ? BASE_URL : 'http://localhost/campusmart/') . "index.php");
exit;
?>

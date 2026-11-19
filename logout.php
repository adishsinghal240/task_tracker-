<?php
/**
 * ====================================================================
 * Internship Task Tracker - User Logout (logout.php)
 * ====================================================================
 * Safely destroys the active user session and redirects back to the login page.
 */

// 1. Resume the current session
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session cookie in browser if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destroy the session data on the server
session_destroy();

// 5. Redirect back to the login page
header("Location: login.php");
exit();
?>

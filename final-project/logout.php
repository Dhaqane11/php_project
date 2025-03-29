<?php
// Start session if not already started
session_start();

// For debugging
// echo "Session before: ";
// print_r($_SESSION);

// Clear all session variables
$_SESSION = array();

// If session cookie is used, destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Remove remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

// For debugging
// echo "Session after: ";
// session_start();
// print_r($_SESSION);

// Redirect to login page with absolute path
header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/login.php");
exit;
?> 
<?php
// Initialize session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session global variables
$_SESSION = [];

// 2. Erase the session cookie completely from the client's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, // Expire timestamp set far back into the past
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Terminate and destroy the active server-side session registry file
session_destroy();

// 4. Redirect cleanly back to the portal entry gateway login script
header("Location: login.php");
exit();
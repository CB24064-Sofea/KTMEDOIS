<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1:3307"; 
$user = "root";
$password = "";
$database = "group5"; 

// Connect 
$link = mysqli_connect($host, $user, $password);

if (!$link) {
    $_SESSION['msg'] = "Connection failed: " . mysqli_connect_error();
    $_SESSION['msgClass'] = "alert-error";
    header("Location: manage_committee.php");
    exit();
}

// Select
if (!mysqli_select_db($link, $database)) {
    $_SESSION['msg'] = "Database selection failed: " . mysqli_error($link);
    $_SESSION['msgClass'] = "alert-error";
    header("Location: manage_committee.php");
    mysqli_close($link);
    exit();
}

// PROCESS COMPOSITE DELETE REQUEST
if (isset($_GET['uid']) && isset($_GET['cid'])) {
    // Sanitize both keys to completely block SQL Injection vulnerabilities
    $userID = mysqli_real_escape_string($link, $_GET['uid']);
    $clubID = mysqli_real_escape_string($link, $_GET['cid']);
    
    // Begin database engine transaction safely
    mysqli_begin_transaction($link);

    try {
        // Construct targeted query using the relational keys
        $query = "DELETE FROM club_committee WHERE userID = '$userID' AND clubID = '$clubID'";
        $result = mysqli_query($link, $query);
        
        if ($result && mysqli_affected_rows($link) > 0) {
            // Commit structural mutations if execution returns true 
            mysqli_commit($link);
            $_SESSION['msg'] = "Committee assignment record was deleted successfully!";
            $_SESSION['msgClass'] = "alert-success";
        } else {
            // Rollback changes if matching key pair configuration target didn't exist
            mysqli_rollback($link);
            $_SESSION['msg'] = "No matching committee assignment record found to delete.";
            $_SESSION['msgClass'] = "alert-error";
        }

    } catch (Exception $e) {
        // Safely rollback transactional space if a structural engine anomaly strikes
        mysqli_rollback($link);
        $_SESSION['msg'] = "System Error: Failed to drop committee assignment record: " . mysqli_error($link);
        $_SESSION['msgClass'] = "alert-error";
    }

} else {
    $_SESSION['msg'] = "Invalid access attempt. Student User ID and Club ID targets are required.";
    $_SESSION['msgClass'] = "alert-error";
}

mysqli_close($link);
header("Location: manage_committee.php");
exit();
?>
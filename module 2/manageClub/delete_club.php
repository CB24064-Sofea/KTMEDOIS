<?php
// Start a session to pass success/error messages between pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1:3307"; 
$user = "root";
$password = "";
$database = "group5"; 

// Connect to the database server
$link = mysqli_connect($host, $user, $password);

if (!$link) {
    $_SESSION['msg'] = "Connection failed: " . mysqli_connect_error();
    $_SESSION['msgClass'] = "alert-error";
    header("Location: manage_club.php");
    exit();
}

// Select the database
if (!mysqli_select_db($link, $database)) {
    $_SESSION['msg'] = "Database selection failed: " . mysqli_error($link);
    $_SESSION['msgClass'] = "alert-error";
    header("Location: manage_club.php");
    mysqli_close($link);
    exit();
}

// 2. Delete Request
if (isset($_GET['id'])) {
    // Sanitize the input parameter to prevent SQL Injection
    $clubID = mysqli_real_escape_string($link, $_GET['id']);
    
    // Start a transaction to ensure both deletes happen safely together
    mysqli_begin_transaction($link);

    try {
        // Step A: First delete linked records from the membership table to avoid foreign key errors
        $deleteMembersQuery = "DELETE FROM membership WHERE clubID = '$clubID'";
        mysqli_query($link, $deleteMembersQuery);

        // Step B: Now delete the club from the club table
        $query = "DELETE FROM club WHERE clubID = '$clubID'";
        $result = mysqli_query($link, $query);
        
        if ($result && mysqli_affected_rows($link) > 0) {
            // Commit changes to database if successful
            mysqli_commit($link);
            $_SESSION['msg'] = "Club and its membership records deleted successfully!";
            $_SESSION['msgClass'] = "alert-success";
        } else {
            // Roll back if the club wasn't found
            mysqli_rollback($link);
            $_SESSION['msg'] = "No club found with that ID.";
            $_SESSION['msgClass'] = "alert-error";
        }

    } catch (Exception $e) {
        // Roll back any changes if a database error happens
        mysqli_rollback($link);
        $_SESSION['msg'] = "Failed to delete club: " . mysqli_error($link);
        $_SESSION['msgClass'] = "alert-error";
    }

} else {
    $_SESSION['msg'] = "Invalid access. No Club ID specified.";
    $_SESSION['msgClass'] = "alert-error";
}

mysqli_close($link);
header("Location: manage_club.php");
exit();
?>
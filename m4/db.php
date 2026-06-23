<?php
// =========================================================================
// FILE        : db.php
// MODULE      : Module 4 — Internal Review & Approval Workflow
// DESCRIPTION : Shared database connection file. Included by all Module 4
//               pages using include 'db.php'. Uses MySQLi extension.
//               Change $servername port to 3306 if not using XAMPP default.
// =========================================================================

$servername = "127.0.0.1:3307"; // XAMPP MySQL default port (change to 3306 if needed)
$username   = "root";            // Default XAMPP MySQL username
$password   = "";                // Default XAMPP MySQL password (empty)
$dbname     = "ktm_edois";       // KTMeDOIS project database name

// Create MySQLi connection object
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection failed — stop execution and show error if so
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
// Connection successful — $conn is now available to all files that include this

<?php
// ===== Local Database Configuration =====
$DB_HOST = "localhost";
$DB_PORT = 3307;      // Change to 3306 if needed
$DB_NAME = "ktm_edois";
$DB_USER = "root";
$DB_PASS = "";

// ===== Connect =====
$conn = new mysqli(
    $DB_HOST,
    $DB_USER,
    $DB_PASS,
    $DB_NAME,
    $DB_PORT
);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
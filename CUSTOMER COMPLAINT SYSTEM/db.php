<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "complaint_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Debugging: Check database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

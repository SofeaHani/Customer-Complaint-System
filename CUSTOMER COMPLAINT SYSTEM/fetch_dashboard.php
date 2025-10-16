<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS not_processed,
    COUNT(CASE WHEN status = 'In Process' THEN 1 END) AS in_process,
    COUNT(CASE WHEN status = 'Resolved' THEN 1 END) AS resolved
    FROM complaints WHERE user_id = $user_id";

$result = $conn->query($query);
$complaints = $result->fetch_assoc();

echo json_encode($complaints);

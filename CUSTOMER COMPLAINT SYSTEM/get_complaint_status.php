<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Example: Count all complaints by the logged-in user
$total_query = "SELECT COUNT(*) as total FROM complaints WHERE user_id = $user_id";
$pending_query = "SELECT COUNT(*) as pending FROM complaints WHERE user_id = $user_id AND status = 'Pending'";
$resolved_query = "SELECT COUNT(*) as resolved FROM complaints WHERE user_id = $user_id AND status = 'Resolved'";

$total_result = $conn->query($total_query)->fetch_assoc();
$pending_result = $conn->query($pending_query)->fetch_assoc();
$resolved_result = $conn->query($resolved_query)->fetch_assoc();

echo json_encode([
    'total' => $total_result['total'],
    'pending' => $pending_result['pending'],
    'resolved' => $resolved_result['resolved']
]);

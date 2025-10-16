<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['new_status'])) {
    $complaintId = intval($_POST['complaint_id']);
    $newStatus = $conn->real_escape_string($_POST['new_status']);

    $updateQuery = "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId";
    if ($conn->query($updateQuery)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

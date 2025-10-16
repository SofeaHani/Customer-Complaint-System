<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

// Check if the category ID is provided
if (isset($_GET['id'])) {
    $categoryId = intval($_GET['id']);

    // Delete the category from the database
    $deleteQuery = "DELETE FROM categories WHERE id = $categoryId";

    if ($conn->query($deleteQuery)) {
        echo "<script>alert('Category deleted successfully!');</script>";
    } else {
        echo "<script>alert('Failed to delete category: " . $conn->error . "');</script>";
    }
}

// Redirect back to the addcategory.php page
header("Location: addcategory.php");
exit();

<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('No category ID provided.'); window.location.href = 'addcategory.php';</script>";
    exit();
}

$category_id = intval($_GET['id']);

// Fetch category details
$query = "SELECT * FROM categories WHERE id = $category_id";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "<script>alert('Category not found.'); window.location.href = 'addcategory.php';</script>";
    exit();
}

$category = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryName'], $_POST['description'])) {
    $categoryName = $conn->real_escape_string($_POST['categoryName']);
    $description = $conn->real_escape_string($_POST['description']);

    $update_query = "UPDATE categories SET category = '$categoryName', description = '$description', updated_at = NOW() WHERE id = $category_id";

    if ($conn->query($update_query)) {
        echo "<script>alert('Category updated successfully!'); window.location.href = 'addcategory.php';</script>";
    } else {
        echo "<script>alert('Failed to update category: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Edit Category</h2>
        <form action="editcategory.php?id=<?php echo $category_id; ?>" method="POST">
            <div class="form-group mb-3">
                <label for="categoryName">Category Name</label>
                <input type="text" id="categoryName" name="categoryName" class="form-control" value="<?php echo htmlspecialchars($category['category']); ?>" required>
            </div>
            <div class="form-group mb-3">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($category['description']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="addcategory.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>

</html>
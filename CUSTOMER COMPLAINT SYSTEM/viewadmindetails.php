<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['id'])) {
    die("Admin ID is required.");
}

$admin_id = intval($_GET['id']);
$query = "SELECT * FROM admins WHERE id = $admin_id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("Admin not found.");
}

$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>Admin Details</h2>
        <table class="table table-bordered">
            <tr>
                <th>ID</th>
                <td><?php echo htmlspecialchars($admin['id']); ?></td>
            </tr>
            <tr>
                <th>Name</th>
                <td><?php echo htmlspecialchars($admin['name']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($admin['email']); ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
            </tr>
            <tr>
                <th>Profile Photo</th>
                <td>
                    <img src="<?php echo htmlspecialchars($admin['photo'] ?? 'default_profile.jpg'); ?>" alt="Profile Photo" class="img-fluid" style="max-width: 150px;">
                </td>
            </tr>
        </table>
        <a href="adminreports.php" class="btn btn-secondary">Back to Reports</a>
    </div>
</body>

</html>
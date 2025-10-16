<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    // Validate date inputs
    if (empty($from_date) || empty($to_date)) {
        echo "<script>alert('Both dates are required.'); window.history.back();</script>";
        exit();
    }

    // Fetch complaints within the date range
    $query = "SELECT * FROM complaints WHERE complaint_date BETWEEN '$from_date' AND '$to_date'";
    $result = $conn->query($query);

    if (!$result) {
        die("Query failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Between Date Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h3 class="mb-4">Complaints Between <?php echo htmlspecialchars($from_date); ?> and <?php echo htmlspecialchars($to_date); ?></h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Complainant Name</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['complainant_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['complaint_details']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Resolved'): ?>
                                    <span class="badge bg-success">Resolved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Not processed yet</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['complaint_date']); ?></td>
                            <td>
                                <a href="viewdetails.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No complaints found in the selected date range.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="betweendatereports.php" class="btn btn-secondary">Back</a>
    </div>
</body>

</html>
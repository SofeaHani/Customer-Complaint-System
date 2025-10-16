<?php
session_start(); // Ensure session is started

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminName = $_POST['name']; // Corrected to match form field name
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the admin email already exists
    $query = "SELECT id, name, password, role FROM admins WHERE email = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify password (plaintext comparison) and role
        if ($password === $admin['password'] && $admin['role'] === 'admin') { // Ensure role is 'admin'
            $_SESSION['id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['role'] = $admin['role'];

            // Debugging: Print session variables
            echo "Session ID: " . $_SESSION['id'] . "<br>";
            echo "Session Name: " . $_SESSION['name'] . "<br>";
            echo "Session Role: " . $_SESSION['role'] . "<br>";

            $_SESSION['admin_id'] = $admin['id']; // Ensure this is set correctly
            error_log("Admin ID session set: " . $_SESSION['admin_id']); // Debugging

            header("Location: adminpage.php"); // Redirect to admin page
            exit();
        } else {
            $error = "Invalid email, password, or role.";
            error_log("Login failed for admin.");
        }
    } else {
        // Insert new admin details into the database with default role 'admin'
        $insertQuery = "INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, 'admin')";
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            die("Query preparation failed: " . $conn->error);
        }
        $insertStmt->bind_param("sss", $adminName, $email, $password); // Store plaintext password
        if ($insertStmt->execute()) {
            $_SESSION['id'] = $conn->insert_id; // Get the last inserted ID
            $_SESSION['name'] = $adminName;
            $_SESSION['role'] = 'admin';

            // Debugging: Print session variables
            echo "Session ID: " . $_SESSION['id'] . "<br>";
            echo "Session Name: " . $_SESSION['name'] . "<br>";
            echo "Session Role: " . $_SESSION['role'] . "<br>";

            $_SESSION['admin_id'] = $conn->insert_id; // Ensure this is set correctly
            error_log("Admin ID session set: " . $_SESSION['admin_id']); // Debugging

            header("Location: adminpage.php"); // Redirect to admin page
            exit();
        } else {
            $error = "Failed to insert new admin: " . $insertStmt->error;
            error_log("Login failed for admin.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: rgba(21, 18, 120, 0.92);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card {
            background-color: rgb(176, 221, 255);
            width: 400px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
        }

        .form-control {
            margin-bottom: 15px;
            border-radius: 10px;
        }

        .btn-primary {
            width: 100%;
            border-radius: 10px;
            background-color: rgb(255, 132, 66);
            border: none;
        }

        .btn-primary:hover {
            background-color: rgb(141, 0, 49);
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-header h2 {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-header p {
            color: #666;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="form-header">
            <h2>Admin Login</h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" class="form-control" placeholder="Admin Name" required>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>

</html>
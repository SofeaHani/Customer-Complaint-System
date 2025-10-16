<?php
session_start(); // Ensure session is started

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if user is not logged in
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    // If user not found, redirect to login
    header("Location: login.php");
    exit();
}

// Ensure all complaints for the user have a default status of "Pending"
$update_status_query = "UPDATE complaints SET status = 'Pending' WHERE user_id = $user_id AND (status IS NULL OR status = '')";
$conn->query($update_status_query);

// Fetch complaints for the logged-in user
$query = "SELECT * FROM complaints WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($query);

$complaints = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f2f4f8;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: #fff;
            padding: 20px;
            position: fixed;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar img {
            width: 140px;
            border-radius: 50%;
            display: block;
            margin: 20px auto;
            height: 140px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar h6 {
            text-align: center;
            color: #4a4a6a;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            margin: 8px 0;
            text-decoration: none;
            color: #4a4a6a;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: rgb(255, 211, 228);
            color: #d63384;
        }

        .sidebar a i {
            margin-right: 12px;
        }

        .sidebar a i.fa-home {
            color: #d63384;
        }

        .sidebar a i.fa-user {
            color: #d63384;
            margin-right: 10px;
            /* Dark Pink Color */
        }

        .sidebar a i.fa-edit {
            color: #d63384;
            margin-right: 10px;
            /* Dark Pink Color */
        }

        .sidebar a i.fa-history {
            color: #d63384;
            margin-right: 10px;
            /* Dark Pink Color */
        }

        .sidebar a i.fa-sign-out-alt {
            margin-right: 10px;
        }

        .content {
            margin-left: 300px;
            padding: 40px;
            font-family: 'Inter', sans-serif;
            /* Match the sidebar font */
            color: #4a4a6a;
            /* Match the sidebar text color */
        }

        .content h2 {
            font-weight: 700;
            /* Match the sidebar font weight */
            color: #2c3e50;
            /* Slightly darker color for headings */
            margin-top: 10px;
            /* Reduce margin-top to move the title closer to the top */
            margin-bottom: 20px;
            /* Add margin-bottom to create space */
        }

        .form-control,
        .btn {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            /* Match the sidebar font */
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .btn-view-details {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-view-details:hover {
            background-color: #0056b3;
        }

        .btn-status {
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            color: #ffffff;
        }

        .btn-status.not-processed {
            background-color: #dc3545;
        }

        .btn-status.processed {
            background-color: #28a745;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .sidebar {
                width: 220px;
            }

            .content {
                margin-left: 220px;
            }

            .sidebar img {
                width: 80px;
                height: 80px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
            }

            .sidebar img {
                width: 60px;
                height: 60px;
            }

            .sidebar a {
                padding: 10px;
            }

            .sidebar h6 {
                font-size: 1rem;
            }

            .card {
                margin-bottom: 15px;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }

        /* Mobile Navigation Toggle */
        .mobile-nav-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: block;
                position: fixed;
                right: 15px;
                top: 15px;
                z-index: 1001;
                border: 0;
                background: transparent;
                font-size: 24px;
                transition: all 0.4s;
                outline: none;
                cursor: pointer;
                padding: 0;
                color: #d63384;
            }

            .sidebar {
                left: -100%;
                position: fixed;
                height: 100vh;
                transition: 0.3s;
            }

            .sidebar.active {
                left: 0;
            }
        }
    </style>
</head>

<body>

    <button class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <img src="<?php echo isset($user['photo']) && !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'https://via.placeholder.com/120'; ?>" alt="Profile Photo">
        <h6><?php echo htmlspecialchars($user['name']); ?></h6>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="account.php"><i class="fas fa-user"></i> Account Settings</a>
        <a href="addcomplaint.php"><i class="fas fa-edit"></i> Lodge Complaint</a>
        <a href="complainthistory.php"><i class="fas fa-history"></i> Complaint History</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <h2>Complaint History</h2>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Complaint Number</th>
                        <th>Complaint Start Date</th>
                        <th>Complaint Last Update Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($complaint['id']); ?></td>
                            <td><?php echo htmlspecialchars($complaint['complaint_date']); ?></td>
                            <td><?php echo htmlspecialchars($complaint['updated_at'] ? $complaint['updated_at'] : ''); ?></td>
                            <td>
                                <button class="btn btn-status <?php echo $complaint['status'] == 'Not Processed' ? 'not-processed' : 'processed'; ?>">
                                    <?php echo htmlspecialchars($complaint['status']); ?>
                                </button>
                            </td>
                            <td>
                                <a href="viewcomplaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-view-details">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Mobile navigation toggle functionality
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const sidebar = document.querySelector('.sidebar');

        mobileNavToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !mobileNavToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileNavToggle.querySelector('i').classList.add('fa-bars');
                mobileNavToggle.querySelector('i').classList.remove('fa-times');
            }
        });
    </script>
</body>

</html>
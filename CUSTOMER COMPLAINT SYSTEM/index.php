<?php
session_start(); // Ensure session is started

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch complaint counts
$user_id = $_SESSION['user_id'];

// Check if the logged-in user is an admin
$check_admin_query = "SELECT * FROM admins WHERE id = $user_id";
$check_admin_result = $conn->query($check_admin_query);

if ($check_admin_result->num_rows == 0) {
    // If not an admin, check if the user is in the users table and insert if not already present
    $check_user_query = "SELECT * FROM users WHERE id = $user_id";
    $check_user_result = $conn->query($check_user_query);

    if ($check_user_result->num_rows == 0) {
        $insert_user_query = "INSERT INTO users (id, role, created_at) VALUES ($user_id, 'user', NOW())";
        $conn->query($insert_user_query);
    }
} else {
    // If the user is an admin, ensure they are in the admins table
    if ($check_admin_result->num_rows == 0) {
        $insert_admin_query = "INSERT INTO admins (id, created_at) VALUES ($user_id, NOW())";
        $conn->query($insert_admin_query);
    }
}

$query = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS not_processed,
    COUNT(CASE WHEN status = 'In Process' THEN 1 END) AS in_process,
    COUNT(CASE WHEN status = 'Resolved' THEN 1 END) AS resolved
    FROM complaints WHERE user_id = $user_id";

$result = $conn->query($query);
$complaints = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f2f4f8;
            font-family: Inter, sans-serif;
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
            width: 120px;
            border-radius: 50%;
            display: block;
            margin: 20px auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar h6 {
            text-align: center;
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            text-decoration: none;
            color: #4a4a6a;
            border-radius: 10px;
            transition: 0.3s;
            font-weight: 500;
        }

        .sidebar a:hover {
            background-color: #f0d3e2;
            color: #d63384;
        }

        .sidebar a i {
            margin-right: 10px;
            color: #d63384;
        }

        .content {
            margin-left: 300px;
            padding: 40px;
            transition: all 0.3s ease;
        }

        .content h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
            padding: 10px;
        }

        .card {
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            color: #fff;
            border: none;
            transition: transform 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .card i {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-purple {
            background-color: #9b59b6;
        }

        .card-blue {
            background-color: #4a90e2;
        }

        .card-orange {
            background-color: #f5a623;
        }

        .card-green {
            background-color: #4caf50;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 991px) {
            .sidebar {
                width: 220px;
            }

            .content {
                margin-left: 220px;
            }

            .sidebar img {
                width: 60px;
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

            .col-md-3 {
                flex: 0 0 100%;
                max-width: 100%;
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
        }

        /* Navigation menu for mobile */
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

        /* Add styles for the active link */
        .sidebar a.active {
            background-color: #d63384;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="strateq.jpeg" alt="Profile">
        <h6>CUSTOMER COMPLAINT SYSTEM</h6>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="account.php"><i class="fas fa-user"></i> Account Settings </a>
        <a href="addcomplaint.php"><i class="fas fa-edit"></i> Lodge Complaint</a>
        <a href="complainthistory.php"><i class="fas fa-history"></i> Complaint History</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2>Dashboard</h2>
        <div class="row">
            <!-- Total Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-purple">
                    <i class="fas fa-folder-open"></i>
                    <h3><?php echo $complaints['total_complaints']; ?></h3>
                    <p>Total Complaints</p>
                </div>
            </div>
            <!-- Not Processed Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-blue">
                    <i class="fas fa-file-alt"></i>
                    <h3><?php echo $complaints['not_processed']; ?> / <?php echo $complaints['total_complaints']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <!-- In Process Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-orange">
                    <i class="fas fa-spinner"></i>
                    <h3><?php echo $complaints['in_process']; ?> / <?php echo $complaints['total_complaints']; ?></h3>
                    <p>In Process</p>
                </div>
            </div>
            <!-- Closed Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-green">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $complaints['resolved']; ?> / <?php echo $complaints['total_complaints']; ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add mobile navigation toggle
        document.body.insertAdjacentHTML('afterbegin',
            '<button class="mobile-nav-toggle">' +
            '<i class="fas fa-bars"></i>' +
            '</button>'
        );

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

        // 1 minute time interval
        // Refresh page every 60,000 milliseconds (1 minute)
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>

</html>
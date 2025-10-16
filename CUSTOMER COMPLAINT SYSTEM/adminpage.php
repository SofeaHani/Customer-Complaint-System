<?php
session_start();

// Debugging: Check if session is set
if (!isset($_SESSION['admin_id'])) {
    error_log("Admin ID session not set. Redirecting to login.");
    header("Location: adminlogin.php");
    exit();
}

// Include database connection
include 'db.php';

// Debugging: Check database connection
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed.");
}

// Fetch complaint counts
$query = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'In Process' THEN 1 END) AS in_process,
    COUNT(CASE WHEN status = 'Resolved' THEN 1 END) AS resolved
    FROM complaints";

$result = $conn->query($query);

// Debugging: Check query execution
if (!$result) {
    error_log("Query failed: " . $conn->error);
    die("Failed to fetch complaints data.");
}

$complaints = $result->fetch_assoc();

// Fetch admin name from session
$adminName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Fetch admin profile details
$adminId = $_SESSION['admin_id'];
$profileQuery = "SELECT photo FROM admins WHERE id = ?";
$stmt = $conn->prepare($profileQuery);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$profileResult = $stmt->get_result();
$profileData = $profileResult->fetch_assoc();
$profilePic = isset($profileData['photo']) && !empty($profileData['photo']) ? $profileData['photo'] : 'default_profile.jpg';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            overflow-y: auto;
            /* Enable vertical scrolling */
        }

        .sidebar img {
            width: 160px;
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

        .sidebar .submenu a {
            padding-left: 25px;
            /* Adjusted padding for better indentation */
            margin-bottom: 18px;
            /* Increased spacing between submenu items */
            display: flex;
            align-items: center;
        }

        .submenu a i {
            margin-right: 12px;
            /* Adjusted spacing between icon and text */
        }

        .submenu {
            display: none;
        }

        .submenu.active {
            display: block;
        }

        .submenu-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Space between text and dropdown icon */
            padding: 15px;
            /* Add padding for better spacing */
        }

        .submenu-toggle i:last-child {
            margin-left: auto;
            /* Push the dropdown icon to the far right */
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

        .card-red {
            background-color: #e74c3c;
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
                width: 100px;
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

        .sidebar h6 {
            text-align: center;
            color: #333;
            font-weight: 700;
            margin-top: -10px;
            /* Adjust spacing */
            font-family: inherit;
            /* Use the same font as admin name */
        }
    </style>
</head>

<body>
    <button class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Admin Profile"> <!-- Fetch profile picture from database -->
        <h6 style="margin-top: 20px;"><?php echo htmlspecialchars($adminName); ?></h6> <!-- Display admin name -->
        <h6>Admin</h6>
        <a href="adminpage.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="accountadmin.php"><i class="fas fa-user"></i> Account Settings</a>
        <a href="#manage-complaint" class="submenu-toggle"><i class="fas fa-cogs"></i> Manage Complaint <i class="fas fa-chevron-down"></i></a>
        <div id="manage-complaint" class="submenu">
            <a href="pending.php"><i class="fas fa-exclamation-circle"></i> Pending Complaint <span class="badge bg-danger"><?php echo $complaints['pending']; ?></span></a>
            <a href="inprocess.php"><i class="fas fa-spinner"></i> In Process Complaint <span class="badge bg-warning"><?php echo $complaints['in_process']; ?></span></a>
            <a href="resolved.php"><i class="fas fa-check-circle"></i> Resolved Complaints <span class="badge bg-success"><?php echo $complaints['resolved']; ?></span></a>
        </div>
        <a href="addcategory.php"><i class="fas fa-list"></i> Add Category</a>
        <a href="#reports-section" class="submenu-toggle"><i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down"></i></a>
        <div id="reports-section" class="submenu">
            <a href="betweendatereports.php"><i class="fas fa-calendar-alt"></i> Complaint Between Date Reports</a>
            <a href="adminreports.php"><i class="fas fa-user-shield"></i> Registered Admin Between Date Reports</a> <!-- Updated icon -->
        </div>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <h2>Admin Dashboard</h2>
        <div class="row">
            <!-- Total Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-blue">
                    <i class="fas fa-folder-open"></i>
                    <h3><?php echo $complaints['total_complaints']; ?></h3>
                    <p>Total</p>
                </div>
            </div>
            <!-- Not Processed Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-orange">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3><?php echo $complaints['pending']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <!-- In Process Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-green">
                    <i class="fas fa-spinner"></i>
                    <h3><?php echo $complaints['in_process']; ?></h3>
                    <p>In Process</p>
                </div>
            </div>

            <!-- Closed Complaints Card -->
            <div class="col-md-3 mb-4">
                <div class="card card-purple">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $complaints['resolved']; ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile navigation toggle functionality
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const sidebar = document.querySelector('.sidebar');
        const submenuToggle = document.querySelector('.submenu-toggle');
        const submenu = document.querySelector('.submenu');

        mobileNavToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });

        submenuToggle.addEventListener('click', function() {
            submenu.classList.toggle('active');
            this.querySelector('i.fa-chevron-down').classList.toggle('fa-chevron-up');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !mobileNavToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileNavToggle.querySelector('i').classList.add('fa-bars');
                mobileNavToggle.querySelector('i').classList.remove('fa-times');
            }
        });

        const reportsToggle = document.querySelector('a[href="#reports-section"]');
        const reportsSubmenu = document.querySelector('#reports-section');

        reportsToggle.addEventListener('click', function() {
            reportsSubmenu.classList.toggle('active');
            this.querySelector('i.fa-chevron-down').classList.toggle('fa-chevron-up');
        });
    </script>
</body>

</html>
<?php
session_start(); // Ensure session is started

// Fetch admin name from session
$adminName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

if (!isset($_SESSION['admin_id'])) {
    // Redirect to login if admin is not logged in
    header("Location: login.php");
    exit();
}

// Include database connection
include 'db.php';

// Fetch admin details
$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admins WHERE id = $admin_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    // If admin not found, redirect to login
    header("Location: login.php");
    exit();
}

$profilePic = isset($admin['photo']) ? $admin['photo'] : 'default_profile.jpg'; // Fetch profile photo

// Handle profile updates and photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? $_POST['name'] : $admin['name']; // Use existing name if not provided
    $email = isset($_POST['email']) ? $_POST['email'] : $admin['email']; // Use existing email if not provided

    // Update admin information in the database
    $update_query = "UPDATE admins SET name = '$name', email = '$email' WHERE id = $admin_id";
    if ($conn->query($update_query)) {
        // Update session variables
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
    }

    // Handle profile photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_name = basename($_FILES['photo']['name']);
        $photo_tmp_name = $_FILES['photo']['tmp_name'];
        $photo_folder = 'uploads/' . $photo_name;

        // Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2 MB

        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_file_size) {
            if (move_uploaded_file($photo_tmp_name, $photo_folder)) {
                // Update the photo path in the database
                $update_photo_query = "UPDATE admins SET photo = '$photo_folder' WHERE id = $admin_id";
                if ($conn->query($update_photo_query)) {
                    // Update session variable for photo
                    $_SESSION['photo'] = $photo_folder;
                    $admin['photo'] = $photo_folder; // Refresh the photo path in the $admin array
                } else {
                    echo "<script>alert('Failed to update the photo in the database.');</script>";
                }
            } else {
                echo "<script>alert('Failed to upload the photo. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type or file size exceeds 2 MB.');</script>";
        }
    }

    // Refresh admin data after update
    $result = $conn->query($query);
    $admin = $result->fetch_assoc();
    $_SESSION['name'] = $admin['name']; // Ensure session name is updated
    $_SESSION['email'] = $admin['email']; // Ensure session email is updated
    $_SESSION['photo'] = $admin['photo']; // Ensure session photo is updated
}

// Fetch complaint status counts
$query = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'In Process' THEN 1 END) AS in_process,
    COUNT(CASE WHEN status = 'Resolved' THEN 1 END) AS resolved
    FROM complaints";

$result = $conn->query($query);

if (!$result) {
    error_log("Query failed: " . $conn->error);
    die("Failed to fetch complaints data.");
}

$complaints = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_date'], $_POST['to_date'])) {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    // Fetch admin details between the selected dates
    $query = "SELECT id, name, email, created_at FROM admins WHERE DATE(created_at) BETWEEN '$from_date' AND '$to_date'";
    $result = $conn->query($query);

    if (!$result) {
        error_log("Query failed: " . $conn->error);
        die("Failed to fetch admin data.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Settings</title>
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
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
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
            color: #4a4a6a;
            margin-bottom: 20px;
            font-weight: 700;
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
            padding-left: 30px;
        }

        .submenu {
            display: none;
        }

        .submenu.active {
            display: block;
        }


        .content {
            margin-left: 300px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .card {
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background-color: #fff;
        }

        .form-label {
            font-weight: 500;
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

        .sidebar h6 {
            text-align: center;
            color: #333;
            font-weight: 700;
            margin-top: -10px;
            /* Adjust spacing */
            font-family: inherit;
            /* Use the same font as admin name */
        }

        .submenu-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .submenu-toggle i.fa-chevron-down {
            margin-left: auto;
        }
    </style>
</head>

<body>
    <button class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Admin Profile" class="img-fluid rounded-circle"> <!-- Display updated profile photo -->
        <h6 style="margin-top: 20px;"><?php echo htmlspecialchars($_SESSION['name']); ?></h6> <!-- Dynamically display admin name -->
        <h6>Admin</h6>
        <a href="adminpage.php" style="margin-bottom: 10px;"><i class="fas fa-home"></i> Dashboard</a>
        <a href="accountadmin.php" style="margin-bottom: 10px;"><i class="fas fa-user"></i> Account Settings</a>
        <a href="#manage-complaint" class="submenu-toggle" style="margin-bottom: 10px;">
            <i class="fas fa-cogs"></i> Manage Complaint
            <i class="fas fa-chevron-down"></i>
        </a>

        <div id="manage-complaint" class="submenu" style="margin-bottom: 10px;">
            <a href="pending.php" style="margin-bottom: 5px;"><i class="fas fa-exclamation-circle"></i> Pending Complaint <span class="badge bg-danger"><?php echo $complaints['pending']; ?></span></a>
            <a href="inprocess.php" style="margin-bottom: 5px;"><i class="fas fa-spinner"></i> In Process Complaint <span class="badge bg-warning"><?php echo $complaints['in_process']; ?></span></a>
            <a href="resolved.php" style="margin-bottom: 5px;"><i class="fas fa-check-circle"></i> Resolved Complaints <span class="badge bg-success"><?php echo $complaints['resolved']; ?></span></a>
        </div>

        <a href="addcategory.php" style="margin-bottom: 10px;"><i class="fas fa-list"></i> Add Category</a>
        <a href="#reports-section" class="submenu-toggle"><i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down"></i></a>
        <div id="reports-section" class="submenu">
            <a href="betweendatereports.php"><i class="fas fa-calendar-alt"></i> Between Date Reports</a>
            <a href="adminreports.php"><i class="fas fa-user-shield"></i> Registered Admin Reports</a> <!-- Updated icon -->
        </div>
        <a href="logout.php" class="text-danger" style="margin-bottom: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <!-- Add Date Range Form -->
        <div class="card">
            <h5 class="card-title">Register Admin Between Dates Report</h5>
            <form method="POST" action="adminreports.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fromDate" class="form-label">From Date</label>
                        <input type="date" id="fromDate" name="from_date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="toDate" class="form-label">To Date</label>
                        <input type="date" id="toDate" name="to_date" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
        <!-- End Date Range Form -->
        <!-- ...existing content... -->

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($result) && $result->num_rows > 0): ?>

            <div class="card mt-4">
                <h5 class="card-title">Admins Between <?php echo htmlspecialchars($from_date); ?> and <?php echo htmlspecialchars($to_date); ?></h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <a href="viewadmindetails.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-primary btn-sm">View Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>


        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="alert alert-warning mt-4">No admin records found for the selected date range.</div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.style.display = sidebar.style.display === 'block' ? 'none' : 'block';
        }

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
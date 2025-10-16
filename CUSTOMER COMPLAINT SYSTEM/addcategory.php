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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);

    // Update admin information in the database
    $update_query = "UPDATE admins SET name = '$name', email = '$email' WHERE id = $admin_id";
    $conn->query($update_query);

    // Update email in session
    $_SESSION['email'] = $email;

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
                    $_SESSION['photo'] = $photo_folder; // Update session with new photo path
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

// Update 'updated_at' column for resolved complaints
$update_resolved_query = "UPDATE complaints SET updated_at = NOW() WHERE status = 'Resolved'";
if (!$conn->query($update_resolved_query)) {
    error_log("Failed to update 'updated_at' for resolved complaints: " . $conn->error);
}

// Handle form submission for adding a new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoryName'], $_POST['description'])) {
    $categoryName = $conn->real_escape_string($_POST['categoryName']);
    $description = $conn->real_escape_string($_POST['description']);

    // Insert new category into the categories table
    $insert_query = "INSERT INTO categories (category, description, created_at, updated_at) 
                     VALUES ('$categoryName', '$description', NOW(), NOW())";

    if ($conn->query($insert_query)) {
        echo "<script>alert('Category added successfully!');</script>";
    } else {
        echo "<script>alert('Failed to add category: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> <!-- For icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        .main-content {
            margin-left: 300px;
            padding: 30px;
        }

        h2 {
            font-family: Inter, sans-serif;
            /* Match sidebar font */
            color: rgb(25, 25, 28);
            /* Match sidebar font color */
            font-weight: 700;
            /* Match sidebar font weight */
            font-size: 32px;
            /* Increased font size */
            margin-bottom: 30px;
            /* Increased bottom margin */
        }

        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card h5 {
            font-family: Inter, sans-serif;
            color: #4a4a6a;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            font-size: 14px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .form-group textarea {
            resize: none;
            height: 100px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }


        /* Navigation menu for mobile */
        .mobile-nav-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
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
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Admin Profile">
        <h6><?php echo htmlspecialchars($adminName); ?></h6>
        <h6>Admin</h6>
        <a href="adminpage.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="accountadmin.php"><i class="fas fa-user"></i> Account Settings</a>
        <a href="#manage-complaint" class="submenu-toggle" style="margin-bottom: 10px;">
            <i class="fas fa-cogs"></i> Manage Complaint
            <i class="fas fa-chevron-down"></i>
        </a>

        <div id="manage-complaint" class="submenu" style="margin-bottom: 10px;">
            <a href="pending.php" style="margin-bottom: 5px;"><i class="fas fa-exclamation-circle"></i> Pending Complaint <span class="badge bg-danger"><?php echo $complaints['pending']; ?></span></a>
            <a href="inprocess.php" style="margin-bottom: 5px;"><i class="fas fa-spinner"></i> In Process Complaint <span class="badge bg-warning"><?php echo $complaints['in_process']; ?></span></a>
            <a href="resolved.php" style="margin-bottom: 5px;"><i class="fas fa-check-circle"></i> Resolved Complaints <span class="badge bg-success"><?php echo $complaints['resolved']; ?></span></a>
        </div>

        <a href="addcategory.php" class="active"><i class="fas fa-list"></i> Add Category</a>
        <a href="#reports-section" class="submenu-toggle"><i class="fas fa-chart-bar"></i> Reports <i class="fas fa-chevron-down"></i></a>
        <div id="reports-section" class="submenu">
            <a href="betweendatereports.php"><i class="fas fa-calendar-alt"></i> Complaint Between Date Reports</a>
            <a href="adminreports.php"><i class="fas fa-user-shield"></i> Registered Admin Between Date Reports</a> <!-- Updated icon -->
        </div>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h2>Add Category</h2>
        <div class="card">
            <h5>Create New Category</h5>
            <form action="addcategory.php" method="POST">
                <div class="form-group">
                    <label for="categoryName">Category Name</label>
                    <input type="text" id="categoryName" name="categoryName" placeholder="Enter category name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter category description" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Create</button>
            </form>
        </div>

        <!-- New Section: Manage Categories -->
        <div class="card">
            <h5>Manage Categories</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Creation Date</th>
                            <th>Last Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch categories from the categories table
                        $categories_query = "SELECT * FROM categories ORDER BY id ASC";
                        $categories_result = $conn->query($categories_query);

                        if ($categories_result->num_rows > 0) {
                            $counter = 1;
                            while ($row = $categories_result->fetch_assoc()) {
                                $category = isset($row['category']) ? htmlspecialchars($row['category']) : 'N/A';
                                $description = isset($row['description']) ? htmlspecialchars($row['description']) : 'N/A';
                                $created_at = isset($row['created_at']) ? htmlspecialchars($row['created_at']) : 'N/A';
                                $updated_at = isset($row['updated_at']) ? htmlspecialchars($row['updated_at']) : 'N/A';

                                echo "<tr>
                                        <td>{$counter}</td>
                                        <td>{$category}</td>
                                        <td>{$description}</td>
                                        <td>{$created_at}</td>
                                        <td>{$updated_at}</td>
                                        <td>
                                            <a href='editcategory.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'><i class='fas fa-edit'></i></a>
                                            <a href='deletecategory.php?id=" . $row['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this category?\");'><i class='fas fa-trash'></i></a>
                                        </td>
                                      </tr>";
                                $counter++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No categories found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
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
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

    // Update the complainant name in the database after login
    $complainant_name = $conn->real_escape_string($user['name']);
    $update_user_query = "UPDATE complaints SET complainant_name = '$complainant_name' WHERE user_id = $user_id";
    $conn->query($update_user_query);
} else {
    // If user not found, redirect to login
    header("Location: login.php");
    exit();
}

// Fetch complaint details for the logged-in user
$complaint_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = "SELECT complaints.*, users.name AS complainant_name 
          FROM complaints 
          INNER JOIN users ON complaints.user_id = users.id 
          WHERE complaints.id = $complaint_id AND complaints.user_id = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $complaint = $result->fetch_assoc();
} else {
    // If complaint not found or does not belong to the user, redirect to complaint history
    header("Location: complainthistory.php");
    exit();
}

// Fetch categories from the database
$categories_query = "SELECT category FROM categories";
$categories_result = $conn->query($categories_query);

if (!$categories_result) {
    echo "Error fetching categories: " . $conn->error;
    exit();
}

// Handle complaint update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint'])) {
    $category = $conn->real_escape_string($_POST['category']);
    $sub_category = $conn->real_escape_string($_POST['sub_category']);
    $state = $conn->real_escape_string($_POST['state']);
    $nature_of_complaint = $conn->real_escape_string($_POST['nature_of_complaint']);
    $complaint_details = $conn->real_escape_string($_POST['complaint_details']);
    $complaint_type = $conn->real_escape_string($_POST['complaint_type']); // Ensure complaint type is captured

    // Handle file upload
    $file_path = $complaint['complaint_document']; // Default to existing file
    if (isset($_FILES['complaint_document']) && $_FILES['complaint_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['complaint_document']['name']);
        $file_path = $upload_dir . time() . '_' . $file_name;

        if (!move_uploaded_file($_FILES['complaint_document']['tmp_name'], $file_path)) {
            echo "Error uploading file.";
            exit();
        }
    }

    $update_query = "UPDATE complaints 
                     SET category = '$category', 
                         sub_category = '$sub_category', 
                         state = '$state', 
                         nature_of_complaint = '$nature_of_complaint', 
                         complaint_details = '$complaint_details', 
                         complaint_type = '$complaint_type', 
                         complaint_document = '$file_path', 
                         updated_at = NOW() 
                     WHERE id = $complaint_id AND user_id = $user_id";

    if ($conn->query($update_query)) {
        header("Location: viewcomplaint.php?id=$complaint_id");
        exit();
    } else {
        echo "Error updating complaint: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Details</title>
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
        <h2><a href="complainthistory.php" style="text-decoration: none; color: inherit;"><i class="fas fa-arrow-left"></i></a> Complaint Details</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <table class="table table-bordered">
                <tr>
                    <th>Complaint Number</th>
                    <td><?php echo htmlspecialchars($complaint['id']); ?></td>
                    <th>Complainant Name</th>
                    <td><?php echo htmlspecialchars($complaint['complainant_name']); ?></td> <!-- Use the synchronized name -->
                </tr>
                <tr>
                    <th>Category</th>
                    <td>
                        <select name="category" class="form-control" required>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($category['category']); ?>"
                                    <?php echo $complaint['category'] == $category['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </td>
                    <th>SubCategory</th>
                    <td>
                        <select name="sub_category" class="form-control" required>
                            <option value="Out of Warranty" <?php echo $complaint['sub_category'] == 'Out of Warranty' ? 'selected' : ''; ?>>Out of Warranty</option>
                            <option value="In Warranty" <?php echo $complaint['sub_category'] == 'In Warranty' ? 'selected' : ''; ?>>In Warranty</option>

                        </select>
                    </td>
                </tr>
                <tr>
                    <th>State</th>
                    <td>
                        <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($complaint['state']); ?>" required>
                    </td>
                    <th>Nature of Complaint</th>
                    <td>
                        <input type="text" name="nature_of_complaint" class="form-control" value="<?php echo htmlspecialchars($complaint['nature_of_complaint']); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th>Complaint Details</th>
                    <td colspan="3">
                        <textarea name="complaint_details" class="form-control" rows="4" required><?php echo htmlspecialchars($complaint['complaint_details']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th>Complaint Type</th>
                    <td colspan="3">
                        <select name="complaint_type" class="form-control" required>
                            <option value="General Query" <?php echo $complaint['complaint_type'] == 'General' ? 'selected' : ''; ?>>General Query</option>
                            <option value="Technical Issue" <?php echo $complaint['complaint_type'] == 'Urgent' ? 'selected' : ''; ?>>Technical Issue</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>File (if any)</th>
                    <td colspan="3">
                        <?php if ($complaint['complaint_document']): ?>
                            <a href="<?php echo htmlspecialchars($complaint['complaint_document']); ?>" target="_blank">View File</a>
                        <?php else: ?>
                            No file uploaded
                        <?php endif; ?>
                        <input type="file" name="complaint_document" class="form-control mt-2">
                    </td>
                </tr>
                <tr>
                    <th>Complaint Start Date</th>
                    <td><?php echo htmlspecialchars($complaint['complaint_date']); ?></td>
                    <th>Complaint Last Update Date</th>
                    <td><?php echo htmlspecialchars($complaint['updated_at']); ?></td>
                </tr>
                <tr>
                    <th>Current Status</th>
                    <td colspan="3">
                        <button class="btn btn-status <?php echo $complaint['status'] == 'Not Processed' ? 'not-processed' : 'processed'; ?>">
                            <?php echo htmlspecialchars($complaint['status']); ?>
                        </button>
                    </td>
                </tr>
            </table>
            <button type="submit" name="update_complaint" class="btn btn-primary">Update</button>
        </form>
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
<?php
// Include database connection
include 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$query = "SELECT name, photo FROM users WHERE id = $user_id";
$result = $conn->query($query);
$user = $result->fetch_assoc();

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    if ($_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $photo_name = $_FILES['profile_photo']['name'];
        $photo_tmp_name = $_FILES['profile_photo']['tmp_name'];
        $photo_folder = 'uploads/' . $photo_name;

        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($photo_tmp_name, $photo_folder)) {
            // Update the user's photo in the database
            $update_photo_query = "UPDATE users SET photo = '$photo_folder' WHERE id = $user_id";
            $conn->query($update_photo_query);

            // Update the user's photo in the session
            $user['photo'] = $photo_folder;
        }
    }
}

// Initialize success message variable
$success_message = '';

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'])) {
    $category = $_POST['category'];
    $sub_category = $_POST['sub_category'];
    $complaint_type = $_POST['complaint_type'];
    $state = $_POST['state'];
    $nature_of_complaint = $_POST['nature_of_complaint'];
    $complaint_details = $_POST['complaint_details'];
    $complaint_date = $_POST['complaint_date'];
    $complaint_document = '';
    $complainant_name = $user['name']; // Fetch the logged-in user's name

    // Reset AUTO_INCREMENT to 1 if the table is empty
    $check_complaints_query = "SELECT COUNT(*) AS total FROM complaints";
    $check_result = $conn->query($check_complaints_query);
    $row = $check_result->fetch_assoc();
    if ($row['total'] == 0) {
        $reset_auto_increment_query = "ALTER TABLE complaints AUTO_INCREMENT = 1";
        $conn->query($reset_auto_increment_query);
    }

    // Handle complaint document upload
    if (isset($_FILES['complaint_document']) && $_FILES['complaint_document']['error'] === UPLOAD_ERR_OK) {
        $document_name = $_FILES['complaint_document']['name'];
        $document_tmp_name = $_FILES['complaint_document']['tmp_name'];
        $document_folder = 'uploads/' . $document_name;

        // Move the uploaded file to the uploads directory
        if (move_uploaded_file($document_tmp_name, $document_folder)) {
            $complaint_document = $document_folder;
        }
    }

    // Insert complaint into the database
    $insert_complaint_query = "INSERT INTO complaints (user_id, complainant_name, category, sub_category, complaint_type, state, nature_of_complaint, complaint_details, complaint_document, complaint_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_complaint_query);
    $stmt->bind_param("isssssssss", $user_id, $complainant_name, $category, $sub_category, $complaint_type, $state, $nature_of_complaint, $complaint_details, $complaint_document, $complaint_date);
    $stmt->execute();
    $complaint_id = $stmt->insert_id; // Get the ID of the inserted complaint
    $stmt->close();

    // Set success message
    $success_message = "Complaint #$complaint_id has been successfully submitted.";
}

// Fetch categories from the database
$categories_query = "SELECT id, category FROM categories"; // Use the correct column name
$categories_result = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
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

        .content h3 {
            font-weight: 700;
            /* Match the sidebar font weight */
            color: #2c3e50;
            /* Slightly darker color for headings */
            margin-top: 10px;
            /* Reduce margin-top to move the title closer to the top */
            margin-bottom: 20px;
            /* Add margin-bottom to create space */
        }

        .content h3 i {
            color: #d63384;
            /* Pink color for the icon */
        }

        .form-control,
        .btn {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            /* Match the sidebar font */
        }

        /* Custom Datepicker Styles */
        .ui-datepicker {
            background: #ffffff;
            border: 1px solid #d63384;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            font-family: 'Inter', sans-serif;
            padding: 10px;
        }

        .ui-datepicker-header {
            background: #d63384;
            color: #ffffff;
            border-bottom: 1px solid #d63384;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }

        .ui-datepicker-title {
            font-weight: bold;
            font-size: 1rem;
        }

        .ui-datepicker-prev,
        .ui-datepicker-next {
            color: #ffffff;
        }

        .ui-datepicker-calendar th {
            color: #d63384;
            font-weight: bold;
        }

        .ui-datepicker-calendar td a {
            color: #333333;
            text-decoration: none;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .ui-datepicker-calendar td a:hover {
            background-color: #f0d3e2;
            color: #d63384;
        }

        .ui-datepicker-calendar td.ui-datepicker-today a {
            background-color: #d63384;
            color: #ffffff;
        }

        .ui-datepicker-calendar td.ui-datepicker-current-day a {
            background-color: #4a90e2;
            color: #ffffff;
        }

        .ui-datepicker-buttonpane {
            background: #ffffff;
            border-top: 1px solid #d63384;
            padding: 10px;
            border-radius: 0 0 8px 8px;
        }

        .ui-datepicker-buttonpane button {
            background: #d63384;
            color: #ffffff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .ui-datepicker-buttonpane button:hover {
            background: #b52a6a;
        }

        /* Custom styles for datepicker input with icon */
        .datepicker-wrapper {
            position: relative;
        }

        .datepicker-wrapper input {
            padding-right: 40px;
        }

        .datepicker-wrapper .datepicker-trigger {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        /* Custom styles for the submit button */
        .btn-pink {
            background-color: #d63384;
            color: #ffffff;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-pink:hover {
            background-color: #b52a6a;
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
        <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile Photo">
        <h6><?php echo htmlspecialchars($user['name']); ?></h6>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="account.php"><i class="fas fa-user"></i> Account Settings</a>
        <a href="addcomplaint.php"><i class="fas fa-edit"></i> Lodge Complaint</a>
        <a href="complainthistory.php"><i class="fas fa-history"></i> Complaint History</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <h3><i class="fas fa-edit" style="margin-right: 10px;"></i>Lodge Complaint</h3>
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Category</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select Category</option>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                        <?php echo htmlspecialchars($category['category']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Sub Category</label>
                            <select class="form-control" name="sub_category" required>
                                <option value="">Select Sub Category</option>
                                <option value="In warranty">In warranty</option>
                                <option value="Out of warranty">Out of warranty</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Complaint Type</label>
                            <select class="form-control" name="complaint_type" required>
                                <option value="">Select Complaint Type</option>
                                <option value="General Query">General Query</option>
                                <option value="Technical Issue">Technical Issue</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>State</label>
                            <input type="text" class="form-control" name="state" placeholder="Enter your state" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Nature of Complaint</label>
                        <input type="text" class="form-control" name="nature_of_complaint" placeholder="Brief description" required>
                    </div>
                    <div class="mb-3">
                        <label>Complaint Details (max 2000 words)</label>
                        <textarea class="form-control" name="complaint_details" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Complaint Related Document (if any)</label>
                        <input type="file" class="form-control" name="complaint_document">
                    </div>
                    <div class="mb-3">
                        <label for="complaint_date">Complaint Date</label>
                        <div class="datepicker-wrapper">
                            <input type="text" id="complaint_date" name="complaint_date" class="form-control" placeholder="Select a date" required>
                            <span class="datepicker-trigger"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-pink">Submit Complaint</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize the date picker
            $("#complaint_date").datepicker({
                dateFormat: "yy-mm-dd", // Format: YYYY-MM-DD
                changeMonth: true,
                changeYear: true,
                yearRange: "1900:2100", // Allow selection of years between 1900 and 2100
                showOtherMonths: true,
                selectOtherMonths: true,
                showButtonPanel: false
            });

            // Trigger the date picker when the custom element is clicked
            $(".datepicker-trigger").click(function() {
                $("#complaint_date").datepicker("show");
            });
        });

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
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
$query = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // If user not found, redirect to login
    header("Location: login.php");
    exit();
}

// Handle profile updates and photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    $pincode = $_POST['pincode'];
    $email = $_POST['email'];

    // Update user information in the database
    $update_query = "UPDATE users 
                     SET name = '$name', contact = '$contact', address = '$address', 
                         state = '$state', country = '$country', pincode = '$pincode', email = '$email' 
                     WHERE id = $user_id";
    $conn->query($update_query);

    // Update email in session
    $_SESSION['email'] = $email;

    // Handle profile photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_name = $_FILES['photo']['name'];
        $photo_tmp_name = $_FILES['photo']['tmp_name'];
        $photo_folder = 'uploads/' . $photo_name;

        if (move_uploaded_file($photo_tmp_name, $photo_folder)) {
            $conn->query("UPDATE users SET photo = '$photo_folder' WHERE id = $user_id");
            $user['photo'] = $photo_folder;
        }
    }

    // Refresh user data after update
    $result = $conn->query($query);
    $user = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
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
            padding: 30px;
            transition: all 0.3s ease;
        }


        .content h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
            padding: 10px;
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
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="<?php echo isset($user['photo']) && !empty($user['photo']) ? $user['photo'] : 'https://via.placeholder.com/120'; ?>" alt="Profile Photo">
        <h6><?php echo $user['name']; ?></h6>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="account.php"><i class="fas fa-user"></i> Account Settings</a>
        <a href="addcomplaint.php"><i class="fas fa-edit"></i> Lodge Complaint</a>
        <a href="complainthistory.php"><i class="fas fa-history"></i> Complaint History</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h3><i class="fas fa-user" style="margin-right: 10px;"></i> Account Settings</h3>

        <!-- Personal Information Section -->
        <section class="card">
            <h5>Personal Information</h5>
            <p style="margin-top: 10px;"><strong>Full Name:</strong> <?php echo $user['name']; ?></p>
            <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
            <p><strong>Contact Number:</strong> <?php echo $user['contact']; ?></p>
            <p><strong>Address:</strong> <?php echo $user['address']; ?></p>
            <p><strong>State:</strong> <?php echo $user['state']; ?></p>
            <p><strong>Pincode:</strong> <?php echo $user['pincode']; ?></p>
            <p><strong>Country:</strong> <?php echo $user['country']; ?></p>
        </section>

        <!-- Update Profile Form Section -->
        <section class="card">
            <h5>Update Profile</h5>
            <form action="account.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label" style="margin-top: 10px;">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?php echo $user['contact']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo $user['address']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?php echo $user['state']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="<?php echo $user['pincode']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="<?php echo $user['country']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" name="photo" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </section>
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
    </script>

</body>

</html>
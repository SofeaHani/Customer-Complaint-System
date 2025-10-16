<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Include database connection
include 'db.php';

// Fetch only in process complaints
$query = "SELECT id, complainant_name, complaint_date, status FROM complaints WHERE status = 'Resolved'";
$result = $conn->query($query);

if (!$result) {
    error_log("Query failed: " . $conn->error);
    die("Failed to fetch in process complaints.");
}

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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['new_status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $conn->real_escape_string($_POST['new_status']);
    $update_query = "UPDATE complaints SET status = '$new_status' WHERE id = $complaint_id";
    $conn->query($update_query);
}

// Fetch complaint status counts
$statusQuery = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending,
    COUNT(CASE WHEN status = 'In Process' THEN 1 END) AS in_process,
    COUNT(CASE WHEN status = 'Resolved' THEN 1 END) AS resolved
    FROM complaints";

$statusResult = $conn->query($statusQuery);

if (!$statusResult) {
    error_log("Status query failed: " . $conn->error);
    die("Failed to fetch complaint status counts.");
}

$complaints = $statusResult->fetch_assoc();

// Fetch counts for dashboard
$count_query = "SELECT status, COUNT(*) as count FROM complaints GROUP BY status";
$count_result = $conn->query($count_query);
$dashboardCounts = [
    'Pending' => 0,
    'In Process' => 0,
    'Resolved' => 0,
    'Other' => 0
];
while ($row = $count_result->fetch_assoc()) {
    if (isset($dashboardCounts[$row['status']])) {
        $dashboardCounts[$row['status']] = $row['count'];
    } else {
        $dashboardCounts['Other'] += $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Pending Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
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
            /* Ensure the sidebar occupies the full height of the viewport */
            background-color: #fff;
            padding: 20px;
            position: fixed;
            top: 0;
            /* Ensure the sidebar starts at the top of the viewport */
            left: 0;
            /* Align the sidebar to the left */
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            /* Enable scrolling if the content exceeds the viewport height */
            text-align: left;
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
            justify-content: flex-start;
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

        .submenu-toggle {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            /* Align text and icons to the left */
            padding: 15px;
            margin: 10px 0;
            text-decoration: none;
            color: #4a4a6a;
            border-radius: 10px;
            transition: 0.3s;
            font-weight: 500;
            cursor: pointer;
        }

        .submenu-toggle i:last-child {
            margin-left: auto;
            /* Push the dropdown icon to the far right */
            color: #d63384;
        }

        .submenu-toggle:hover {
            background-color: #f0d3e2;
            color: #d63384;
        }

        .submenu {
            display: none;
            /* Hide submenu by default */
            padding-left: 20px;
        }

        .submenu.active {
            display: block;
            /* Show submenu when active */
        }

        .container {
            margin-top: 50px;
            margin-left: 300px;
            /* Matches the width of the sidebar */
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: calc(100% - 320px);
            /* Ensures the container fits within the remaining space */
            overflow-x: auto;
            /* Adds horizontal scrolling if the content overflows */
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
        }

        .badge-danger {
            background-color: #e74c3c;
        }

        .badge-warning {
            background-color: #f39c12;
        }

        .badge-success {
            background-color: #2ecc71;
        }

        .badge-info {
            background-color: #3498db;
        }

        /* Navigation menu for mobile */
        .mobile-nav-toggle {
            display: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <button class="mobile-nav-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Admin Profile" class="img-fluid rounded-circle"> <!-- Display updated profile photo -->
        <h6 style="margin-top: 20px;"><?php echo htmlspecialchars($adminName); ?></h6> <!-- Display admin name -->
        <h6>Admin</h6>
        <a href="adminpage.php" style="margin-bottom: 10px;"><i class="fas fa-home"></i> Dashboard</a>
        <a href="accountadmin.php" style="margin-bottom: 10px;"><i class="fas fa-user"></i> Account Settings</a>

        <!-- Manage Complaint -->
        <a href="javascript:void(0);" class="submenu-toggle" data-target="#submenu-complaint">
            <i class="fas fa-cogs"></i> Manage Complaint
            <i class="fas fa-chevron-down float-end"></i>
        </a>
        <div id="submenu-complaint" class="submenu">
            <a href="pending.php"><i class="fas fa-exclamation-circle"></i> Pending Complaint <span class="badge bg-danger"><?php echo $complaints['pending']; ?></span></a>
            <a href="inprocess.php"><i class="fas fa-spinner"></i> In Process Complaint <span class="badge bg-warning"><?php echo $complaints['in_process']; ?></span></a>
            <a href="resolved.php"><i class="fas fa-check-circle"></i> Resolved Complaints <span class="badge bg-success"><?php echo $complaints['resolved']; ?></span></a>
        </div>


        <a href="addcategory.php" style="margin-bottom: 10px;"><i class="fas fa-list"></i> Add Category</a>

        <!-- Reports -->
        <a href="javascript:void(0);" class="submenu-toggle" data-target="#submenu-reports">
            <i class="fas fa-chart-bar"></i> Reports
            <i class="fas fa-chevron-down float-end"></i>
        </a>
        <div id="submenu-reports" class="submenu">
            <a href="betweendatereports.php"><i class="fas fa-calendar-alt"></i> Complaint Between Date Reports</a>
            <a href="adminreports.php"><i class="fas fa-user-shield"></i> Registered Admin Between Date Reports</a>
        </div>


        <a href="logout.php" class="text-danger" style="margin-bottom: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <h2>Resolved Complaints</h2>
        <table id="complaintsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Complaint No</th>
                    <th>Complainant Name</th>
                    <th>Complaint Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['complainant_name']; ?></td>
                        <td><?php echo $row['complaint_date']; ?></td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <span class="badge badge-status badge-danger">Pending</span>
                            <?php elseif ($row['status'] === 'In Process'): ?>
                                <span class="badge badge-status badge-warning">In Process</span>
                            <?php elseif ($row['status'] === 'Resolved'): ?>
                                <span class="badge badge-status badge-success">Resolved</span>
                            <?php else: ?>
                                <span class="badge badge-status badge-info"><?php echo $row['status']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="complaint_id" value="<?php echo $row['id']; ?>">
                                <select name="new_status" class="form-select form-select-sm" style="width:auto; display:inline;">
                                    <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Process" <?php echo $row['status'] === 'In Process' ? 'selected' : ''; ?>>In Process</option>
                                    <option value="Resolved" <?php echo $row['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-success">Update</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-primary view-details-btn" data-id="<?php echo $row['id']; ?>">View Details</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Complaint Details -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Complaint Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <!-- Details will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.style.display = sidebar.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
            const sidebar = document.querySelector('.sidebar');
            const submenuToggles = document.querySelectorAll('.submenu-toggle');
            const submenu = document.querySelector('.submenu');

            // Toggle sidebar for mobile view
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
        });

        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const submenu = this.nextElementSibling;
                submenu.classList.toggle('active');

                const icon = this.querySelector('i.fa-chevron-down, i.fa-chevron-up');
                if (icon) {
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                }
            });
        });

        $(document).ready(function() {
            $('#complaintsTable').DataTable();

            // Handle status update
            $(document).on('submit', 'form', function(e) {
                e.preventDefault(); // Prevent the default form submission

                const form = $(this);
                const complaintId = form.find('input[name="complaint_id"]').val();
                const newStatus = form.find('select[name="new_status"]').val();

                $.ajax({
                    url: 'update_status.php', // Endpoint to handle the status update
                    method: 'POST',
                    data: {
                        complaint_id: complaintId,
                        new_status: newStatus
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Remove the row from the current table
                            const row = form.closest('tr');
                            const table = $('#complaintsTable').DataTable();
                            table.row(row).remove().draw();

                            alert('Status updated successfully!');

                            // Optionally, refresh the target table (if applicable)
                            // For example, if the new status is "Resolved," refresh the resolved table
                            if (newStatus === 'Resolved') {
                                // Reload the resolved complaints table
                                $('#resolvedComplaintsTable').DataTable().ajax.reload();
                            }
                        } else {
                            alert('Failed to update status. Please try again.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating the status.');
                    }
                });
            });

            // Handle View Details button click
            $('.view-details-btn').on('click', function() {
                const complaintId = $(this).data('id');
                $.ajax({
                    url: 'fetch_complaint_details.php',
                    method: 'POST',
                    data: {
                        complaint_id: complaintId
                    },
                    success: function(response) {
                        $('#detailsModalBody').html(response);
                        $('#detailsModal').modal('show');
                    },
                    error: function() {
                        alert('Failed to fetch complaint details. Please try again.');
                    }
                });
            });
        });

        // Update dashboard counts dynamically
        const dashboardCounts = <?php echo json_encode($dashboardCounts); ?>;
        $('#pendingCount').text(dashboardCounts['Pending']);
        $('#inProgressCount').text(dashboardCounts['In Process']);
        $('#resolvedCount').text(dashboardCounts['Resolved']);
        $('#otherCount').text(dashboardCounts['Other']);

        const reportsToggle = document.querySelector('a[href="#reports-section"]');
        const reportsSubmenu = document.querySelector('#reports-section');

        reportsToggle.addEventListener('click', function() {
            reportsSubmenu.classList.toggle('active');
            this.querySelector('i.fa-chevron-down').classList.toggle('fa-chevron-up');
        });
    </script>
</body>

</html>
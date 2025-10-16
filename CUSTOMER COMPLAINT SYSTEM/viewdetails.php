<?php
// Database connection
include('db.php');

// Fetch complaint ID from GET request
if (isset($_GET['id'])) {
    $complaint_id = intval($_GET['id']);

    // Check if the form was submitted to update the status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['remark'])) {
        $status = $_POST['status'];
        $remark = $_POST['remark'];

        // Update the status in the database
        $update_query = "UPDATE complaints SET status = ?, remark = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $status, $remark, $complaint_id);
        $update_stmt->execute();

        // Refresh the page to reflect the updated status
        header("Location: viewdetails.php?id=" . $complaint_id);
        exit();
    }

    // Fetch complaint details from the database
    $query = "SELECT * FROM complaints WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Determine the final status based on the current status
        $final_status = '';
        switch ($row['status']) {
            case 'Pending':
                $final_status = 'Pending ';
                break;
            case 'In Progress':
                $final_status = 'In Progress';
                break;
            case 'Resolved':
                $final_status = 'Complaint Resolved';
                break;
            default:
                $final_status = 'Status Unknown';
        }
?>
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                font-size: 18px;
                text-align: left;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
            }

            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }

            .action-buttons button {
                background-color: #f0f0f0;
                border: 1px solid #ccc;
                padding: 5px 10px;
                margin-right: 5px;
                cursor: pointer;
                border-radius: 5px;
            }

            .action-buttons button:hover {
                background-color: #ddd;
            }

            .status {
                font-weight: bold;
                color: red;
            }

            .action-form {
                display: none;
                margin-top: 20px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                background-color: #f9f9f9;
            }

            .action-form label {
                font-weight: bold;
                margin-right: 10px;
            }

            .action-form select,
            .action-form textarea {
                width: 100%;
                margin-bottom: 10px;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }

            .action-form button {
                background-color: #4CAF50;
                color: white;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 5px;
            }

            .action-form button:hover {
                background-color: #45a049;
            }

            .history-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                font-size: 16px;
                text-align: left;
            }

            .history-table th,
            .history-table td {
                border: 1px solid #ddd;
                padding: 8px;
            }

            .history-table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }

            .back-button {
                display: inline-block;
                margin: 20px 0;
                background-color: #007BFF;
                color: white;
                padding: 8px 15px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
            }

            .back-button:hover {
                background-color: #0056b3;
            }
        </style>

        <!-- Back to Reports Button -->
        <a href="betweendatereports.php" class="back-button">← Back to Report</a>

        <table>
            <tr>
                <th>Complaint Number</th>
                <td><?php echo $row['id']; ?></td>
                <th>Complainant Name</th>
                <td><?php echo htmlspecialchars($row['complainant_name']); ?></td>
                <th>Complaint Date</th>
                <td><?php echo $row['complaint_date']; ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <th>SubCategory</th>
                <td><?php echo htmlspecialchars($row['sub_category']); ?></td>
                <th>Complaint Type</th>
                <td><?php echo htmlspecialchars($row['complaint_type']); ?></td>
            </tr>
            <tr>
                <th>State</th>
                <td><?php echo htmlspecialchars($row['state']); ?></td>
                <th>Nature of Complaint</th>
                <td><?php echo htmlspecialchars($row['nature_of_complaint']); ?></td>
            </tr>
            <tr>
                <th>Complaint Details</th>
                <td colspan="5"><?php echo htmlspecialchars($row['complaint_details']); ?></td>
            </tr>
            <tr>
                <th>File (if any)</th>
                <td colspan="5">
                    <?php if (!empty($row['complaint_document'])) { ?>
                        <a href="<?php echo htmlspecialchars($row['complaint_document']); ?>" target="_blank">View File</a>
                    <?php } else { ?>
                        No File Attached
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th>Final Status</th>
                <td colspan="5" class="status"><?php echo htmlspecialchars($final_status); ?></td>
            </tr>
            <tr>
                <th>Action</th>
                <td colspan="5" class="action-buttons">
                    <button onclick="showActionForm()">Take Action</button>
                </td>
            </tr>
        </table>

        <!-- Action Form -->
        <div id="actionForm" class="action-form">
            <form method="POST" action="">
                <input type="hidden" name="complaint_id" value="<?php echo $complaint_id; ?>">
                <label for="status">Status:</label>
                <select name="status" id="status" required>
                    <option value="">Select Status</option>
                    <option value="In Process">In Process</option>
                    <option value="Closed">Closed</option>
                </select>
                <label for="remark">Remark:</label>
                <textarea name="remark" id="remark" rows="4" placeholder="Enter your remarks here..." required></textarea>
                <button type="submit">Submit</button>
            </form>
        </div>

        <script>
            function showActionForm() {
                document.getElementById('actionForm').style.display = 'block';
            }

            function forwardTo() {
                alert('Forward To clicked');
            }

            function takeAction() {
                alert('Take Action clicked');
            }

            function viewUserDetails() {
                alert('View User Details clicked');
            }
        </script>
<?php
    } else {
        echo "No complaint found with the given ID.";
    }
} else {
    echo "No complaint ID provided.";
}
?>
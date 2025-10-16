<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $query = "SELECT id, complainant_name, category, sub_category, state, nature_of_complaint, complaint_details, complaint_document, complaint_date, updated_at, status FROM complaints WHERE id = $complaint_id";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
?>
        <table class="table table-bordered">
            <tr>
                <th>Complaint Number</th>
                <td><?php echo $row['id']; ?></td>
                <th>Complainant Name</th>
                <td><?php echo $row['complainant_name']; ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo $row['category']; ?></td>
                <th>SubCategory</th>
                <td><?php echo $row['sub_category']; ?></td>
            </tr>
            <tr>
                <th>State</th>
                <td><?php echo $row['state']; ?></td>
                <th>Nature of Complaint</th>
                <td><?php echo $row['nature_of_complaint']; ?></td>
            </tr>
            <tr>
                <th>Complaint Details</th>
                <td colspan="3"><?php echo $row['complaint_details']; ?></td>
            </tr>
            <tr>
                <th>File (if any)</th>
                <td colspan="3">
                    <?php if (!empty($row['complaint_document'])): ?>
                        <a href="<?php echo htmlspecialchars($row['complaint_document']); ?>" target="_blank">View File</a>
                    <?php else: ?>
                        No File
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Complaint Start Date</th>
                <td><?php echo $row['complaint_date']; ?></td>
                <th>Complaint Last Update Date</th>
                <td><?php echo $row['updated_at']; ?></td>
            </tr>
            <tr>
                <th>Final Status</th>
                <td colspan="3">
                    <span class="badge badge-status <?php echo $row['status'] === 'Resolved' ? 'badge-success' : ($row['status'] === 'In Process' ? 'badge-warning' : 'badge-danger'); ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
            </tr>
        </table>
<?php
    } else {
        echo '<p>Complaint details not found.</p>';
    }
} else {
    echo '<p>Invalid request.</p>';
}
?>
<?php
include 'db.php';

// Example count
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM complaints");
$data = mysqli_fetch_assoc($result);
$total = $data['total'];
?>
<p>Total Complaints: <strong><?php echo $total; ?></strong></p>
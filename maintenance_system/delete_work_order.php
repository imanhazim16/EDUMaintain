<?php
require 'config.php';
check_admin();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 2. Soft Delete the work order
    $sql = "UPDATE work_orders SET is_deleted = 1 WHERE id = $id";
    
    if ($conn->query($sql)) {
        header("Location: dashboard.php?msg=Work order deleted successfully");
    } else {
        header("Location: dashboard.php?err=Error deleting work order: " . $conn->error);
    }
} else {
    header("Location: dashboard.php");
}
exit();
?>

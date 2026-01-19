<?php
require 'config.php';
check_login();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Verify ownership before deleting
    $check = $conn->prepare("SELECT id FROM work_orders WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Delete associated images first
        $conn->query("DELETE FROM work_order_images WHERE work_order_id = $id");

        // Delete the work order
        $stmt = $conn->prepare("DELETE FROM work_orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php?msg=Request deleted successfully");
        } else {
            header("Location: dashboard.php?err=Error deleting request: " . $conn->error);
        }
        $stmt->close();
    } else {
        header("Location: dashboard.php?err=Unauthorized action or request not found");
    }
    $check->close();
} else {
    header("Location: dashboard.php");
}
exit();
?>

<?php
require 'config.php';
check_login();

$role = $_SESSION['role'];
$today = date('Y-m-d');

$sql = "SELECT id, title, created_at, priority 
        FROM announcements 
        WHERE (expiry_date >= '$today' OR expiry_date IS NULL) 
        AND (target_audience LIKE '%$role%' OR target_audience = 'all')
        ORDER BY created_at DESC LIMIT 10";

$result = $conn->query($sql);
$announcements = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $announcements[] = [
            'id' => $row['id'],
            'message' => $row['title'], // Using title as the preview message
            'created_at' => $row['created_at'],
            'link' => 'announcement_details.php?id=' . $row['id'], // Show details page
            'priority' => $row['priority']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($announcements);
?>

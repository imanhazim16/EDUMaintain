<!-- Announcement System -->
<?php
$role = $_SESSION['role'];
$today = date('Y-m-d');
$ann_sql = "SELECT * FROM announcements 
            WHERE (expiry_date >= '$today' OR expiry_date IS NULL) 
            AND (target_audience LIKE '%$role%' OR target_audience = 'all')
            ORDER BY priority DESC, created_at DESC";
$ann_res = $conn->query($ann_sql);

if ($ann_res && $ann_res->num_rows > 0):
    while($ann = $ann_res->fetch_assoc()):
        if ($ann['priority'] == 'high'):
?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1"><?php echo htmlspecialchars($ann['title']); ?></h6>
                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
                </div>
            </div>
<?php 
        endif;
    endwhile;
endif;
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-0">Assigned Tasks</h5>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tech_id = $_SESSION['user_id'];
                            $sql = "SELECT w.*, c.name as category_name 
                                    FROM work_orders w 
                                    LEFT JOIN categories c ON w.category_id = c.id 
                                    WHERE w.assigned_technician_id = $tech_id AND w.is_deleted = 0 
                                    ORDER BY FIELD(status, 'assigned', 'in_progress', 'completed') ASC, priority DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $priority_text = match($row['priority']) {
                                        '1' => 'Low',
                                        '2' => 'Medium',
                                        '3' => 'High',
                                        '4' => 'Critical',
                                        default => 'Unknown'
                                    };

                                    $status_badge = match($row['status']) {
                                        'pending' => 'bg-danger',
                                        'assigned' => 'bg-info',
                                        'in_progress' => 'bg-warning text-white',
                                        'completed' => 'bg-success',
                                        'rejected' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };

                                    echo "<tr>
                                        <td>#{$row['id']}</td>
                                        <td>{$row['title']}</td>
                                        <td>{$row['category_name']}</td>
                                        <td><strong class='" . ($row['priority'] >= 3 ? 'text-danger' : '') . "'>$priority_text</strong></td>
                                        <td><span class='badge $status_badge'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>
                                        <td>
                                            <a href='request_details.php?id={$row['id']}' class='btn btn-light border text-primary btn-sm'><i class='fas fa-eye me-1'></i> Update</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No active tasks assigned.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

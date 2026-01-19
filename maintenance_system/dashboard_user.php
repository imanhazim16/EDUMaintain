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
            <div class="card-body d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Requests</h5>
                <a href="create_request.php" class="btn btn-success"><i class="fas fa-plus"></i> New Request</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <form method="GET" action="dashboard.php" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search your requests..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php if(($_GET['status']??'')=='pending') echo 'selected'; ?>>Pending</option>
                            <option value="in_progress" <?php if(($_GET['status']??'')=='in_progress') echo 'selected'; ?>>In Progress</option>
                            <option value="completed" <?php if(($_GET['status']??'')=='completed') echo 'selected'; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
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
                                <th>Image</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $user_id = $_SESSION['user_id'];
                            
                            // Filter Logic
                            $where_clauses = ["w.user_id = ? AND w.is_deleted = 0"];
                            $params = [$user_id];
                            $types = "i";

                            // Search
                            if (!empty($_GET['search'])) {
                                $search = "%" . clean_input($_GET['search']) . "%";
                                $where_clauses[] = "(w.title LIKE ? OR w.description LIKE ?)";
                                $params[] = $search;
                                $params[] = $search;
                                $types .= "ss";
                            }

                            // Status Filter
                            if (!empty($_GET['status'])) {
                                $status = clean_input($_GET['status']);
                                $where_clauses[] = "w.status = ?";
                                $params[] = $status;
                                $types .= "s";
                            }

                            $where_sql = implode(" AND ", $where_clauses);

                            $sql = "SELECT w.*, c.name as category_name,
                                    (SELECT image_path FROM work_order_images WHERE work_order_id = w.id LIMIT 1) as new_image_path
                                    FROM work_orders w 
                                    LEFT JOIN categories c ON w.category_id = c.id 
                                    WHERE $where_sql 
                                    ORDER BY w.created_at DESC";
                            
                            $stmt = $conn->prepare($sql);
                            if (!empty($params)) {
                                $stmt->bind_param($types, ...$params);
                            }
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_badge = match($row['status']) {
                                        'pending' => 'bg-danger',
                                        'assigned' => 'bg-info',
                                        'in_progress' => 'bg-warning text-white',
                                        'completed' => 'bg-success',
                                        'rejected' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                    
                                    $priority_text = match($row['priority']) {
                                        '1' => 'Low',
                                        '2' => 'Medium',
                                        '3' => 'High',
                                        '4' => 'Critical',
                                        default => 'Unknown'
                                    };

                                    // Check for new image path first, then legacy
                                    $display_image = !empty($row['new_image_path']) ? $row['new_image_path'] : $row['image_path'];
                                    $img_thumb = $display_image ? "<a href='$display_image' target='_blank' class='badge bg-info text-decoration-none'>View</a>" : "<span class='text-muted small'>None</span>";

                                    echo "<tr>
                                        <td>#{$row['id']}</td>
                                        <td>{$row['title']}</td>
                                        <td>{$row['category_name']}</td>
                                        <td>{$priority_text}</td>
                                        <td><span class='badge $status_badge'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>
                                        <td>$img_thumb</td>
                                        <td>" . date('Y-m-d H:i', strtotime($row['created_at'])) . "</td>
                                        <td>
                                            <a href='edit_request.php?id={$row['id']}' class='btn btn-sm btn-light text-primary border' title='Edit'><i class='fas fa-eye'></i></a>
                                            <a href='#' onclick='confirmDelete({$row['id']})' class='btn btn-sm btn-light text-danger border ms-1' title='Delete'><i class='fas fa-trash'></i></a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No requests found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this request? This action cannot be undone.')) {
        window.location.href = 'delete_user_request.php?id=' + id;
    }
}
</script>

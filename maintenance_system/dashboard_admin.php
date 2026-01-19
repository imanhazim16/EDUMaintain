<!-- Hero Section -->
<div class="hero-section">
    <div class="container-fluid p-0">
        <h5>Welcome to EduMaintain</h5>
        <h1>Our UiTM EduMaintain provide</h1>
        <p>
            Reliable scheduling, tracking and reporting tools for equipment and facilities maintenance. 
            It is easy and simple to use for management of medical organizations or university facilities.
        </p>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['err']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

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

<!-- Content with padding below hero -->
<div class="px-4">
    <!-- Colored Widgets -->
    <div class="row mb-5">
    <div class="col-md-4 mb-4">
        <div class="widget-card bg-widget-uitm-primary">
            <div class="widget-icon"><i class="fas fa-users"></i></div>
            <h3 class="widget-title">User Directory</h3>
            <p class="widget-desc">
                View and manage all registered staff and student accounts in the system.
            </p>
            <a href="view_users.php" class="btn btn-outline-light mt-3 rounded-pill btn-sm">View Users</a>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="widget-card bg-widget-uitm-accent">
            <div class="widget-icon"><i class="fas fa-user-clock"></i></div>
            <h3 class="widget-title">Technician Performance</h3>
            <p class="widget-desc">
                Check detailed availability, assigned tasks, and performance metrics for all technicians.
            </p>
            <a href="technician_performance.php" class="btn btn-outline-light mt-3 rounded-pill btn-sm">View Performance</a>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="widget-card bg-widget-uitm-dark">
            <div class="widget-icon"><i class="far fa-clone"></i></div>
            <h3 class="widget-title">View Reports</h3>
            <p class="widget-desc">
                Viewing important reports like PPM, Installation, Daily Inspection, and Cost Analysis.
            </p>
            <a href="report.php" class="btn btn-outline-light mt-3 rounded-pill btn-sm">View Reports</a>
        </div>
    </div>
</div>

<!-- Bottom Section: Table + Sidebar -->
<div class="row">
    <!-- Recent Work Orders Table (Left 70%) -->
    <div class="col-md-12 mb-4">
        <div class="card card-table shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-secondary">Work Orders</h5>

            </div>
            <div class="card-body p-0">
                <!-- Search & Filters -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" action="dashboard.php" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search ID, Title, or Name" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="pending" <?php if(($_GET['status']??'')=='pending' || !isset($_GET['status'])) echo 'selected'; ?>>Pending</option>
                                <option value="in_progress" <?php if(($_GET['status']??'')=='in_progress') echo 'selected'; ?>>In Progress</option>
                                <option value="completed" <?php if(($_GET['status']??'')=='completed') echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="priority" class="form-select form-select-sm">
                                <option value="">All Priority</option>
                                <option value="1" <?php if(($_GET['priority']??'')=='1') echo 'selected'; ?>>Low</option>
                                <option value="2" <?php if(($_GET['priority']??'')=='2') echo 'selected'; ?>>Medium</option>
                                <option value="3" <?php if(($_GET['priority']??'')=='3') echo 'selected'; ?>>High</option>
                                <option value="4" <?php if(($_GET['priority']??'')=='4') echo 'selected'; ?>>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                        </div>
                    </form>
                </div>
                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Requester</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Filter Logic
                            // Filter Logic
                            $where_clauses = ["is_deleted = 0"];
                            $params = [];
                            $types = "";

                            // Determine Default Filter
                            $status_filter = null;
                            if (isset($_GET['status'])) {
                                $status_filter = $_GET['status']; // Explicitly set (could be empty "All")
                            } elseif (isset($_GET['search'])) {
                                $status_filter = ''; // Search mode defaults to All
                            } else {
                                $status_filter = 'pending'; // Default Dashboard View
                            }

                            // Search
                            if (!empty($_GET['search'])) {
                                $search = "%" . clean_input($_GET['search']) . "%";
                                $where_clauses[] = "(w.title LIKE ? OR w.description LIKE ? OR u.name LIKE ?)";
                                $params[] = $search;
                                $params[] = $search;
                                $params[] = $search;
                                $types .= "sss";
                            }

                            // Status Filter Application
                            if (!empty($status_filter)) {
                                $status = clean_input($status_filter);
                                $where_clauses[] = "w.status = ?";
                                $params[] = $status;
                                $types .= "s";
                            }

                            // Priority Filter
                            if (!empty($_GET['priority'])) {
                                $priority = clean_input($_GET['priority']);
                                $where_clauses[] = "w.priority = ?";
                                $params[] = $priority;
                                $types .= "i";
                            }

                            $where_sql = implode(" AND ", $where_clauses);
                            
                            // Adjust Limit: Default 5, but if searching/filtering, show 50
                            $limit = (empty($_GET['search']) && empty($_GET['status']) && empty($_GET['priority'])) ? 5 : 50;

                            $sql = "SELECT w.*, u.name as reporter_name 
                                    FROM work_orders w 
                                    JOIN users u ON w.user_id = u.id 
                                    WHERE $where_sql
                                    ORDER BY w.created_at DESC LIMIT ?";
                            
                            $params[] = $limit;
                            $types .= "i";

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
                                        '1' => 'Low', '2' => 'Medium', '3' => 'High', '4' => 'Critical', default => 'Unknown'
                                    };

                                    echo "<tr>
                                        <td>#{$row['id']}</td>
                                        <td>{$row['title']}</td>
                                        <td>{$row['reporter_name']}</td>
                                        <td><span class='fw-bold " . ($row['priority'] >= 3 ? 'text-danger' : '') . "'>{$priority_text}</span></td>
                                        <td><span class='badge $status_badge'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>
                                        <td>
                                            <a href='request_details.php?id={$row['id']}' class='btn btn-sm btn-light text-primary border'><i class='fas fa-eye'></i></a>
                                            <a href='broadcast.php?source=request&id={$row['id']}' class='btn btn-sm btn-light text-warning border ms-1' title='Broadcast'><i class='fas fa-bullhorn'></i></a>
                                            <a href='delete_work_order.php?id={$row['id']}' class='btn btn-sm btn-light text-danger border ms-1' onclick='return confirm(\"Are you sure you want to delete this work order?\")'><i class='fas fa-trash'></i></a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center p-3'>No recent data</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


</div>
</div>

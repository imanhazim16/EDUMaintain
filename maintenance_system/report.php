<?php
require 'config.php';
check_admin();

$role = $_SESSION['role'];
// Stats Calculation
$total_cost_placeholder = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Maintenance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            .no-print, #sidebar, .navbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
            .report-content { padding: 0 !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; border: none !important; }
            #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
        .report-header { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
        .report-content { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="no-print">
            <div class="sidebar-header">
                <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-tools"></i> UiTM EduMaintain</h4>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                
                <?php if ($role === 'admin'): ?>
                    <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
                    <li><a href="technician_performance.php"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
                    <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i> Report</a></li>
                <?php endif; ?>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>

                <li class="mt-4 border-top pt-2">
                    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-glass mb-4 shadow-sm no-print">
                <div class="container-fluid <?php echo ($role === 'admin') ? 'px-0' : 'px-4'; ?>">
                    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                        <i class="fas fa-tools me-2"></i>UiTM EduMaintain
                    </a>
                    <div class="d-flex align-items-center">
                        <span class="me-3 d-none d-md-block">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    </div>
                </div>
            </nav>

            <div class="<?php echo ($role === 'admin') ? 'p-0' : 'p-4'; ?>">
                <div class="px-4">
                    <div class="report-content mb-4 mt-2">
                        <div class="d-flex justify-content-end align-items-center no-print mb-4">

                            <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print"></i> Print Report</button>
                        </div>

        <div class="report-header text-center">
            <h2>Monthly Maintenance Summary</h2>
            <p class="text-muted">Generated on: <?php echo date('F j, Y'); ?></p>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-5 text-center g-3">
            <div class="col">
                <div class="border p-3 rounded h-100">
                    <h6 class="text-muted small text-uppercase">Total Requests</h6>
                    <h3>
                        <?php 
                        $res = $conn->query("SELECT COUNT(*) FROM work_orders"); 
                        echo $res->fetch_row()[0]; 
                        ?>
                    </h3>
                </div>
            </div>
            <div class="col">
                <div class="border p-3 rounded h-100">
                    <h6 class="text-muted small text-uppercase">Pending</h6>
                    <h3 class="text-danger">
                        <?php 
                        $res = $conn->query("SELECT COUNT(*) FROM work_orders WHERE status='pending'"); 
                        echo $res->fetch_row()[0]; 
                        ?>
                    </h3>
                </div>
            </div>
            <div class="col">
                <div class="border p-3 rounded h-100">
                    <h6 class="text-muted small text-uppercase">In Progress</h6>
                    <h3 class="text-warning">
                        <?php 
                        $res = $conn->query("SELECT COUNT(*) FROM work_orders WHERE status='in_progress'"); 
                        echo $res->fetch_row()[0]; 
                        ?>
                    </h3>
                </div>
            </div>
            <div class="col">
                <div class="border p-3 rounded h-100">
                    <h6 class="text-muted small text-uppercase">Completed Repairs</h6>
                    <h3 class="text-success">
                        <?php 
                        $res = $conn->query("SELECT COUNT(*) FROM work_orders WHERE status='completed'"); 
                        echo $res->fetch_row()[0]; 
                        ?>
                    </h3>
                </div>
            </div>

        </div>

        <div class="mb-5">
            <h4 class="mb-3">Work Order Records</h4>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Requester</th>
                            <th>Technician</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT w.id, w.title, w.created_at, w.priority, w.status, w.is_deleted, u.name as tech_name, r.name as requester_name 
                                FROM work_orders w 
                                LEFT JOIN users u ON w.assigned_technician_id = u.id 
                                LEFT JOIN users r ON w.user_id = r.id
                                ORDER BY w.created_at DESC";
                        $result = $conn->query($sql);
                        while($row = $result->fetch_assoc()) {
                            $status_badge = match($row['status']) {
                                'pending' => 'bg-danger text-white',
                                'in_progress' => 'bg-warning text-white',
                                'completed' => 'bg-success text-white',
                                default => 'bg-secondary'
                            };
                            
                            $deleted_badge = $row['is_deleted'] ? '<span class="badge bg-secondary ms-1">Deleted</span>' : '';
                            $row_class = $row['is_deleted'] ? 'text-muted' : '';

                            echo "<tr class='$row_class'>
                                <td>#{$row['id']}</td>
                                <td>".date('Y-m-d', strtotime($row['created_at']))."</td>
                                <td>
                                    <div class='fw-bold'>{$row['title']} $deleted_badge</div>
                                    <small class='" . ($row['priority'] >= 3 ? 'text-danger' : 'text-muted') . "'>" . ($row['priority']==4?'Critical':($row['priority']==3?'High':($row['priority']==2?'Medium':'Low'))) . "</small>
                                </td>
                                <td>{$row['requester_name']}</td>
                                <td>".($row['tech_name'] ?? '<span class="text-muted italic">Unassigned</span>')."</td>
                                <td class='text-end'><span class='badge $status_badge'>".ucfirst($row['status'])."</span></td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <h4 class="mb-3">Cost Analysis</h4>
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <div class="row text-center py-2">
                        <div class="col-md-4 border-end">
                            <h6 class="text-muted small">MAINTENANCE EXPENDITURE</h6>
                            <h4 class="fw-bold text-primary">RM <?php 
                                $cost_res = $conn->query("SELECT SUM(repair_cost) FROM work_orders"); 
                                $total_cost = $cost_res->fetch_row()[0] ?? 0;
                                echo number_format($total_cost, 2); 
                            ?></h4>
                        </div>
                        <div class="col-md-4 border-end">
                            <h6 class="text-muted small">AVERAGE COST PER REPAIR</h6>
                            <h4 class="fw-bold">
                                RM <?php 
                                $res = $conn->query("SELECT AVG(repair_cost) FROM work_orders WHERE status='completed' AND repair_cost > 0");
                                echo number_format($res->fetch_row()[0] ?? 0, 2);
                                ?>
                            </h4>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted small">PENDING ESTIMATES</h6>
                            <h4 class="fw-bold text-warning">
                                RM <?php 
                                $res = $conn->query("SELECT SUM(repair_cost) FROM work_orders WHERE status != 'completed'");
                                echo number_format($res->fetch_row()[0] ?? 0, 2);
                                ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-end">
                <small class="text-muted"><i class="fas fa-info-circle"></i> Cost analysis includes parts and labor recorded per work order.</small>
            </div>

            <!-- Cost Breakdown Table -->
            <div class="mt-4">
                <h5 class="mb-3 text-secondary">Expenditure Breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Work Order Title</th>
                                <th>Technician</th>
                                <th class="text-end">Cost (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cost_sql = "SELECT w.id, w.title, w.created_at, w.repair_cost, u.name as tech_name 
                                        FROM work_orders w 
                                        LEFT JOIN users u ON w.assigned_technician_id = u.id 
                                        WHERE w.repair_cost > 0 
                                        ORDER BY w.repair_cost DESC";
                            $cost_result = $conn->query($cost_sql);
                            
                            if ($cost_result->num_rows > 0) {
                                while($c_row = $cost_result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>#{$c_row['id']}</td>
                                        <td>".date('Y-m-d', strtotime($c_row['created_at']))."</td>
                                        <td>{$c_row['title']}</td>
                                        <td>".($c_row['tech_name'] ?? 'Unassigned')."</td>
                                        <td class='text-end fw-bold'>".number_format($c_row['repair_cost'], 2)."</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted fst-italic'>No recorded expenditures yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5 text-muted fst-italic">
            <small>-- End of Report --</small>
        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require 'config.php';
check_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Performance - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-tools"></i> UiTM EduMaintain</h4>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                
                <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
                <li><a href="technician_performance.php" class="active"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
                <li><a href="report.php"><i class="fas fa-file-alt"></i> Report</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>

                <li class="mt-4 border-top pt-2">
                    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-glass mb-4 shadow-sm">
                <div class="container-fluid px-0">
                    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                        <i class="fas fa-tools me-2"></i>UiTM EduMaintain
                    </a>
                    <div class="d-flex align-items-center">
                        <span class="me-3 d-none d-md-block">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    </div>
                </div>
            </nav>

            <div class="p-0">
                <div class="px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark">Technician Performance</h2>
            <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print"></i> Print Report</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <!-- Performance Stats Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4">Technician Name</th>
                                <th class="text-center">Assigned Tasks</th>
                                <th class="text-center">Completed Tasks</th>
                                <th class="text-center">Pending Tasks</th>
                                <th class="text-center">Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT u.name,
                                    COUNT(w.id) as total,
                                    SUM(CASE WHEN w.status = 'completed' THEN 1 ELSE 0 END) as completed,
                                    SUM(CASE WHEN w.status != 'completed' THEN 1 ELSE 0 END) as pending
                                    FROM users u
                                    LEFT JOIN work_orders w ON u.id = w.assigned_technician_id
                                    WHERE u.role = 'technician'
                                    GROUP BY u.id";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $rate = $row['total'] > 0 ? round(($row['completed'] / $row['total']) * 100) : 0;
                                    $bar_color = $rate >= 75 ? 'bg-success' : ($rate >= 50 ? 'bg-warning' : 'bg-danger');

                                    echo "<tr>
                                        <td class='ps-4'>
                                            <div class='d-flex align-items-center'>
                                                <div class='rounded-circle bg-light border me-3 d-flex align-items-center justify-content-center' style='width:40px;height:40px;'>
                                                    <i class='fas fa-user text-secondary'></i>
                                                </div>
                                                <div class='fw-bold'>{$row['name']}</div>
                                            </div>
                                        </td>
                                        <td class='text-center fs-5'>{$row['total']}</td>
                                        <td class='text-center fs-5 text-success fw-bold'>{$row['completed']}</td>
                                        <td class='text-center fs-5 text-danger fw-bold'>{$row['pending']}</td>
                                        <td class='text-center' style='width: 25%;'>
                                            <div class='d-flex align-items-center justify-content-center'>
                                                <span class='me-2 small fw-bold'>$rate%</span>
                                                <div class='progress flex-grow-1' style='height: 8px; max-width: 100px;'>
                                                    <div class='progress-bar $bar_color' role='progressbar' style='width: $rate%'></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center p-5 text-muted'>No technicians found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 fw-bold text-dark">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-md-4 mb-2">
                        <button type="button" class="btn btn-outline-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#addTechnicianModal">
                            <i class="fas fa-user-plus me-2"></i> Add New Technician
                        </button>
                    </div>
                    <div class="col-md-4 mb-2">
                         <a href="broadcast.php" class="btn btn-outline-info w-100 py-3"><i class="fas fa-bullhorn me-2"></i> Broadcast Announcement</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-center text-muted small mt-4">
                &copy; <?php echo date('Y'); ?> Maintenance Management System
            </div>
        </div>

        <!-- Add Technician Modal -->
        <div class="modal fade" id="addTechnicianModal" tabindex="-1" aria-labelledby="addTechnicianModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title fw-bold text-primary" id="addTechnicianModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Technician</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="add_technician.php" method="POST">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="techName" class="form-label text-secondary fw-semibold">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="techName" name="name" required placeholder="e.g. Ahmad Ali">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="techEmail" class="form-label text-secondary fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-0" id="techEmail" name="email" required placeholder="e.g. ahmad@uitm.edu.my">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="techPassword" class="form-label text-secondary fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0" id="techPassword" name="password" required placeholder="Enter password">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Add Technician</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

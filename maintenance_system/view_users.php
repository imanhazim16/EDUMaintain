<?php
require 'config.php';
check_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Directory - UiTM EduMaintain</title>
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
                
                <li><a href="view_users.php" class="active"><i class="fas fa-users"></i> User Directory</a></li>
                <li><a href="technician_performance.php"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
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
            <div>
                <h2 class="fw-bold text-dark">User Directory</h2>
                <p class="text-muted small mb-0">List of all registered staff and students.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print"></i> Print List</button>
        </div>

        <!-- Users Table Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small text-muted">
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, name, email, role, created_at FROM users WHERE role IN ('staff', 'student') ORDER BY created_at DESC";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $role_badge = $row['role'] == 'staff' ? 'bg-primary' : 'bg-secondary';
                                    $date = date('M d, Y', strtotime($row['created_at']));
                                    
                                    echo "<tr>
                                        <td class='ps-4'>
                                            <div class='d-flex align-items-center'>
                                                <div class='rounded-circle bg-light border me-3 d-flex align-items-center justify-content-center' style='width:40px;height:40px;'>
                                                    <i class='fas fa-user-circle fa-lg text-secondary'></i>
                                                </div>
                                                <div class='fw-bold'>{$row['name']}</div>
                                            </div>
                                        </td>
                                        <td>{$row['email']}</td>
                                        <td><span class='badge rounded-pill $role_badge'>" . ucfirst($row['role']) . "</span></td>
                                        <td><small class='text-muted'>$date</small></td>
                                        <td class='text-end pe-4'>
                                            <span class='badge bg-success-subtle text-success border border-success-subtle'>Active</span>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center p-5 text-muted'>No users found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-center text-muted small mt-4">
                &copy; <?php echo date('Y'); ?> Maintenance Management System
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

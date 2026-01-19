<?php
require 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$ann_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT a.*, u.name as creator_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id WHERE a.id = ?");
$stmt->bind_param("i", $ann_id);
$stmt->execute();
$ann = $stmt->get_result()->fetch_assoc();

if (!$ann) {
    die("Announcement not found.");
}

$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ann['title']); ?> - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .ann-card { border-radius: 20px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.08); overflow: hidden; }
        .ann-header { background: linear-gradient(135deg, #4A148C 0%, #7B1FA2 100%); color: white; padding: 40px; }
        .priority-badge { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
    </style>
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
                
                <?php if ($role === 'admin'): ?>
                    <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
                    <li><a href="technician_performance.php"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
                    <li><a href="report.php"><i class="fas fa-file-alt"></i> Report</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                
                <?php elseif ($role === 'technician'): ?>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php else: ?>
                    <li><a href="create_request.php"><i class="fas fa-plus-circle"></i> New Request</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php endif; ?>

                <li class="mt-4 border-top pt-2">
                    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-glass mb-4 shadow-sm">
                <div class="container-fluid px-4">
                    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                        <i class="fas fa-tools me-2"></i>UiTM EduMaintain
                    </a>
                    <div class="d-flex align-items-center">
                        <span class="me-3 d-none d-md-block">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    </div>
                </div>
            </nav>

            <div class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="card ann-card">
                            <div class="ann-header text-center">
                                <?php 
                                $p_class = match($ann['priority']) {
                                    'high' => 'bg-danger',
                                    'medium' => 'bg-warning text-dark',
                                    'low' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <div class="badge <?php echo $p_class; ?> priority-badge mb-3"><?php echo htmlspecialchars($ann['priority']); ?> Priority</div>
                                <h1 class="fw-bold"><?php echo htmlspecialchars($ann['title']); ?></h1>
                                <div class="mt-3 opacity-75">
                                    <i class="far fa-calendar-alt me-2"></i> <?php echo date('F j, Y, g:i a', strtotime($ann['created_at'])); ?>
                                    <span class="mx-2">|</span>
                                    <i class="far fa-user me-2"></i> Published by <?php echo htmlspecialchars($ann['creator_name'] ?? 'System'); ?>
                                </div>
                            </div>
                            <div class="card-body p-5">
                                <div class="ann-message fs-5 text-secondary" style="line-height: 1.8;">
                                    <?php 
                                    $msg = htmlspecialchars(str_replace(['\r\n', '\r', '\n'], "\n", $ann['message']));
                                    
                                    // Highlight status lines (case insensitive, handles spaces and underscores)
                                    $msg = preg_replace_callback('/Status:\s*(Pending|In[_ ]*Progress|Completed)/i', function($m) {
                                        $status = strtolower(str_replace([' ', '_'], '', $m[1]));
                                        $class = match($status) {
                                            'pending' => 'text-status-pending',
                                            'inprogress' => 'text-status-progress',
                                            'completed' => 'text-status-completed',
                                            default => ''
                                        };
                                        $display = ucwords(str_replace('_', ' ', $m[1]));
                                        return "<strong>Status:</strong> <span class='status-box $class'>$display</span>";
                                    }, $msg);

                                    echo nl2br($msg); 
                                    ?>
                                </div>
                                
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

<?php
require 'config.php';
check_admin();

$msg = '';
$title_val = '';
$message_val = '';
$priority_val = 'low';

if (isset($_GET['source']) && $_GET['source'] == 'request' && isset($_GET['id'])) {
    $req_id = intval($_GET['id']);
    // Join to get reporter and technician names
    $sql = "SELECT w.*, r.name as reporter_name, t.name as tech_name 
            FROM work_orders w 
            JOIN users r ON w.user_id = r.id 
            LEFT JOIN users t ON w.assigned_technician_id = t.id 
            WHERE w.id = $req_id";
            
    $req = $conn->query($sql)->fetch_assoc();
    
    if ($req) {
        $title_val = "Update on: " . $req['title'];
        
        $tech_display = $req['tech_name'] ? $req['tech_name'] : "Unassigned";
        
        $message_val = "Technician: " . $tech_display . "\n";
        $message_val .= "Request ID: #" . $req['id'] . "\n";
        $message_val .= "Reporter: " . $req['reporter_name'] . "\n\n";
        $message_val .= "Details: " . $req['description'] . "\n";
        $message_val .= "Status: " . ucfirst($req['status']);
        
        $p_map = [1=>'low', 2=>'medium', 3=>'high', 4=>'high'];
        $priority_val = $p_map[$req['priority']] ?? 'low';
        
        // Auto-select category based on title/desc keywords or default
        if (stripos($req['title'], 'repair') !== false) $category_val = 'facility';
        else $category_val = 'facility';
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = clean_input($_POST['title']);
    $message = clean_input($_POST['message']);
    
    // Append progress update note if exists
    if (!empty($_POST['update_note'])) {
        $update_note = clean_input($_POST['update_note']);
        $message .= "\n\n--- Progress Update ---\n" . $update_note;
    }
    
    $priority = clean_input($_POST['priority']);
    $category = clean_input($_POST['category']);
    $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    $created_by = $_SESSION['user_id'];
    
    // Process target audience
    $audience = [];
    if (isset($_POST['target_students'])) $audience[] = 'student';
    if (isset($_POST['target_staff'])) $audience[] = 'staff';
    if (isset($_POST['target_technicians'])) $audience[] = 'technician';
    $target_string = implode(',', $audience);

    $stmt = $conn->prepare("INSERT INTO announcements (title, message, priority, category, target_audience, expiry_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $message, $priority, $category, $target_string, $expiry, $created_by);
    
    if ($stmt->execute()) {
        $msg = "Announcement published successfully!";
    } else {
        $msg = "Error: " . $conn->error;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    header("Location: broadcast.php?msg=Deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Announcement - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-form { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .priority-high { border-left: 5px solid #dc3545; }
        .priority-medium { border-left: 5px solid #ffc107; }
        .priority-low { border-left: 5px solid #0dcaf0; }
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
                
                <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
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
        <div class="row">
            <div class="col-md-12">
                <div class="card card-form mb-4">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4"><i class="fas fa-bullhorn text-primary me-2"></i> Create Announcement</h4>
                        
                        <?php if($msg): ?>
                            <div class="alert alert-info"><?php echo $msg; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Announcement Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Scheduled Downtime" value="<?php echo htmlspecialchars($title_val); ?>" required>
                            </div>

                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="4" placeholder="Enter the details here..." required><?php echo htmlspecialchars($message_val); ?></textarea>
                            </div>

                            <?php if (isset($_GET['source']) && $_GET['source'] == 'request'): ?>
                            <div class="mb-3">
                                <label class="form-label text-primary fw-bold">Progress Update Note (Optional)</label>
                                <textarea name="update_note" class="form-control border-primary" rows="2" placeholder="e.g. Technician is currently on-site."></textarea>
                                <small class="text-muted">This will be appended to the bottom of the message.</small>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Target Audience</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_students" id="t1" checked>
                                        <label class="form-check-label" for="t1">Students</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_staff" id="t2" checked>
                                        <label class="form-check-label" for="t2">Staff</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_technicians" id="t3">
                                        <label class="form-check-label" for="t3">Technicians</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Priority Level</label>
                                    <select name="priority" class="form-select">
                                        <option value="low" <?php echo ($priority_val == 'low') ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo ($priority_val == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo ($priority_val == 'high') ? 'selected' : ''; ?>>High (Red Banner)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control">
                                </div>
                            </div>

                            <input type="hidden" name="category" value="general">

                            <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-paper-plane me-2"></i> Publish to Dashboard</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card card-form">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Active Announcements</h4>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Audience</th>
                                        <th>Expiry</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
                                    while($row = $res->fetch_assoc()) {
                                        $p_class = "priority-" . $row['priority'];
                                        echo "<tr class='$p_class'>
                                            <td>
                                                <div class='fw-bold'>{$row['title']}</div>
                                            </td>
                                            <td><small class='badge bg-light text-dark border'>".str_replace(',', ', ', $row['target_audience'])."</small></td>
                                            <td><small>".($row['expiry_date'] ?? 'No expiry')."</small></td>
                                            <td>
                                                <a href='broadcast.php?delete={$row['id']}' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"Delete this announcement?\")'><i class='fas fa-trash'></i></a>
                                            </td>
                                        </tr>";
                                    }
                                    if ($res->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No announcements yet.</td></tr>";
                                    ?>
                                </tbody>
                            </table>
                        </div>
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

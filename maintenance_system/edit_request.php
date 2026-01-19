<?php
require 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$role = $_SESSION['role'];

// Verify ownership
$check = $conn->prepare("SELECT * FROM work_orders WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $id, $user_id);
$check->execute();
$request = $check->get_result()->fetch_assoc();

if (!$request) {
    header("Location: dashboard.php?err=Unauthorized action or request not found");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $phone = clean_input($_POST['phone']);
    $comments = clean_input($_POST['additional_comments']);
    $location = clean_input($_POST['college_block']);
    $college = '';
    $block = '';
    
    if (strpos($location, 'Kolej') === 0) {
        $college = $location;
    } elseif (strpos($location, 'Block') === 0) {
        $block = $location;
    }
    
    $room = clean_input($_POST['room']);
    
    // Fetch priority based on category
    $cat_query = $conn->query("SELECT base_priority FROM categories WHERE id=$category_id");
    $cat_row = $cat_query->fetch_assoc();
    $priority = $cat_row['base_priority'] ?? 2; 

    $stmt = $conn->prepare("UPDATE work_orders SET title=?, description=?, category_id=?, priority=?, college=?, block=?, room_no=?, requester_phone=?, additional_comments=?, requester_first_name=?, requester_last_name=? WHERE id=?");
    $stmt->bind_param("ssiisssssssi", $title, $description, $category_id, $priority, $college, $block, $room, $phone, $comments, $first_name, $last_name, $id);

    if ($stmt->execute()) {
        $success = "Request updated successfully! <a href='dashboard.php'>Back to Dashboard</a>";
        // Update request data for display
        $check->execute();
        $request = $check->get_result()->fetch_assoc();
    } else {
        $error = "Error updating request: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Request - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-header { text-align: left; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .form-header h1 { font-size: 1.75rem; color: #2c3e50; font-weight: 700; }
        .form-header p { color: #7f8c8d; font-size: 0.9rem; }
        .form-label { font-weight: 500; color: #34495e; margin-bottom: 6px; font-size: 0.95rem; }
        .form-control, .form-select { border: 1px solid #dcdde1; padding: 10px; border-radius: 4px; font-size: 0.9rem; }
        .btn-submit { background-color: #3498db; border: none; padding: 12px 35px; font-weight: 600; font-size: 1rem; border-radius: 4px; color: white; transition: background 0.3s; }
        .btn-submit:hover { background-color: #2980b9; }
        .section-title { font-size: 0.95rem; font-weight: 600; color: #2c3e50; margin-top: 25px; margin-bottom: 15px; border-left: 4px solid #3498db; padding-left: 10px; }
    </style>
</head>
<body>
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
                <?php elseif ($role === 'technician'): ?>
                    <!-- Technician Specific Menu -->
                <?php else: ?>
                    <li><a href="create_request.php"><i class="fas fa-plus-circle"></i> New Request</a></li>
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

            <div class="p-4">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Edit Maintenance Request</h1>
                        <p>Update the details of your maintenance request #<?php echo $id; ?>.</p>
                    </div>

            <?php if($error): ?>
                <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success mb-4"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Asset / Machine ID</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($request['title']); ?>">
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="category" class="form-select" required>
                            <?php
                            $cats = $conn->query("SELECT * FROM categories");
                            while($c = $cats->fetch_assoc()) {
                                $selected = ($c['id'] == $request['category_id']) ? 'selected' : '';
                                echo "<option value='{$c['id']}' $selected>{$c['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="section-title">Location Details</div>
                <div class="row mb-4">
                    <div class="col-md-8">
                        <label class="form-label">College / Block</label>
                        <select name="college_block" class="form-select" required>
                            <option value="">Select College / Block...</option>
                            <?php
                            $options = [
                                "Kolej Tunku Abdul Rahman", 
                                "Kolej Tun Hussein Onn", 
                                "Kolej Tun Razak", 
                                "Kolej Tun Dr Mahathir",
                                "Block A", 
                                "Block B", 
                                "Block C", 
                                "Block D"
                            ];
                            
                            $current_val = !empty($request['college']) ? $request['college'] : $request['block'];

                            foreach($options as $opt) {
                                $selected = ($current_val == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $selected>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Room / Location</label>
                        <input type="text" name="room" class="form-control" required value="<?php echo htmlspecialchars($request['room_no']); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Problem Description</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($request['description']); ?></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Requester Name</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required value="<?php echo htmlspecialchars($request['requester_first_name']); ?>">
                                <div class="sub-label">First Name</div>
                            </div>
                            <div class="col-6">
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required value="<?php echo htmlspecialchars($request['requester_last_name']); ?>">
                                <div class="sub-label">Last Name</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($request['requester_phone']); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Additional Comments</label>
                    <textarea name="additional_comments" class="form-control" rows="3"><?php echo htmlspecialchars($request['additional_comments']); ?></textarea>
                </div>

                <div class="text-center mt-5">
                    <button type="submit" class="btn btn-submit">Update Request</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary ms-2" style="padding: 12px 35px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$request_id = intval($_GET['id']);
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle Form Submissions
$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_status'])) {
        $new_status = clean_input($_POST['status']);
        $updates = ["status='$new_status'"];
        $tech_changed = false;
        $new_tech_id = 0;

        // Admin can also update technician
        if ($user_role === 'admin' && isset($_POST['technician_id']) && !empty($_POST['technician_id'])) {
            $new_tech_id = intval($_POST['technician_id']);
            
            // Check if technician actually changed to send notification
            $check_sql = "SELECT assigned_technician_id FROM work_orders WHERE id=$request_id";
            $current_tech = $conn->query($check_sql)->fetch_assoc()['assigned_technician_id'];
            
            if ($new_tech_id != $current_tech) {
                $updates[] = "assigned_technician_id=$new_tech_id";
                $tech_changed = true;
            }
        }

        // Prevent Technician from selecting 'completed' via simple dropdown
        if ($user_role === 'technician' && $new_status === 'completed') {
            $msg = "Please use the 'Complete Job' form to finish the request.";
        } else {
            $sql = "UPDATE work_orders SET " . implode(', ', $updates) . " WHERE id=$request_id";
            if($conn->query($sql)) {
                $msg = "Update successful.";
                
                if ($tech_changed) {
                    // Technician changed, notification logic removed in favor of Broadcast system
                }
            }
        }
    }
    elseif (isset($_POST['complete_job'])) {
        $repair_cost = floatval($_POST['repair_cost']);
        $replace_cost = floatval($_POST['replacement_cost']);
        $notes = clean_input($_POST['technician_notes']);
        
        // Business Rule: If Repair > Replacement -> Replace
        $resolution = ($repair_cost > $replace_cost) ? 'replacement' : 'repair';

        // Update Work Order
        $stmt = $conn->prepare("UPDATE work_orders SET repair_cost=?, replacement_cost=?, resolution_type=?, technician_notes=?, status='completed' WHERE id=?");
        $stmt->bind_param("ddssi", $repair_cost, $replace_cost, $resolution, $notes, $request_id);
        
        if($stmt->execute()) {
            $msg = "Job completed! Resolution: " . ucfirst($resolution);
            
            // Notify Requester
            // Only if requester is not the one completing it (tech could be self-assigning, edge case but safe)
            $requester_id = $request['user_id'] ?? 0; // We need to fetch request first to get ID, or run a query. 
            // Since $request isn't fetched yet (it's below), we need to fetch user_id first or move this logic down.
            // Actually, best to just query it quickly or move fetching up. 
            // Let's do a quick query to get reporter ID.
            $r_query = $conn->query("SELECT user_id FROM work_orders WHERE id=$request_id");
            $r_row = $r_query->fetch_assoc();
            if ($r_row) {
                $r_id = $r_row['user_id'];
                $n_msg = "Your Request #$request_id has been completed.";
                $n_link = "request_details.php?id=$request_id";
                $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ($r_id, '$n_msg', '$n_link')");
            }

            // Handle Completion Images (Existing logic...)
            if (isset($_FILES['completion_images']) && count($_FILES['completion_images']['name']) > 0) {
                $upload_dir = 'uploads/';
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                for($i = 0; $i < count($_FILES['completion_images']['name']); $i++) {
                    if ($_FILES['completion_images']['error'][$i] == 0) {
                        $filename = $_FILES['completion_images']['name'][$i];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed)) {
                            $new_filename = uniqid() . "_done_" . $i . "." . $ext;
                            $target_file = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES["completion_images"]["tmp_name"][$i], $target_file)) {
                                $img_stmt = $conn->prepare("INSERT INTO work_order_images (work_order_id, image_path, image_type) VALUES (?, ?, 'completion')");
                                $img_stmt->bind_param("is", $request_id, $target_file);
                                $img_stmt->execute();
                            }
                        }
                    }
                }
            }
             
            // Handle Camera Images (JSON) - NEW ADDITION
            if (!empty($_POST['completion_camera_images'])) {
                $cam_images = json_decode($_POST['completion_camera_images'], true);
                if (is_array($cam_images)) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
                    foreach($cam_images as $data) {
                        if(preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                            $data = substr($data, strpos($data, ',') + 1);
                            $type = strtolower($type[1]); 
                            $data = base64_decode($data);
                            
                            $new_filename = uniqid() . '_done_cam.' . $type;
                            if(file_put_contents($upload_dir . $new_filename, $data)) {
                                $img_stmt = $conn->prepare("INSERT INTO work_order_images (work_order_id, image_path, image_type) VALUES (?, ?, 'completion')");
                                $path = $upload_dir . $new_filename;
                                $img_stmt->bind_param("is", $request_id, $path);
                                $img_stmt->execute();
                            }
                        }
                    }
                }
            }

        } else {
            $msg = "Error completing job: " . $conn->error;
        }
    }
}

// Fetch request details
$id = intval($_GET['id']); // New variable for consistency with the provided change
$sql = "SELECT w.*, u.name as reporter, u.email as reporter_email, c.name as category, t.name as tech_name 
        FROM work_orders w 
        JOIN users u ON w.user_id = u.id 
        JOIN categories c ON w.category_id = c.id 
        LEFT JOIN users t ON w.assigned_technician_id = t.id 
        WHERE w.id = $id"; // Use $id here
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Request not found.");
}

$request = $result->fetch_assoc();

// Fetch Images
// Fetch Images
$img_sql = "SELECT image_path, image_type FROM work_order_images WHERE work_order_id = $id";
$img_res = $conn->query($img_sql);
$issue_images = [];
$completion_images = [];

if($img_res) {
    while($row = $img_res->fetch_assoc()) {
        if ($row['image_type'] == 'completion') {
            $completion_images[] = $row['image_path'];
        } else {
            $issue_images[] = $row['image_path'];
        }
    }
}
// Including the main image if it exists
if(!empty($request['image_path'])) {
    $issue_images[] = $request['image_path'];
}
$issue_images = array_unique($issue_images); // Remove dupes if any



// Check permission to view (Admin, Tech, or Owner)
if ($user_role !== 'admin' && $user_role !== 'technician' && $request['user_id'] != $user_id) {
    die("Access Denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request['id']; ?> - UiTM EduMaintain</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
                    <li><a href="technician_performance.php"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
                    <li><a href="report.php"><i class="fas fa-file-alt"></i> Report</a></li>
                <?php elseif ($user_role === 'student' || $user_role === 'staff'): ?>
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
                <div class="container-fluid <?php echo ($user_role === 'admin') ? 'px-0' : 'px-4'; ?>">
                    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                        <i class="fas fa-tools me-2"></i>UiTM EduMaintain
                    </a>
                    <div class="d-flex align-items-center">
                        <span class="me-3 d-none d-md-block">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    </div>
                </div>
            </nav>

            <div class="<?php echo ($user_role === 'admin') ? 'p-0' : 'p-4'; ?>">
                <div class="px-4">
        <?php if($msg): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Request #<?php echo $request['id']; ?>: <?php echo htmlspecialchars($request['title']); ?></h4>
                        <?php 
                        $status_badge = match($request['status']) {
                            'pending' => 'bg-danger',
                            'in_progress' => 'bg-warning text-white',
                            'completed' => 'bg-success',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($request['status']); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            Reported by <strong><?php echo htmlspecialchars($request['reporter']); ?></strong> 
                            on <?php echo $request['created_at']; ?>
                        </p>
                        <hr>
                        <table class="table table-sm table-borderless mb-4">
                                <tr>
                                    <th width="30%">Location:</th>
                                    <td>
                                        <?php 
                                        $loc = [];
                                        if(!empty($request['college'])) $loc[] = $request['college'];
                                        if(!empty($request['block'])) $loc[] = $request['block'];
                                        if(!empty($request['room_no'])) $loc[] = $request['room_no'];
                                        echo implode(', ', $loc);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td><?php echo nl2br(htmlspecialchars($request['description'])); ?></td>
                                </tr>
                        </table>
                        <?php if(!empty($issue_images)): ?>
                            <hr>
                            <h5>Attached Evidence (Issue)</h5>
                            <div class="row g-2">
                                <?php foreach($issue_images as $path): ?>
                                    <div class="col-md-3">
                                        <a href="<?php echo $path; ?>" target="_blank">
                                            <img src="<?php echo $path; ?>" class="img-fluid rounded border shadow-sm" alt="Evidence">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Completion Report -->
                        <?php if($request['status'] === 'completed' || $request['resolution_type']): ?>
                            <div class="mt-5 p-3 border rounded bg-light">
                                <h5 class="text-success"><i class="fas fa-check-circle"></i> Service Report</h5>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Repair Cost:</strong> RM <?php echo $request['repair_cost']; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Replacement Cost:</strong> RM <?php echo $request['replacement_cost']; ?>
                                    </div>
                                </div>
                                <div class="alert <?php echo ($request['resolution_type'] == 'replacement') ? 'alert-warning' : 'alert-primary'; ?>">
                                    <strong>Resolution:</strong> <?php echo ucfirst($request['resolution_type']); ?>
                                    <?php if($request['resolution_type'] == 'replacement'): ?>
                                        (Replacement was cheaper or necessary)
                                    <?php endif; ?>
                                </div>
                                <p><strong>Technician Notes:</strong><br><?php echo nl2br(htmlspecialchars($request['technician_notes'])); ?></p>
                                
                                <?php if(!empty($completion_images)): ?>
                                    <h6 class="mt-3">Proof of Completion:</h6>
                                    <div class="row g-2">
                                        <?php foreach($completion_images as $path): ?>
                                            <div class="col-md-3">
                                                <a href="<?php echo $path; ?>" target="_blank">
                                                    <img src="<?php echo $path; ?>" class="img-fluid rounded border shadow-sm" alt="Completion Evidence">
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-10 mt-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Details & Actions</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Category:</strong> <?php echo $request['category']; ?></p>
                        <p><strong>Priority:</strong> 
                            <?php 
                            $p = $request['priority'];
                            $pt = ($p==4?'Critical':($p==3?'High':($p==2?'Medium':'Low')));
                            echo "<span class='fw-bold ".($p>=3?'text-danger':'')."'>$pt</span>";
                            ?>
                        </p>
                        <p><strong>Assigned Tech:</strong> <?php echo $request['tech_name'] ?? 'Unassigned'; ?></p>

                        <hr>

                        <!-- Combined Actions: Assign Technician & Update Status -->
                        <?php if($user_role === 'admin' || $user_role === 'technician'): ?>
                            <form method="POST" class="mb-4">
                                <?php if($user_role === 'admin'): ?>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Assign Technician</label>
                                        <select name="technician_id" class="form-select">
                                            <option value="">Select Technician</option>
                                            <?php
                                            $techs = $conn->query("SELECT id, name FROM users WHERE role='technician'");
                                            while($t = $techs->fetch_assoc()) {
                                                $selected = ($request['assigned_technician_id'] == $t['id']) ? 'selected' : '';
                                                echo "<option value='{$t['id']}' $selected>{$t['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Update Status</label>
                                    <div class="input-group">
                                        <select name="status" class="form-select" required>
                                            <?php
                                            $statuses = ['pending', 'in_progress', 'completed'];
                                            foreach($statuses as $s) {
                                                $sel = ($request['status'] == $s) ? 'selected' : '';
                                                echo "<option value='$s' $sel>" . ucfirst(str_replace('_',' ',$s)) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-warning">Update</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Technician Completion Form -->
                        <!-- Show if status is NOT completed OR if it IS completed but costs are missing (legacy/bug fix) -->
                        <?php if($user_role === 'technician' && ($request['status'] !== 'completed' || is_null($request['repair_cost']))): ?>
                            <hr>
                            <h6 class="fw-bold text-success">Complete Job</h6>
                            <form method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded border">
                                <div class="mb-2">
                                    <label class="small text-muted">Repair Cost (RM)</label>
                                    <input type="number" step="0.01" name="repair_cost" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small text-muted">Replacement Cost (RM)</label>
                                    <input type="number" step="0.01" name="replacement_cost" class="form-control form-control-sm" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small text-muted">Notes</label>
                                    <textarea name="technician_notes" class="form-control form-control-sm" rows="2" required></textarea>
                                </div>
                                
                                <label class="small text-muted">Proof of Completion</label>
                                <!-- Camera Integration -->
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2" id="start-camera-completion">
                                    <i class="fas fa-camera"></i> Use Camera
                                </button>
                                <input type="file" name="completion_images[]" class="form-control form-control-sm mb-2" multiple>
                                
                                <!-- Camera Preview Area -->
                                <div id="completion-camera-container" class="text-center mb-2" style="display:none;">
                                    <video id="comp-video" style="width:100%; max-height:200px;" autoplay playsinline></video>
                                    <canvas id="comp-canvas" class="d-none"></canvas>
                                    <button type="button" id="comp-capture" class="btn btn-success btn-sm mt-1 rounded-pill">Capture</button>
                                </div>
                                <input type="hidden" name="completion_camera_images" id="completion_camera_images">
                                <div id="comp-gallery" class="row g-1 mb-2"></div>


                                <div class="d-grid">
                                    <button type="submit" name="complete_job" class="btn btn-success btn-sm">Submit & Complete</button>
                                </div>
                            </form>
                            
                            <!-- Inline Script for Completion Camera -->
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const startBtn = document.getElementById('start-camera-completion');
                                const container = document.getElementById('completion-camera-container');
                                const video = document.getElementById('comp-video');
                                const canvas = document.getElementById('comp-canvas');
                                const captureBtn = document.getElementById('comp-capture');
                                const input = document.getElementById('completion_camera_images');
                                const gallery = document.getElementById('comp-gallery');
                                let photos = [];

                                if(startBtn) {
                                    startBtn.addEventListener('click', async () => {
                                        try {
                                            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                                            video.srcObject = stream;
                                            container.style.display = 'block';
                                            startBtn.classList.add('d-none');
                                        } catch(e) {
                                            alert("Camera error: " + e);
                                        }
                                    });

                                    captureBtn.addEventListener('click', () => {
                                        canvas.width = video.videoWidth;
                                        canvas.height = video.videoHeight;
                                        canvas.getContext('2d').drawImage(video, 0, 0);
                                        const url = canvas.toDataURL('image/png');
                                        photos.push(url);
                                        input.value = JSON.stringify(photos);
                                        
                                        // Add to gallery
                                        const div = document.createElement('div');
                                        div.className = 'col-3 position-relative';
                                        div.innerHTML = `<img src="${url}" class="img-fluid rounded"><span class="position-absolute top-0 end-0 bg-danger text-white rounded-circle px-1" style="font-size:10px; cursor:pointer;" onclick="this.parentElement.remove(); removeFromList('${url}')">x</span>`;
                                        gallery.appendChild(div);
                                    });
                                }
                                
                                window.removeFromList = function(url) {
                                    photos = photos.filter(p => p !== url);
                                    input.value = JSON.stringify(photos);
                                };
                            });
                            </script>
                        <?php endif; ?>
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

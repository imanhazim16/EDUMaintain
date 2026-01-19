<?php
require 'config.php';
check_login();

$success = '';
$error = '';
$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = clean_input($_POST['title']); // Mapping Asset ID to title
    $description = clean_input($_POST['description']);
    $category_id = intval($_POST['category_id']);
    
    // New Fields
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $phone = clean_input($_POST['phone']);
    $comments = clean_input($_POST['additional_comments']);
    
    // Location Details
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

    $uploaded_images = [];

    // 1. Handle Camera Images (JSON Array)
    if (!empty($_POST['camera_images'])) {
        $cam_images = json_decode($_POST['camera_images'], true);
        if (is_array($cam_images)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach($cam_images as $data) {
                if(preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                    $data = substr($data, strpos($data, ',') + 1);
                    $type = strtolower($type[1]);
                    $data = base64_decode($data);
                    
                    $new_filename = uniqid() . '_cam.' . $type;
                    if(file_put_contents($upload_dir . $new_filename, $data)) {
                        $uploaded_images[] = $upload_dir . $new_filename;
                    }
                }
            }
        }
    }

    // 2. Handle File Uploads
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        for($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                $filename = $_FILES['images']['name'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid() . "_" . $i . "." . $ext;
                    $target_file = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                        $uploaded_images[] = $target_file;
                    }
                }
            }
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO work_orders (user_id, title, description, category_id, priority, college, block, room_no, requester_phone, additional_comments, requester_first_name, requester_last_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiisssssss", $_SESSION['user_id'], $title, $description, $category_id, $priority, $college, $block, $room, $phone, $comments, $first_name, $last_name);

        if ($stmt->execute()) {
            $work_order_id = $conn->insert_id;
            if (!empty($uploaded_images)) {
                $img_stmt = $conn->prepare("INSERT INTO work_order_images (work_order_id, image_path) VALUES (?, ?)");
                foreach($uploaded_images as $path) {
                    $img_stmt->bind_param("is", $work_order_id, $path);
                    $img_stmt->execute();
                }
                $img_stmt->close();
            }
            $success = "Request submitted successfully! <a href='dashboard.php'>Track Status</a>";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Request - UiTM EduMaintain</title>
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
        .sub-label { font-size: 0.7rem; color: #95a5a6; margin-top: 3px; }
        .btn-submit { background-color: #2ecc71; border: none; padding: 12px 35px; font-weight: 600; font-size: 1rem; border-radius: 4px; color: white; transition: background 0.3s; }
        .btn-submit:hover { background-color: #27ae60; }
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
                    <li><a href="create_request.php" class="active"><i class="fas fa-plus-circle"></i> New Request</a></li>
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
                        <h1>Maintenance Request Form</h1>
                        <p>Please fill out the form to report the maintenance details of the machine or facility.</p>
                    </div>

            <?php if($error): ?>
                <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success mb-4"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Asset / Machine ID</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. AC-UNIT-01">
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="category" class="form-select" required>
                            <option value="">Select Category...</option>
                            <?php
                            $cats = $conn->query("SELECT * FROM categories");
                            while($c = $cats->fetch_assoc()) {
                                echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Issue</label>
                        <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        <div class="sub-label">Date</div>
                    </div>
                </div>

                <div class="section-title">Location Details</div>
                <div class="row mb-4">
                    <div class="col-md-8">
                        <label class="form-label">College / Block</label>
                        <select name="college_block" class="form-select" required>
                            <option value="">Select College / Block...</option>
                            <option value="Kolej Tunku Abdul Rahman">Kolej Tunku Abdul Rahman</option>
                            <option value="Kolej Tun Hussein Onn">Kolej Tun Hussein Onn</option>
                            <option value="Kolej Tun Razak">Kolej Tun Razak</option>
                            <option value="Kolej Tun Dr Mahathir">Kolej Tun Dr Mahathir</option>
                            <option value="Block A">Block A</option>
                            <option value="Block B">Block B</option>
                            <option value="Block C">Block C</option>
                            <option value="Block D">Block D</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Room / Location</label>
                        <input type="text" name="room" class="form-control" placeholder="e.g. Room 205" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Problem Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Describe the problem in detail..." required></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Requester Name</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                                <div class="sub-label">First Name</div>
                            </div>
                            <div class="col-6">
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                                <div class="sub-label">Last Name</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="(000) 000-0000" required>
                        <div class="sub-label">Please enter a valid phone number.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Additional Comments</label>
                    <textarea name="additional_comments" class="form-control" rows="3"></textarea>
                </div>

                <div class="section-title">Photo Evidence</div>
                <div class="mb-4 p-3 border rounded bg-light">
                    <div class="row gx-2 mb-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-primary w-100" id="start-camera">
                                <i class="fas fa-camera me-2"></i> Use Camera
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="btn btn-outline-secondary w-100 mb-0">
                                <i class="fas fa-upload me-2"></i> Upload Files
                                <input type="file" name="images[]" class="d-none" multiple id="file-input">
                            </label>
                        </div>
                    </div>
                    <div id="file-chosen" class="text-center text-muted small fst-italic">No files chosen</div>
                    <div id="photo-gallery" class="row g-2 mt-3"></div>
                    <input type="hidden" name="camera_images" id="camera_images">
                    
                    <div id="camera-container" class="mt-3 text-center" style="display:none;">
                        <video id="camera-stream" class="rounded border w-100" style="max-height: 300px;" autoplay playsinline></video>
                        <canvas id="camera-canvas" class="d-none"></canvas>
                        <button type="button" id="capture-btn" class="btn btn-success mt-2 rounded-pill px-4">
                            <i class="fas fa-circle me-1"></i> Capture Photo
                        </button>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <button type="submit" class="btn btn-submit">Submit Request</button>
                </div>
            </form>
            
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        document.getElementById('file-input').addEventListener('change', function() {
            document.getElementById('file-chosen').textContent = this.files.length + ' files selected';
        });

        // Camera Logic
        const startBtn = document.getElementById('start-camera');
        const camContainer = document.getElementById('camera-container');
        const video = document.getElementById('camera-stream');
        const canvas = document.getElementById('camera-canvas');
        const captureBtn = document.getElementById('capture-btn');
        const gallery = document.getElementById('photo-gallery');
        const camInput = document.getElementById('camera_images');
        let capturedPhotos = [];

        startBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                camContainer.style.display = 'block';
                startBtn.classList.add('d-none');
            } catch (err) {
                alert("Could not access camera: " + err);
            }
        });

        captureBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const dataUrl = canvas.toDataURL('image/png');
            capturedPhotos.push(dataUrl);
            camInput.value = JSON.stringify(capturedPhotos);

            const div = document.createElement('div');
            div.className = 'col-3 position-relative';
            div.innerHTML = `
                <img src="${dataUrl}" class="img-fluid rounded border shadow-sm">
                <span class="position-absolute top-0 end-0 bg-danger text-white rounded-circle px-2" style="cursor:pointer; transform: translate(50%, -50%);" onclick="this.parentElement.remove(); deletePhoto('${dataUrl}')">&times;</span>
            `;
            gallery.appendChild(div);
        });

        function deletePhoto(url) {
            capturedPhotos = capturedPhotos.filter(p => p !== url);
            camInput.value = JSON.stringify(capturedPhotos);
        }
    </script>
</body>
</html>

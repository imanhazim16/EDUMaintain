<?php
require 'config.php';
check_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$role = $_SESSION['role'];

// Handle Image Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $age = intval($_POST['age']);
    $gender = clean_input($_POST['gender']);
    $phone = clean_input($_POST['phone']);
    $avatar_path = $_POST['current_avatar'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . "_avatar." . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_path = $upload_dir . $new_filename;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, age=?, gender=?, phone=?, avatar=? WHERE id=?");
    $stmt->bind_param("ssisssi", $name, $email, $age, $gender, $phone, $avatar_path, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['name'] = $name; // Update session name
        $success = "Profile updated successfully!";
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
    $stmt->close();
}

// Fetch user data
$sql = "SELECT * FROM users WHERE id = $user_id";
$user = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container { width: 100%; }
        .profile-header { background: linear-gradient(135deg, #4A148C, #7B1FA2); height: 180px; border-radius: 15px 15px 0 0; position: relative; }
        .profile-img-wrapper { position: absolute; bottom: -50px; left: 30px; }
        .profile-img { width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; object-fit: cover; background-color: #eee; }
        .profile-card { background: white; border-radius: 0 0 15px 15px; padding: 60px 30px 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .info-label { color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; }
        .info-value { color: #2c3e50; font-size: 1rem; font-weight: 500; }
        .btn-edit { background: #3498db; color: white; border-radius: 20px; padding: 6px 20px; border: none; transition: all 0.3s; font-size: 0.9rem; }
        .btn-edit:hover { background: #2980b9; transform: translateY(-1px); }
        .edit-mode { display: none; }
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
                <?php else: ?>
                    <li><a href="create_request.php"><i class="fas fa-plus-circle"></i> New Request</a></li>
                <?php endif; ?>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>

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
                <div class="profile-container">
        
        <div class="profile-header">
            <div class="profile-img-wrapper">
                <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'uploads/default_avatar.png'); ?>" alt="Avatar" class="profile-img shadow-sm">
            </div>
        </div>
        
        <div class="profile-card">
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div id="view-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-3 py-2 mt-2" style="background-color: #f3e5f5; color: #7B1FA2; border: 1px solid #e1bee7;"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <button class="btn-edit" onclick="toggleEdit()"><i class="fas fa-edit me-2"></i>Edit Profile</button>
                </div>
                
                <hr>
                
                <div class="mt-4">
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light-subtle">
                             <div class="info-label mb-1">Email Address</div>
                             <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light-subtle">
                             <div class="info-label mb-1">Contact Number</div>
                             <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not Provided'); ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light-subtle">
                             <div class="info-label mb-1">Age</div>
                             <div class="info-value"><?php echo $user['age'] ?: 'Not Specified'; ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light-subtle">
                             <div class="info-label mb-1">Gender</div>
                             <div class="info-value"><?php echo htmlspecialchars($user['gender'] ?: 'Not Specified'); ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="p-3 border rounded bg-light-subtle">
                             <div class="info-label mb-1">Joined Date</div>
                             <div class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="edit-section" class="edit-mode">
                <h3 class="fw-bold mb-4">Edit Profile</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="current_avatar" value="<?php echo $user['avatar']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number (Phone)</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" value="<?php echo $user['age']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male" <?php echo $user['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $user['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $user['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Change Avatar</label>
                        <input type="file" name="avatar" class="form-control">
                        <div class="form-text">Choose a new file to change your profile picture.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="update_profile" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                        <button type="button" class="btn btn-outline-secondary px-4" onclick="toggleEdit()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEdit() {
            const view = document.getElementById('view-section');
            const edit = document.getElementById('edit-section');
            if (view.style.display === 'none') {
                view.style.display = 'block';
                edit.style.display = 'none';
            } else {
                view.style.display = 'none';
                edit.style.display = 'block';
            }
        }
    </script>
</body>
</html>

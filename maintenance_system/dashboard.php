<?php
require 'config.php';
check_login();

$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                
                <?php if ($role === 'admin'): ?>
                    <!-- Admin Specific Menu -->
                    <li><a href="view_users.php"><i class="fas fa-users"></i> User Directory</a></li>
                    <li><a href="technician_performance.php"><i class="fas fa-chart-line"></i> Technician Performance</a></li>
                    <li><a href="report.php"><i class="fas fa-file-alt"></i> Report</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                
                <?php elseif ($role === 'technician'): ?>
                    <!-- Technician Specific Menu -->

                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>

                <?php else: ?>
                    <!-- Student/Staff Specific Menu -->

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
            <!-- Top Navbar (Mobile toggle & User info) -->
            <nav class="navbar navbar-expand-lg navbar-light bg-glass mb-4 shadow-sm">
                <div class="container-fluid <?php echo ($role === 'admin') ? 'px-0' : 'px-4'; ?>">
                    <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                        <i class="fas fa-tools me-2"></i>UiTM EduMaintain
                    </a>
                    <div class="d-flex align-items-center">
                        <!-- Notification Bell for Broadcasts -->
                        <div class="dropdown me-3 position-relative">
                            <a href="#" class="text-dark text-decoration-none" id="notiDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell fa-lg"></i>
                                <span id="noti-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                                    0
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notiDropdown" id="noti-list" style="width: 300px; max-height: 400px; overflow-y: auto;">
                                <li class="dropdown-header">Announcements</li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="text-center text-muted small py-2" id="no-noti">No new announcements</li>
                            </ul>
                        </div>

                        <span class="me-3 d-none d-md-block">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                    </div>
                </div>
            </nav>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                fetchAnnouncements();
                // Poll every 30 seconds
                setInterval(fetchAnnouncements, 30000);
            });

            function fetchAnnouncements() {
                fetch('fetch_announcements.php')
                    .then(response => response.json())
                    .then(data => {
                        const list = document.getElementById('noti-list');
                        const badge = document.getElementById('noti-badge');
                        const noNoti = document.getElementById('no-noti');
                        
                        // Clear current list items except header and divider
                        const existingItems = list.querySelectorAll('li:not(.dropdown-header):not(:has(hr))');
                        existingItems.forEach(i => i.remove());

                        if (data.length > 0) {
                            badge.textContent = data.length;
                            badge.classList.remove('d-none');
                            if(noNoti) noNoti.classList.add('d-none');
                            
                            data.forEach(n => {
                                const li = document.createElement('li');
                                li.innerHTML = `<a class="dropdown-item text-wrap small" href="${n.link || '#'}"><i class="fas fa-bullhorn text-primary me-2"></i>${n.message} <br><span class="text-muted" style="font-size:0.75rem">${new Date(n.created_at).toLocaleString()}</span></a>`;
                                list.appendChild(li);
                            });
                        } else {
                            badge.classList.add('d-none');
                            if(noNoti) noNoti.classList.remove('d-none');
                            // Add back the "No announcements" message if it was removed
                            if(!noNoti) {
                                const li = document.createElement('li');
                                li.id = "no-noti";
                                li.className = "text-center text-muted small py-2";
                                li.textContent = "No new announcements";
                                list.appendChild(li);
                            }
                        }
                    });
            }
            </script>

            <div class="<?php echo ($role === 'admin') ? 'p-0' : 'p-4'; ?>">
                <?php
                if ($role === 'admin') {
                    include 'dashboard_admin.php';
                } elseif ($role === 'technician') {
                    include 'dashboard_technician.php';
                } else {
                    include 'dashboard_user.php';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            if(sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>

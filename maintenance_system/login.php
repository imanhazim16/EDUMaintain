<?php
require 'config.php';

$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $role = clean_input($_POST['role']);

    if (empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        // Check user by email first
        $sql = "SELECT id, name, password, role FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Allow login if role matches OR if user is admin or technician
                if ($row['role'] === $role || $row['role'] === 'admin' || $row['role'] === 'technician') {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['role'] = $row['role'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Role mismatch. Please select the correct role.";
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UiTM EduMaintain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            background: linear-gradient(135deg, #2D0854 0%, #4A148C 100%);
            /* Mesh gradient feel - More Purple */
            background-image: 
                radial-gradient(at 0% 0%, hsla(270, 100%, 15%, 1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(280, 100%, 25%, 1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, hsla(290, 100%, 35%, 1) 0, transparent 50%);
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }

        /* Ambient background shapes for depth */
        .ambient-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
        }
        .shape-1 { background: #7B1FA2; width: 400px; height: 400px; top: -100px; left: -100px; }
        .shape-2 { background: #4A148C; width: 300px; height: 300px; bottom: -50px; right: -50px; }

        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            position: relative;
            z-index: 10;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 25px;
            color: #fff;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }
        .brand-logo h2 {
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        /* Glassmorphism Card */
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 40px 30px;
            color: white;
        }

        .card h5 {
            color: #fff;
            font-weight: 600;
            letter-spacing: 1px;
            font-size: 1.5rem;
        }

        /* Role Selector - Neon 3D Pills */
        .role-selector {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            padding: 5px;
            border-radius: 50px;
            margin-bottom: 25px;
            position: relative;
        }
        
        .role-selector input[type="radio"] { display: none; }
        
        .role-selector label {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 50px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            z-index: 2;
        }

        .role-selector input[type="radio"]:checked + label {
            color: #fff;
            text-shadow: 0 0 8px rgba(155, 89, 182, 0.6);
            background: linear-gradient(135deg, #9C27B0, #4A148C);
            box-shadow: 0 4px 15px rgba(156, 39, 176, 0.4);
        }

        /* Inputs */
        .form-label {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(155, 89, 182, 0.5);
            box-shadow: 0 0 15px rgba(155, 89, 182, 0.2);
            color: #fff;
        }

        .form-control::placeholder { color: rgba(255, 255, 255, 0.3); }

        /* Login Button */
        .btn-login {
            background: linear-gradient(90deg, #7B1FA2 0%, #4A148C 100%);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            width: 100%;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(74, 20, 140, 0.4);
            transition: all 0.3s transform;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 20, 140, 0.5);
            background: linear-gradient(90deg, #6A1B9A 0%, #4A148C 100%);
        }

        /* Google Button */
        .btn-google {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-google:hover {
            background: #fff;
            transform: translateY(-1px);
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            margin-top: 25px;
        }
    </style>
</head>
<body>

    <div class="ambient-shape shape-1"></div>
    <div class="ambient-shape shape-2"></div>

    <div class="login-container">
        <div class="brand-logo">
            <h2><i class="fas fa-cube me-2"></i>UiTM EduMaintain</h2>
        </div>
        
        <div class="card">
            <h5 class="text-center mb-4">Welcome Back</h5>
            
            <?php if($error): ?>
                <div class="alert alert-danger text-center p-2 small border-0 bg-danger bg-opacity-25 text-white"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="role-selector">
                    <input type="radio" name="role" id="student" value="student" checked>
                    <label for="student">Student</label>

                    <input type="radio" name="role" id="staff" value="staff">
                    <label for="staff">Staff</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email / Username</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-login">SIGN IN</button>



                <div class="text-center mt-4">
                    <span style="color: rgba(255,255,255,0.6);" class="small">Don't have an account? </span>
                    <a href="register.php" class="text-info small text-decoration-none fw-bold ms-1">Sign Up</a>
                </div>
            </form>
        </div>
        
        <div class="text-center footer-text">
            &copy; 2026 Maintenance System &bull; Privacy & Terms
        </div>
    </div>

</body>
</html>

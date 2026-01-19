<?php
require 'config.php';
check_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        header("Location: technician_performance.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        header("Location: technician_performance.php?error=" . urlencode("Email already registered."));
        exit();
    }

    // Hash password and insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'technician';
    
    // Using prepared statement for better security
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: technician_performance.php?success=" . urlencode("New technician added successfully!"));
    } else {
        header("Location: technician_performance.php?error=" . urlencode("Error processing request."));
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: technician_performance.php");
    exit();
}
?>

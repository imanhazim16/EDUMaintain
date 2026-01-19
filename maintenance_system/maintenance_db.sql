-- Database: maintenance_system

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'staff', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    avatar VARCHAR(255),
    age INT,
    gender VARCHAR(20),
    phone VARCHAR(20)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    base_priority INT NOT NULL DEFAULT 1 -- 1: Low, 2: Medium, 3: High, 4: Critical
);

CREATE TABLE work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    priority INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    assigned_technician_id INT DEFAULT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- New columns from ERD
    college VARCHAR(100),
    block VARCHAR(50),
    room_no VARCHAR(50),
    repair_cost DECIMAL(10,2) DEFAULT 0.00,
    replacement_cost DECIMAL(10,2) DEFAULT 0.00,
    resolution_type ENUM('repair', 'replacement') DEFAULT NULL,
    technician_notes TEXT,
    requester_phone VARCHAR(20),
    additional_comments TEXT,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (assigned_technician_id) REFERENCES users(id)
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    category VARCHAR(50) DEFAULT 'general',
    target_audience VARCHAR(100) DEFAULT 'all',
    expiry_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE work_order_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    image_type ENUM('issue', 'completion') DEFAULT 'issue',
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, base_priority) VALUES 
('Lighting', 1),
('Air Conditioning', 2),
('Plumbing', 3),
('Electrical', 3),
('Safety Hazard', 4),
('Furniture', 1),
('Other', 1);

-- Insert default admin (password: admin123)
-- Hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

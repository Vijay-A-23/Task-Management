-- Database schema for Task Manager application
-- Compatible with MySQL 5.7+ and MariaDB 10.2+

-- Drop database if it exists (be careful with this in production)
DROP DATABASE IF EXISTS task_manager;

-- Create database
CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE task_manager;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_login DATETIME NULL,
    INDEX (email)
) ENGINE=InnoDB;

-- Tasks table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    due_date DATE NOT NULL,
    status ENUM('To-Do', 'In Progress', 'Done') NOT NULL DEFAULT 'To-Do',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (created_by),
    INDEX (status),
    INDEX (due_date)
) ENGINE=InnoDB;

-- Task collaborators table
CREATE TABLE task_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('Viewer', 'Editor', 'Owner') NOT NULL DEFAULT 'Viewer',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (task_id, user_id),
    INDEX (user_id)
) ENGINE=InnoDB;

-- Invitations table
CREATE TABLE invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    invited_email VARCHAR(100) NOT NULL,
    role ENUM('Viewer', 'Editor', 'Owner') NOT NULL DEFAULT 'Viewer',
    token VARCHAR(64) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX (invited_email),
    INDEX (token),
    INDEX (status)
) ENGINE=InnoDB;

-- Insert sample data for demonstration (comment out in production)

-- Sample users (password: password123)
INSERT INTO users (name, email, password_hash, created_at) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('Robert Johnson', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Sample tasks
INSERT INTO tasks (title, description, due_date, status, created_by, created_at, updated_at) VALUES
('Website Redesign', 'Redesign the company website with new branding', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'To-Do', 1, NOW(), NOW()),
('Q3 Financial Report', 'Prepare financial report for Q3', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'In Progress', 1, NOW(), NOW()),
('Client Presentation', 'Prepare presentation for client meeting', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'To-Do', 2, NOW(), NOW()),
('Product Launch', 'Coordinate the launch of new product', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'To-Do', 3, NOW(), NOW()),
('Database Optimization', 'Optimize database queries for better performance', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'In Progress', 2, NOW(), NOW());

-- Sample task collaborators
INSERT INTO task_collaborators (task_id, user_id, role, created_at) VALUES
(1, 2, 'Editor', NOW()),
(1, 3, 'Viewer', NOW()),
(2, 3, 'Viewer', NOW()),
(3, 1, 'Editor', NOW()),
(4, 1, 'Viewer', NOW()),
(4, 2, 'Editor', NOW());

-- Sample invitations
INSERT INTO invitations (task_id, invited_email, role, token, status, created_at) VALUES
(1, 'new_user@example.com', 'Viewer', UUID(), 'pending', NOW()),
(3, 'new_user@example.com', 'Editor', UUID(), 'pending', NOW());

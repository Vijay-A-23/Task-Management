-- SQLite schema for Task Manager application

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    last_login DATETIME NULL
);

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NULL,
    due_date DATE NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('To-Do', 'In Progress', 'Done')) DEFAULT 'To-Do',
    created_by INTEGER NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Task collaborators table
CREATE TABLE IF NOT EXISTS task_collaborators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('Viewer', 'Editor', 'Owner')) DEFAULT 'Viewer',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (task_id, user_id)
);

-- Invitations table
CREATE TABLE IF NOT EXISTS invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    invited_email TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('Viewer', 'Editor', 'Owner')) DEFAULT 'Viewer',
    token TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('pending', 'accepted', 'declined')) DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Sample data
-- Sample users (password: password123)
INSERT INTO users (name, email, password_hash, created_at) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', DATETIME('now')),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', DATETIME('now')),
('Robert Johnson', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', DATETIME('now'));

-- Sample tasks
INSERT INTO tasks (title, description, due_date, status, created_by, created_at, updated_at) VALUES
('Website Redesign', 'Redesign the company website with new branding', DATE('now', '+7 days'), 'To-Do', 1, DATETIME('now'), DATETIME('now')),
('Q3 Financial Report', 'Prepare financial report for Q3', DATE('now', '+14 days'), 'In Progress', 1, DATETIME('now'), DATETIME('now')),
('Client Presentation', 'Prepare presentation for client meeting', DATE('now', '+3 days'), 'To-Do', 2, DATETIME('now'), DATETIME('now')),
('Product Launch', 'Coordinate the launch of new product', DATE('now', '+30 days'), 'To-Do', 3, DATETIME('now'), DATETIME('now')),
('Database Optimization', 'Optimize database queries for better performance', DATE('now', '+10 days'), 'In Progress', 2, DATETIME('now'), DATETIME('now'));

-- Sample task collaborators
INSERT INTO task_collaborators (task_id, user_id, role, created_at) VALUES
(1, 2, 'Editor', DATETIME('now')),
(1, 3, 'Viewer', DATETIME('now')),
(2, 3, 'Viewer', DATETIME('now')),
(3, 1, 'Editor', DATETIME('now')),
(4, 1, 'Viewer', DATETIME('now')),
(4, 2, 'Editor', DATETIME('now'));

-- Sample invitations
INSERT INTO invitations (task_id, invited_email, role, token, status, created_at) VALUES
(1, 'new_user@example.com', 'Viewer', lower(hex(randomblob(16))), 'pending', DATETIME('now')),
(3, 'new_user@example.com', 'Editor', lower(hex(randomblob(16))), 'pending', DATETIME('now'));
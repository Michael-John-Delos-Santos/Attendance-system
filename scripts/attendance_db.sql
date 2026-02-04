DROP DATABASE IF EXISTS attendance_db;
CREATE DATABASE attendance_db;
USE attendance_db;

-- 1. Admin Configuration
CREATE TABLE admin_config (
    id INT PRIMARY KEY CHECK (id = 1),
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    admin_key VARCHAR(255) NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin: admin / password123 | Key: SCHOOL-KEY-2026
INSERT INTO admin_config (id, username, password_hash, admin_key) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SCHOOL-KEY-2026');

-- 2. Grade Level Schedules (NEW TABLE)
CREATE TABLE grade_settings (
    grade_level VARCHAR(50) PRIMARY KEY,
    start_time TIME NOT NULL DEFAULT '08:00:00' -- The time a student is considered "Late"
);

-- Seed Default Times
INSERT INTO grade_settings (grade_level, start_time) VALUES 
('Nursery', '08:00:00'),
('Kinder', '08:00:00'),
('Preparatory', '08:00:00'),
('Grade 1', '07:30:00'),
('Grade 2', '07:30:00'),
('Grade 3', '07:30:00'),
('Grade 4', '07:00:00'),
('Grade 5', '07:00:00'),
('Grade 6', '07:00:00');

-- 3. Students Table
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id_number VARCHAR(50) UNIQUE NOT NULL,
  qr_token VARCHAR(50) UNIQUE NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  grade_level ENUM(
      'Nursery', 'Kinder', 'Preparatory', 
      'Grade 1', 'Grade 2', 'Grade 3', 
      'Grade 4', 'Grade 5', 'Grade 6'
  ) NOT NULL,
  parent_name VARCHAR(100),
  parent_email VARCHAR(100),
  status ENUM('Active','Inactive') DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Attendance Table
CREATE TABLE attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  time_in TIME NOT NULL,
  status ENUM('Present','Absent','Late') DEFAULT 'Present',
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  UNIQUE KEY unique_daily_log (student_id, attendance_date)
);

CREATE INDEX idx_student_qr ON students(qr_token);
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
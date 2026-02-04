-- Updated database schema for School of St. Maximilian Mary Kolbe
-- Create the attendance database and tables for School of St. Maximilian Mary Kolbe
CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Students table
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  roll_number VARCHAR(20) UNIQUE NOT NULL,
  qr_token VARCHAR(10) UNIQUE NOT NULL,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  grade_section VARCHAR(50) NOT NULL,
  parent_name VARCHAR(100),
  parent_email VARCHAR(100),
  status ENUM('Active','Inactive') DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Faculty table
CREATE TABLE faculty (
  faculty_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('Teacher','Admin') DEFAULT 'Teacher',
  status ENUM('Active','Inactive') DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  time_in TIME NOT NULL,
  status ENUM('Present','Absent','Late') DEFAULT 'Present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  UNIQUE KEY unique_student_date (student_id, attendance_date)
);

-- Notifications table for parent email tracking
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  parent_email VARCHAR(100),
  message TEXT,
  email_subject VARCHAR(200),
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Sent','Failed','Pending') DEFAULT 'Pending',
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_notifications_date ON notifications(sent_at);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_roll ON students(roll_number);
CREATE INDEX idx_students_qr ON students(qr_token);

-- Faculty attendance (manual Clock In / Clock Out)
CREATE TABLE faculty_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY faculty_date (faculty_id, attendance_date),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Indexes for faster lookup
CREATE INDEX idx_faculty_attendance_date ON faculty_attendance(attendance_date);
CREATE INDEX idx_faculty_attendance_faculty ON faculty_attendance(faculty_id);


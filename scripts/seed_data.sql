-- Updated seed data with Filipino names and School of St. Maximilian Mary Kolbe context
-- Insert default admin user (password: admin123)
INSERT INTO faculty (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sister Maria Gonzales', 'Admin');

-- Insert sample teachers
INSERT INTO faculty (username, password, full_name, role) VALUES 
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mrs. Carmen Reyes', 'Teacher'),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Jose Santos', 'Teacher');

-- Insert sample students with Filipino names
INSERT INTO students (roll_number, qr_token, first_name, last_name, grade_section, parent_name, parent_email) VALUES 
('ST001', 'XZ91KQ', 'Maria', 'Santos', 'Grade 10-A', 'Rosa Santos', 'rosa.santos@email.com'),
('ST002', 'AB23CD', 'Juan', 'Dela Cruz', 'Grade 10-A', 'Pedro Dela Cruz', 'pedro.delacruz@email.com'),
('ST003', 'EF45GH', 'Ana', 'Rodriguez', 'Grade 10-B', 'Carmen Rodriguez', 'carmen.rodriguez@email.com'),
('ST004', 'IJ67KL', 'Miguel', 'Garcia', 'Grade 10-B', 'Elena Garcia', 'elena.garcia@email.com'),
('ST005', 'MN89OP', 'Sofia', 'Fernandez', 'Grade 11-A', 'Luis Fernandez', 'luis.fernandez@email.com'),
('ST006', 'QR12ST', 'Carlos', 'Martinez', 'Grade 11-A', 'Isabel Martinez', 'isabel.martinez@email.com'),
('ST007', 'UV34WX', 'Isabella', 'Lopez', 'Grade 11-B', 'Roberto Lopez', 'roberto.lopez@email.com'),
('ST008', 'YZ56AB', 'Diego', 'Morales', 'Grade 11-B', 'Patricia Morales', 'patricia.morales@email.com'),
('ST009', 'CD78EF', 'Gabriela', 'Ramos', 'Grade 12-A', 'Fernando Ramos', 'fernando.ramos@email.com'),
('ST010', 'GH90IJ', 'Antonio', 'Herrera', 'Grade 12-A', 'Maria Herrera', 'maria.herrera@email.com');

-- Insert sample attendance records for today
INSERT INTO attendance (student_id, attendance_date, time_in, status) VALUES 
(1, CURDATE(), '07:15:00', 'Present'),
(2, CURDATE(), '07:18:00', 'Present'),
(3, CURDATE(), '07:45:00', 'Late'),
(4, CURDATE(), '07:20:00', 'Present'),
(5, CURDATE(), '07:12:00', 'Present'),
(6, CURDATE(), '07:25:00', 'Present'),
(7, CURDATE(), '07:30:00', 'Present'),
(9, CURDATE(), '07:10:00', 'Present'),
(10, CURDATE(), '07:22:00', 'Present');

-- Insert sample notification records
INSERT INTO notifications (student_id, parent_email, message, email_subject, sent_at, status) VALUES 
(1, 'rosa.santos@email.com', 'Your child, Maria Santos, is present today at School of St. Maximilian Mary Kolbe.', 'Attendance Notification - School of St. Maximilian Mary Kolbe', NOW(), 'Sent'),
(2, 'pedro.delacruz@email.com', 'Your child, Juan Dela Cruz, is present today at School of St. Maximilian Mary Kolbe.', 'Attendance Notification - School of St. Maximilian Mary Kolbe', NOW(), 'Sent'),
(3, 'carmen.rodriguez@email.com', 'Your child, Ana Rodriguez, arrived late today at School of St. Maximilian Mary Kolbe.', 'Late Arrival Notification - School of St. Maximilian Mary Kolbe', NOW(), 'Sent'),
(8, 'patricia.morales@email.com', 'Your child, Diego Morales, is absent today from School of St. Maximilian Mary Kolbe.', 'Absence Notification - School of St. Maximilian Mary Kolbe', NOW(), 'Pending');

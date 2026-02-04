<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Get today's statistics
$today = date('Y-m-d');
$stats = [];

// Total students
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
$stmt->execute();
$stats['total_students'] = $stmt->fetch()['total'];

// Present today
$stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE attendance_date = ? AND status IN ('Present', 'Late')");
$stmt->execute([$today]);
$stats['present_today'] = $stmt->fetch()['present'];

// Absent today
$stats['absent_today'] = $stats['total_students'] - $stats['present_today'];

// Late today
$stmt = $pdo->prepare("SELECT COUNT(*) as late FROM attendance WHERE attendance_date = ? AND status = 'Late'");
$stmt->execute([$today]);
$stats['late_today'] = $stmt->fetch()['late'];

// Recent attendance
$stmt = $pdo->prepare("
    SELECT s.first_name, s.last_name, s.grade_section, a.time_in, a.status 
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    WHERE a.attendance_date = ? 
    ORDER BY a.time_in DESC 
    LIMIT 10
");
$stmt->execute([$today]);
$recent_attendance = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard - <?= SCHOOL_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-700 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 flex-shrink-0 bg-white border-r border-gray-200 p-6">
            <div class="mb-8">
                <h1 class="text-xl font-bold text-blue-700"><?= SCHOOL_NAME ?></h1>
                <p class="text-sm text-gray-500">Attendance System</p>
            </div>
            
            <nav class="flex flex-col space-y-2">
                <a href="index.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">📊 Dashboard</a>
                <a href="scan.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📱 QR Scanner</a>
                <a href="students.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">👥 Students</a>
                <a href="attendance.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📋 Attendance</a>
                <a href="reports.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📈 Reports</a>
                <?php if ($currentUser['role'] === 'Admin'): ?>
                <a href="faculty.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">👨‍🏫 Faculty</a>
                <?php endif; ?>
                <a href="logout.php" class="px-3 py-2 rounded-lg hover:bg-red-100 text-red-600 font-medium">🚪 Logout</a>
            </nav>
            
            <div class="mt-8 p-4 bg-blue-50 rounded-lg text-center">
                <p class="text-sm font-medium">Welcome back,</p>
                <p class="font-semibold text-blue-700"><?= htmlspecialchars($currentUser['full_name']) ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h2>
                <p class="text-gray-500">Today's attendance overview - <?= date('F j, Y') ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Total Students</p>
                        <p class="text-2xl font-bold text-blue-700"><?= $stats['total_students'] ?></p>
                    </div>
                    <div class="text-3xl">👥</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Present Today</p>
                        <p class="text-2xl font-bold text-green-600"><?= $stats['present_today'] ?></p>
                    </div>
                    <div class="text-3xl">✅</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Absent Today</p>
                        <p class="text-2xl font-bold text-red-600"><?= $stats['absent_today'] ?></p>
                    </div>
                    <div class="text-3xl">❌</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Late Today</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $stats['late_today'] ?></p>
                    </div>
                    <div class="text-3xl">⏰</div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-xl font-semibold mb-4">Recent Attendance</h3>
                <?php if (empty($recent_attendance)): ?>
                    <p class="text-gray-500 text-center py-8">No attendance records for today yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-2 font-medium">Student Name</th>
                                    <th class="text-left px-4 py-2 font-medium">Section</th>
                                    <th class="text-left px-4 py-2 font-medium">Time In</th>
                                    <th class="text-left px-4 py-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-medium"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($record['grade_section']) ?></td>
                                    <td class="px-4 py-2"><?= date('g:i A', strtotime($record['time_in'])) ?></td>
                                    <td class="px-4 py-2">
                                        <?php
                                            $status = strtolower($record['status']);
                                            $color = $status === 'present' ? 'green' : ($status === 'absent' ? 'red' : 'yellow');
                                        ?>
                                        <span class="px-2 py-1 rounded-full font-semibold text-white bg-<?= $color ?>-600">
                                            <?= $record['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <div class="text-4xl mb-4">📱</div>
                    <h3 class="text-lg font-semibold mb-2">QR Scanner</h3>
                    <p class="text-gray-500 mb-4">Scan student QR codes for attendance</p>
                    <a href="scan.php" class="inline-block bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold hover:opacity-90">Start Scanning</a>
                </div>

                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <div class="text-4xl mb-4">👥</div>
                    <h3 class="text-lg font-semibold mb-2">Manage Students</h3>
                    <p class="text-gray-500 mb-4">Add, edit, or view student information</p>
                    <a href="students.php" class="inline-block bg-pink-500 text-white px-4 py-2 rounded-lg font-semibold hover:opacity-90">Manage Students</a>
                </div>

                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <div class="text-4xl mb-4">📈</div>
                    <h3 class="text-lg font-semibold mb-2">Generate Reports</h3>
                    <p class="text-gray-500 mb-4">Export attendance data to Excel</p>
                    <a href="reports.php" class="inline-block bg-pink-500 text-white px-4 py-2 rounded-lg font-semibold hover:opacity-90">View Reports</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

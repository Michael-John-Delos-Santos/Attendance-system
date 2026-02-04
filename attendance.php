<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

$today = date('Y-m-d');
$section = $_GET['section'] ?? '';

// Get list of sections from students table
$stmt = $pdo->query("SELECT DISTINCT grade_section FROM students WHERE status='Active' ORDER BY grade_section ASC");
$sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch attendance for selected section
$attendance = [];
if ($section) {
    $stmt = $pdo->prepare("
        SELECT s.first_name, s.last_name, s.grade_section, a.time_in, a.status 
        FROM attendance a 
        JOIN students s ON a.student_id = s.student_id 
        WHERE a.attendance_date = ? AND s.grade_section = ? 
        ORDER BY a.time_in ASC
    ");
    $stmt->execute([$today, $section]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Today - <?= SCHOOL_NAME ?></title>
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
                <a href="index.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📊 Dashboard</a>
                <a href="scan.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📱 QR Scanner</a>
                <a href="students.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">👥 Students</a>
                <a href="attendance.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">📋 Attendance</a>
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

    <!-- Main content -->
    <div class="flex-1 p-6">
        <h2 class="text-3xl font-bold mb-4">Attendance Today - <?= date('F j, Y') ?></h2>

        <!-- Section Filter -->
        <form method="get" class="mb-6">
            <label for="section" class="mr-2 font-medium">Select Section:</label>
            <select name="section" id="section" class="px-3 py-2 border rounded">
                <option value="">-- Choose Section --</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= htmlspecialchars($sec) ?>" <?= $sec === $section ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sec) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="ml-2 px-4 py-2 bg-blue-700 text-white rounded hover:bg-blue-800">View</button>
        </form>

        <!-- Attendance Table -->
        <?php if ($section): ?>
            <?php if (empty($attendance)): ?>
                <p class="text-gray-500">No students present today in section <?= htmlspecialchars($section) ?>.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse bg-white shadow rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Student Name</th>
                                <th class="px-4 py-2 text-left">Section</th>
                                <th class="px-4 py-2 text-left">Time In</th>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $record): ?>
                                <?php
                                    $status = strtolower($record['status']);
                                    $color = $status === 'present' ? 'green' : ($status === 'late' ? 'yellow' : 'red');
                                ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-medium"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($record['grade_section']) ?></td>
                                    <td class="px-4 py-2"><?= date('g:i A', strtotime($record['time_in'])) ?></td>
                                    <td class="px-4 py-2">
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
        <?php else: ?>
            <p class="text-gray-500">Select a section to view today's attendance.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

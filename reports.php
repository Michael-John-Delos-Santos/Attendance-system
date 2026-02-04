<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Get today's date
$today = date('Y-m-d');

// Get all sections for dropdown
$stmt = $pdo->prepare("SELECT DISTINCT grade_section FROM students WHERE status='Active'");
$stmt->execute();
$sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Selected section filter
$selected_section = $_GET['section'] ?? '';

// Fetch attendance for today, optionally filtered by section
$sql = "
    SELECT s.first_name, s.last_name, s.grade_section, s.parent_email, a.time_in, a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    WHERE a.attendance_date = ?
";
$params = [$today];

if ($selected_section) {
    $sql .= " AND s.grade_section = ?";
    $params[] = $selected_section;
}

$sql .= " ORDER BY s.grade_section, a.time_in ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export to CSV/Excel if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $today . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Section', 'Time In', 'Status', 'Parent Email']);

    foreach ($attendance as $row) {
        fputcsv($output, [
            $row['first_name'] . ' ' . $row['last_name'],
            $row['grade_section'],
            date('g:i A', strtotime($row['time_in'])),
            $row['status'],
            $row['parent_email']
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?= SCHOOL_NAME ?></title>
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
                <a href="attendance.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📋 Attendance</a>
                <a href="reports.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">📈 Reports</a>
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
        <h2 class="text-3xl font-bold mb-2">Attendance Reports</h2>
        <p class="text-gray-500 mb-6">Today's attendance - <?= date('F j, Y') ?></p>

        <!-- Section Filter -->
        <form method="get" class="mb-6 flex items-center space-x-4">
            <label for="section" class="font-medium">Filter by Section:</label>
            <select name="section" id="section" class="border border-gray-300 rounded px-3 py-2">
                <option value="">All Sections</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?= htmlspecialchars($section) ?>" <?= $section === $selected_section ? 'selected' : '' ?>>
                        <?= htmlspecialchars($section) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:opacity-90">Filter</button>
            <a href="?<?= $selected_section ? "section=$selected_section&" : '' ?>export=csv" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:opacity-90">Export CSV</a>
        </form>

        <!-- Attendance Table -->
        <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
            <?php if (empty($attendance)): ?>
                <p class="text-gray-500 text-center py-8">No attendance records for today.</p>
            <?php else: ?>
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium">Student Name</th>
                            <th class="text-left px-4 py-2 font-medium">Section</th>
                            <th class="text-left px-4 py-2 font-medium">Time In</th>
                            <th class="text-left px-4 py-2 font-medium">Status</th>
                            <th class="text-left px-4 py-2 font-medium">Parent Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $row): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['grade_section']) ?></td>
                            <td class="px-4 py-2"><?= date('g:i A', strtotime($row['time_in'])) ?></td>
                            <td class="px-4 py-2">
                                <?php
                                    $status = strtolower($row['status']);
                                    $color = $status === 'present' ? 'green' : ($status === 'late' ? 'yellow' : 'red');
                                ?>
                                <span class="px-2 py-1 rounded-full font-semibold text-white bg-<?= $color ?>-600">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['parent_email']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

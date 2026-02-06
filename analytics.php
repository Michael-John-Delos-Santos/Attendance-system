<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Student Analytics');

$pdo = getDBConnection();

// --- FILTERS ---
$startDate = $_GET['start'] ?? date('Y-m-01'); // Default to 1st of month
$endDate   = $_GET['end']   ?? date('Y-m-d');  // Default to today
$grade     = $_GET['grade'] ?? '';

// --- BASE QUERY ---
// We count the status occurrences for each student within the date range
$sql = "SELECT s.student_id_number, s.first_name, s.last_name, s.grade_level,
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as total_present,
        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as total_late,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as total_absent
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id 
             AND a.attendance_date BETWEEN :start AND :end
        WHERE s.status = 'Active'";

$params = ['start' => $startDate, 'end' => $endDate];

if (!empty($grade)) {
    $sql .= " AND s.grade_level = :grade";
    $params['grade'] = $grade;
}

$sql .= " GROUP BY s.student_id ORDER BY s.last_name ASC";

// --- EXPORT CSV HANDLER ---
if (isset($_POST['export_analytics'])) {
    if (verifyAdminKey($_POST['admin_key'])) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Analytics_' . $startDate . '_to_' . $endDate . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student ID', 'Last Name', 'First Name', 'Grade', 'Total Present', 'Total Late', 'Total Absent']);
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['student_id_number'],
                $row['last_name'],
                $row['first_name'],
                $row['grade_level'],
                $row['total_present'],
                $row['total_late'],
                $row['total_absent']
            ]);
        }
        fclose($output);
        exit;
    } else {
        $error = "Invalid Admin Key!";
    }
}

// --- FETCH DATA FOR VIEW ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Grand Totals for Cards
$grandPresent = array_sum(array_column($analytics, 'total_present'));
$grandLate    = array_sum(array_column($analytics, 'total_late'));
$grandAbsent  = array_sum(array_column($analytics, 'total_absent'));

require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <header class="mb-8">
            <h2 class="text-3xl font-bold tracking-tight text-primary">Student Analytics</h2>
            <p class="text-muted-foreground mt-1">Performance summary and attendance counts per student.</p>
        </header>

        <div class="bg-card p-6 rounded-lg border shadow-sm mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Start Date</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>" class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">End Date</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>" class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Grade Level</label>
                    <select name="grade" class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                        <option value="">All Levels</option>
                        <?php foreach (['Nursery', 'Kinder', 'Preparatory', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'] as $g): ?>
                            <option value="<?= $g ?>" <?= $grade == $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-primary-foreground px-6 py-2 rounded font-bold hover:opacity-90 shadow transition-all">
                    <i class="fa-solid fa-filter"></i> Apply Filters
                </button>
                <div class="flex-1"></div>
                <button type="button" onclick="document.getElementById('exportModal').showModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow flex items-center gap-2 font-medium">
                    <i class="fa-solid fa-file-csv"></i> Export Summary
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-card p-6 rounded-lg border shadow-sm flex items-center justify-between border-l-4 border-green-500">
                <div>
                    <p class="text-sm font-bold text-muted-foreground uppercase">Total Present</p>
                    <p class="text-3xl font-bold text-green-600"><?= $grandPresent ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>
            <div class="bg-card p-6 rounded-lg border shadow-sm flex items-center justify-between border-l-4 border-yellow-500">
                <div>
                    <p class="text-sm font-bold text-muted-foreground uppercase">Total Late</p>
                    <p class="text-3xl font-bold text-yellow-600"><?= $grandLate ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 text-xl">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="bg-card p-6 rounded-lg border shadow-sm flex items-center justify-between border-l-4 border-red-500">
                <div>
                    <p class="text-sm font-bold text-muted-foreground uppercase">Total Absent</p>
                    <p class="text-3xl font-bold text-red-600"><?= $grandAbsent ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600 text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-lg border shadow-sm overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground font-bold uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3">Student Name</th>
                        <th class="px-6 py-3">Grade</th>
                        <th class="px-6 py-3 text-center text-green-700">Present</th>
                        <th class="px-6 py-3 text-center text-yellow-700">Late</th>
                        <th class="px-6 py-3 text-center text-red-700">Absent</th>
                        <th class="px-6 py-3 text-center">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (count($analytics) > 0): ?>
                        <?php foreach ($analytics as $row): 
                            $totalDays = $row['total_present'] + $row['total_late'] + $row['total_absent'];
                            $rate = $totalDays > 0 ? round((($row['total_present'] + $row['total_late']) / $totalDays) * 100) : 0;
                            $rateColor = $rate >= 80 ? 'text-green-600' : ($rate >= 50 ? 'text-yellow-600' : 'text-red-600');
                        ?>
                        <tr class="hover:bg-muted/50 transition">
                            <td class="px-6 py-4 font-medium text-foreground">
                                <?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?>
                                <div class="text-[10px] text-muted-foreground"><?= htmlspecialchars($row['student_id_number']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-muted px-2 py-1 rounded text-xs border font-semibold"><?= htmlspecialchars($row['grade_level']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-green-600 bg-green-50/50">
                                <?= $row['total_present'] ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-yellow-600 bg-yellow-50/50">
                                <?= $row['total_late'] ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-red-600 bg-red-50/50">
                                <?= $row['total_absent'] ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold <?= $rateColor ?>">
                                <?= $rate ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-muted-foreground">No data found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<dialog id="exportModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-2 text-green-600">Export Analytics</h3>
        <p class="mb-4 text-sm text-muted-foreground">Enter Admin Key to download the CSV summary.</p>
        
        <input type="password" name="admin_key" placeholder="Enter Admin Key" required class="w-full border p-2 mb-4 rounded bg-background focus:ring-2 focus:ring-green-500 outline-none">
        
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('exportModal').close()" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="export_analytics" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">Download</button>
        </div>
    </form>
</dialog>
</body>
</html>
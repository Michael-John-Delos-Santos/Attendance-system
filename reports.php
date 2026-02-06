<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Reports');

$pdo = getDBConnection();
$message = '';
$messageType = '';

// --- HELPER: SORT LINK GENERATOR ---
$sort = $_GET['sort'] ?? 'attendance_date';
$dir  = $_GET['dir'] ?? 'DESC';

function getSortLink($column, $label, $currentSort, $currentDir) {
    $nextDir = ($currentSort === $column && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    
    // Build URL params
    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = $nextDir;
    $url = '?' . http_build_query($params);
    
    // Icon logic
    $icon = 'fa-sort text-muted-foreground/30';
    if ($currentSort === $column) {
        $icon = ($currentDir === 'ASC') ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary';
    }
    
    return "<a href='$url' class='group flex items-center gap-2 cursor-pointer select-none hover:text-primary transition-colors' title='Click to Sort'>
                $label <i class='fa-solid $icon'></i>
            </a>";
}

// --- 1. HANDLE CSV EXPORT (Now supports Sorting) ---
if (isset($_POST['export_csv'])) {
    if (verifyAdminKey($_POST['admin_key'])) {
        // Retrieve filters
        $startDate = $_POST['ex_start_date'];
        $endDate   = $_POST['ex_end_date'];
        $grade     = $_POST['ex_grade'];
        $statusF   = $_POST['ex_status'];
        
        // Retrieve Sorting (NEW)
        $exSort    = $_POST['ex_sort'] ?? 'attendance_date';
        $exDir     = $_POST['ex_dir'] ?? 'DESC';

        // Validate Sorting (Security)
        $allowedSorts = ['attendance_date', 'last_name', 'grade_level', 'time_in', 'time_out', 'status'];
        if (!in_array($exSort, $allowedSorts)) $exSort = 'attendance_date';
        if ($exDir !== 'ASC' && $exDir !== 'DESC') $exDir = 'DESC';

        // Build Query
        $sql = "SELECT a.attendance_date, a.time_in, a.time_out, s.student_id_number, s.first_name, s.last_name, s.grade_level, a.status 
                FROM attendance a 
                JOIN students s ON a.student_id = s.student_id 
                WHERE a.attendance_date BETWEEN :start AND :end";
        
        $params = ['start' => $startDate, 'end' => $endDate];
        
        if (!empty($grade)) { $sql .= " AND s.grade_level = :grade"; $params['grade'] = $grade; }
        if (!empty($statusF)) { $sql .= " AND a.status = :status"; $params['status'] = $statusF; }
        
        // Apply Dynamic Sort
        $sql .= " ORDER BY $exSort $exDir";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Attendance_Report_' . $startDate . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Time In', 'Time Out', 'Student ID', 'Last Name', 'First Name', 'Grade Level', 'Status']);
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['attendance_date'],
                date('g:i A', strtotime($row['time_in'])),
                $row['time_out'] ? date('g:i A', strtotime($row['time_out'])) : '--',
                $row['student_id_number'],
                $row['last_name'],
                $row['first_name'],
                $row['grade_level'],
                $row['status']
            ]);
        }
        fclose($output);
        exit();
    } else {
        $message = "Invalid Admin Key! Export denied."; $messageType = 'red';
    }
}

// --- 2. HANDLE CLEAR LOGS ---
if (isset($_POST['clear_logs'])) {
    if (verifyAdminKey($_POST['admin_key'])) {
        try {
            $pdo->exec("DELETE FROM attendance");
            $message = "All attendance logs have been cleared."; $messageType = 'green';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); $messageType = 'red';
        }
    } else {
        $message = "Invalid Admin Key!"; $messageType = 'red';
    }
}

// --- 3. VIEW QUERY ---
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$gradeFilter = $_GET['grade'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT a.*, s.first_name, s.last_name, s.grade_level, s.student_id_number 
        FROM attendance a 
        JOIN students s ON a.student_id = s.student_id 
        WHERE a.attendance_date BETWEEN :start AND :end";

$params = ['start' => $startDate, 'end' => $endDate];

if (!empty($gradeFilter)) { $sql .= " AND s.grade_level = :grade"; $params['grade'] = $gradeFilter; }
if (!empty($statusFilter)) { $sql .= " AND a.status = :status"; $params['status'] = $statusFilter; }

// Validate Sort for View
$allowedSorts = ['attendance_date', 'last_name', 'grade_level', 'time_in', 'time_out', 'status'];
if (!in_array($sort, $allowedSorts)) $sort = 'attendance_date';
if ($dir !== 'ASC' && $dir !== 'DESC') $dir = 'DESC';

$sql .= " ORDER BY $sort $dir";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Stats
$totalPresent = 0; $totalLate = 0;
foreach ($logs as $l) {
    if ($l['status'] == 'Present') $totalPresent++;
    if ($l['status'] == 'Late') $totalLate++;
}

require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-primary">Attendance Reports</h2>
                <p class="text-muted-foreground mt-1">Click column headers to sort. The export will match your sorting.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md border <?= $messageType === 'green' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-destructive/10 border-destructive/20 text-destructive' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-card p-6 rounded-lg border shadow-sm mb-8">
            <form class="flex flex-col lg:flex-row gap-4 items-end" method="GET" id="filterForm">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

                <div class="w-full lg:w-auto">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border p-2 rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="w-full lg:w-auto">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border p-2 rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div class="w-full lg:w-48">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Grade Level</label>
                    <select name="grade" class="w-full border p-2 rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                        <option value="">All Levels</option>
                        <?php foreach (['Nursery', 'Kinder', 'Preparatory', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'] as $g) {
                            $sel = ($gradeFilter == $g) ? 'selected' : '';
                            echo "<option value='$g' $sel>$g</option>";
                        } ?>
                    </select>
                </div>
                <div class="w-full lg:w-40">
                    <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Status</label>
                    <select name="status" class="w-full border p-2 rounded bg-background focus:ring-2 focus:ring-primary outline-none">
                        <option value="">All Statuses</option>
                        <option value="Present" <?= $statusFilter == 'Present' ? 'selected' : '' ?>>Present</option>
                        <option value="Late" <?= $statusFilter == 'Late' ? 'selected' : '' ?>>Late</option>
                    </select>
                </div>
                
                <button type="submit" class="bg-primary text-primary-foreground px-6 py-2 rounded font-bold hover:opacity-90 shadow transition-all">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>

                <div class="flex-1"></div>

                <div class="flex gap-2">
                    <button type="button" onclick="openExportModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow flex items-center gap-2 font-medium">
                        <i class="fa-solid fa-file-csv"></i> Export
                    </button>
                    <button type="button" onclick="document.getElementById('clearModal').showModal()" class="bg-destructive/10 text-destructive border border-destructive/20 px-4 py-2 rounded hover:bg-destructive hover:text-white shadow-sm flex items-center gap-2 font-medium">
                        <i class="fa-solid fa-trash"></i> Clear
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 max-w-2xl">
            <div class="bg-card p-4 rounded-lg border shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-muted-foreground">Total Records</p><p class="text-2xl font-bold"><?= count($logs) ?></p></div>
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center"><i class="fa-solid fa-list"></i></div>
            </div>
            <div class="bg-card p-4 rounded-lg border shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-muted-foreground">Present</p><p class="text-2xl font-bold text-green-600"><?= $totalPresent ?></p></div>
                <div class="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center"><i class="fa-solid fa-check"></i></div>
            </div>
            <div class="bg-card p-4 rounded-lg border shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-muted-foreground">Late</p><p class="text-2xl font-bold text-yellow-600"><?= $totalLate ?></p></div>
                <div class="w-10 h-10 bg-yellow-50 text-yellow-600 rounded-full flex items-center justify-center"><i class="fa-solid fa-clock"></i></div>
            </div>
        </div>

        <div class="bg-card rounded-lg border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted text-muted-foreground font-medium border-b border-border">
                        <tr>
                            <th class="px-6 py-3 whitespace-nowrap"><?= getSortLink('attendance_date', 'Date', $sort, $dir) ?></th>
                            <th class="px-6 py-3 whitespace-nowrap"><?= getSortLink('last_name', 'Student Name', $sort, $dir) ?></th>
                            <th class="px-6 py-3 whitespace-nowrap"><?= getSortLink('grade_level', 'Grade', $sort, $dir) ?></th>
                            <th class="px-6 py-3 whitespace-nowrap">Time in</th>
                            <th class="px-6 py-3 whitespace-nowrap">Time out</th>
                            <th class="px-6 py-3 whitespace-nowrap"><?= getSortLink('status', 'Status', $sort, $dir) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $row): ?>
                            <tr class="hover:bg-muted/50 transition">
                                <td class="px-6 py-4 text-muted-foreground font-mono text-xs">
                                    <?= date('M j, Y', strtotime($row['attendance_date'])) ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-foreground">
                                    <?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?>
                                    <div class="text-xs text-muted-foreground font-normal"><?= htmlspecialchars($row['student_id_number']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="?grade=<?= urlencode($row['grade_level']) ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="bg-muted px-2 py-1 rounded text-xs font-semibold text-muted-foreground border hover:bg-primary hover:text-white transition-colors">
                                        <?= htmlspecialchars($row['grade_level']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-mono text-green-700">
                                    <?= date('g:i A', strtotime($row['time_in'])) ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-blue-700">
                                    <?= $row['time_out'] ? date('g:i A', strtotime($row['time_out'])) : '<span class="text-muted-foreground/40">--:--</span>' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="?status=<?= $row['status'] ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border hover:opacity-80 transition-opacity <?= $row['status'] == 'Late' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-green-50 text-green-700 border-green-200' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $row['status'] == 'Late' ? 'bg-yellow-500' : 'bg-green-500' ?>"></span>
                                        <?= $row['status'] ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic">No records found matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<dialog id="exportModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-2 text-green-600">Confirm CSV Export</h3>
        <p class="mb-4 text-sm text-muted-foreground">This export will match your current sort order and filters.</p>
        
        <input type="hidden" name="ex_start_date" id="ex_start">
        <input type="hidden" name="ex_end_date" id="ex_end">
        <input type="hidden" name="ex_grade" id="ex_grade">
        <input type="hidden" name="ex_status" id="ex_status">

        <input type="hidden" name="ex_sort" id="ex_sort">
        <input type="hidden" name="ex_dir" id="ex_dir">

        <input type="password" name="admin_key" placeholder="Enter Admin Key" required class="w-full border p-2 mb-4 rounded bg-background focus:ring-2 focus:ring-green-500 outline-none">
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('exportModal').close()" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="export_csv" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700 shadow">Download</button>
        </div>
    </form>
</dialog>

<dialog id="clearModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-2 text-destructive">⚠️ Clear All History?</h3>
        <p class="mb-4 text-sm text-muted-foreground">This will delete <b>ALL</b> attendance records forever.</p>
        <input type="password" name="admin_key" placeholder="Admin Key" required class="w-full border p-2 mb-4 rounded bg-background focus:ring-2 focus:ring-destructive outline-none">
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('clearModal').close()" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="clear_logs" class="bg-destructive text-destructive-foreground px-4 py-2 rounded font-bold hover:bg-destructive/90 shadow">Delete All</button>
        </div>
    </form>
</dialog>

<script>
    function openExportModal() {
        // Copy Filters
        document.getElementById('ex_start').value = document.getElementsByName('start_date')[0].value;
        document.getElementById('ex_end').value = document.getElementsByName('end_date')[0].value;
        document.getElementById('ex_grade').value = document.getElementsByName('grade')[0].value;
        document.getElementById('ex_status').value = document.getElementsByName('status')[0].value;
        
        // Copy Sort State (NEW)
        document.getElementById('ex_sort').value = document.getElementsByName('sort')[0].value;
        document.getElementById('ex_dir').value = document.getElementsByName('dir')[0].value;

        document.getElementById('exportModal').showModal();
    }
</script>
</body>
</html>
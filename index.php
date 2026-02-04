<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Dashboard');

$pdo = getDBConnection();
$today = date('Y-m-d');

// --- 1. STATISTICS CALCULATIONS ---

// A. Total Active Students
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();

// B. On Time (Strictly 'Present' status)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'Present'");
$stmt->execute([$today]);
$onTimeToday = $stmt->fetchColumn();

// C. Late (Strictly 'Late' status)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'Late'");
$stmt->execute([$today]);
$lateToday = $stmt->fetchColumn();

// D. Absent (Total - (On Time + Late))
$absentToday = $totalStudents - ($onTimeToday + $lateToday);

// --- 2. RECENT ACTIVITY LOGS ---
$stmt = $pdo->prepare("
    SELECT s.first_name, s.last_name, s.grade_level, a.time_in, a.status 
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    WHERE a.attendance_date = ? 
    ORDER BY a.time_in DESC 
    LIMIT 10
");
$stmt->execute([$today]);
$recentScans = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-primary">Dashboard</h2>
                <p class="text-muted-foreground mt-1">Overview for <?= date('l, F j, Y') ?></p>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-card p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Students</p>
                        <h3 class="text-3xl font-bold mt-2 text-foreground"><?= number_format($totalStudents) ?></h3>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 h-1 w-full bg-blue-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 w-full"></div>
                </div>
            </div>

            <div class="bg-card p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">On Time</p>
                        <h3 class="text-3xl font-bold mt-2 text-green-600"><?= number_format($onTimeToday) ?></h3>
                    </div>
                    <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                        <i class="fa-solid fa-check text-xl"></i>
                    </div>
                </div>
                <?php $presentPct = $totalStudents > 0 ? ($onTimeToday / $totalStudents) * 100 : 0; ?>
                <div class="mt-4 h-1 w-full bg-green-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500" style="width: <?= $presentPct ?>%"></div>
                </div>
            </div>

            <div class="bg-card p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Late</p>
                        <h3 class="text-3xl font-bold mt-2 text-yellow-600"><?= number_format($lateToday) ?></h3>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                        <i class="fa-solid fa-clock text-xl"></i>
                    </div>
                </div>
                <?php $latePct = $totalStudents > 0 ? ($lateToday / $totalStudents) * 100 : 0; ?>
                <div class="mt-4 h-1 w-full bg-yellow-100 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-500" style="width: <?= $latePct ?>%"></div>
                </div>
            </div>

            <div class="bg-card p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Absent</p>
                        <h3 class="text-3xl font-bold mt-2 text-red-500"><?= number_format($absentToday) ?></h3>
                    </div>
                    <div class="p-3 bg-red-50 text-red-500 rounded-lg">
                        <i class="fa-solid fa-user-xmark text-xl"></i>
                    </div>
                </div>
                <?php $absentPct = $totalStudents > 0 ? ($absentToday / $totalStudents) * 100 : 0; ?>
                <div class="mt-4 h-1 w-full bg-red-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500" style="width: <?= $absentPct ?>%"></div>
                </div>
            </div>

        </div>

        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-border bg-muted/10 flex justify-between items-center">
                <h3 class="font-bold text-foreground flex items-center gap-2">
                    <i class="fa-solid fa-list-ul text-muted-foreground"></i> Recent Scans
                </h3>
                <a href="reports.php" class="text-sm text-primary hover:underline font-medium">View Full Report →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground font-medium border-b border-border">
                        <tr>
                            <th class="px-6 py-3">Student Name</th>
                            <th class="px-6 py-3">Grade Level</th>
                            <th class="px-6 py-3">Time In</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php if (count($recentScans) > 0): ?>
                            <?php foreach ($recentScans as $row): ?>
                                <tr class="hover:bg-muted/50 transition">
                                    <td class="px-6 py-4 font-medium text-foreground">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground">
                                            <?= htmlspecialchars($row['grade_level']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-muted-foreground">
                                        <?= date('g:i A', strtotime($row['time_in'])) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border 
                                            <?= $row['status'] == 'Late' 
                                                ? 'bg-yellow-50 text-yellow-700 border-yellow-200' 
                                                : 'bg-green-50 text-green-700 border-green-200' ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $row['status'] == 'Late' ? 'bg-yellow-500' : 'bg-green-500' ?>"></span>
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-muted-foreground italic flex flex-col items-center justify-center">
                                    <div class="bg-muted/50 p-4 rounded-full mb-3">
                                        <i class="fa-regular fa-clipboard text-2xl"></i>
                                    </div>
                                    No attendance records found for today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>
</body>
</html>
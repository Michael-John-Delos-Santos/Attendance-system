<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Schedule');

$pdo = getDBConnection();
$message = '';
$msgType = '';

// Handle Schedule Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    if (verifyAdminKey($_POST['admin_key'])) {
        try {
            $pdo->beginTransaction();
            
            // 1. Update Global Dismissal Window
            $window = (int)$_POST['dismissal_window'];
            $pdo->prepare("UPDATE admin_config SET dismissal_window = ? WHERE id = 1")->execute([$window]);

            // 2. Update Grade Schedules
            $stmt = $pdo->prepare("INSERT INTO grade_settings (grade_level, start_time, end_time) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)");
            
            foreach ($_POST['schedules'] as $level => $times) {
                $start = date('H:i:s', strtotime($times['start']));
                $end   = date('H:i:s', strtotime($times['end']));
                $stmt->execute([$level, $start, $end]);
            }
            
            $pdo->commit();
            $message = "Settings updated successfully!"; $msgType = 'green';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage(); $msgType = 'red';
        }
    } else {
        $message = "Invalid Admin Key!"; $msgType = 'red';
    }
}

// Fetch Data
$settings = $pdo->query("SELECT * FROM grade_settings")->fetchAll(PDO::FETCH_UNIQUE);
$globalConfig = $pdo->query("SELECT dismissal_window FROM admin_config WHERE id = 1")->fetch();
$dismissalWindow = $globalConfig['dismissal_window'] ?? 45;
$allGrades = ['Nursery', 'Kinder', 'Preparatory', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <form method="POST" class="space-y-6"> <header class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-primary">Schedule & Restrictions</h2>
                    <p class="text-muted-foreground mt-1">Manage arrival, dismissal, and time-out rules.</p>
                </div>
                
                <div class="bg-card border p-2 rounded-lg shadow-sm flex items-center gap-3">
                    <input type="password" name="admin_key" required placeholder="Admin Key" 
                           class="w-40 p-2 text-sm border rounded bg-background focus:ring-2 focus:ring-primary outline-none"
                           title="Enter Admin Key to Save">
                    <button type="submit" name="update_schedule" 
                            class="bg-primary text-primary-foreground px-6 py-2 rounded font-bold hover:opacity-90 shadow transition-all flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-md border <?= $msgType === 'green' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-destructive/10 border-destructive/20 text-destructive' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-card p-6 rounded-lg border shadow-sm border-l-4 border-yellow-400">
                <h3 class="font-bold text-lg mb-2 flex items-center gap-2 text-foreground">
                    <i class="fa-solid fa-ban text-yellow-500"></i> Early Time-Out Restriction
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Prevent students from scanning out too early. They can only time out within this window before dismissal.
                </p>
                <div class="flex items-center gap-4 bg-muted/30 p-4 rounded-lg inline-flex">
                    <label class="font-semibold text-sm text-foreground">Allow Time-Out:</label>
                    <input type="number" name="dismissal_window" value="<?= $dismissalWindow ?>" min="0" max="120"
                           class="w-20 p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none text-center font-bold text-lg">
                    <span class="text-sm font-semibold text-foreground">minutes before dismissal.</span>
                </div>
            </div>

            <div class="bg-card p-8 rounded-lg border shadow-sm">
                <h3 class="font-bold text-lg mb-6 text-foreground">Grade Level Timings</h3>
                <div class="grid grid-cols-1 gap-4">
                    
                    <div class="hidden md:flex gap-4 px-4 pb-2 text-xs font-bold text-muted-foreground uppercase tracking-wider">
                        <div class="w-32">Grade Level</div>
                        <div class="flex-1">Late Threshold (Arrival)</div>
                        <div class="flex-1">Dismissal Time (Departure)</div>
                    </div>

                    <?php foreach ($allGrades as $grade): ?>
                        <?php 
                            $sTime = $settings[$grade]['start_time'] ?? '08:00'; 
                            $eTime = $settings[$grade]['end_time'] ?? '15:00'; 
                        ?>
                        <div class="p-4 rounded-lg bg-muted/20 border border-transparent hover:border-primary/20 hover:bg-muted/40 transition-all flex flex-col md:flex-row md:items-center gap-4">
                            <label class="font-bold w-32 text-primary flex items-center gap-2">
                                <i class="fa-solid fa-graduation-cap text-muted-foreground text-xs"></i> <?= $grade ?>
                            </label>
                            
                            <div class="flex-1 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="md:hidden text-xs font-bold text-muted-foreground block mb-1">Late After</label>
                                    <input type="time" name="schedules[<?= $grade ?>][start]" value="<?= $sTime ?>" 
                                           class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none font-mono">
                                </div>
                                <div>
                                    <label class="md:hidden text-xs font-bold text-muted-foreground block mb-1">Dismissal</label>
                                    <input type="time" name="schedules[<?= $grade ?>][end]" value="<?= $eTime ?>" 
                                           class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none font-mono">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </main>
</div>
</body>
</html>
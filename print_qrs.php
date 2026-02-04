<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Print QRs');

$pdo = getDBConnection();
$students = $pdo->query("SELECT * FROM students WHERE status='Active' ORDER BY grade_level, last_name")->fetchAll();

require_once 'header.php';
?>
<style> @media print { aside, .no-print { display: none !important; } } </style>

<div class="flex h-screen">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <div class="flex justify-between items-center mb-8 no-print">
            <h2 class="text-3xl font-bold tracking-tight">Student QR Codes</h2>
            <button onclick="window.print()" class="bg-primary text-primary-foreground px-6 py-2 rounded shadow hover:opacity-90 flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print Now
            </button>
        </div>

        <div class="grid grid-cols-4 gap-6">
            <?php foreach ($students as $s): ?>
                <div class="bg-card border-2 border-dashed p-6 text-center rounded-xl break-inside-avoid shadow-sm">
                    <img src="qr_codes/<?= $s['qr_token'] ?>.png" alt="QR" class="mx-auto w-32 h-32 object-contain mb-3">
                    <h3 class="font-bold text-lg uppercase leading-tight"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></h3>
                    <p class="text-sm text-muted-foreground mt-1 mb-2">ID: <?= htmlspecialchars($s['student_id_number']) ?></p>
                    <span class="inline-block bg-secondary text-secondary-foreground text-xs font-bold px-3 py-1 rounded-full uppercase">
                        <?= htmlspecialchars($s['grade_level']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
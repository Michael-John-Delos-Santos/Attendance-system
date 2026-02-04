<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'System Settings');

$pdo = getDBConnection();
$message = '';
$msgType = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyAdminKey($_POST['admin_key'])) {
        try {
            $sql = "UPDATE admin_config SET 
                    smtp_email = ?, 
                    smtp_password = ?, 
                    template_subject_in = ?, 
                    template_body_in = ?, 
                    template_subject_out = ?, 
                    template_body_out = ? 
                    WHERE id = 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['smtp_email'],
                $_POST['smtp_password'],
                $_POST['sub_in'],
                $_POST['body_in'],
                $_POST['sub_out'],
                $_POST['body_out']
            ]);
            
            $message = "Settings saved successfully!"; 
            $msgType = 'green';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); 
            $msgType = 'red';
        }
    } else {
        $message = "Invalid Admin Key!"; 
        $msgType = 'red';
    }
}

// Fetch Current Settings
$config = $pdo->query("SELECT * FROM admin_config WHERE id = 1")->fetch();

require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <header class="mb-8">
            <h2 class="text-3xl font-bold tracking-tight text-primary">System Settings</h2>
            <p class="text-muted-foreground mt-1">Configure email credentials and notification templates.</p>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md border <?= $msgType === 'green' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-destructive/10 border-destructive/20 text-destructive' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="max-w-4xl space-y-8">
            
            <div class="bg-card p-6 rounded-lg border shadow-sm">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-blue-900">
                    <i class="fa-solid fa-envelope"></i> SMTP Email Configuration
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Gmail Address</label>
                        <input type="email" name="smtp_email" value="<?= htmlspecialchars($config['smtp_email']) ?>" 
                               class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none" placeholder="school@gmail.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">App Password</label>
                        <input type="password" name="smtp_password" value="<?= htmlspecialchars($config['smtp_password']) ?>" 
                               class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-primary outline-none" placeholder="xxxx xxxx xxxx xxxx">
                        <p class="text-[10px] text-muted-foreground mt-1">Use a Google App Password, not your login password.</p>
                    </div>
                </div>
            </div>

            <div class="bg-card p-6 rounded-lg border shadow-sm">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-green-700">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Arrival Email (Time In)
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Subject Line</label>
                        <input type="text" name="sub_in" value="<?= htmlspecialchars($config['template_subject_in']) ?>" 
                               class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Email Body (HTML)</label>
                        <textarea name="body_in" rows="6" 
                                  class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-green-500 outline-none font-mono text-sm"><?= htmlspecialchars($config['template_body_in']) ?></textarea>
                        <p class="text-[10px] text-muted-foreground mt-1">
                            Available Placeholders: 
                            <code class="bg-muted px-1 rounded">{student_name}</code>
                            <code class="bg-muted px-1 rounded">{parent_name}</code>
                            <code class="bg-muted px-1 rounded">{time}</code>
                            <code class="bg-muted px-1 rounded">{date}</code>
                            <code class="bg-muted px-1 rounded">{status}</code>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-card p-6 rounded-lg border shadow-sm">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-blue-700">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Departure Email (Time Out)
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Subject Line</label>
                        <input type="text" name="sub_out" value="<?= htmlspecialchars($config['template_subject_out']) ?>" 
                               class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Email Body (HTML)</label>
                        <textarea name="body_out" rows="6" 
                                  class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-blue-500 outline-none font-mono text-sm"><?= htmlspecialchars($config['template_body_out']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 border-t pt-6">
                <input type="password" name="admin_key" required placeholder="Enter Admin Key to Save" 
                       class="w-64 p-2 border rounded bg-background focus:ring-2 focus:ring-destructive outline-none">
                <button type="submit" class="bg-primary text-primary-foreground px-8 py-2 rounded font-bold hover:opacity-90 shadow">
                    Save Configuration
                </button>
            </div>

        </form>
    </main>
</div>
</body>
</html>
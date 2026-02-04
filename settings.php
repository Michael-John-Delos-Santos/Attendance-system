<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'System Settings');

$pdo = getDBConnection();
$message = '';
$msgType = '';

// --- 1. HANDLE EMAIL SETTINGS SAVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_email_config'])) {
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
            
            $message = "Email configuration saved successfully!"; 
            $msgType = 'green';
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage(); $msgType = 'red';
        }
    } else {
        $message = "Invalid Admin Key! Changes not saved."; $msgType = 'red';
    }
}

// --- 2. HANDLE ADMIN KEY CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_admin_key'])) {
    $currentKey = $_POST['current_key'];
    $newKey = $_POST['new_key'];

    if (verifyAdminKey($currentKey)) {
        if (!empty($newKey) && strlen($newKey) >= 6) {
            try {
                $stmt = $pdo->prepare("UPDATE admin_config SET admin_key = ? WHERE id = 1");
                $stmt->execute([$newKey]);
                $message = "Admin Key updated successfully!"; 
                $msgType = 'green';
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage(); $msgType = 'red';
            }
        } else {
            $message = "New Key must be at least 6 characters long."; $msgType = 'red';
        }
    } else {
        $message = "Current Admin Key is incorrect."; $msgType = 'red';
    }
}

// --- 3. HANDLE LOGIN PASSWORD CHANGE (NEW) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    // Fetch current hash
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_config WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (password_verify($currentPass, $admin['password_hash'])) {
        if ($newPass === $confirmPass) {
            if (strlen($newPass) >= 6) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE admin_config SET password_hash = ? WHERE id = 1");
                $update->execute([$newHash]);
                
                $message = "Login Password updated successfully!"; 
                $msgType = 'green';
            } else {
                $message = "New Password must be at least 6 characters."; $msgType = 'red';
            }
        } else {
            $message = "New Passwords do not match."; $msgType = 'red';
        }
    } else {
        $message = "Current Password is incorrect."; $msgType = 'red';
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
            <p class="text-muted-foreground mt-1">Configure email, security keys, and login credentials.</p>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md border <?= $msgType === 'green' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-destructive/10 border-destructive/20 text-destructive' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl space-y-8 pb-12">
            
            <form method="POST" class="space-y-8">
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
                                Available Placeholders: {student_name}, {parent_name}, {time}, {date}, {status}
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
                    <button type="submit" name="save_email_config" class="bg-primary text-primary-foreground px-8 py-2 rounded font-bold hover:opacity-90 shadow">
                        Save Email Settings
                    </button>
                </div>
            </form>

            <hr class="border-t border-muted-foreground/20">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <form method="POST" class="bg-card p-6 rounded-lg border shadow-sm border-l-4 border-yellow-400 h-full">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-yellow-600">
                        <i class="fa-solid fa-key"></i> Change Admin Key
                    </h3>
                    <p class="text-xs text-muted-foreground mb-4">
                        The Master Key used for sensitive actions (clearing logs, exports).
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">New Admin Key</label>
                            <input type="text" name="new_key" required placeholder="Enter New Key" minlength="6"
                                   class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-yellow-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Confirm Current Key</label>
                            <input type="password" name="current_key" required placeholder="Current Admin Key" 
                                   class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-destructive outline-none mb-2">
                            <button type="submit" name="change_admin_key" class="w-full bg-yellow-500 text-white px-4 py-2 rounded font-bold hover:bg-yellow-600 shadow">
                                Update Key
                            </button>
                        </div>
                    </div>
                </form>

                <form method="POST" class="bg-card p-6 rounded-lg border shadow-sm border-l-4 border-blue-600 h-full">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-blue-800">
                        <i class="fa-solid fa-lock"></i> Change Login Password
                    </h3>
                    <p class="text-xs text-muted-foreground mb-4">
                        The password used to log in to this dashboard.
                    </p>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">New Password</label>
                                <input type="password" name="new_password" required placeholder="New Pass" minlength="6"
                                       class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Confirm</label>
                                <input type="password" name="confirm_password" required placeholder="Confirm" minlength="6"
                                       class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-muted-foreground uppercase mb-1">Current Password</label>
                            <input type="password" name="current_password" required placeholder="Current Password" 
                                   class="w-full p-2 border rounded bg-background focus:ring-2 focus:ring-destructive outline-none mb-2">
                            <button type="submit" name="change_password" class="w-full bg-blue-700 text-white px-4 py-2 rounded font-bold hover:bg-blue-800 shadow">
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>

            </div>

        </div>
    </main>
</div>
</body>
</html>
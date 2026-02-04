<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php'; 
requireAdmin();
define('PAGE_TITLE', 'Students');

$pdo = getDBConnection();
$message = '';
$messageType = '';

// Grade Levels
$gradeLevels = ['Nursery', 'Kinder', 'Preparatory', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

// --- LOGIC: HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Import CSV
    if (isset($_POST['import_csv'])) {
        if (verifyAdminKey($_POST['admin_key'])) {
            if ($_FILES['csv_file']['error'] == 0) {
                $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
                fgetcsv($handle); // Skip header
                $count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 4) {
                        $qrToken = md5($data[0] . time() . uniqid());
                        try {
                            $stmt = $pdo->prepare("INSERT INTO students (student_id_number, qr_token, first_name, last_name, grade_level, parent_name, parent_email) VALUES (?,?,?,?,?,?,?)");
                            $stmt->execute([$data[0], $qrToken, $data[1], $data[2], $data[3], $data[4]??'', $data[5]??'']);
                            if (!is_dir('qr_codes')) mkdir('qr_codes');
                            QRcode::png($qrToken, 'qr_codes/' . $qrToken . '.png', QR_ECLEVEL_L, 5);
                            $count++;
                        } catch (Exception $e) { }
                    }
                }
                fclose($handle);
                $message = "Imported $count students successfully."; $messageType = "green";
            }
        } else { $message = "Invalid Admin Key!"; $messageType = "red"; }
    }

    // 2. Add Single Student
    if (isset($_POST['add_student'])) {
        $qrToken = md5($_POST['student_id_number'] . time());
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id_number, qr_token, first_name, last_name, grade_level, parent_name, parent_email) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$_POST['student_id_number'], $qrToken, $_POST['first_name'], $_POST['last_name'], $_POST['grade_level'], $_POST['parent_name'], $_POST['parent_email']]);
            if (!is_dir('qr_codes')) mkdir('qr_codes');
            QRcode::png($qrToken, 'qr_codes/' . $qrToken . '.png', QR_ECLEVEL_L, 5);
            $message = "Student added!"; $messageType = "green";
        } catch (Exception $e) { $message = "Error: " . $e->getMessage(); $messageType = "red"; }
    }

    // 3. Edit Student
    if (isset($_POST['edit_student'])) {
        if (verifyAdminKey($_POST['admin_key'])) {
            try {
                $stmt = $pdo->prepare("UPDATE students SET student_id_number=?, first_name=?, last_name=?, grade_level=?, parent_name=?, parent_email=? WHERE student_id=?");
                $stmt->execute([$_POST['student_id_number'], $_POST['first_name'], $_POST['last_name'], $_POST['grade_level'], $_POST['parent_name'], $_POST['parent_email'], $_POST['student_id']]);
                $message = "Student updated successfully."; $messageType = "green";
            } catch (Exception $e) { $message = "Error: " . $e->getMessage(); $messageType = "red"; }
        } else { $message = "Invalid Admin Key! Changes NOT saved."; $messageType = "red"; }
    }

    // 4. Delete Student
    if (isset($_POST['delete_student'])) {
        if (verifyAdminKey($_POST['admin_key'])) {
            $stmt = $pdo->prepare("SELECT qr_token FROM students WHERE student_id = ?");
            $stmt->execute([$_POST['student_id']]);
            $s = $stmt->fetch();
            if ($s) {
                if (file_exists('qr_codes/' . $s['qr_token'] . '.png')) unlink('qr_codes/' . $s['qr_token'] . '.png');
                $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$_POST['student_id']]);
                $message = "Student deleted."; $messageType = "green";
            }
        } else { $message = "Invalid Admin Key!"; $messageType = "red"; }
    }
}

$students = $pdo->query("SELECT * FROM students ORDER BY grade_level, last_name")->fetchAll();
require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 bg-muted/20">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Student Management</h2>
                <p class="text-muted-foreground mt-1">Manage enrollments and details</p>
            </div>
            <div class="flex gap-3">
                <button onclick="document.getElementById('addModal').showModal()" class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:opacity-90 shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Add Student
                </button>
                <button onclick="document.getElementById('csvModal').showModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-file-csv"></i> Import CSV
                </button>
                <a href="print_qrs.php" target="_blank" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Print All
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md border <?= $messageType === 'green' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-destructive/10 border-destructive/20 text-destructive' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-card rounded-lg border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted text-muted-foreground font-medium border-b">
                        <tr>
                            <th class="px-6 py-3">ID Number</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Grade</th>
                            <th class="px-6 py-3">Parent Info</th>
                            <th class="px-6 py-3">QR</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($students as $s): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4 font-medium"><?= htmlspecialchars($s['student_id_number']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
                            <td class="px-6 py-4"><span class="bg-secondary text-secondary-foreground px-2.5 py-0.5 rounded-full text-xs font-semibold"><?= htmlspecialchars($s['grade_level']) ?></span></td>
                            <td class="px-6 py-4 text-xs text-muted-foreground">
                                <div class="text-foreground font-medium"><?= htmlspecialchars($s['parent_name']) ?></div>
                                <?= htmlspecialchars($s['parent_email']) ?>
                            </td>
                            <td class="px-6 py-4"><img src="qr_codes/<?= $s['qr_token'] ?>.png" class="w-8 h-8 border rounded bg-white p-0.5"></td>
                            <td class="px-6 py-4 flex gap-3">
                                <button onclick="printSingleQR('<?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>','<?= htmlspecialchars($s['grade_level']) ?>','<?= $s['qr_token'] ?>')" class="text-muted-foreground hover:text-primary transition-colors" title="Print QR"><i class="fa-solid fa-print"></i></button>
                                <button onclick='openEditModal(<?= json_encode($s) ?>)' class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button onclick="confirmDelete(<?= $s['student_id'] ?>)" class="text-destructive hover:text-destructive/80 transition-colors" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<dialog id="addModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card text-card-foreground border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-4">Add New Student</h3>
        <div class="space-y-3">
            <input type="text" name="student_id_number" placeholder="ID Number" required class="w-full border p-2 rounded bg-background">
            <input type="text" name="first_name" placeholder="First Name" required class="w-full border p-2 rounded bg-background">
            <input type="text" name="last_name" placeholder="Last Name" required class="w-full border p-2 rounded bg-background">
            <select name="grade_level" required class="w-full border p-2 rounded bg-background">
                <option value="">Select Grade Level</option>
                <?php foreach ($gradeLevels as $gl): ?><option value="<?= $gl ?>"><?= $gl ?></option><?php endforeach; ?>
            </select>
            <input type="text" name="parent_name" placeholder="Parent Name" class="w-full border p-2 rounded bg-background">
            <input type="email" name="parent_email" placeholder="Parent Email" class="w-full border p-2 rounded bg-background">
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" onclick="document.getElementById('addModal').close()" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="add_student" class="bg-primary text-primary-foreground px-4 py-2 rounded shadow hover:opacity-90">Save</button>
        </div>
    </form>
</dialog>

<dialog id="editModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card text-card-foreground border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-4 text-primary">Edit Student</h3>
        <input type="hidden" name="student_id" id="edit_id">
        <div class="space-y-3">
            <input type="text" name="student_id_number" id="edit_id_num" class="w-full border p-2 rounded bg-background">
            <input type="text" name="first_name" id="edit_fname" class="w-full border p-2 rounded bg-background">
            <input type="text" name="last_name" id="edit_lname" class="w-full border p-2 rounded bg-background">
            <select name="grade_level" id="edit_grade" class="w-full border p-2 rounded bg-background">
                <?php foreach ($gradeLevels as $gl): ?><option value="<?= $gl ?>"><?= $gl ?></option><?php endforeach; ?>
            </select>
            <input type="text" name="parent_name" id="edit_pname" class="w-full border p-2 rounded bg-background">
            <input type="email" name="parent_email" id="edit_pemail" class="w-full border p-2 rounded bg-background">
            <div class="pt-2 border-t mt-2">
                <label class="text-xs font-bold text-destructive uppercase">Admin Key Required</label>
                <input type="password" name="admin_key" required class="w-full border border-destructive/30 p-2 rounded bg-background mt-1">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" onclick="document.getElementById('editModal').close()" class="px-4 py-2 text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="edit_student" class="bg-primary text-primary-foreground px-4 py-2 rounded shadow hover:opacity-90">Update</button>
        </div>
    </form>
</dialog>

<dialog id="csvModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card text-card-foreground border">
    <form method="POST" enctype="multipart/form-data">
        <h3 class="font-bold text-lg mb-4">Import CSV</h3>
        <input type="file" name="csv_file" accept=".csv" required class="w-full border p-2 mb-3 rounded bg-background">
        <input type="password" name="admin_key" placeholder="Admin Key" required class="w-full border border-destructive/30 p-2 rounded bg-background">
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="document.getElementById('csvModal').close()" class="text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="import_csv" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700">Import</button>
        </div>
    </form>
</dialog>

<dialog id="deleteModal" class="p-6 rounded-lg shadow-xl w-96 backdrop:bg-black/50 bg-card text-card-foreground border">
    <form method="POST">
        <h3 class="font-bold text-lg mb-2 text-destructive">Confirm Deletion</h3>
        <p class="text-sm text-muted-foreground mb-4">Enter Admin Key to permanently delete this student.</p>
        <input type="hidden" name="student_id" id="delete_student_id">
        <input type="password" name="admin_key" placeholder="Admin Key" required class="w-full border border-destructive/30 p-2 rounded bg-background">
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="document.getElementById('deleteModal').close()" class="text-muted-foreground hover:text-foreground">Cancel</button>
            <button type="submit" name="delete_student" class="bg-destructive text-destructive-foreground px-4 py-2 rounded shadow hover:opacity-90">Delete</button>
        </div>
    </form>
</dialog>

<script>
    function confirmDelete(id) { document.getElementById('delete_student_id').value = id; document.getElementById('deleteModal').showModal(); }
    function openEditModal(s) {
        document.getElementById('edit_id').value = s.student_id;
        document.getElementById('edit_id_num').value = s.student_id_number;
        document.getElementById('edit_fname').value = s.first_name;
        document.getElementById('edit_lname').value = s.last_name;
        document.getElementById('edit_grade').value = s.grade_level;
        document.getElementById('edit_pname').value = s.parent_name;
        document.getElementById('edit_pemail').value = s.parent_email;
        document.getElementById('editModal').showModal();
    }
    function printSingleQR(name, grade, token) {
        const win = window.open('', '', 'height=500,width=500');
        win.document.write('<html><body style="text-align:center; font-family:sans-serif; padding:40px;">');
        win.document.write('<img src="qr_codes/' + token + '.png" width="250" style="margin-bottom:10px;">');
        win.document.write('<h2 style="margin:0; text-transform:uppercase;">' + name + '</h2>');
        win.document.write('<p style="color:#555;">' + grade + '</p>');
        win.document.write('<script>window.print();<\/script></body></html>');
        win.document.close();
    }
</script>
</body>
</html>
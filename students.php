<?php
require_once 'config.php';
requireLogin();
require_once 'phpqrcode/qrlib.php';

$pdo = getDBConnection();
$currentUser = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $student_id = $_POST['student_id'] ?? '';

    // Add student
if ($action === 'add') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $grade_section = $_POST['section'] ?? '';
    $parent_name = $_POST['parent_name'] ?? '';
    $parent_email = $_POST['parent_email'] ?? '';

    if (!empty($first_name) && !empty($last_name) && !empty($grade_section)) {
        // Generate roll number automatically
        $stmt = $pdo->query("SELECT MAX(CAST(roll_number AS UNSIGNED)) AS max_roll FROM students");
        $row = $stmt->fetch();
        $roll_number = $row && $row['max_roll'] ? $row['max_roll'] + 1 : 1000000;

        $qr_token = substr(uniqid(), 0, 10);

        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO students 
            (roll_number, qr_token, first_name, last_name, grade_section, parent_name, parent_email) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$roll_number, $qr_token, $first_name, $last_name, $grade_section, $parent_name, $parent_email]);

        // Generate QR code as PNG
        if (!file_exists('qr_codes')) mkdir('qr_codes', 0777, true);
        $filePath = 'qr_codes/' . $qr_token . '.png';
        QRcode::png($qr_token, $filePath, QR_ECLEVEL_L, 8, 4);


        $success = "Student added successfully!";
    } else {
        $error = "All required fields must be filled.";
    }
}


    // Delete student
    if ($action === 'delete' && !empty($student_id)) {
        $stmt = $pdo->prepare("SELECT qr_token FROM students WHERE student_id=?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        if ($student) {
            $qr_file = 'qr_codes/' . $student['qr_token'] . '.png';
            if (file_exists($qr_file)) unlink($qr_file);
        }

        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->execute([$student_id]);
        $success = "Student deleted successfully!";
    }
}

// Get all students
$stmt = $pdo->prepare("SELECT * FROM students ORDER BY last_name, first_name");
$stmt->execute();
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - <?= SCHOOL_NAME ?></title>
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
                <a href="students.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">👥 Students</a>
                <a href="attendance.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📋 Attendance</a>
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

    <!-- Main Content -->
    <div class="flex-1 p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Students</h2>
                <p class="text-gray-500">Manage student information and QR codes</p>
            </div>
            <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                ➕ Add Student
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-800 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Students Table -->
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Roll #</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Section</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Parent Name</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Parent Email</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">QR Code</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="px-6 py-4"><?= htmlspecialchars($student['roll_number']) ?></td>
                            <td class="px-6 py-4 font-medium"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($student['grade_section']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($student['parent_name'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($student['parent_email'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4">
                                <?php if (!empty($student['qr_token'])): ?>
                                    <img src="qr_codes/<?= $student['qr_token'] ?>.png" alt="QR Code" width="50">
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="<?= $student['status'] === 'Active' ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                                    <?= $student['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 flex gap-2">
                                <button onclick="generateQR('<?= $student['qr_token'] ?>', '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')" 
                                    class="text-blue-600 hover:underline text-sm">QR</button>
                                <form method="POST" onsubmit="return confirm('Delete this student?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                    <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-lg">
        <h3 class="text-xl font-semibold mb-4">Add New Student</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" name="first_name" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="last_name" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <input type="text" name="section" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Name</label>
                <input type="text" name="parent_name" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Email</label>
                <input type="email" name="parent_email" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div class="flex gap-4 mt-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Add Student</button>
                <button type="button" onclick="hideAddModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function generateQR(qrToken, studentName) {
    alert(`QR Token for ${studentName}:\n\n${qrToken}\n\nA scannable QR code is already generated in qr_codes/${qrToken}.png`);
}
</script>
</body>
</html>

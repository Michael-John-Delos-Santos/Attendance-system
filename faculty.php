<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();
$currentUser = getCurrentUser();
if (!$currentUser) {
    die("Error: No logged-in user found.");
}

// Fetch all faculty
$stmt = $pdo->query("SELECT faculty_id, username, full_name, role FROM faculty ORDER BY full_name ASC");
$faculty_list = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Notifications for success/error
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Management - <?= SCHOOL_NAME ?></title>
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
                <a href="reports.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">📈 Reports</a>
                <?php if ($currentUser['role'] === 'Admin'): ?>
                <a href="faculty.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">👨‍🏫 Faculty</a>
                <?php endif; ?>
                <a href="logout.php" class="px-3 py-2 rounded-lg hover:bg-red-100 text-red-600 font-medium">🚪 Logout</a>
            </nav>
            
            <div class="mt-8 p-4 bg-blue-50 rounded-lg text-center">
                <p class="text-sm font-medium">Welcome back,</p>
                <p class="font-semibold text-blue-700"><?= htmlspecialchars($currentUser['full_name']) ?></p>
            </div>
        </div>

    <!-- Main content -->
    <div class="flex-1 p-6">
        <h2 class="text-3xl font-bold mb-4">Faculty Management</h2>

        <?php if($success): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if(in_array($currentUser['role'], ['Admin','Owner'])): ?>
        <div class="mb-8 p-6 bg-white rounded-lg shadow grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Left: Reset Password / Clock In-Out -->
            <div>
                <h3 class="text-xl font-semibold mb-4">Reset Password</h3>
                <form method="POST" action="reset_password.php" class="space-y-2">
                    <select name="faculty_id" class="border p-2 rounded w-full" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach($faculty_list as $fac): ?>
                            <option value="<?= $fac['faculty_id'] ?>"><?= htmlspecialchars($fac['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="password" name="new_password" placeholder="New Password" class="border p-2 rounded w-full" required>
                    <button type="submit" class="w-full bg-yellow-500 text-white py-2 rounded hover:bg-yellow-600">Reset Password</button>
                </form>

                <h3 class="text-xl font-semibold mt-6 mb-4">Clock In / Clock Out</h3>
                <form method="POST" action="clock.php" class="space-y-2">
                    <select name="faculty_id" class="border p-2 rounded w-full" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach($faculty_list as $fac): ?>
                            <option value="<?= $fac['faculty_id'] ?>"><?= htmlspecialchars($fac['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="in" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">Clock In</button>
                    <button type="submit" name="action" value="out" class="w-full bg-red-600 text-white py-2 rounded hover:bg-red-700">Clock Out</button>
                </form>
            </div>

            <!-- Right: Add Faculty -->
            <div>
                <h3 class="text-xl font-semibold mb-4">Add Faculty</h3>
                <form method="POST" action="register_faculty.php" class="space-y-2">
                    <input type="text" name="username" placeholder="Username" class="border p-2 rounded w-full" required>
                    <input type="text" name="full_name" placeholder="Full Name" class="border p-2 rounded w-full" required>
                    <input type="password" name="password" placeholder="Password" class="border p-2 rounded w-full" required>
                    <select name="role" class="border p-2 rounded w-full">
                        <option value="Teacher">Teacher</option>
                        <option value="Admin">Admin</option>
                    </select>
                    <button type="submit" class="w-full bg-blue-700 text-white py-2 rounded hover:bg-blue-800">Register Faculty</button>
                </form>
            </div>

        </div>
        <?php endif; ?>

        <!-- Faculty List -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-xl font-semibold mb-4">All Faculty</h3>
            <?php if(empty($faculty_list)): ?>
                <p class="text-gray-500">No faculty members found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse bg-white shadow rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Username</th>
                                <th class="px-4 py-2 text-left">Full Name</th>
                                <th class="px-4 py-2 text-left">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($faculty_list as $fac): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-medium"><?= htmlspecialchars($fac['username']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($fac['full_name']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($fac['role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

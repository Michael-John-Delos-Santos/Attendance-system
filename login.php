<?php
require_once 'config.php';

// Ensure default admin and owner accounts exist
$pdo = getDBConnection();

// Admin account (Principal)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty WHERE username='principalLogin'");
$count = $stmt->fetch()['count'] ?? 0;
if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO faculty (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'principalLogin',
        password_hash('maximillianschool', PASSWORD_DEFAULT),
        'Principal',
        'Admin',
        'Active'
    ]);
}

// Owner account
$stmt = $pdo->query("SELECT COUNT(*) as count FROM faculty WHERE username='admin'");
$count = $stmt->fetch()['count'] ?? 0;
if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO faculty (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'admin',
        password_hash('maximillianschoolVIP', PASSWORD_DEFAULT),
        'Owner',
        'Owner',
        'Active'
    ]);
}

// --- Login logic ---
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM faculty WHERE username = ? AND status = 'Active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['faculty_id'] = $user['faculty_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= SCHOOL_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans">
<div class="bg-white shadow-md rounded-lg w-full max-w-md p-8">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-blue-700 mb-2"><?= SCHOOL_NAME ?></h1>
        <p class="text-gray-500">Attendance Management System</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                required
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                required
            >
        </div>

        <button type="submit" class="w-full bg-blue-700 text-white py-2 rounded-lg font-semibold hover:opacity-90 transition">
            Sign In
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-500">
        <p>Default credentials:</p>
        <p><strong>Principal Login:</strong> principalLogin | <strong>Password:</strong> maximillianschool</p>
        <p><strong>Owner Login:</strong> admin | <strong>Password:</strong> maximillianschoolVIP</p>
    </div>
</div>
</body>
</html>

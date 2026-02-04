<?php
// 1. LOGIN LOGIC
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM admin_config WHERE id = 1");
            $stmt->execute();
            $admin = $stmt->fetch();

            if ($admin && $username === $admin['username']) {
                if (password_verify($password, $admin['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $admin['username'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Account not found.";
            }
        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= defined('SCHOOL_NAME') ? SCHOOL_NAME : 'Attendance System' ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen relative overflow-hidden">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100 relative z-10">
        
        <div class="bg-blue-900 p-8 text-center border-b border-blue-950 relative">
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-yellow-400 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg border border-yellow-500">
                    <i class="fa-solid fa-school text-3xl text-blue-900"></i>
                </div>
                
                <h2 class="text-xl font-bold leading-tight text-white tracking-wide">
                    <?= defined('SCHOOL_NAME') ? SCHOOL_NAME : 'School Attendance' ?>
                </h2>
                <p class="text-xs font-bold uppercase mt-2 tracking-widest text-blue-200">
                    Admin Portal
                </p>
            </div>
        </div>

        <div class="p-8">
            <?php if ($error): ?>
                <div class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm flex items-center gap-2 animate-pulse">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Username</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-blue-600 transition-colors">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="username" required placeholder="Enter username"
                               class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white placeholder-gray-400">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Password</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-blue-600 transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required placeholder="Enter password"
                               class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-gray-50 focus:bg-white placeholder-gray-400">
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-yellow-400 hover:bg-yellow-500 text-blue-900 font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 mt-2 flex justify-center items-center gap-2 group border-b-4 border-blue-600 active:border-b-0 active:translate-y-1">
                    <span>Sign In</span>
                    <i class="fa-solid fa-arrow-right text-blue-900 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 text-center border-t border-gray-200">
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">
                
            </p>
        </div>
    </div>

</body>
</html>
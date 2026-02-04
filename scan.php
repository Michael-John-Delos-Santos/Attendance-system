<?php
require_once 'config.php';
requireLogin();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - <?= SCHOOL_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="app/globals.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
                <a href="scan.php" class="px-3 py-2 rounded-lg bg-blue-100 text-blue-700 font-medium">📱 QR Scanner</a>
                <a href="students.php" class="px-3 py-2 rounded-lg hover:bg-blue-50">👥 Students</a>
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
        <div class="flex-1 p-6 bg-gray-50">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">QR Code Scanner</h2>
                <p class="text-gray-500">Scan student QR codes to record attendance</p>
            </div>

            <div class="max-w-2xl mx-auto">
                <div class="php-card text-center bg-white shadow-md rounded-xl p-6">
                    <div class="mb-6">
                        <button id="start-scan" class="php-button text-lg px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            📱 Start Camera
                        </button>
                        <button id="stop-scan" class="php-button secondary text-lg px-8 py-4 ml-4 bg-red-600 text-white rounded-lg hover:bg-red-700" style="display: none;">
                            ⏹️ Stop Camera
                        </button>
                    </div>

                    <div id="qr-reader" class="border border-gray-300 rounded-lg" style="display: none;"></div>
                    
                    <div id="scan-result" class="scan-result mt-4 text-center" style="display: none;"></div>
                </div>

                <!-- Recent Scans -->
                <div class="php-card mt-6 bg-white shadow-md rounded-xl p-6">
                    <h3 class="text-xl font-semibold mb-4">Recent Scans</h3>
                    <div id="recent-scans">
                        <p class="text-gray-500 text-center py-4">No scans yet today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let isScanning = false;

        document.getElementById('start-scan').addEventListener('click', startScanning);
        document.getElementById('stop-scan').addEventListener('click', stopScanning);

        function startScanning() {
            if (isScanning) return;

            const qrReaderElement = document.getElementById('qr-reader');
            qrReaderElement.style.display = 'block';
            
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                },
                false
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            
            document.getElementById('start-scan').style.display = 'none';
            document.getElementById('stop-scan').style.display = 'inline-block';
            isScanning = true;
        }

        function stopScanning() {
            if (!isScanning) return;

            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }

            document.getElementById('qr-reader').style.display = 'none';
            document.getElementById('start-scan').style.display = 'inline-block';
            document.getElementById('stop-scan').style.display = 'none';
            isScanning = false;
        }

        function onScanSuccess(decodedText, decodedResult) {
            processAttendance(decodedText);
        }

        function onScanFailure(error) {
            // silently fail
        }

        function processAttendance(qrCode) {
            fetch('api/log_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ qr_token: qrCode })
            })
            .then(response => response.json())
            .then(data => {
                showScanResult(data);
                if (data.success) loadRecentScans();
            })
            .catch(error => {
                showScanResult({ success: false, message: 'Network error occurred' });
            });
        }

        function showScanResult(result) {
            const resultElement = document.getElementById('scan-result');
            resultElement.style.display = 'block';
            
            if (result.success) {
                resultElement.className = 'scan-result scan-success';
                resultElement.innerHTML = `<div class="text-lg font-semibold">✅ Success!</div><div>${result.message}</div>`;
            } else {
                resultElement.className = 'scan-result scan-error';
                resultElement.innerHTML = `<div class="text-lg font-semibold">❌ Error</div><div>${result.message}</div>`;
            }

            setTimeout(() => { resultElement.style.display = 'none'; }, 3000);
        }

        function loadRecentScans() {
            fetch('api/recent_scans.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recent-scans');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-center py-4">No scans yet today</p>';
                        return;
                    }

                    let html = '<div class="space-y-3">';
                    data.forEach(scan => {
                        html += `
                            <div class="flex items-center justify-between p-3 bg-gray-100 rounded-lg">
                                <div>
                                    <div class="font-medium">${scan.student_name}</div>
                                    <div class="text-sm text-gray-500">${scan.grade_section}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium">${scan.time_in}</div>
                                    <span class="status-${scan.status.toLowerCase()}">${scan.status}</span>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                });
        }

        loadRecentScans();
    </script>
</body>
</html>

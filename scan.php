<?php
require_once 'config.php';
requireAdmin();
define('PAGE_TITLE', 'Scanner');
require_once 'header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8 flex flex-col items-center justify-center bg-muted/20">
        <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="bg-card p-6 rounded-lg border shadow-sm flex flex-col">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-primary">
                    <i class="fa-solid fa-camera"></i> Live Scanner
                </h3>
                <div id="reader" class="rounded-lg overflow-hidden bg-black/5 h-80 w-full relative border-2 border-dashed border-border flex items-center justify-center">
                    <p class="text-muted-foreground text-sm flex flex-col items-center gap-2">
                        <i class="fa-solid fa-video-slash text-2xl"></i>
                        Camera is off
                    </p>
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <button onclick="startScanner()" id="start-btn" class="bg-primary text-primary-foreground px-8 py-3 rounded-full font-bold shadow hover:opacity-90 transition-all flex items-center gap-2"><i class="fa-solid fa-power-off"></i> Start Camera</button>
                    <button onclick="stopScanner()" id="stop-btn" class="hidden bg-destructive text-destructive-foreground px-8 py-3 rounded-full font-bold shadow hover:opacity-90 transition-all flex items-center gap-2"><i class="fa-solid fa-stop"></i> Stop Camera</button>
                </div>
            </div>
            
            <div class="flex flex-col gap-6">
                <div class="bg-card p-6 rounded-lg border shadow-sm">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2 text-primary"><i class="fa-solid fa-keyboard"></i> Manual Entry</h3>
                    <form onsubmit="handleManualSubmit(event)" class="flex gap-3">
                        <input type="text" id="manual-input" placeholder="Enter Student ID (e.g. 2026001)" class="flex-1 border rounded-md px-4 py-2 focus:ring-2 focus:ring-primary outline-none bg-background">
                        <button type="submit" class="bg-secondary text-secondary-foreground px-6 py-2 rounded-md font-bold hover:bg-secondary/80 transition-colors border border-border">Log</button>
                    </form>
                </div>
                
                <div class="bg-card flex-1 rounded-lg border shadow-sm overflow-hidden flex flex-col min-h-[300px]">
                    <div id="result-container" class="hidden p-4 text-center font-bold border-b transition-colors"><span id="scan-message"></span></div>
                    <div class="p-3 bg-muted/30 border-b text-xs font-bold text-muted-foreground uppercase tracking-wide">Session Activity</div>
                    <div id="scan-log" class="p-4 overflow-y-auto flex-1 space-y-2 text-sm bg-background">
                        <div class="text-center py-8 text-muted-foreground opacity-50"><i class="fa-solid fa-clock-rotate-left text-2xl mb-2 block"></i>Waiting for scans...</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const html5QrCode = new Html5Qrcode("reader");
    let isScanning = false; let lastScanToken = ""; let lastScanTime = 0;

    function onScanSuccess(decodedText) { processAttendance(decodedText); }

    function processAttendance(identifier) {
        if (identifier === lastScanToken && Date.now() - lastScanTime < 3000) return;
        lastScanToken = identifier; lastScanTime = Date.now();

        const container = document.getElementById('result-container');
        const msg = document.getElementById('scan-message');
        container.classList.remove('hidden');
        container.className = "p-4 text-center font-bold border-b bg-blue-50 text-blue-700";
        msg.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        fetch('api/log_attendance.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ qr_token: identifier }) })
        .then(response => response.json())
        .then(data => updateUI(data))
        .catch(err => updateUI({ success: false, message: "Network Error" }));
    }

    function updateUI(data) {
        const container = document.getElementById('result-container');
        const msg = document.getElementById('scan-message');
        const log = document.getElementById('scan-log');

        if(data.success) {
            if (data.message.includes('TIME OUT')) {
                container.className = "p-4 text-center font-bold border-b bg-blue-100 text-blue-800";
                msg.innerHTML = '<i class="fa-solid fa-person-walking-arrow-right"></i> ' + data.message;
            } else if (data.message.includes('Late')) {
                container.className = "p-4 text-center font-bold border-b bg-yellow-100 text-yellow-800";
                msg.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + data.message;
            } else {
                container.className = "p-4 text-center font-bold border-b bg-green-100 text-green-800";
                msg.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + data.message;
            }
        } else {
            container.className = "p-4 text-center font-bold border-b bg-red-100 text-red-800";
            msg.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
        }

        const entry = document.createElement('div');
        entry.className = `p-3 rounded-md border-l-4 text-sm flex justify-between items-center shadow-sm bg-card ${data.success ? (data.message.includes('OUT') ? 'border-blue-500' : 'border-green-500') : 'border-red-500'}`;
        entry.innerHTML = `<span class="font-medium">${data.message}</span><span class="text-xs text-muted-foreground">${new Date().toLocaleTimeString()}</span>`;
        
        if(log.innerText.includes("Waiting")) log.innerHTML = '';
        log.insertBefore(entry, log.firstChild);
    }

    function startScanner() { document.getElementById('start-btn').classList.add('hidden'); document.getElementById('stop-btn').classList.remove('hidden'); html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess); isScanning = true; }
    function stopScanner() { if(isScanning) { html5QrCode.stop().then(() => { document.getElementById('start-btn').classList.remove('hidden'); document.getElementById('stop-btn').classList.add('hidden'); document.getElementById('reader').innerHTML = '<p class="text-muted-foreground text-sm flex flex-col items-center gap-2"><i class="fa-solid fa-video-slash text-2xl"></i> Camera is off</p>'; isScanning = false; }); } }
    function handleManualSubmit(e) { e.preventDefault(); const inp = document.getElementById('manual-input'); if(inp.value.trim()) { processAttendance(inp.value.trim()); inp.value = ''; inp.focus(); } }
</script>
</body>
</html>
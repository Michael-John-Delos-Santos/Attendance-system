<aside class="w-64 bg-gradient-to-b from-blue-800 to-blue-950 text-white hidden md:flex flex-col relative overflow-hidden shadow-2xl border-r border-blue-900">
    
    <style>
        @keyframes float-bubble {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.2); }
            66% { transform: translate(-20px, 20px) scale(0.8); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: float-bubble 5s infinite ease-in-out;
        }
        .delay-2000 { animation-delay: 2s; }
        .delay-4000 { animation-delay: 3s; }
    </style>

    <div class="absolute top-[-20px] left-[-20px] w-48 h-48 bg-blue-500 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob"></div>
    
    <div class="absolute bottom-[-20px] right-[-20px] w-48 h-48 bg-cyan-400 rounded-full mix-blend-screen filter blur-3xl opacity-30 animate-blob delay-2000"></div>
    
    <div class="absolute top-1/2 left-1/2 w-32 h-32 bg-purple-400 rounded-full mix-blend-screen filter blur-2xl opacity-20 animate-blob delay-4000"></div>

    <div class="p-6 border-b border-yellow-500 relative z-10 bg-yellow-400 text-blue-900 shadow-md">
        <h1 class="font-bold text-2xl flex items-center gap-3 tracking-wide leading-tight">
            <span style="color:white; text-shadow: 2px 2px 4px rgba(0,0,0,0.6);">
                School of Saint Maximillian Mary Kolbe
            </span>
        </h1>
        <p class="text-xs mt-2 font-bold uppercase tracking-wider" style="color:white; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);">
            Attendance System
        </p>
    </div>

    <nav class="flex-1 p-4 space-y-2 relative z-10 mt-2">
        <?php
        $nav = [
            'index.php' => ['icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
            'students.php' => ['icon' => 'fa-user-graduate', 'label' => 'Students'],
            'schedule.php' => ['icon' => 'fa-clock', 'label' => 'Schedule'],
            'scan.php' => ['icon' => 'fa-qrcode', 'label' => 'Scanner'],
            'reports.php' => ['icon' => 'fa-file-contract', 'label' => 'Reports'],
            'analytics.php' => ['icon' => 'fa-chart-bar', 'label' => 'Analytics'],
            'settings.php' => ['icon' => 'fa-gear', 'label' => 'Settings'], 
        ];
        
        $current = basename($_SERVER['PHP_SELF']);
        
        foreach ($nav as $file => $item) {
            $isActive = ($current == $file);
            
            // Logic: Active gets a glass effect
            $classes = $isActive 
                ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border-l-4 border-yellow-400' 
                : 'text-blue-200 hover:bg-white/5 hover:text-white hover:translate-x-1';
                
            echo "<a href='$file' class='flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all duration-300 group $classes'>
                    <i class='fa-solid {$item['icon']} w-6 text-center " . ($isActive ? 'text-yellow-400' : 'text-blue-400 group-hover:text-yellow-200') . "'></i> 
                    {$item['label']}
                  </a>";
        }
        ?>
    </nav>

    <div class="p-4 border-t border-white/10 relative z-10 bg-black/20 backdrop-blur-sm">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-200 hover:bg-red-500/20 hover:text-white transition-all hover:scale-105">
            <i class="fa-solid fa-arrow-right-from-bracket w-6 text-center"></i> 
            Sign Out
        </a>
    </div>
</aside>
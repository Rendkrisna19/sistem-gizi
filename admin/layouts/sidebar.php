<?php 
    // Mendapatkan nama file yang sedang dibuka untuk efek menu aktif
    $current_page = basename($_SERVER['PHP_SELF']); 
?>

<aside id="sidebar" class="bg-gradient-to-b from-emerald-900 via-emerald-800 to-emerald-900 text-white w-64 flex flex-col absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 z-50 sidebar-transition shadow-[4px_0_24px_rgba(0,0,0,0.15)] border-r border-emerald-700/50">
    
    <div class="h-20 flex items-center justify-center bg-black/20 border-b border-emerald-700/50 backdrop-blur-md">
        <span class="text-2xl font-extrabold tracking-wider flex items-center gap-3">
            <div class="bg-gradient-to-br from-emerald-400 to-emerald-600 text-white p-2.5 rounded-xl shadow-lg border border-emerald-300/30">
                <i class="fa-solid fa-leaf"></i>
            </div>
            SIPEGIZI
        </span>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
        <p class="px-4 text-[10px] font-bold text-emerald-400/80 uppercase tracking-widest mb-3 mt-2">Menu Utama</p>
        
        <a href="index.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'index.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-house w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Dashboard</span>
        </a>
        
        <a href="data_latih.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'data_latih.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-database w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Data Latih (KNN)</span>
        </a>
        
        <a href="data_balita.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'data_balita.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-children w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Data Balita</span>
        </a>
        
        <p class="px-4 text-[10px] font-bold text-emerald-400/80 uppercase tracking-widest mb-3 mt-8">Proses Analisis</p>

        <a href="klasifikasi.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'klasifikasi.php' || $current_page == 'proses_knn.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-calculator w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Klasifikasi Gizi</span>
        </a>
        
        <a href="riwayat.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'riwayat.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-clock-rotate-left w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Riwayat Laporan</span>
        </a>
        <a href="manajemen_user.php" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 <?= $current_page == 'manajemen_user.php' ? 'bg-emerald-600/90 text-white shadow-md border-l-4 border-emerald-300 backdrop-blur-sm' : 'text-emerald-100/80 hover:bg-emerald-700/50 hover:text-white' ?>">
            <i class="fa-solid fa-users-gear w-5 text-center transition-transform group-hover:scale-110"></i> 
            <span class="font-medium text-sm">Manajemen User</span>
        </a>
    </nav>

    <div class="px-5 mb-5 mt-auto">
        <div class="bg-black/20 rounded-2xl p-4 border border-emerald-500/20 backdrop-blur-sm text-center shadow-inner relative overflow-hidden">
            <div class="absolute -right-4 -top-4 opacity-10 text-5xl"><i class="fa-regular fa-clock"></i></div>
            <div id="live-time" class="text-2xl font-black text-emerald-50 tracking-widest drop-shadow-md">00:00:00</div>
            <div id="live-date" class="text-[11px] font-medium text-emerald-300 mt-1 uppercase tracking-wider">Memuat Tanggal...</div>
        </div>
    </div>

    <div class="p-4 bg-black/30 border-t border-emerald-700/50">
        <a href="../logout.php" class="flex items-center justify-center gap-2 px-4 py-3 bg-red-500/10 text-red-400 rounded-xl hover:bg-red-500 hover:text-white transition-all duration-300 text-sm font-bold border border-red-500/20 hover:border-red-500 shadow-sm">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar Sistem
        </a>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

<div class="flex-1 flex flex-col overflow-y-auto bg-[#f8fafc] relative">
    
    <header class="h-20 bg-white/80 backdrop-blur-lg shadow-sm border-b border-gray-100 flex items-center justify-between px-4 sm:px-8 z-30 sticky top-0">
        
        <div class="flex items-center gap-4">
            <button id="mobile-menu-btn" class="md:hidden p-2 rounded-xl text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 focus:outline-none transition">
                <i class="fa-solid fa-bars text-2xl"></i>
            </button>
            <h1 class="text-xl font-bold text-gray-800 hidden md:block tracking-tight">Administrator Panel</h1>
        </div>

        <div class="flex items-center gap-5 sm:gap-6">
            <button class="relative p-2 text-gray-400 hover:text-emerald-600 transition group">
                <i class="fa-regular fa-bell text-xl group-hover:animate-swing"></i>
                <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
            </button>

            <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

          <div class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded-xl transition">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator') ?></p>
                    <p class="text-[11px] text-emerald-600 font-bold uppercase tracking-wider mt-0.5">Super Admin</p>
                </div>
                
                <div class="w-11 h-11 rounded-full border-2 border-white shadow-md relative bg-emerald-100 flex items-center justify-center overflow-hidden">
                    <?php 
                        // Cek foto di session
                        $foto_profil = (isset($_SESSION['foto']) && $_SESSION['foto'] != '' && $_SESSION['foto'] != 'default.png') 
                            ? '../uploads/profiles/' . $_SESSION['foto'] 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nama_lengkap']) . '&background=10b981&color=fff&bold=true';
                    ?>
                    <img src="<?= $foto_profil ?>" alt="Profil" class="w-full h-full object-cover">
                    
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
                <i class="fa-solid fa-chevron-down text-xs text-gray-400 hidden sm:block ml-1"></i>
            </div>
        </div>
    </header>

    <div id="auto-toast" class="fixed bottom-6 right-6 z-50 flex items-start gap-4 bg-white/90 backdrop-blur-md border-l-4 border-emerald-500 p-5 rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] w-80 toast-exit transition-all duration-500">
        <div class="bg-gradient-to-br from-emerald-100 to-emerald-200 text-emerald-600 rounded-full w-10 h-10 flex items-center justify-center flex-shrink-0 shadow-inner">
            <i class="fa-solid fa-bullhorn text-lg"></i>
        </div>
        <div>
            <h4 class="text-sm font-extrabold text-gray-800" id="toast-title">Informasi Sistem</h4>
            <p class="text-xs text-gray-500 mt-1.5 leading-relaxed font-medium" id="toast-msg">Memuat pesan...</p>
        </div>
        <button onclick="hideToastNow()" class="text-gray-300 hover:text-red-500 ml-auto transition"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <script>
        // 1. Script Buka-Tutup Sidebar Mobile
        const btn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        btn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // 2. Script Jam & Tanggal Realtime (Bahasa Indonesia)
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            
            document.getElementById('live-time').innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
            document.getElementById('live-date').innerText = now.toLocaleDateString('id-ID', dateOptions);
        }
        setInterval(updateClock, 1000);
        updateClock(); // Panggil sekali saat load

        // 3. Script Auto Toast Notification (Rotasi Pesan)
        const toastMessages = [
            { title: "Selamat Datang!", msg: "Web SIPEGIZI ini digunakan untuk klasifikasi status gizi anak secara cerdas." },
            { title: "Algoritma K-NN", msg: "Sistem menggunakan metode K-Nearest Neighbor untuk akurasi prediksi yang tinggi." },
            { title: "Fitur Otomatis", msg: "Data yang diinput bidan akan otomatis masuk ke laporan riwayat Admin." },
            { title: "Cetak Laporan", msg: "Gunakan fitur Export Excel atau Cetak PDF untuk merekap laporan bulanan." }
        ];
        
        let msgIndex = 0;
        const toastEl = document.getElementById('auto-toast');
        let toastTimer;

        function showToast() {
            // Ganti teks konten
            document.getElementById('toast-title').innerText = toastMessages[msgIndex].title;
            document.getElementById('toast-msg').innerText = toastMessages[msgIndex].msg;

            // Animasikan masuk
            toastEl.classList.remove('toast-exit');
            toastEl.classList.add('toast-enter');

            // Jadwalkan untuk hilang setelah 5 detik tampil
            toastTimer = setTimeout(() => {
                hideToastNow();
            }, 5000);

            // Pindah ke pesan selanjutnya untuk siklus berikutnya
            msgIndex = (msgIndex + 1) % toastMessages.length;
        }

        function hideToastNow() {
            clearTimeout(toastTimer);
            toastEl.classList.remove('toast-enter');
            toastEl.classList.add('toast-exit');
        }

        // Panggil toast pertama kali setelah web diload (delay 2 detik)
        setTimeout(() => {
            showToast();
            // Setelah toast pertama, ulangi siklus setiap 8 detik (5 dtk tampil + 3 dtk jeda)
            setInterval(showToast, 8000);
        }, 2000);
    </script>
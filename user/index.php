<?php
session_start();
require_once '../database/config.php';

// Cek keamanan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil Statistik Detail untuk Bidan
// Total Balita yang pernah diperiksa bidan ini
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM riwayat_klasifikasi WHERE id_user = ?");
$stmt_total->execute([$_SESSION['user_id']]);
$total_periksa = $stmt_total->fetchColumn();

// Ambil jumlah balita dengan status Stunting/Buruk untuk perhatian khusus
$stmt_warning = $pdo->prepare("SELECT COUNT(*) FROM riwayat_klasifikasi WHERE id_user = ? AND (hasil_klasifikasi LIKE '%Buruk%' OR hasil_klasifikasi LIKE '%Stunt%')");
$stmt_warning->execute([$_SESSION['user_id']]);
$total_warning = $stmt_warning->fetchColumn();

// 2. Mendapatkan sapaan berdasarkan waktu
$jam = date('H');
if ($jam < 12) $sapaan = 'Selamat Pagi';
elseif ($jam < 15) $sapaan = 'Selamat Siang';
elseif ($jam < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - SIPEGIZI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; }
        .glass-card { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .custom-gradient { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); }
    </style>
</head>
<body class="text-gray-800 antialiased">

    <nav class="bg-white/80 backdrop-blur-md shadow-sm border-b border-emerald-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-18 py-4 items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-emerald-600 text-white p-2.5 rounded-xl shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-leaf text-lg"></i>
                    </div>
                    <div>
                        <span class="block font-bold text-xl tracking-tight text-emerald-900 leading-none">SIPEGIZI</span>
                        <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Sistem Pakar Gizi</span>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="index.php" class="text-emerald-700 font-bold border-b-2 border-emerald-600 pb-1">Beranda</a>
                    <a href="form_gizi.php" class="text-gray-500 hover:text-emerald-600 font-medium transition">Input Gizi</a>
                    <a href="riwayat.php" class="text-gray-500 hover:text-emerald-600 font-medium transition">Riwayat</a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right hidden lg:block">
                        <p class="text-xs font-bold text-gray-400 uppercase">Petugas Aktif</p>
                        <p class="text-sm font-bold text-emerald-900"><?= $_SESSION['nama_lengkap'] ?></p>
                    </div>
                    <a href="../logout.php" class="bg-red-500 text-white hover:bg-red-600 p-2.5 rounded-xl transition shadow-lg shadow-red-100">
                        <i class="fa-solid fa-power-off"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="custom-gradient rounded-[2.5rem] shadow-2xl p-8 md:p-12 text-white relative overflow-hidden mb-10">
            <div class="absolute -right-10 -bottom-10 opacity-10 text-[15rem]"><i class="fa-solid fa-user-nurse"></i></div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center relative z-10">
                <div class="lg:col-span-2 text-center md:text-left">
                    <div class="inline-block px-4 py-1.5 bg-white/20 rounded-full text-xs font-bold tracking-widest uppercase mb-4 backdrop-blur-md">
                        <i class="fa-solid fa-calendar-check mr-2"></i> <span id="live-date">Memuat Tanggal...</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-extrabold mb-4"><?= $sapaan ?>, Bidan <?= explode(' ', $_SESSION['nama_lengkap'])[0] ?>!</h1>
                    <p class="text-emerald-50 text-lg max-w-2xl font-light leading-relaxed">
                        Siap melakukan pemantauan gizi hari ini? Gunakan kecerdasan buatan <span class="font-bold underline decoration-emerald-300">K-Nearest Neighbor</span> untuk klasifikasi status gizi yang lebih akurat dan cepat.
                    </p>
                </div>
                <div class="flex flex-col gap-4">
                    <div class="glass-card p-6 rounded-3xl text-center">
                        <p class="text-emerald-100 text-xs font-bold uppercase tracking-wider mb-1">Total Pemeriksaan</p>
                        <p class="text-5xl font-black"><?= $total_periksa ?></p>
                    </div>
                    <div class="bg-orange-500/30 backdrop-blur-md p-4 rounded-2xl border border-orange-300/30 flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-[10px] font-bold uppercase">Butuh Perhatian</p>
                            <p class="text-xl font-bold"><?= $total_warning ?> Balita</p>
                        </div>
                        <i class="fa-solid fa-triangle-exclamation text-orange-200 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-8 space-y-8">
                <h2 class="text-2xl font-black text-gray-800 flex items-center gap-3">
                    <span class="w-2 h-8 bg-emerald-600 rounded-full"></span>
                    Akses Cepat Layanan
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="form_gizi.php" class="group bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:border-emerald-500 transition-all duration-500 hover:-translate-y-2 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 text-gray-50 group-hover:text-emerald-50 transition-colors text-8xl"><i class="fa-solid fa-file-medical"></i></div>
                        <div class="relative z-10">
                            <div class="bg-emerald-100 text-emerald-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-inner">
                                <i class="fa-solid fa-weight-scale"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Input Data Balita</h3>
                            <p class="text-sm text-gray-500 mt-3 leading-relaxed">Mulai pemeriksaan dengan menginput berat, tinggi, dan umur balita untuk dianalisis.</p>
                            <div class="mt-6 flex items-center gap-2 text-emerald-600 font-bold text-sm">
                                Buka Form <i class="fa-solid fa-chevron-right text-[10px] group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <a href="riwayat.php" class="group bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-2xl hover:border-blue-500 transition-all duration-500 hover:-translate-y-2 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 text-gray-50 group-hover:text-blue-50 transition-colors text-8xl"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div class="relative z-10">
                            <div class="bg-blue-100 text-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-inner">
                                <i class="fa-solid fa-print"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Rekap & Laporan</h3>
                            <p class="text-sm text-gray-500 mt-3 leading-relaxed">Lihat kembali riwayat pemeriksaan dan cetak rapor hasil gizi secara profesional.</p>
                            <div class="mt-6 flex items-center gap-2 text-blue-600 font-bold text-sm">
                                Buka Riwayat <i class="fa-solid fa-chevron-right text-[10px] group-hover:translate-x-2 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                    <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-microchip text-emerald-600"></i> Cara Kerja Sistem KNN
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-emerald-600 border border-emerald-100">1</div>
                            <p class="text-xs font-bold text-gray-700">Input Data</p>
                            <p class="text-[10px] text-gray-400 mt-1">Bidan memasukkan BB, TB, dan Umur Balita.</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-emerald-600 border border-emerald-100">2</div>
                            <p class="text-xs font-bold text-gray-700">Hitung Jarak</p>
                            <p class="text-[10px] text-gray-400 mt-1">Sistem mencari data paling mirip dari database.</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-emerald-600 border border-emerald-100">3</div>
                            <p class="text-xs font-bold text-gray-700">Hasil Gizi</p>
                            <p class="text-[10px] text-gray-400 mt-1">Muncul hasil klasifikasi (Normal, Kurang, atau Buruk).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-8">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Waktu Saat Ini</p>
                    <h2 id="live-clock" class="text-4xl font-black text-emerald-600 tracking-tighter">00:00:00</h2>
                </div>

                <div class="bg-emerald-900 rounded-3xl p-8 text-white relative overflow-hidden">
                    <div class="absolute -right-2 -top-2 opacity-20 text-6xl rotate-12"><i class="fa-solid fa-lightbulb"></i></div>
                    <h3 class="font-bold mb-4">Tips Hari Ini</h3>
                    <p id="quote-display" class="text-sm text-emerald-100 leading-relaxed italic italic">"Pemberian ASI Eksklusif selama 6 bulan pertama sangat krusial untuk mencegah stunting pada balita."</p>
                    <button onclick="changeQuote()" class="mt-6 text-[10px] font-bold uppercase tracking-widest text-emerald-400 hover:text-white transition">Tips Lain <i class="fa-solid fa-sync ml-1"></i></button>
                </div>

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h4 class="text-xs font-bold text-gray-800 mb-4 uppercase tracking-widest">Koneksi Sistem</h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Database MySQL</span>
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Algoritma KNN</span>
                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Aktif</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Jam Digital
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('live-clock').innerText = now.toLocaleTimeString('id-ID');
            document.getElementById('live-date').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Tips Gizi Acak
        const quotes = [
            '"Pemberian ASI Eksklusif selama 6 bulan pertama sangat krusial untuk mencegah stunting pada balita."',
            '"Pastikan balita mendapatkan imunisasi dasar lengkap sesuai jadwal untuk menjaga daya tahan tubuh."',
            '"Variasi menu makanan pendamping ASI (MPASI) penting untuk memenuhi kebutuhan gizi mikro anak."',
            '"Pantau berat badan balita setiap bulan di Posyandu terdekat untuk deteksi dini masalah gizi."'
        ];
        function changeQuote() {
            const random = Math.floor(Math.random() * quotes.length);
            document.getElementById('quote-display').innerText = quotes[random];
        }
    </script>
</body>
</html>
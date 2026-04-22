<?php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Gizi - SIPEGIZI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; } </style>
</head>
<body class="text-gray-800 antialiased">

    <nav class="bg-white shadow-sm border-b border-emerald-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <div class="bg-emerald-500 text-white p-2 rounded-lg"><i class="fa-solid fa-leaf"></i></div>
                    <span class="font-bold text-xl tracking-tight text-emerald-900">SIPEGIZI <span class="text-emerald-500 font-medium text-sm hidden sm:inline">Posyandu</span></span>
                </div>
                
                <div class="hidden md:flex gap-6">
                    <a href="index.php" class="text-gray-500 hover:text-emerald-600 font-medium transition px-2">Beranda</a>
                    <a href="form_gizi.php" class="text-emerald-600 font-semibold border-b-2 border-emerald-500 pb-1 px-2">Input Gizi</a>
                    <a href="riwayat.php" class="text-gray-500 hover:text-emerald-600 font-medium transition px-2">Riwayat</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="../logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2"><i class="fa-solid fa-power-off"></i> Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="index.php" class="hover:text-emerald-600"><i class="fa-solid fa-house"></i> Beranda</a>
            <i class="fa-solid fa-chevron-right text-xs"></i>
            <span class="text-gray-800 font-medium">Input Gizi Balita</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2">
                <form action="proses_analisa.php" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    
                    <div class="p-6 border-b border-gray-100 bg-emerald-50/30">
                        <h2 class="text-lg font-bold text-emerald-800 mb-4 flex items-center gap-2">
                            <span class="bg-emerald-200 text-emerald-700 w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span> 
                            Identitas Balita
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NIK Balita (Jika Ada)</label>
                                <input type="text" name="nik" maxlength="16" placeholder="Contoh: 18710..." class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Balita <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_balita" required placeholder="Masukkan nama..." class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_ortu" required placeholder="Nama ibu/ayah..." class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <select name="jenis_kelamin" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                                    <option value="" selected disabled>-- Pilih --</option>
                                    <option value="1">Laki-laki</option>
                                    <option value="0">Perempuan</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Domisili Kabupaten/Kota <span class="text-red-500">*</span></label>
                                <select name="kabupaten" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                                    <option value="" selected disabled>-- Pilih Kabupaten/Kota di Provinsi Lampung --</option>
                                    <option value="Kota Bandar Lampung">Kota Bandar Lampung</option>
                                    <option value="Kota Metro">Kota Metro</option>
                                    <option value="Kab. Lampung Barat">Kab. Lampung Barat</option>
                                    <option value="Kab. Tanggamus">Kab. Tanggamus</option>
                                    <option value="Kab. Lampung Selatan">Kab. Lampung Selatan</option>
                                    <option value="Kab. Lampung Timur">Kab. Lampung Timur</option>
                                    <option value="Kab. Lampung Tengah">Kab. Lampung Tengah</option>
                                    <option value="Kab. Lampung Utara">Kab. Lampung Utara</option>
                                    <option value="Kab. Way Kanan">Kab. Way Kanan</option>
                                    <option value="Kab. Tulang Bawang">Kab. Tulang Bawang</option>
                                    <option value="Kab. Pesawaran">Kab. Pesawaran</option>
                                    <option value="Kab. Pringsewu">Kab. Pringsewu</option>
                                    <option value="Kab. Mesuji">Kab. Mesuji</option>
                                    <option value="Kab. Tulang Bawang Barat">Kab. Tulang Bawang Barat</option>
                                    <option value="Kab. Pesisir Barat">Kab. Pesisir Barat</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <h2 class="text-lg font-bold text-emerald-800 mb-4 flex items-center gap-2">
                            <span class="bg-emerald-200 text-emerald-700 w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span> 
                            Data Pengukuran (Antropometri)
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                                <input type="date" id="tgl_lahir" name="tanggal_lahir" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Umur (Bulan)</label>
                                <div class="relative">
                                    <input type="number" id="umur_bulan" name="umur_bulan" readonly class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-100 text-gray-600 outline-none font-bold cursor-not-allowed">
                                    <span class="absolute right-4 top-2.5 text-gray-400 font-medium">Bulan</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tinggi Badan <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" step="0.1" name="tinggi_badan" required placeholder="0.0" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none">
                                    <span class="absolute right-4 top-2.5 text-gray-400 font-medium">cm</span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berat Badan <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" step="0.1" name="berat_badan" required placeholder="0.0" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none">
                                    <span class="absolute right-4 top-2.5 text-gray-400 font-medium">kg</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t border-gray-100">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl transition shadow-md flex justify-center items-center gap-2 text-lg">
                            <i class="fa-solid fa-microchip"></i> Proses Klasifikasi KNN
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-emerald-800 rounded-2xl shadow-lg p-6 text-white h-full">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-circle-info text-emerald-300"></i> Panduan Pengisian</h3>
                    <p class="text-emerald-100/90 text-sm mb-6 leading-relaxed">Pastikan data tinggi dan berat badan dimasukkan dengan akurat menggunakan tanda titik (.) untuk angka desimal (contoh: 75.5).</p>
                    <div class="bg-black/20 p-4 rounded-xl border border-white/10 mb-4">
                        <h4 class="font-semibold text-emerald-300 text-sm mb-1">Pilih Kabupaten</h4>
                        <p class="text-xs text-emerald-100/70">Pastikan memilih Domisili Kabupaten agar data prevalensi stunting di Dashboard Admin akurat.</p>
                    </div>
                    <div class="bg-black/20 p-4 rounded-xl border border-white/10 mb-4">
                        <h4 class="font-semibold text-emerald-300 text-sm mb-1">Umur Otomatis</h4>
                        <p class="text-xs text-emerald-100/70">Sistem akan otomatis menghitung umur balita (bulan) berdasarkan Tanggal Lahir.</p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.getElementById('tgl_lahir').addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let months = (today.getFullYear() - birthDate.getFullYear()) * 12;
            months -= birthDate.getMonth();
            months += today.getMonth();
            if (today.getDate() < birthDate.getDate()) months--;
            if(months < 0) months = 0;
            document.getElementById('umur_bulan').value = months;
        });
    </script>
</body>
</html>
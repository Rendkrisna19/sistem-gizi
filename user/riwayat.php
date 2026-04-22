<?php
session_start();
require_once '../database/config.php';

// Cek keamanan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Mengambil data riwayat pemeriksaan KHUSUS yang dilakukan oleh Bidan yang sedang login
// Kita gabungkan (JOIN) tabel riwayat_klasifikasi dengan tabel data_balita
$sql = "SELECT rk.*, b.nama_balita, b.nik, b.jenis_kelamin 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        WHERE rk.id_user = ? 
        ORDER BY rk.tanggal_ukur DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$data_riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Gizi - SIPEGIZI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    <a href="form_gizi.php" class="text-gray-500 hover:text-emerald-600 font-medium transition px-2">Input Gizi</a>
                    <a href="riwayat.php" class="text-emerald-600 font-semibold border-b-2 border-emerald-500 pb-1 px-2">Riwayat</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="../logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-power-off"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="index.php" class="hover:text-emerald-600"><i class="fa-solid fa-house"></i> Beranda</a>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                    <span class="text-gray-800 font-medium">Riwayat Pemeriksaan</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Riwayat Klasifikasi Gizi Balita</h1>
                <p class="text-gray-500 text-sm mt-1">Daftar rekam medis balita yang telah Anda periksa.</p>
            </div>

           <div class="flex items-center gap-3">
    <a href="cetak/cetak_excel.php" class="bg-white border-2 border-green-600 text-green-700 hover:bg-green-50 px-4 py-2.5 rounded-xl text-sm font-bold transition shadow-sm flex items-center gap-2">
        <i class="fa-solid fa-file-excel text-lg"></i> Export Excel
    </a>
    <a href="cetak/cetak_pdf.php" target="_blank" class="bg-red-600 border-2 border-red-600 text-white hover:bg-red-700 px-4 py-2.5 rounded-xl text-sm font-bold transition shadow-md flex items-center gap-2">
        <i class="fa-solid fa-file-pdf text-lg"></i> Cetak PDF
    </a>
</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-emerald-50/50 text-emerald-800 uppercase tracking-wider text-xs border-b border-emerald-100">
                        <tr>
                            <th class="py-4 px-6 font-bold">Tanggal</th>
                            <th class="py-4 px-6 font-bold">Nama Balita</th>
                            <th class="py-4 px-6 font-bold text-center">Umur & Kelamin</th>
                            <th class="py-4 px-6 font-bold text-center">BB / TB</th>
                            <th class="py-4 px-6 font-bold text-center">Status Gizi (K-NN)</th>
                            <th class="py-4 px-6 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        
                        <?php foreach($data_riwayat as $row): 
                            // Menentukan warna badge (label) berdasarkan status gizi
                            $status = strtolower($row['hasil_klasifikasi']);
                            $badge_color = 'bg-emerald-100 text-emerald-700 border-emerald-200'; // Default Hijau (Normal)
                            
                            if (strpos($status, 'kurang') !== false || strpos($status, 'underweight') !== false) {
                                $badge_color = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            } elseif (strpos($status, 'buruk') !== false || strpos($status, 'stunt') !== false) {
                                $badge_color = 'bg-red-100 text-red-700 border-red-200';
                            } elseif (strpos($status, 'lebih') !== false || strpos($status, 'obesitas') !== false) {
                                $badge_color = 'bg-blue-100 text-blue-700 border-blue-200';
                            }
                        ?>
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-700"><?= date('d M Y', strtotime($row['tanggal_ukur'])) ?></div>
                                <div class="text-xs text-gray-400 mt-1">ID: #<?= $row['id'] ?></div>
                            </td>
                            
                            <td class="py-4 px-6">
                                <div class="font-bold text-emerald-700"><?= htmlspecialchars($row['nama_balita']) ?></div>
                                <div class="text-xs text-gray-500 mt-1">NIK: <?= $row['nik'] ?: '-' ?></div>
                            </td>
                            
                            <td class="py-4 px-6 text-center">
                                <div class="font-medium text-gray-800"><?= $row['umur_saat_ukur'] ?> Bulan</div>
                                <?php if($row['jenis_kelamin'] == 1): ?>
                                    <div class="text-xs text-blue-600 mt-1"><i class="fa-solid fa-mars"></i> Laki-laki</div>
                                <?php else: ?>
                                    <div class="text-xs text-pink-600 mt-1"><i class="fa-solid fa-venus"></i> Perempuan</div>
                                <?php endif; ?>
                            </td>

                            <td class="py-4 px-6 text-center">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-medium border border-gray-200">
                                    <?= $row['berat_badan'] ?> kg
                                </span>
                                <span class="mx-1 text-gray-300">/</span>
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-medium border border-gray-200">
                                    <?= $row['tinggi_badan'] ?> cm
                                </span>
                            </td>

                            <td class="py-4 px-6 text-center">
                                <span class="px-3 py-1.5 rounded-lg border text-xs font-bold uppercase tracking-wider <?= $badge_color ?>">
                                    <?= htmlspecialchars($row['hasil_klasifikasi']) ?>
                                </span>
                            </td>

                            <td class="py-4 px-6 text-center">
                                <a href="lihat_rapor.php?id=<?= $row['id'] ?>" class="bg-white border border-gray-300 text-gray-600 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 w-8 h-8 rounded-full flex items-center justify-center transition tooltip" title="Lihat Detail Rapor">
    <i class="fa-solid fa-eye text-sm"></i>
</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(empty($data_riwayat)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-16">
                                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-clipboard-list text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Belum Ada Riwayat</h3>
                                <p class="text-gray-500 mt-1">Anda belum melakukan pemeriksaan gizi satupun.</p>
                                <a href="form_gizi.php" class="mt-4 inline-block bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition">
                                    <i class="fa-solid fa-plus mr-2"></i> Input Pengukuran Baru
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
            
            <?php if(!empty($data_riwayat)): ?>
            <div class="bg-gray-50 p-4 border-t border-gray-100 text-xs text-gray-500 text-center sm:text-left">
                Menampilkan total <b><?= count($data_riwayat) ?></b> riwayat pemeriksaan Anda.
            </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>
<?php
session_start();
require_once '../database/config.php';

// Cek akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Mengambil SEMUA data riwayat pemeriksaan dan menggabungkannya dengan tabel data_balita & users (petugas)
$sql = "SELECT rk.*, b.nama_balita, b.nik, b.jenis_kelamin, u.nama_lengkap as nama_petugas 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        JOIN users u ON rk.id_user = u.id 
        ORDER BY rk.tanggal_ukur DESC";

$stmt = $pdo->query($sql);
$data_riwayat = $stmt->fetchAll();

// Menghitung statistik untuk Dashboard Atas
$total_periksa = count($data_riwayat);
$total_buruk = 0; $total_kurang = 0; $total_normal = 0;

foreach($data_riwayat as $d) {
    $status = strtolower($d['hasil_klasifikasi']);
    if (strpos($status, 'buruk') !== false || strpos($status, 'stunt') !== false) $total_buruk++;
    elseif (strpos($status, 'kurang') !== false || strpos($status, 'underweight') !== false) $total_kurang++;
    else $total_normal++;
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<main class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Laporan Riwayat Klasifikasi</h2>
            <p class="text-gray-500 mt-1">Pantau seluruh hasil pemeriksaan gizi dari semua petugas/bidan.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="cetak/cetak_excel.php" class="bg-white border border-green-600 text-green-700 hover:bg-green-50 px-4 py-2.5 rounded-xl text-sm font-bold transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-lg"></i> Export Excel
            </a>
            <a href="cetak/cetak_pdf.php" target="_blank" class="bg-red-600 border border-red-600 text-white hover:bg-red-700 px-4 py-2.5 rounded-xl text-sm font-bold transition shadow-md flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-lg"></i> Cetak PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-full flex items-center justify-center text-xl"><i class="fa-solid fa-users"></i></div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Pemeriksaan</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $total_periksa ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="bg-emerald-100 text-emerald-600 w-12 h-12 rounded-full flex items-center justify-center text-xl"><i class="fa-solid fa-face-smile"></i></div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Gizi Normal / Baik</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $total_normal ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="bg-yellow-100 text-yellow-600 w-12 h-12 rounded-full flex items-center justify-center text-xl"><i class="fa-solid fa-face-meh"></i></div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Gizi Kurang</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $total_kurang ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="bg-red-100 text-red-600 w-12 h-12 rounded-full flex items-center justify-center text-xl"><i class="fa-solid fa-face-frown"></i></div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Gizi Buruk / Stunting</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $total_buruk ?></h3>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-full">
        <div class="overflow-x-auto max-h-[600px] custom-scrollbar">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider sticky top-0 shadow-sm z-10">
                    <tr>
                        <th class="py-4 px-6 border-b font-semibold">Tanggal & Petugas</th>
                        <th class="py-4 px-6 border-b font-semibold">Pasien Balita</th>
                        <th class="py-4 px-6 border-b font-semibold text-center">BB / TB</th>
                        <th class="py-4 px-6 border-b font-semibold text-center">Status Gizi (K-NN)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    
                    <?php foreach($data_riwayat as $row): 
                        $status = strtolower($row['hasil_klasifikasi']);
                        $badge_color = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                        if (strpos($status, 'kurang') !== false || strpos($status, 'underweight') !== false) $badge_color = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                        elseif (strpos($status, 'buruk') !== false || strpos($status, 'stunt') !== false) $badge_color = 'bg-red-100 text-red-700 border-red-200';
                        elseif (strpos($status, 'lebih') !== false || strpos($status, 'obesitas') !== false) $badge_color = 'bg-blue-100 text-blue-700 border-blue-200';
                    ?>
                    <tr class="hover:bg-emerald-50/40 transition duration-150">
                        <td class="py-3 px-6">
                            <div class="font-bold text-gray-700 mb-1"><i class="fa-regular fa-calendar text-emerald-500 mr-1"></i> <?= date('d M Y', strtotime($row['tanggal_ukur'])) ?></div>
                            <div class="text-xs text-gray-500"><i class="fa-solid fa-user-nurse text-gray-400 mr-1"></i> Bidan <?= htmlspecialchars($row['nama_petugas']) ?></div>
                        </td>
                        
                        <td class="py-3 px-6">
                            <div class="font-bold text-emerald-700"><?= htmlspecialchars($row['nama_balita']) ?></div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= $row['umur_saat_ukur'] ?> Bulan | 
                                <?= $row['jenis_kelamin'] == 1 ? '<span class="text-blue-500">Laki-laki</span>' : '<span class="text-pink-500">Perempuan</span>' ?>
                            </div>
                        </td>

                        <td class="py-3 px-6 text-center">
                            <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-semibold border border-gray-200"><?= $row['berat_badan'] ?> kg</span>
                            <span class="mx-1 text-gray-300">/</span>
                            <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-semibold border border-gray-200"><?= $row['tinggi_badan'] ?> cm</span>
                        </td>

                        <td class="py-3 px-6 text-center">
                            <span class="px-3 py-1.5 rounded-lg border text-xs font-bold uppercase tracking-wider <?= $badge_color ?>">
                                <?= htmlspecialchars($row['hasil_klasifikasi']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(empty($data_riwayat)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-16">
                            <div class="text-gray-300 mb-3"><i class="fa-solid fa-clipboard-list text-5xl"></i></div>
                            <p class="text-gray-500 font-medium">Belum ada riwayat pemeriksaan satupun.</p>
                        </td>
                    </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</main>

        </div> </div>
</body>
</html>
<?php
session_start();
require_once '../database/config.php';

// Cek keamanan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Cek apakah ada ID yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: riwayat.php");
    exit;
}

$id_riwayat = $_GET['id'];

// Ambil detail riwayat, pastikan hanya milik user yang sedang login untuk keamanan
$sql = "SELECT rk.*, b.nama_balita, b.nik, b.nama_ortu, b.jenis_kelamin 
        FROM riwayat_klasifikasi rk 
        JOIN data_balita b ON rk.id_balita = b.id 
        WHERE rk.id = ? AND rk.id_user = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_riwayat, $_SESSION['user_id']]);
$data = $stmt->fetch();

// Jika data tidak ditemukan / bukan milik bidan ini, kembalikan ke riwayat
if (!$data) {
    header("Location: riwayat.php");
    exit;
}

// Tentukan Tema Warna berdasarkan Status Gizi
$prediksi_final = $data['hasil_klasifikasi'];
$theme_color = 'emerald';
$icon = 'fa-face-smile';
$deskripsi = 'Pertumbuhan anak sangat baik. Pertahankan asupan gizi seimbang!';

if (stripos($prediksi_final, 'kurang') !== false || stripos($prediksi_final, 'underweight') !== false) {
    $theme_color = 'yellow';
    $icon = 'fa-face-meh';
    $deskripsi = 'Perlu perhatian. Segera konsultasikan dengan ahli gizi untuk penambahan asupan makanan pendamping.';
} elseif (stripos($prediksi_final, 'buruk') !== false || stripos($prediksi_final, 'stunt') !== false) {
    $theme_color = 'red';
    $icon = 'fa-face-frown';
    $deskripsi = 'PERINGATAN: Membutuhkan rujukan segera ke Puskesmas/Rumah Sakit untuk penanganan medis lebih lanjut.';
} elseif (stripos($prediksi_final, 'lebih') !== false || stripos($prediksi_final, 'obesitas') !== false) {
    $theme_color = 'blue';
    $icon = 'fa-face-rolling-eyes';
    $deskripsi = 'Risiko kelebihan berat badan. Kurangi makanan manis dan tingkatkan aktivitas fisik balita.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Rapor Gizi - SIPEGIZI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style> 
        body { font-family: 'Poppins', sans-serif; background-color: #f1f5f9; } 
        @media print {
            .no-print { display: none !important; }
            body { background-color: white; }
            .print-shadow-none { box-shadow: none !important; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body class="text-gray-800 antialiased py-8 px-4 sm:px-6">

    <div class="max-w-4xl mx-auto">
        
        <div class="flex justify-between items-center mb-6 no-print">
            <a href="riwayat.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
            </a>
            <button onclick="window.print()" class="bg-emerald-600 text-white hover:bg-emerald-700 px-5 py-2 rounded-lg font-medium transition shadow-md flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Dokumen
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden print-shadow-none border border-gray-100 relative">
            
            <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none z-0">
                <i class="fa-solid fa-file-shield text-[300px]"></i>
            </div>

            <div class="bg-emerald-800 text-white p-8 relative overflow-hidden z-10">
                <div class="absolute top-0 right-0 opacity-10 text-9xl transform translate-x-4 -translate-y-8">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <div class="relative flex flex-col sm:flex-row justify-between items-center sm:items-end">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight">Salinan Rapor Gizi</h1>
                        <p class="text-emerald-200 mt-1 flex items-center gap-2">
                            <i class="fa-solid fa-hospital"></i> SIPEGIZI Posyandu Provinsi Lampung
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 text-center sm:text-right">
                        <p class="text-xs text-emerald-300 uppercase tracking-widest mb-1">Tanggal Pemeriksaan</p>
                        <p class="text-lg font-bold bg-white/20 px-4 py-1.5 rounded-lg inline-block backdrop-blur-sm">
                            <?= date('d F Y', strtotime($data['tanggal_ukur'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-8 relative z-10">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Identitas Pasien</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500 w-1/3">No. Rekam Medis</td><td class="py-2 font-mono text-gray-400">#RM-<?= $data['id_balita'] ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">NIK</td><td class="py-2 font-semibold"><?= htmlspecialchars($data['nik'] ?: '-') ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Nama Balita</td><td class="py-2 font-bold text-emerald-700"><?= htmlspecialchars($data['nama_balita']) ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Orang Tua</td><td class="py-2 font-semibold"><?= htmlspecialchars($data['nama_ortu']) ?></td></tr>
                            <tr><td class="py-2 text-gray-500">Kelamin</td><td class="py-2 font-semibold"><?= $data['jenis_kelamin'] == 1 ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                        </table>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Hasil Antropometri Historis</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Umur Saat Ukur</p>
                                <p class="text-lg font-bold text-gray-800"><?= $data['umur_saat_ukur'] ?> <span class="text-xs font-normal">Bln</span></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Berat Badan</p>
                                <p class="text-lg font-bold text-gray-800"><?= $data['berat_badan'] ?> <span class="text-xs font-normal">Kg</span></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Tinggi Badan</p>
                                <p class="text-lg font-bold text-gray-800"><?= $data['tinggi_badan'] ?> <span class="text-xs font-normal">cm</span></p>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <span class="bg-emerald-50 text-emerald-600 border border-emerald-100 text-[11px] px-3 py-1 rounded-full">
                                <i class="fa-solid fa-robot"></i> Dianalisis dengan K-NN (K=<?= $data['nilai_k'] ?>)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-<?= $theme_color ?>-50 border-2 border-<?= $theme_color ?>-200 rounded-2xl p-6 text-center relative overflow-hidden">
                    <p class="text-sm font-semibold text-<?= $theme_color ?>-600 uppercase tracking-widest mb-2">Kesimpulan Status Gizi</p>
                    <h2 class="text-4xl sm:text-5xl font-black text-<?= $theme_color ?>-700 uppercase tracking-tight mb-4">
                        <?= htmlspecialchars($prediksi_final) ?>
                    </h2>
                    <div class="bg-white/60 inline-flex items-center gap-3 px-6 py-3 rounded-full text-<?= $theme_color ?>-800 text-sm font-medium border border-<?= $theme_color ?>-100">
                        <i class="fa-solid <?= $icon ?> text-xl"></i> <?= $deskripsi ?>
                    </div>
                </div>

            </div>
            
            <div class="bg-gray-50 p-5 border-t border-gray-100 flex justify-between items-center text-xs text-gray-400">
                <p>Dokumen ini dicetak dari Sistem Pendukung Keputusan K-NN pada <?= date('d/m/Y H:i') ?></p>
                <p class="font-medium text-gray-600">Petugas: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
            </div>
        </div>
    </div>

</body>
</html>
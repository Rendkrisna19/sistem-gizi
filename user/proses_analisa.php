<?php
session_start();
require_once '../database/config.php';

// Cek keamanan: Hanya user (Bidan) yang boleh mengakses file ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

// Pastikan file ini diakses dari submit form, bukan diketik manual di URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    // ==============================================================================
    // TAHAP 1: TANGKAP INPUTAN DARI FORM & SIMPAN KE DATA BALITA
    // ==============================================================================
    $nik = !empty($_POST['nik']) ? trim($_POST['nik']) : 'TMP-' . time(); // Jika NIK kosong, beri ID sementara
    $nama_balita = trim($_POST['nama_balita']);
    $nama_ortu = trim($_POST['nama_ortu']);
    $jk = (int)$_POST['jenis_kelamin']; // 1 = Laki-laki, 0 = Perempuan
    $tgl_lahir = $_POST['tanggal_lahir'];
    $kabupaten = isset($_POST['kabupaten']) ? $_POST['kabupaten'] : 'Belum diisi'; // Menangkap data Kabupaten
    
    $umur_bulan = (int)$_POST['umur_bulan'];
    $tinggi_badan = (float)$_POST['tinggi_badan'];
    $berat_badan = (float)$_POST['berat_badan']; 

    // Cek apakah balita ini sudah pernah didaftarkan (Berdasarkan NIK)
    $stmt_cek = $pdo->prepare("SELECT id, kabupaten FROM data_balita WHERE nik = ? AND nik NOT LIKE 'TMP-%'");
    $stmt_cek->execute([$nik]);
    $cek_balita = $stmt_cek->fetch();

    if ($cek_balita) {
        // Jika sudah ada, gunakan ID balita tersebut
        $id_balita = $cek_balita['id'];
        
        // Update data kabupatennya jika ternyata dia pindah/ada perubahan
        $stmt_update_kab = $pdo->prepare("UPDATE data_balita SET kabupaten = ? WHERE id = ?");
        $stmt_update_kab->execute([$kabupaten, $id_balita]);
    } else {
        // Jika belum ada, daftarkan sebagai pasien baru dengan data Kabupaten
        $stmt_insert = $pdo->prepare("INSERT INTO data_balita (nik, nama_balita, nama_ortu, jenis_kelamin, kabupaten, tanggal_lahir, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$nik, $nama_balita, $nama_ortu, $jk, $kabupaten, $tgl_lahir, 'Belum diisi']);
        $id_balita = $pdo->lastInsertId();
    }


    // ==============================================================================
    // TAHAP 2: PROSES ALGORITMA K-NEAREST NEIGHBOR (KNN)
    // ==============================================================================
    $k_value = 5; // Nilai K terbaik yang disarankan untuk Skripsi
    
    // Cek apakah Admin sudah mengupload data latih
    $cek_latih = $pdo->query("SELECT COUNT(*) FROM data_latih")->fetchColumn();
    if ($cek_latih == 0) {
        die("<div style='padding:20px; text-align:center; font-family:sans-serif;'><h3>Data Latih Kosong!</h3><p>Silakan minta Administrator untuk mengimpor file CSV Dataset terlebih dahulu.</p><a href='index.php'>Kembali</a></div>");
    }

    // A. Cari Nilai MIN & MAX untuk Normalisasi Data
    $stmt_minmax = $pdo->query("SELECT 
        MIN(umur_bulan) as min_umur, MAX(umur_bulan) as max_umur,
        MIN(tinggi_badan) as min_tb, MAX(tinggi_badan) as max_tb
        FROM data_latih");
    $minMax = $stmt_minmax->fetch();

    // Hindari pembagian dengan 0
    $selisih_umur = ($minMax['max_umur'] - $minMax['min_umur']) == 0 ? 1 : ($minMax['max_umur'] - $minMax['min_umur']);
    $selisih_tb = ($minMax['max_tb'] - $minMax['min_tb']) == 0 ? 1 : ($minMax['max_tb'] - $minMax['min_tb']);

    // B. Normalisasi Data Inputan Balita Baru
    $input_umur_norm = ($umur_bulan - $minMax['min_umur']) / $selisih_umur;
    $input_tb_norm = ($tinggi_badan - $minMax['min_tb']) / $selisih_tb;

    // C. Hitung Jarak Euclidean ke Semua Data Latih
    $stmt_data = $pdo->query("SELECT id, jenis_kelamin, umur_bulan, tinggi_badan, status_gizi FROM data_latih");
    $hasil_jarak = [];

    while ($latih = $stmt_data->fetch()) {
        $latih_umur_norm = ($latih['umur_bulan'] - $minMax['min_umur']) / $selisih_umur;
        $latih_tb_norm = ($latih['tinggi_badan'] - $minMax['min_tb']) / $selisih_tb;
        $latih_jk = $latih['jenis_kelamin'];

        // Rumus Euclidean Distance
        $jarak = sqrt(
            pow(($jk - $latih_jk), 2) + 
            pow(($input_umur_norm - $latih_umur_norm), 2) + 
            pow(($input_tb_norm - $latih_tb_norm), 2)
        );

        $hasil_jarak[] = [
            'id_latih' => $latih['id'],
            'jarak' => $jarak,
            'status_gizi' => $latih['status_gizi']
        ];
    }

    // D. Urutkan Jarak dari yang Terkecil ke Terbesar
    usort($hasil_jarak, function($a, $b) {
        return $a['jarak'] <=> $b['jarak'];
    });

    // E. Ambil K-Tetangga Terdekat
    $tetangga_terdekat = array_slice($hasil_jarak, 0, $k_value);

    // F. Lakukan Voting / Mayoritas
    $voting = [];
    foreach ($tetangga_terdekat as $tetangga) {
        $status = trim($tetangga['status_gizi']);
        if (isset($voting[$status])) {
            $voting[$status]++;
        } else {
            $voting[$status] = 1;
        }
    }
    arsort($voting); // Urutkan dari jumlah terbanyak
    $prediksi_final = array_key_first($voting); // Status gizi yang terpilih


    // ==============================================================================
    // TAHAP 3: SIMPAN HASIL KE TABEL RIWAYAT KLASIFIKASI
    // ==============================================================================
    $stmt_riwayat = $pdo->prepare("INSERT INTO riwayat_klasifikasi 
        (id_balita, id_user, tanggal_ukur, umur_saat_ukur, berat_badan, tinggi_badan, hasil_klasifikasi, nilai_k) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $tgl_hari_ini = date('Y-m-d');
    $stmt_riwayat->execute([
        $id_balita, 
        $_SESSION['user_id'], 
        $tgl_hari_ini, 
        $umur_bulan, 
        $berat_badan, 
        $tinggi_badan, 
        $prediksi_final, 
        $k_value
    ]);

} catch (PDOException $e) {
    die("Terjadi Kesalahan Database: " . $e->getMessage());
}

// Tentukan Warna Kartu berdasarkan Status Gizi
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
    <title>Hasil Klasifikasi KNN - SIPEGIZI</title>
    
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
            <a href="form_gizi.php" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-medium transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Form
            </a>
            <button onclick="window.print()" class="bg-emerald-600 text-white hover:bg-emerald-700 px-5 py-2 rounded-lg font-medium transition shadow-md flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Rapor
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden print-shadow-none border border-gray-100">
            
            <div class="bg-emerald-800 text-white p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-10 text-9xl transform translate-x-4 -translate-y-8">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <div class="relative z-10 flex flex-col sm:flex-row justify-between items-center sm:items-end">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight">Rapor Status Gizi Balita</h1>
                        <p class="text-emerald-200 mt-1 flex items-center gap-2">
                            <i class="fa-solid fa-hospital"></i> SIPEGIZI Posyandu Provinsi Lampung
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 text-center sm:text-right">
                        <p class="text-xs text-emerald-300 uppercase tracking-widest mb-1">Tanggal Pemeriksaan</p>
                        <p class="text-lg font-bold bg-white/20 px-4 py-1.5 rounded-lg inline-block backdrop-blur-sm">
                            <?= date('d F Y') ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Identitas Pasien</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500 w-1/3">NIK</td><td class="py-2 font-semibold"><?= htmlspecialchars($nik) ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Nama Balita</td><td class="py-2 font-semibold text-emerald-700"><?= htmlspecialchars($nama_balita) ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Orang Tua</td><td class="py-2 font-semibold"><?= htmlspecialchars($nama_ortu) ?></td></tr>
                            <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Kelamin</td><td class="py-2 font-semibold"><?= $jk == 1 ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                            <tr><td class="py-2 text-gray-500">Kab/Kota</td><td class="py-2 font-semibold text-gray-800"><i class="fa-solid fa-location-dot text-red-500 mr-1"></i> <?= htmlspecialchars($kabupaten) ?></td></tr>
                        </table>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Hasil Antropometri</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center">
                                <p class="text-xs text-gray-500 mb-1">Umur</p>
                                <p class="text-lg font-bold text-gray-800"><?= $umur_bulan ?> <span class="text-xs font-normal">Bulan</span></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center">
                                <p class="text-xs text-gray-500 mb-1">Berat</p>
                                <p class="text-lg font-bold text-gray-800"><?= $berat_badan ?> <span class="text-xs font-normal">Kg</span></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center">
                                <p class="text-xs text-gray-500 mb-1">Tinggi</p>
                                <p class="text-lg font-bold text-gray-800"><?= $tinggi_badan ?> <span class="text-xs font-normal">cm</span></p>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2 text-right">Diperiksa oleh: Bidan <?= $_SESSION['nama_lengkap'] ?></p>
                    </div>
                </div>

                <div class="bg-<?= $theme_color ?>-50 border-2 border-<?= $theme_color ?>-200 rounded-2xl p-6 text-center relative overflow-hidden mb-8">
                    <p class="text-sm font-semibold text-<?= $theme_color ?>-600 uppercase tracking-widest mb-2">Kesimpulan Status Gizi</p>
                    <h2 class="text-4xl sm:text-5xl font-black text-<?= $theme_color ?>-700 uppercase tracking-tight mb-4">
                        <?= htmlspecialchars($prediksi_final) ?>
                    </h2>
                    <div class="bg-white/60 inline-flex items-center gap-3 px-6 py-3 rounded-full text-<?= $theme_color ?>-800 text-sm font-medium border border-<?= $theme_color ?>-100">
                        <i class="fa-solid <?= $icon ?> text-xl"></i> <?= $deskripsi ?>
                    </div>
                </div>

                <div class="mt-8 no-print">
                    <button onclick="document.getElementById('knnDetails').classList.toggle('hidden')" class="w-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 py-3 rounded-xl font-medium text-sm transition flex justify-between items-center px-4">
                        <span><i class="fa-solid fa-code-branch mr-2"></i> Lihat Bukti Perhitungan K-Nearest Neighbor (K=<?= $k_value ?>)</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    
                    <div id="knnDetails" class="hidden mt-4 bg-white border border-gray-200 rounded-xl overflow-hidden shadow-inner">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="py-3 px-4">Rank</th>
                                    <th class="py-3 px-4">Data Latih ID</th>
                                    <th class="py-3 px-4">Jarak Euclidean</th>
                                    <th class="py-3 px-4 text-right">Status Gizi (Label)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tetangga_terdekat as $index => $t): ?>
                                <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50">
                                    <td class="py-2 px-4 font-bold text-gray-400">#<?= $index + 1 ?></td>
                                    <td class="py-2 px-4">Dataset-<?= $t['id_latih'] ?></td>
                                    <td class="py-2 px-4 font-mono text-emerald-600"><?= number_format($t['jarak'], 5) ?></td>
                                    <td class="py-2 px-4 text-right font-semibold uppercase text-gray-700"><?= $t['status_gizi'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            
            <div class="bg-gray-50 p-4 text-center text-xs text-gray-400 border-t border-gray-100">
                Sistem Pendukung Keputusan K-NN &copy; <?= date('Y') ?>
            </div>
        </div>
    </div>

</body>
</html>
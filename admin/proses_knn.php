<?php
session_start();
require_once '../database/config.php';

// Cek akses admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 1. Tangkap data inputan dari form balita baru
// Anggap ini data balita "Budi" yang mau dicek
$input_jk = 1; // 1 = Laki-laki, 0 = Perempuan
$input_umur = 24; // bulan
$input_tinggi = 85.5; // cm

// Tetapkan nilai K (Berdasarkan saran untuk skripsi, kita pakai K=5)
$k_value = 5;

// 2. Cari Nilai MIN dan MAX dari database untuk proses Normalisasi (Min-Max Scaler)
// Sama persis seperti fungsi scaler.fit_transform() di Python
$stmt_minmax = $pdo->query("SELECT 
    MIN(umur_bulan) as min_umur, MAX(umur_bulan) as max_umur,
    MIN(tinggi_badan) as min_tb, MAX(tinggi_badan) as max_tb
    FROM data_latih");
$minMax = $stmt_minmax->fetch();

// Hitung input Budi yang sudah di-normalisasi (skala 0 sampai 1)
$input_umur_norm = ($input_umur - $minMax['min_umur']) / ($minMax['max_umur'] - $minMax['min_umur']);
$input_tb_norm = ($input_tinggi - $minMax['min_tb']) / ($minMax['max_tb'] - $minMax['min_tb']);
// Jenis kelamin tidak perlu dinormalisasi karena nilainya sudah 0 dan 1

// 3. Ambil semua Data Latih dari database
$stmt_data = $pdo->query("SELECT id, jenis_kelamin, umur_bulan, tinggi_badan, status_gizi FROM data_latih");
$semua_data_latih = $stmt_data->fetchAll();

$hasil_jarak = [];

// 4. Proses Menghitung Jarak (Euclidean Distance)
foreach ($semua_data_latih as $latih) {
    // Normalisasi data latih yang sedang di-looping
    $latih_umur_norm = ($latih['umur_bulan'] - $minMax['min_umur']) / ($minMax['max_umur'] - $minMax['min_umur']);
    $latih_tb_norm = ($latih['tinggi_badan'] - $minMax['min_tb']) / ($minMax['max_tb'] - $minMax['min_tb']);
    $latih_jk = $latih['jenis_kelamin']; // Sudah 0 atau 1

    // RUMUS EUCLIDEAN DISTANCE (Sama dengan metrik 'euclidean' di Python)
    $jarak = sqrt(
        pow(($input_jk - $latih_jk), 2) + 
        pow(($input_umur_norm - $latih_umur_norm), 2) + 
        pow(($input_tb_norm - $latih_tb_norm), 2)
    );

    // Simpan jarak dan label status gizinya ke dalam array
    $hasil_jarak[] = [
        'id_latih' => $latih['id'],
        'jarak' => $jarak,
        'status_gizi' => $latih['status_gizi']
    ];
}

// 5. Urutkan dari jarak yang PALING DEKAT (terkecil)
usort($hasil_jarak, function($a, $b) {
    return $a['jarak'] <=> $b['jarak'];
});

// 6. Ambil sejumlah K tetangga terdekat (Ambil 5 teratas)
$tetangga_terdekat = array_slice($hasil_jarak, 0, $k_value);

// 7. Lakukan Voting (Mayoritas status gizi apa dari 5 tetangga tersebut?)
$voting = [];
foreach ($tetangga_terdekat as $tetangga) {
    $status = $tetangga['status_gizi'];
    if (isset($voting[$status])) {
        $voting[$status]++;
    } else {
        $voting[$status] = 1;
    }
}

// Cari yang paling banyak (modus)
arsort($voting); // Urutkan array berdasarkan jumlah terbanyak
$prediksi_final = array_key_first($voting); // Ambil nama status gizi peringkat 1

// --- OUTPUT UNTUK TESTING ---
echo "<div style='font-family: Arial; padding: 20px;'>";
echo "<h2>Hasil Klasifikasi KNN (K=$k_value)</h2>";
echo "<b>Data Input:</b> L/P = $input_jk | Umur = $input_umur bulan | TB = $input_tinggi cm <br><br>";

echo "<b>5 Tetangga Terdekat:</b><br>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
        <tr><th>Ranking</th><th>Jarak Euclidean</th><th>Status Gizi Tetangga</th></tr>";
foreach ($tetangga_terdekat as $index => $t) {
    $no = $index + 1;
    echo "<tr><td>$no</td><td>".round($t['jarak'], 4)."</td><td>".$t['status_gizi']."</td></tr>";
}
echo "</table><br>";

echo "<h3 style='color: green;'>Kesimpulan Prediksi Status Gizi: <u>$prediksi_final</u></h3>";
echo "</div>";
?>
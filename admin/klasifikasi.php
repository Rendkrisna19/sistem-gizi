<?php
session_start();
require_once '../database/config.php';

// Cek akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$hasil_prediksi = null;
$tetangga_terdekat = [];
$pesan_error = '';

// PROSES ALGORITMA KNN JIKA FORM DISUBMIT
if (isset($_POST['simulasi_knn'])) {
    $jk = (int)$_POST['jenis_kelamin'];
    $umur_bulan = (int)$_POST['umur_bulan'];
    $tinggi_badan = (float)$_POST['tinggi_badan'];
    $k_value = (int)$_POST['nilai_k']; // Admin bisa bebas mengganti nilai K untuk testing

    // Cek apakah dataset kosong
    $cek_latih = $pdo->query("SELECT COUNT(*) FROM data_latih")->fetchColumn();
    
    if ($cek_latih == 0) {
        $pesan_error = "Dataset masih kosong! Silakan import data latih terlebih dahulu.";
    } else {
        // 1. Cari Nilai MIN & MAX dari database untuk Normalisasi
        $stmt_minmax = $pdo->query("SELECT 
            MIN(umur_bulan) as min_umur, MAX(umur_bulan) as max_umur,
            MIN(tinggi_badan) as min_tb, MAX(tinggi_badan) as max_tb
            FROM data_latih");
        $minMax = $stmt_minmax->fetch();

        $selisih_umur = ($minMax['max_umur'] - $minMax['min_umur']) == 0 ? 1 : ($minMax['max_umur'] - $minMax['min_umur']);
        $selisih_tb = ($minMax['max_tb'] - $minMax['min_tb']) == 0 ? 1 : ($minMax['max_tb'] - $minMax['min_tb']);

        // 2. Normalisasi Data Inputan
        $input_umur_norm = ($umur_bulan - $minMax['min_umur']) / $selisih_umur;
        $input_tb_norm = ($tinggi_badan - $minMax['min_tb']) / $selisih_tb;

        // 3. Hitung Jarak Euclidean ke Semua Data Latih
        $stmt_data = $pdo->query("SELECT id, jenis_kelamin, umur_bulan, tinggi_badan, status_gizi FROM data_latih");
        $hasil_jarak = [];

        while ($latih = $stmt_data->fetch()) {
            $latih_umur_norm = ($latih['umur_bulan'] - $minMax['min_umur']) / $selisih_umur;
            $latih_tb_norm = ($latih['tinggi_badan'] - $minMax['min_tb']) / $selisih_tb;

            $jarak = sqrt(
                pow(($jk - $latih['jenis_kelamin']), 2) + 
                pow(($input_umur_norm - $latih_umur_norm), 2) + 
                pow(($input_tb_norm - $latih_tb_norm), 2)
            );

            $hasil_jarak[] = [
                'id_latih' => $latih['id'],
                'umur' => $latih['umur_bulan'],
                'tb' => $latih['tinggi_badan'],
                'jarak' => $jarak,
                'status_gizi' => $latih['status_gizi']
            ];
        }

        // 4. Urutkan Jarak (Terkecil ke Terbesar)
        usort($hasil_jarak, function($a, $b) {
            return $a['jarak'] <=> $b['jarak'];
        });

        // 5. Ambil K-Tetangga Terdekat
        $tetangga_terdekat = array_slice($hasil_jarak, 0, $k_value);

        // 6. Voting Mayoritas
        $voting = [];
        foreach ($tetangga_terdekat as $tetangga) {
            $status = trim($tetangga['status_gizi']);
            if (isset($voting[$status])) {
                $voting[$status]++;
            } else {
                $voting[$status] = 1;
            }
        }
        arsort($voting);
        $hasil_prediksi = array_key_first($voting);
    }
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<main class="p-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Simulasi & Klasifikasi KNN</h2>
        <p class="text-gray-500 mt-1">Uji coba algoritma K-Nearest Neighbor secara mandiri tanpa menyimpannya ke rekam medis.</p>
    </div>

    <?php if($pesan_error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl mb-6 shadow-sm flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            <span class="font-medium"><?= $pesan_error ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-emerald-100 text-emerald-600 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                        <i class="fa-solid fa-vials"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Parameter Uji</h3>
                </div>
                
                <form action="" method="post">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis Kelamin</label>
                            <select name="jenis_kelamin" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-gray-50 focus:bg-white transition text-sm">
                                <option value="1" <?= isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == '1' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="0" <?= isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == '0' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Umur (Bulan)</label>
                            <div class="relative">
                                <input type="number" step="1" name="umur_bulan" required value="<?= $_POST['umur_bulan'] ?? '' ?>" placeholder="Contoh: 24" class="w-full pl-4 pr-16 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-gray-50 focus:bg-white transition text-sm">
                                <span class="absolute right-4 top-2.5 text-gray-400 font-medium text-sm">Bulan</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tinggi / Panjang Badan</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="tinggi_badan" required value="<?= $_POST['tinggi_badan'] ?? '' ?>" placeholder="Contoh: 85.5" class="w-full pl-4 pr-12 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-gray-50 focus:bg-white transition text-sm">
                                <span class="absolute right-4 top-2.5 text-gray-400 font-medium text-sm">cm</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tentukan Nilai K (Tetangga)</label>
                            <div class="flex items-center gap-3">
                                <input type="range" name="nilai_k" min="1" max="15" step="2" value="<?= $_POST['nilai_k'] ?? 5 ?>" class="w-full accent-emerald-600" oninput="document.getElementById('k_display').innerText = this.value">
                                <span class="bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-lg border border-emerald-200" id="k_display"><?= $_POST['nilai_k'] ?? 5 ?></span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1 italic">Disarankan menggunakan bilangan ganjil (1, 3, 5, 7) untuk menghindari hasil voting seri.</p>
                        </div>
                    </div>
                    
                    <button type="submit" name="simulasi_knn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl transition shadow-md flex justify-center items-center gap-2">
                        <i class="fa-solid fa-microchip"></i> Jalankan Simulasi KNN
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 h-full flex flex-col overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800">Output Algoritma</h3>
                </div>
                
                <div class="p-6 flex-1 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] relative">
                    
                    <?php if(!$hasil_prediksi): ?>
                        <div class="h-full flex flex-col items-center justify-center py-16 text-center">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 border-4 border-white shadow-sm">
                                <i class="fa-solid fa-robot text-4xl text-gray-300"></i>
                            </div>
                            <h4 class="text-xl font-bold text-gray-600">Menunggu Parameter...</h4>
                            <p class="text-gray-400 mt-2 max-w-sm">Silakan masukkan parameter Jenis Kelamin, Umur, dan Tinggi di form sebelah kiri untuk melihat proses kerja K-NN.</p>
                        </div>
                    <?php else: 
                        // Tentukan Tema Warna Berdasarkan Hasil
                        $status_lower = strtolower($hasil_prediksi);
                        $theme = 'emerald'; $icon = 'fa-face-smile';
                        if (strpos($status_lower, 'kurang') !== false || strpos($status_lower, 'underweight') !== false) {
                            $theme = 'yellow'; $icon = 'fa-face-meh';
                        } elseif (strpos($status_lower, 'buruk') !== false || strpos($status_lower, 'stunt') !== false) {
                            $theme = 'red'; $icon = 'fa-face-frown';
                        } elseif (strpos($status_lower, 'lebih') !== false || strpos($status_lower, 'obesitas') !== false) {
                            $theme = 'blue'; $icon = 'fa-face-rolling-eyes';
                        }
                    ?>
                        <div class="animate-fade-in-up">
                            
                            <div class="bg-<?= $theme ?>-50 border-2 border-<?= $theme ?>-200 rounded-2xl p-8 text-center relative overflow-hidden shadow-sm mb-8">
                                <div class="absolute -right-4 -top-4 opacity-10 text-9xl text-<?= $theme ?>-600"><i class="fa-solid <?= $icon ?>"></i></div>
                                
                                <p class="text-sm font-bold text-<?= $theme ?>-600 uppercase tracking-widest mb-2 relative z-10">Kesimpulan Status Gizi</p>
                                <h2 class="text-4xl sm:text-5xl font-black text-<?= $theme ?>-700 uppercase tracking-tight relative z-10">
                                    <?= htmlspecialchars($hasil_prediksi) ?>
                                </h2>
                            </div>

                            <div>
                                <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-people-group text-emerald-600"></i> 
                                    <?= $k_value ?> Tetangga Terdekat (Berdasarkan Euclidean Distance)
                                </h4>
                                
                                <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                                    <table class="w-full text-left text-sm bg-white">
                                        <thead class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                                            <tr>
                                                <th class="py-3 px-4 font-bold border-b">Rank</th>
                                                <th class="py-3 px-4 font-bold border-b text-center">Dataset ID</th>
                                                <th class="py-3 px-4 font-bold border-b text-center">Data Latih (Umur & TB)</th>
                                                <th class="py-3 px-4 font-bold border-b text-center">Jarak</th>
                                                <th class="py-3 px-4 font-bold border-b text-right">Label Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <?php foreach($tetangga_terdekat as $index => $t): ?>
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="py-3 px-4 text-gray-400 font-bold">#<?= $index + 1 ?></td>
                                                <td class="py-3 px-4 text-center font-medium">Dataset-<?= $t['id_latih'] ?></td>
                                                <td class="py-3 px-4 text-center text-gray-500 text-xs">
                                                    <?= $t['umur'] ?> Bln, <?= $t['tb'] ?> cm
                                                </td>
                                                <td class="py-3 px-4 text-center font-mono text-emerald-600 font-semibold bg-emerald-50/50">
                                                    <?= number_format($t['jarak'], 6) ?>
                                                </td>
                                                <td class="py-3 px-4 text-right">
                                                    <span class="bg-gray-100 border border-gray-200 text-gray-700 px-2.5 py-1 rounded-md text-xs font-bold uppercase">
                                                        <?= htmlspecialchars($t['status_gizi']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-gray-400 mt-3"><i class="fa-solid fa-circle-info mr-1"></i> Data di atas adalah <?= $k_value ?> data historis paling mirip dengan inputan simulasi, diurutkan dari jarak terdekat (terkecil).</p>
                            </div>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<style>
    /* Custom Animasi untuk Hasil Muncul */
    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
</style>

        </div> </div>
</body>
</html>
<?php
session_start();
require_once '../database/config.php';

// Cek akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';

// 1. PROSES IMPORT CSV (Dioptimasi untuk Ratusan Ribu Data)
if (isset($_POST['import'])) {
    // Matikan batas waktu eksekusi PHP agar tidak timeout saat upload 121k baris
    set_time_limit(0); 
    
    $fileName = $_FILES["file_csv"]["tmp_name"];
    
    if ($_FILES["file_csv"]["size"] > 0) {
        $file = fopen($fileName, "r");
        fgetcsv($file, 10000, ","); // Lewati Header
        
        $berhasil = 0;
        
        // Memulai transaksi database (SANGAT PENTING agar import data masal jauh lebih cepat)
        $pdo->beginTransaction();
        $sql = "INSERT INTO data_latih (jenis_kelamin, umur_bulan, berat_badan, tinggi_badan, status_gizi) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            if (count($column) >= 4) {
                $umur = $column[0];
                $jk_teks = strtolower(trim($column[1]));
                $jk = ($jk_teks == 'laki-laki' || $jk_teks == 'l' || $jk_teks == '1') ? 1 : 0;
                $tb = $column[2];
                $status = trim($column[3]);
                $bb = 0; 

                $stmt->execute([$jk, $umur, $bb, $tb, $status]);
                $berhasil++;
                
                // Commit data per 5.000 baris agar RAM laptop/server tidak penuh
                if ($berhasil % 5000 == 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }
        }
        
        // Commit sisa data yang belum dieksekusi
        $pdo->commit();
        fclose($file);

        $pesan = "<div class='bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-4'>
                    <div class='bg-emerald-500 text-white p-2 rounded-full'><i class='fa-solid fa-check'></i></div>
                    <div>
                        <h4 class='font-bold'>Import Berhasil!</h4>
                        <p class='text-sm mt-1'>Sebanyak <strong>" . number_format($berhasil, 0, ',', '.') . "</strong> baris data latih berhasil dimasukkan ke database.</p>
                    </div>
                  </div>";
    }
}

// 2. PROSES HAPUS SEMUA DATA LATIH
if (isset($_POST['kosongkan'])) {
    $pdo->query("TRUNCATE TABLE data_latih");
    $pesan = "<div class='bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-4'>
                <div class='bg-red-500 text-white p-2 rounded-full'><i class='fa-solid fa-trash'></i></div>
                <div>
                    <h4 class='font-bold'>Dataset Dikosongkan</h4>
                    <p class='text-sm mt-1'>Seluruh data latih KNN telah dihapus dari sistem.</p>
                </div>
              </div>";
}

// 3. PAGINATION LOGIC
$limit = 50; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total keseluruhan data
$stmt_total = $pdo->query("SELECT COUNT(*) FROM data_latih");
$total_data = $stmt_total->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Ambil data untuk halaman yang sedang aktif
$stmt_tampil = $pdo->prepare("SELECT * FROM data_latih ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt_tampil->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_tampil->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_tampil->execute();
$data_latih = $stmt_tampil->fetchAll();

include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<div id="loadingOverlay" class="fixed inset-0 bg-gray-900/80 z-[100] hidden flex-col items-center justify-center backdrop-blur-sm transition-all duration-300">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-emerald-500 mb-4"></div>
    <h2 class="text-white text-2xl font-bold tracking-wider">Memproses Dataset...</h2>
    <p class="text-emerald-200 mt-3 text-sm text-center max-w-md bg-emerald-900/50 p-4 rounded-lg">
        Sistem sedang membaca dan memasukkan ratusan ribu baris data. <br>
        <span class="text-yellow-300 font-semibold mt-2 block"><i class="fa-solid fa-triangle-exclamation"></i> Mohon jangan tutup atau refresh halaman ini!</span>
    </p>
</div>

<main class="p-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Data Latih KNN</h2>
        <p class="text-gray-500 mt-1">Total Dataset Tersedia: <span class="font-bold text-emerald-600 bg-emerald-100 px-2 py-1 rounded-md"><?= number_format($total_data, 0, ',', '.') ?> Baris</span></p>
    </div>

    <?= $pesan ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-emerald-100 text-emerald-600 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Upload CSV Baru</h3>
                </div>
                
                <form action="" method="post" enctype="multipart/form-data" onsubmit="showLoading()">
                    <div class="mb-5">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:bg-gray-50 transition cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                            <input type="file" name="file_csv" accept=".csv" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                        </div>
                        <div class="mt-4 bg-blue-50/50 p-4 rounded-xl border border-blue-100 text-xs text-gray-600 leading-relaxed">
                            <strong class="text-blue-800"><i class="fa-solid fa-circle-info mr-1"></i> Format Kolom:</strong><br>
                            1. Umur (bulan)<br>
                            2. Jenis Kelamin<br>
                            3. Tinggi Badan (cm)<br>
                            4. Status Gizi (Label)
                        </div>
                    </div>
                    <button type="submit" name="import" class="w-full bg-emerald-600 text-white py-3 rounded-xl hover:bg-emerald-700 hover:shadow-lg transition duration-200 font-bold flex justify-center items-center gap-2">
                        <i class="fa-solid fa-database"></i> Mulai Proses Import
                    </button>
                </form>

                <div class="my-6 border-t border-gray-100"></div>
                
                <form action="" method="post" onsubmit="return confirm('PERINGATAN: Yakin mereset algoritma KNN dengan menghapus semua dataset ini?');">
                    <button type="submit" name="kosongkan" class="w-full bg-white text-red-500 border-2 border-red-100 py-2.5 rounded-xl hover:bg-red-50 hover:border-red-200 transition duration-200 font-semibold text-sm flex justify-center items-center gap-2">
                        <i class="fa-solid fa-trash-can"></i> Kosongkan Dataset
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                    <h3 class="font-bold text-gray-800">Preview Data Latih</h3>
                    <span class="text-xs font-semibold bg-gray-200 text-gray-600 px-3 py-1 rounded-full">Hal. <?= $page ?> dari <?= number_format($total_pages, 0, ',', '.') ?></span>
                </div>
                
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="py-4 px-6 border-b font-semibold">ID</th>
                                <th class="py-4 px-6 border-b font-semibold">Kelamin</th>
                                <th class="py-4 px-6 border-b font-semibold">Umur</th>
                                <th class="py-4 px-6 border-b font-semibold">Tinggi</th>
                                <th class="py-4 px-6 border-b font-semibold">Label Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach($data_latih as $row): ?>
                            <tr class="hover:bg-emerald-50/40 transition duration-150">
                                <td class="py-3 px-6 text-gray-400 font-medium">#<?= $row['id'] ?></td>
                                <td class="py-3 px-6">
                                    <?php if($row['jenis_kelamin'] == 1): ?>
                                        <span class="text-blue-600 bg-blue-50 px-2 py-1 rounded-md"><i class="fa-solid fa-mars mr-1"></i> Laki-laki</span>
                                    <?php else: ?>
                                        <span class="text-pink-600 bg-pink-50 px-2 py-1 rounded-md"><i class="fa-solid fa-venus mr-1"></i> Perempuan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6"><span class="font-medium text-gray-700"><?= $row['umur_bulan'] ?></span> bln</td>
                                <td class="py-3 px-6"><span class="font-medium text-gray-700"><?= $row['tinggi_badan'] ?></span> cm</td>
                                <td class="py-3 px-6">
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold uppercase tracking-wide">
                                        <?= htmlspecialchars($row['status_gizi']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($data_latih)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-16">
                                    <div class="text-gray-200 mb-4"><i class="fa-solid fa-database text-6xl"></i></div>
                                    <p class="text-gray-500 font-semibold text-lg">Dataset Kosong</p>
                                    <p class="text-sm text-gray-400 mt-1">Silakan upload file CSV di samping untuk melatih algoritma KNN.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="p-4 border-t border-gray-100 bg-white flex items-center justify-between">
                    <p class="text-sm text-gray-500">Menampilkan halaman <span class="font-bold text-gray-700"><?= $page ?></span> dari <span class="font-bold text-gray-700"><?= number_format($total_pages, 0, ',', '.') ?></span></p>
                    
                    <div class="flex items-center gap-1">
                        <?php if($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition"><i class="fa-solid fa-chevron-left"></i> Prev</a>
                        <?php endif; ?>

                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition">Next <i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

        </div> </div> <script>
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }
    </script>
</body>
</html>
<?php
session_start();
require_once '../database/config.php';

// Keamanan: Cek apakah yang akses benar-benar admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// =========================================================
// 1. MENGAMBIL DATA STATISTIK UTAMA DARI DATABASE
// =========================================================

// Total Balita Terdaftar
$total_balita = $pdo->query("SELECT COUNT(*) FROM data_balita")->fetchColumn();

// Total Pemeriksaan
$total_pemeriksaan = $pdo->query("SELECT COUNT(*) FROM riwayat_klasifikasi")->fetchColumn();

// Menghitung Jumlah per Kategori Status Gizi
$stmt_status = $pdo->query("
    SELECT 
        SUM(CASE WHEN LOWER(hasil_klasifikasi) LIKE '%normal%' OR LOWER(hasil_klasifikasi) LIKE '%baik%' THEN 1 ELSE 0 END) as normal,
        SUM(CASE WHEN LOWER(hasil_klasifikasi) LIKE '%kurang%' OR LOWER(hasil_klasifikasi) LIKE '%underweight%' THEN 1 ELSE 0 END) as kurang,
        SUM(CASE WHEN LOWER(hasil_klasifikasi) LIKE '%buruk%' OR LOWER(hasil_klasifikasi) LIKE '%stunt%' THEN 1 ELSE 0 END) as buruk,
        SUM(CASE WHEN LOWER(hasil_klasifikasi) LIKE '%lebih%' OR LOWER(hasil_klasifikasi) LIKE '%obesitas%' THEN 1 ELSE 0 END) as lebih
    FROM riwayat_klasifikasi
");
$status_counts = $stmt_status->fetch(PDO::FETCH_ASSOC);

$jml_normal = $status_counts['normal'] ?? 0;
$jml_kurang = $status_counts['kurang'] ?? 0;
$jml_buruk  = $status_counts['buruk'] ?? 0;
$jml_lebih  = $status_counts['lebih'] ?? 0;


// =========================================================
// 2. MENGAMBIL DATA PREVALENSI KABUPATEN (REAL DATA)
// =========================================================

// Daftar 15 Kabupaten/Kota standar Provinsi Lampung
$list_kabupaten = [
    'Kota Bandar Lampung', 'Kota Metro', 'Kab. Lampung Barat', 'Kab. Tanggamus', 
    'Kab. Lampung Selatan', 'Kab. Lampung Timur', 'Kab. Lampung Tengah', 'Kab. Lampung Utara', 
    'Kab. Way Kanan', 'Kab. Tulang Bawang', 'Kab. Pesawaran', 'Kab. Pringsewu', 
    'Kab. Mesuji', 'Kab. Tulang Bawang Barat', 'Kab. Pesisir Barat'
];

// Query untuk menghitung total pemeriksaan dan total stunting per kabupaten
$sql_kab = "
    SELECT 
        b.kabupaten,
        COUNT(rk.id) as total_periksa,
        SUM(CASE WHEN LOWER(rk.hasil_klasifikasi) LIKE '%buruk%' OR LOWER(rk.hasil_klasifikasi) LIKE '%stunt%' THEN 1 ELSE 0 END) as total_stunting
    FROM riwayat_klasifikasi rk
    JOIN data_balita b ON rk.id_balita = b.id
    WHERE b.kabupaten IS NOT NULL AND b.kabupaten != '' AND b.kabupaten != 'Belum diisi'
    GROUP BY b.kabupaten
";
$stmt_kab = $pdo->query($sql_kab);
$data_db_kab = $stmt_kab->fetchAll(PDO::FETCH_ASSOC);

// Ubah ke format array [nama_kabupaten => [periksa, stunting]] agar mudah diolah
$kab_stats = [];
foreach($data_db_kab as $row) {
    $kab_stats[$row['kabupaten']] = [
        'periksa' => $row['total_periksa'],
        'stunting' => $row['total_stunting']
    ];
}

// Siapkan array data persentase untuk dilempar ke Grafik ApexCharts
$data_persentase_kab = [];
foreach($list_kabupaten as $kab) {
    // Jika ada datanya dan total periksa lebih dari 0
    if(isset($kab_stats[$kab]) && $kab_stats[$kab]['periksa'] > 0) {
        $persen = ($kab_stats[$kab]['stunting'] / $kab_stats[$kab]['periksa']) * 100;
        $data_persentase_kab[] = round($persen, 1); // Bulatkan 1 angka di belakang koma (misal: 14.5)
    } else {
        $data_persentase_kab[] = 0; // Jika belum ada pasien dari kabupaten ini, persentase 0%
    }
}


// =========================================================
// 3. MENGAMBIL LOG AKTIVITAS TERAKHIR
// =========================================================
$stmt_recent = $pdo->query("
    SELECT rk.*, b.nama_balita, u.nama_lengkap as nama_bidan
    FROM riwayat_klasifikasi rk 
    JOIN data_balita b ON rk.id_balita = b.id 
    JOIN users u ON rk.id_user = u.id 
    ORDER BY rk.tanggal_ukur DESC, rk.id DESC 
    LIMIT 5
");
$recent_activities = $stmt_recent->fetchAll();

// Include Header dan Sidebar
include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<main class="p-6 bg-gray-50/50 min-h-screen">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Dashboard KPI Utama</h2>
            <p class="text-gray-500 mt-1 font-medium">Ringkasan analitik klasifikasi status gizi balita Provinsi Lampung.</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="bg-emerald-100 p-2 rounded-lg text-emerald-600"><i class="fa-solid fa-calendar-day"></i></div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Tanggal Hari Ini</p>
                <p class="text-sm font-bold text-gray-700"><?= date('d F Y') ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute -right-4 -top-4 bg-blue-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Total Balita Terdaftar</p>
                    <h3 class="text-4xl font-black text-gray-800"><?= number_format($total_balita) ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-children"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute -right-4 -top-4 bg-emerald-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Gizi Baik / Normal</p>
                    <h3 class="text-4xl font-black text-gray-800"><?= number_format($jml_normal) ?></h3>
                </div>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-face-smile"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute -right-4 -top-4 bg-yellow-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Gizi Kurang</p>
                    <h3 class="text-4xl font-black text-gray-800"><?= number_format($jml_kurang) ?></h3>
                </div>
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-face-meh"></i></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute -right-4 -top-4 bg-red-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Gizi Buruk & Stunting</p>
                    <h3 class="text-4xl font-black text-gray-800"><?= number_format($jml_buruk) ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-face-frown"></i></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Distribusi Status Gizi</h3>
            <div id="statusChart" class="flex justify-center"></div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Komparasi Gizi Balita (Klasifikasi K-NN)</h3>
            <div id="barChart" class="w-full"></div>
        </div>

    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Prevalensi Stunting per Kabupaten/Kota</h3>
                <p class="text-sm text-gray-500">Persentase balita terindikasi gizi buruk berdasarkan data ril di sistem.</p>
            </div>
            <div class="bg-red-50 text-red-600 px-4 py-2 rounded-xl text-xs font-bold border border-red-100 shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-chart-line"></i> Live Data Analysis
            </div>
        </div>
        <div id="prevalensiChart" class="w-full"></div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">5 Pemeriksaan Terakhir</h3>
            <a href="riwayat.php" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-4 px-6 font-semibold">Tanggal</th>
                        <th class="py-4 px-6 font-semibold">Petugas Bidan</th>
                        <th class="py-4 px-6 font-semibold">Nama Balita</th>
                        <th class="py-4 px-6 font-semibold text-center">Hasil K-NN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(empty($recent_activities)): ?>
                        <tr><td colspan="4" class="text-center py-8 text-gray-400">Belum ada aktivitas pemeriksaan.</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_activities as $ra): 
                            // Warna Badge
                            $status = strtolower($ra['hasil_klasifikasi']);
                            $badge = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            if (strpos($status, 'kurang') !== false) $badge = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            elseif (strpos($status, 'buruk') !== false || strpos($status, 'stunt') !== false) $badge = 'bg-red-100 text-red-700 border-red-200';
                            elseif (strpos($status, 'lebih') !== false) $badge = 'bg-blue-100 text-blue-700 border-blue-200';
                        ?>
                        <tr class="hover:bg-emerald-50/30 transition">
                            <td class="py-4 px-6 font-medium text-gray-700"><?= date('d M Y', strtotime($ra['tanggal_ukur'])) ?></td>
                            <td class="py-4 px-6 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs"><i class="fa-solid fa-user-nurse"></i></div>
                                <?= htmlspecialchars($ra['nama_bidan']) ?>
                            </td>
                            <td class="py-4 px-6 font-bold text-gray-800"><?= htmlspecialchars($ra['nama_balita']) ?></td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider border <?= $badge ?>">
                                    <?= htmlspecialchars($ra['hasil_klasifikasi']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
    // 1. Konfigurasi Donut Chart
    var donutOptions = {
        series: [<?= $jml_normal ?>, <?= $jml_kurang ?>, <?= $jml_buruk ?>, <?= $jml_lebih ?>],
        labels: ['Normal / Baik', 'Gizi Kurang', 'Gizi Buruk', 'Gizi Lebih'],
        chart: {
            type: 'donut',
            height: 320,
            fontFamily: 'Poppins, sans-serif'
        },
        colors: ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: { fontSize: '14px', fontWeight: 600 },
                        value: { fontSize: '24px', fontWeight: 800, color: '#333' },
                        total: { show: true, label: 'Total Data', fontSize: '16px', fontWeight: 700 }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        stroke: { width: 0 },
        legend: { position: 'bottom', markers: { radius: 12 } }
    };
    var donutChart = new ApexCharts(document.querySelector("#statusChart"), donutOptions);
    donutChart.render();

    // 2. Konfigurasi Bar Chart (Vertikal)
    var barOptions = {
        series: [{
            name: 'Jumlah Balita',
            data: [<?= $jml_normal ?>, <?= $jml_kurang ?>, <?= $jml_buruk ?>, <?= $jml_lebih ?>]
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Poppins, sans-serif'
        },
        colors: ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'],
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true, 
            }
        },
        dataLabels: {
            enabled: true,
            style: { fontSize: '14px', fontWeight: 700, colors: ['#fff'] }
        },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: ['Gizi Normal', 'Gizi Kurang', 'Gizi Buruk', 'Gizi Lebih'],
            labels: { style: { fontSize: '13px', fontWeight: 600 } }
        },
        yaxis: { title: { text: 'Jumlah Kasus' } },
        legend: { show: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4, yaxis: { lines: { show: true } } }
    };
    var barChart = new ApexCharts(document.querySelector("#barChart"), barOptions);
    barChart.render();

    // 3. Konfigurasi Horizontal Bar Chart (Prevalensi Kabupaten Lampung - REAL DATA)
    var arrKabupaten = <?= json_encode($list_kabupaten) ?>;
    var arrPersentase = <?= json_encode($data_persentase_kab) ?>;

    var prevalensiOptions = {
        series: [{
            name: 'Prevalensi Stunting',
            data: arrPersentase // Data langsung dari PHP (Database)
        }],
        chart: {
            type: 'bar',
            height: 480,
            fontFamily: 'Poppins, sans-serif',
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                dataLabels: { position: 'top' }, 
            }
        },
        colors: ['#ef4444'], 
        dataLabels: {
            enabled: true,
            offsetX: 20,
            style: { fontSize: '12px', colors: ['#333'] },
            formatter: function (val) { return val + "%"; }
        },
        stroke: { show: true, width: 1, colors: ['#fff'] },
        xaxis: {
            categories: arrKabupaten, // Nama Kabupaten dari PHP
            labels: { formatter: function (val) { return val + "%"; } }
        },
        yaxis: { labels: { style: { fontSize: '12px', fontWeight: 500 } } },
        tooltip: {
            y: { formatter: function (val) { return val + "% Kasus Gizi Buruk/Stunting"; } }
        }
    };
    var prevalensiChart = new ApexCharts(document.querySelector("#prevalensiChart"), prevalensiOptions);
    prevalensiChart.render();
</script>

        </div> 
    </div> 
</body>
</html>
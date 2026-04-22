<?php
session_start();
require_once '../database/config.php';

// =================================================================================
// 1. BLOK AJAX (Hanya dieksekusi jika ada request dari jQuery untuk filter/pagination)
// =================================================================================
if (isset($_GET['action']) && $_GET['action'] == 'fetch_data') {
    $limit = 10; // Tampilkan 10 data per halaman
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

    $whereSQL = "";
    $params = [];

    // Jika ada filter pencarian
    if (!empty($keyword)) {
        $whereSQL = " WHERE nama_balita LIKE ? OR nik LIKE ? OR nama_ortu LIKE ?";
        $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
    }

    // Hitung total data untuk Pagination
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM data_balita" . $whereSQL);
    $stmt_total->execute($params);
    $total_data = $stmt_total->fetchColumn();
    $total_pages = ceil($total_data / $limit);

    // Ambil data sesuai filter dan limit
    $sql = "SELECT * FROM data_balita" . $whereSQL . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data_balita = $stmt->fetchAll();

    $html = "";
    if (count($data_balita) > 0) {
        foreach ($data_balita as $row) {
            // Hitung umur otomatis dari tanggal lahir
            $tgl_lahir = new DateTime($row['tanggal_lahir']);
            $today = new DateTime('today');
            $umur_bulan = ($today->diff($tgl_lahir)->y * 12) + $today->diff($tgl_lahir)->m;

            $jk_badge = $row['jenis_kelamin'] == 1 
                ? "<span class='text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs font-medium'><i class='fa-solid fa-mars'></i> L</span>" 
                : "<span class='text-pink-600 bg-pink-50 px-2 py-1 rounded text-xs font-medium'><i class='fa-solid fa-venus'></i> P</span>";

            $html .= "<tr class='border-b border-gray-50 hover:bg-emerald-50/50 transition duration-150'>
                        <td class='py-3 px-5 text-gray-500'>#{$row['id']}</td>
                        <td class='py-3 px-5 font-semibold text-gray-700'>{$row['nik']}</td>
                        <td class='py-3 px-5 font-bold text-emerald-700'>{$row['nama_balita']}</td>
                        <td class='py-3 px-5'>{$row['nama_ortu']}</td>
                        <td class='py-3 px-5'>{$jk_badge}</td>
                        <td class='py-3 px-5'>
                            <div class='text-sm text-gray-800'>".date('d-m-Y', strtotime($row['tanggal_lahir']))."</div>
                            <div class='text-xs text-gray-400'>({$umur_bulan} bln)</div>
                        </td>
                        <td class='py-3 px-5 text-center'>
                            <div class='flex items-center justify-center gap-2'>
                                <button onclick='editData(".json_encode($row).")' class='bg-amber-100 text-amber-600 hover:bg-amber-500 hover:text-white w-8 h-8 rounded-lg transition tooltip flex items-center justify-center' title='Edit'><i class='fa-solid fa-pen'></i></button>
                                <form method='POST' action='' onsubmit='return confirm(\"Yakin ingin menghapus data balita ini? Riwayat pemeriksaannya juga akan terhapus.\");' class='inline-block'>
                                    <input type='hidden' name='id_hapus' value='{$row['id']}'>
                                    <button type='submit' name='delete_balita' class='bg-red-100 text-red-600 hover:bg-red-500 hover:text-white w-8 h-8 rounded-lg transition tooltip flex items-center justify-center' title='Hapus'><i class='fa-solid fa-trash'></i></button>
                                </form>
                            </div>
                        </td>
                      </tr>";
        }
    } else {
        $html .= "<tr><td colspan='7' class='text-center py-12 text-gray-400'>
                    <i class='fa-solid fa-folder-open text-4xl mb-3'></i><br>
                    Data balita tidak ditemukan.
                  </td></tr>";
    }

    // Buat Tombol Pagination (HTML)
    $paginationHtml = "";
    if ($total_pages > 1) {
        for ($i = 1; $i <= $total_pages; $i++) {
            $activeClass = ($i == $page) ? "bg-emerald-600 text-white border-emerald-600" : "bg-white text-gray-600 border-gray-200 hover:bg-emerald-50";
            $paginationHtml .= "<button onclick='loadData($i)' class='px-3 py-1.5 border rounded-lg text-sm font-medium transition $activeClass'>$i</button>";
        }
    }

    // Kirim balasan ke jQuery dalam bentuk JSON
    echo json_encode(['table_html' => $html, 'pagination_html' => $paginationHtml, 'total' => $total_data]);
    exit;
}

// =================================================================================
// 2. BLOK PHP UTAMA (Keamanan, Tambah, Edit, Hapus Data)
// =================================================================================

// Cek akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';

// Proses Hapus
if (isset($_POST['delete_balita'])) {
    $id = $_POST['id_hapus'];
    $stmt = $pdo->prepare("DELETE FROM data_balita WHERE id = ?");
    if($stmt->execute([$id])) {
        $pesan = "<div class='bg-emerald-100 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm border border-emerald-200'>Data balita berhasil dihapus.</div>";
    }
}

// Proses Tambah Data
if (isset($_POST['add_balita'])) {
    $nik = trim($_POST['nik']);
    $nama = trim($_POST['nama_balita']);
    $ortu = trim($_POST['nama_ortu']);
    $jk = $_POST['jenis_kelamin'];
    $tgl = $_POST['tanggal_lahir'];
    $alamat = trim($_POST['alamat']);

    $stmt = $pdo->prepare("INSERT INTO data_balita (nik, nama_balita, nama_ortu, jenis_kelamin, tanggal_lahir, alamat) VALUES (?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$nik, $nama, $ortu, $jk, $tgl, $alamat])) {
        $pesan = "<div class='bg-emerald-100 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm border border-emerald-200'>Berhasil! Data balita baru telah ditambahkan.</div>";
    }
}

// Proses Update/Edit Data
if (isset($_POST['update_balita'])) {
    $id = $_POST['id_balita'];
    $nik = trim($_POST['nik']);
    $nama = trim($_POST['nama_balita']);
    $ortu = trim($_POST['nama_ortu']);
    $jk = $_POST['jenis_kelamin'];
    $tgl = $_POST['tanggal_lahir'];
    $alamat = trim($_POST['alamat']);

    $stmt = $pdo->prepare("UPDATE data_balita SET nik=?, nama_balita=?, nama_ortu=?, jenis_kelamin=?, tanggal_lahir=?, alamat=? WHERE id=?");
    if($stmt->execute([$nik, $nama, $ortu, $jk, $tgl, $alamat, $id])) {
        $pesan = "<div class='bg-blue-100 text-blue-800 p-4 rounded-xl mb-6 shadow-sm border border-blue-200'>Data balita berhasil diperbarui.</div>";
    }
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<main class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Data Balita</h2>
            <p class="text-gray-500 mt-1">Kelola data induk pasien balita yang terdaftar di posyandu.</p>
        </div>
        <button onclick="openModal('modalAdd')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Tambah Balita Baru
        </button>
    </div>

    <?= $pesan ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
        
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="w-full md:w-1/3 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                </div>
                <input type="text" id="searchInput" onkeyup="searchData()" placeholder="Cari NIK, Nama Balita / Orang Tua..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition text-sm">
            </div>
            <div class="text-sm font-semibold text-gray-500 bg-white px-4 py-2 rounded-lg border border-gray-200">
                Total Data: <span id="totalDataLabel" class="text-emerald-600">0</span>
            </div>
        </div>
        
        <div class="overflow-x-auto flex-1 min-h-[400px]">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-emerald-800 text-emerald-50 uppercase tracking-wider text-xs">
                    <tr>
                        <th class="py-4 px-5 font-semibold rounded-tl-lg">ID</th>
                        <th class="py-4 px-5 font-semibold">NIK</th>
                        <th class="py-4 px-5 font-semibold">Nama Balita</th>
                        <th class="py-4 px-5 font-semibold">Orang Tua</th>
                        <th class="py-4 px-5 font-semibold">L/P</th>
                        <th class="py-4 px-5 font-semibold">Tgl Lahir & Umur</th>
                        <th class="py-4 px-5 font-semibold text-center rounded-tr-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-gray-100">
                    <tr><td colspan="7" class="text-center py-10"><i class="fa-solid fa-spinner fa-spin text-emerald-500 text-3xl"></i> Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="p-5 border-t border-gray-100 bg-gray-50/30 flex items-center justify-center gap-2" id="paginationControls">
            </div>
    </div>
</main>

<div id="modalAdd" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 overflow-hidden shadow-2xl transform scale-95 transition-transform" id="modalAddContent">
        <div class="bg-emerald-700 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-user-plus mr-2"></i> Tambah Data Balita</h3>
            <button onclick="closeModal('modalAdd')" class="text-emerald-200 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="" method="POST" class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                    <input type="text" name="nik" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Balita</label>
                    <input type="text" name="nama_balita" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_ortu" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500 bg-white">
                        <option value="1">Laki-laki</option>
                        <option value="0">Perempuan</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Domisili</label>
                    <textarea name="alamat" rows="2" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('modalAdd')" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 font-medium">Batal</button>
                <button type="submit" name="add_balita" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 overflow-hidden shadow-2xl transform scale-95 transition-transform" id="modalEditContent">
        <div class="bg-amber-500 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Data Balita</h3>
            <button onclick="closeModal('modalEdit')" class="text-amber-100 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="id_balita" id="edit_id">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                    <input type="text" name="nik" id="edit_nik" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Balita</label>
                    <input type="text" name="nama_balita" id="edit_nama" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_ortu" id="edit_ortu" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="edit_jk" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500 bg-white">
                        <option value="1">Laki-laki</option>
                        <option value="0">Perempuan</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="edit_tgl" required class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Domisili</label>
                    <textarea name="alamat" id="edit_alamat" rows="2" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-amber-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 font-medium">Batal</button>
                <button type="submit" name="update_balita" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentPage = 1;

    // Fungsi Utama AJAX (Mengambil data dari file ini sendiri secara diam-diam)
    function loadData(page = 1) {
        currentPage = page;
        let keyword = $('#searchInput').val(); // Ambil apa yang diketik di kotak pencarian

        // Munculkan efek loading di tabel
        $('#tableBody').html('<tr><td colspan="7" class="text-center py-10"><i class="fa-solid fa-spinner fa-spin text-emerald-500 text-3xl"></i> Memuat data...</td></tr>');

        $.ajax({
            url: 'data_balita.php', // Panggil file ini sendiri
            type: 'GET',
            data: { 
                action: 'fetch_data', 
                page: page, 
                keyword: keyword 
            },
            dataType: 'json',
            success: function(response) {
                // Tempelkan HTML hasil respons ke dalam tabel dan pagination tanpa reload!
                $('#tableBody').html(response.table_html);
                $('#paginationControls').html(response.pagination_html);
                $('#totalDataLabel').text(response.total);
            },
            error: function() {
                $('#tableBody').html('<tr><td colspan="7" class="text-center py-10 text-red-500">Gagal memuat data. Periksa koneksi internet Anda.</td></tr>');
            }
        });
    }

    // Fungsi saat mengetik di pencarian (Debounce agar tidak lemot)
    let searchTimeout;
    function searchData() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadData(1); // Kembali ke halaman 1 saat mencari
        }, 400); // Tunggu 400ms setelah user berhenti ngetik
    }

    // Panggil data pertama kali saat halaman selesai dimuat
    $(document).ready(function() {
        loadData();
    });

    // === Fungsi Kontrol Modal Tambah/Edit ===
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(modalId + 'Content');
        modal.classList.remove('hidden');
        setTimeout(() => content.classList.remove('scale-95'), 10);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(modalId + 'Content');
        content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // Fungsi khusus untuk tombol Edit (Memasukkan data dari tabel ke form modal)
    function editData(data) {
        $('#edit_id').val(data.id);
        $('#edit_nik').val(data.nik);
        $('#edit_nama').val(data.nama_balita);
        $('#edit_ortu').val(data.nama_ortu);
        $('#edit_jk').val(data.jenis_kelamin);
        $('#edit_tgl').val(data.tanggal_lahir);
        $('#edit_alamat').val(data.alamat);
        openModal('modalEdit');
    }
</script>

        </div> </div> </body>
</html>
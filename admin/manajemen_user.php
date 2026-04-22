<?php
session_start();
require_once '../database/config.php';

// Cek akses admin (Hanya Super Admin yang boleh kelola user)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';
$upload_dir = '../uploads/profiles/';

// ==========================================
// PROSES CRUD (TAMBAH, EDIT, HAPUS)
// ==========================================

// 1. PROSES TAMBAH USER
if (isset($_POST['add_user'])) {
    $nama = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $foto = 'default.png';

    // Cek apakah username sudah ada
    $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_cek->execute([$username]);
    
    if ($stmt_cek->rowCount() > 0) {
        $pesan = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 shadow-sm border border-red-200'>Username sudah digunakan! Gunakan username lain.</div>";
    } else {
        // Proses Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto);
        }

        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, password, role, foto) VALUES (?, ?, ?, ?, ?)");
        if($stmt->execute([$nama, $username, $password, $role, $foto])) {
            $pesan = "<div class='bg-emerald-100 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm border border-emerald-200'>Berhasil menambahkan pengguna baru!</div>";
        }
    }
}

// 2. PROSES EDIT USER
if (isset($_POST['edit_user'])) {
    $id = $_POST['id_user'];
    $nama = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $foto_lama = $_POST['foto_lama'];
    
    // Siapkan query dasar
    $sql = "UPDATE users SET nama_lengkap=?, username=?, role=?";
    $params = [$nama, $username, $role];

    // Jika password diisi (ingin diganti)
    if (!empty($_POST['password'])) {
        $sql .= ", password=?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    // Jika foto baru diupload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_baru = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_baru);
        
        $sql .= ", foto=?";
        $params[] = $foto_baru;

        // Hapus foto lama jika bukan default
        if ($foto_lama != 'default.png' && file_exists($upload_dir . $foto_lama)) {
            unlink($upload_dir . $foto_lama);
        }
    }

    $sql .= " WHERE id=?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    if($stmt->execute($params)) {
        // Jika yang diedit adalah diri sendiri, update session nama
        if ($id == $_SESSION['user_id']) {
            $_SESSION['nama_lengkap'] = $nama;
            if (isset($foto_baru)) $_SESSION['foto'] = $foto_baru;
        }
        $pesan = "<div class='bg-blue-100 text-blue-800 p-4 rounded-xl mb-6 shadow-sm border border-blue-200'>Data pengguna berhasil diperbarui!</div>";
    }
}

// 3. PROSES HAPUS USER
if (isset($_POST['delete_user'])) {
    $id = $_POST['id_hapus'];
    
    // Jangan izinkan admin menghapus dirinya sendiri
    if ($id == $_SESSION['user_id']) {
        $pesan = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 shadow-sm border border-red-200'>Anda tidak dapat menghapus akun Anda sendiri!</div>";
    } else {
        // Ambil nama foto untuk dihapus dari folder
        $stmt_foto = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
        $stmt_foto->execute([$id]);
        $data_hapus = $stmt_foto->fetch();

        if ($data_hapus['foto'] != 'default.png' && file_exists($upload_dir . $data_hapus['foto'])) {
            unlink($upload_dir . $data_hapus['foto']);
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if($stmt->execute([$id])) {
            $pesan = "<div class='bg-emerald-100 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm border border-emerald-200'>Akun pengguna berhasil dihapus.</div>";
        }
    }
}

// Ambil semua data user
$stmt_users = $pdo->query("SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
$users = $stmt_users->fetchAll();

include 'layouts/header.php';
include 'layouts/sidebar.php';
?>

<main class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h2>
            <p class="text-gray-500 mt-1">Kelola hak akses Admin dan Bidan (Petugas Posyandu).</p>
        </div>
        <button onclick="openModal('modalAdd')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-md transition-all flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
        </button>
    </div>

    <?= $pesan ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/80 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-4 px-6 font-semibold">Profil</th>
                        <th class="py-4 px-6 font-semibold">Nama Lengkap</th>
                        <th class="py-4 px-6 font-semibold">Username</th>
                        <th class="py-4 px-6 font-semibold text-center">Hak Akses (Role)</th>
                        <th class="py-4 px-6 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($users as $u): 
                        // Cek apakah punya foto, jika tidak pakai API Ui-Avatars agar terlihat inisial namanya
                        $foto_url = ($u['foto'] && $u['foto'] != 'default.png') ? '../uploads/profiles/'.$u['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($u['nama_lengkap']).'&background=10b981&color=fff';
                    ?>
                    <tr class="hover:bg-emerald-50/40 transition">
                        <td class="py-3 px-6">
                            <img src="<?= $foto_url ?>" alt="Foto" class="w-12 h-12 rounded-full object-cover border-2 border-emerald-100 shadow-sm">
                        </td>
                        <td class="py-3 px-6 font-bold text-gray-800">
                            <?= htmlspecialchars($u['nama_lengkap']) ?>
                            <?php if($u['id'] == $_SESSION['user_id']) echo "<span class='ml-2 text-[10px] bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full'>Anda</span>"; ?>
                        </td>
                        <td class="py-3 px-6 text-gray-500">@<?= htmlspecialchars($u['username']) ?></td>
                        <td class="py-3 px-6 text-center">
                            <?php if($u['role'] == 'admin'): ?>
                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-lg text-xs font-bold uppercase"><i class="fa-solid fa-user-shield mr-1"></i> Admin</span>
                            <?php else: ?>
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold uppercase"><i class="fa-solid fa-user-nurse mr-1"></i> Bidan / User</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick='editUser(<?= json_encode($u) ?>)' class="bg-amber-100 text-amber-600 hover:bg-amber-500 hover:text-white w-8 h-8 rounded-lg transition tooltip flex items-center justify-center"><i class="fa-solid fa-pen"></i></button>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): // Sembunyikan tombol hapus untuk diri sendiri ?>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus akun ini?');" class="inline-block">
                                    <input type="hidden" name="id_hapus" value="<?= $u['id'] ?>">
                                    <button type="submit" name="delete_user" class="bg-red-100 text-red-600 hover:bg-red-500 hover:text-white w-8 h-8 rounded-lg transition tooltip flex items-center justify-center"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="modalAdd" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-md mx-4 overflow-hidden shadow-2xl transform scale-95 transition-transform" id="modalAddContent">
        <div class="bg-emerald-700 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-user-plus mr-2"></i> Tambah Pengguna</h3>
            <button onclick="closeModal('modalAdd')" class="text-emerald-200 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hak Akses (Role)</label>
                    <select name="role" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-emerald-500 focus:bg-white transition">
                        <option value="user">Bidan / Petugas Posyandu</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Profil (Opsional)</label>
                    <input type="file" name="foto" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-8 border-t pt-4">
                <button type="button" onclick="closeModal('modalAdd')" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 font-medium transition">Batal</button>
                <button type="submit" name="add_user" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md transition">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] hidden flex items-center justify-center transition-opacity">
    <div class="bg-white rounded-3xl w-full max-w-md mx-4 overflow-hidden shadow-2xl transform scale-95 transition-transform" id="modalEditContent">
        <div class="bg-amber-500 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Pengguna</h3>
            <button onclick="closeModal('modalEdit')" class="text-amber-100 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="id_user" id="edit_id">
            <input type="hidden" name="foto_lama" id="edit_foto_lama">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-amber-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="edit_username" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-amber-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-xs font-normal text-amber-600">(Kosongkan jika tidak ingin ganti)</span></label>
                    <input type="password" name="password" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-amber-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hak Akses (Role)</label>
                    <select name="role" id="edit_role" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-amber-500 focus:bg-white transition">
                        <option value="user">Bidan / Petugas Posyandu</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ganti Foto Profil <span class="text-xs font-normal text-amber-600">(Opsional)</span></label>
                    <input type="file" name="foto" accept="image/*" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-8 border-t pt-4">
                <button type="button" onclick="closeModal('modalEdit')" class="px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 font-medium transition">Batal</button>
                <button type="submit" name="edit_user" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-md transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
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

    // Memasukkan data user dari tabel ke form modal edit
    function editUser(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nama').value = data.nama_lengkap;
        document.getElementById('edit_username').value = data.username;
        document.getElementById('edit_role').value = data.role;
        document.getElementById('edit_foto_lama').value = data.foto;
        openModal('modalEdit');
    }
</script>

        </div>
    </div>
</body>
</html>
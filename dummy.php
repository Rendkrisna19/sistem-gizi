<?php
require_once 'database/config.php';

try {
    // Mulai transaksi database agar proses insert 50 data jauh lebih cepat
    $pdo->beginTransaction();

    // 1. Ambil 1 ID User (Bidan/Petugas) acak dari database untuk dijadikan pemeriksa
    // Jika belum ada user bidan, kita ambil id=1 (admin) sebagai default sementara
    $stmt_user = $pdo->query("SELECT id FROM users LIMIT 1");
    $user_id = $stmt_user->fetchColumn();

    if (!$user_id) {
        die("Error: Anda belum memiliki akun User/Admin satupun di database.");
    }

    // 2. Kumpulan Nama Acak
    $nama_laki = ['Budi', 'Andi', 'Dika', 'Rizky', 'Fajar', 'Aditya', 'Reza', 'Ilham', 'Farel', 'Rafi', 'Gilang', 'Bayu'];
    $nama_perempuan = ['Siti', 'Ayu', 'Rina', 'Putri', 'Nisa', 'Zahra', 'Aulia', 'Dina', 'Sari', 'Lestari', 'Intan', 'Maya'];
    $nama_ortu_list = ['Bapak Anton', 'Ibu Ratna', 'Bapak Budi', 'Ibu Siti', 'Bapak Joko', 'Ibu Dewi', 'Bapak Herman', 'Ibu Susi'];
    
    // Proporsi Status Gizi (Diperbanyak Normal agar KPI terlihat realistis)
    $status_list = ['Normal', 'Normal', 'Normal', 'Normal', 'Gizi Kurang', 'Gizi Kuruk', 'Stunting', 'Gizi Lebih', 'Obesitas'];

    $berhasil = 0;

    // 3. Looping sebanyak 50 kali untuk membuat 50 data
    for ($i = 1; $i <= 50; $i++) {
        
        // --- BUAT DATA INDUK BALITA ---
        $jk = rand(0, 1); // 1 = Laki, 0 = Perempuan
        $nama_depan = $jk == 1 ? $nama_laki[array_rand($nama_laki)] : $nama_perempuan[array_rand($nama_perempuan)];
        $nama_balita = $nama_depan . " " . chr(rand(65, 90)); // Contoh: Budi A
        
        $ortu = $nama_ortu_list[array_rand($nama_ortu_list)];
        $nik = '1871' . rand(100000000000, 999999999999); // 16 Digit NIK acak Lampung

        $umur_bulan = rand(6, 59); // Acak umur antara 6 - 59 bulan
        $tgl_lahir = date('Y-m-d', strtotime("-$umur_bulan months"));
        $alamat = 'Jl. Mawar No. ' . rand(1, 100) . ', Bandar Lampung';

        $stmt_balita = $pdo->prepare("INSERT INTO data_balita (nik, nama_balita, nama_ortu, jenis_kelamin, tanggal_lahir, alamat) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_balita->execute([$nik, $nama_balita, $ortu, $jk, $tgl_lahir, $alamat]);
        $id_balita = $pdo->lastInsertId(); // Ambil ID balita yang baru saja dibuat

        // --- BUAT DATA RIWAYAT PEMERIKSAAN ---
        // Tanggal ukur diacak dari 30 hari ke belakang sampai hari ini
        $tgl_ukur = date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'));
        
        $bb = rand(50, 180) / 10; // Acak berat badan 5.0 kg s/d 18.0 kg
        $tb = rand(650, 1100) / 10; // Acak tinggi badan 65.0 cm s/d 110.0 cm
        $status = $status_list[array_rand($status_list)];

        $stmt_riwayat = $pdo->prepare("INSERT INTO riwayat_klasifikasi (id_balita, id_user, tanggal_ukur, umur_saat_ukur, berat_badan, tinggi_badan, hasil_klasifikasi, nilai_k) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_riwayat->execute([$id_balita, $user_id, $tgl_ukur, $umur_bulan, $bb, $tb, $status, 5]);
        
        $berhasil++;
    }

    // Eksekusi semua query
    $pdo->commit();

    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
            <h2 style='color:green;'>Selesai!</h2>
            <p><strong>$berhasil</strong> Data Dummy (Balita & Riwayat) sukses ditambahkan ke Database.</p>
            <p>Silakan buka <a href='admin/index.php'>Dashboard Admin</a> untuk melihat hasilnya.</p>
            <br><small style='color:red;'>PENTING: Jangan lupa hapus file <b>generate_dummy.php</b> ini setelah selesai digunakan!</small>
          </div>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red;'>Terjadi Error:</h3> " . $e->getMessage();
}
?>
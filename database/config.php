<?php
// Konfigurasi Database
$host     = 'localhost';
$dbname   = 'db_gizi'; // Ganti dengan nama databasemu nanti
$username = 'root';            // Default XAMPP
$password = '';                // Default XAMPP biasanya kosong

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Mengatur mode error PDO menjadi Exception agar error mudah dilacak
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Echo ini bisa dihapus nanti kalau sudah jalan, ini hanya untuk tes koneksi
    // echo "Koneksi database berhasil!"; 

} catch (PDOException $e) {
    // Menangkap dan menampilkan error jika koneksi gagal
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
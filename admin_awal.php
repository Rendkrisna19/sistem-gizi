<?php
// Panggil file koneksi database
require_once 'database/config.php';

// Data akun admin yang ingin dibuat
$nama_lengkap = "Administrator Sistem";
$username     = "admin";
$password_asli = "admin123"; // Password yang akan diketik saat login
$role         = "admin";

// Mengacak (Hash) password menggunakan standar Bcrypt bawaan PHP
$password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

try {
    // Mengecek apakah username sudah ada di database
    $stmt_check = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt_check->execute(['username' => $username]);
    
    if ($stmt_check->rowCount() > 0) {
        echo "<h3 style='color:orange;'>Akun dengan username '$username' sudah terdaftar di database!</h3>";
    } else {
        // Query untuk menyimpan data admin ke tabel users
        $sql = "INSERT INTO users (nama_lengkap, username, password, role) 
                VALUES (:nama_lengkap, :username, :password, :role)";
        
        $stmt = $pdo->prepare($sql);
        
        // Eksekusi eksekusi data
        $stmt->execute([
            ':nama_lengkap' => $nama_lengkap,
            ':username'     => $username,
            ':password'     => $password_hash,
            ':role'         => $role
        ]);

        echo "<h3 style='color:green;'>Berhasil! Akun Admin pertama sukses dibuat.</h3>";
        echo "<b>Username:</b> $username <br>";
        echo "<b>Password:</b> $password_asli <br>";
        echo "<br><i>Silakan hapus file admin.php ini setelah digunakan demi keamanan.</i>";
    }

} catch(PDOException $e) {
    echo "<h3 style='color:red;'>Terjadi Kesalahan:</h3> " . $e->getMessage();
}
?>
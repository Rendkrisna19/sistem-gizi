<?php
session_start();
require_once 'database/config.php';

// Jika sudah login, cek rolenya dan langsung arahkan ke dashboard masing-masing
if(isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['foto'] = $user['foto']; // <-- TAMBAHKAN BARIS INI

        // Redirect berdasarkan role
        if ($user['role'] == 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: user/index.php"); 
        }
        exit;
    } else {
        $error = "Username atau Password yang Anda masukkan salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas - SIPEGIZI Lampung</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style> 
        body { font-family: 'Poppins', sans-serif; } 
        .bg-login-pattern {
            background-color: #047857;
            background-image: radial-gradient(#10b981 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 sm:p-8">

    <div class="max-w-5xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
        
        <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center bg-white relative">
            
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-6">
                    <div class="bg-emerald-500 text-white p-2 rounded-lg"><i class="fa-solid fa-leaf text-xl"></i></div>
                    <span class="font-bold text-2xl tracking-tight text-emerald-900">SIPEGIZI</span>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-800">Selamat Datang!</h2>
                <p class="text-gray-500 mt-2">Silakan masuk untuk mengakses panel petugas posyandu.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start gap-3 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mt-1"></i>
                    <span class="text-sm font-medium"><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required placeholder="Masukkan username Anda" class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-gray-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-11 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-gray-700">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center cursor-pointer text-gray-400 hover:text-emerald-600" onclick="togglePassword()">
                            <i id="eye-icon" class="fa-solid fa-eye"></i>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    Masuk Sekarang <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-600">
                    Belum memiliki akun petugas? <br>
                    <a href="register.php" class="text-emerald-600 font-bold hover:underline mt-1 inline-block">Daftar Akun Baru</a>
                </p>
            </div>
        </div>

        <div class="hidden md:flex w-1/2 bg-login-pattern relative flex-col justify-center items-center p-12 text-white">
            <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/90 to-emerald-700/80"></div>
            
            <div class="relative z-10 text-center max-w-md">
                <div class="bg-white/20 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-sm border border-white/30 shadow-2xl">
                    <i class="fa-solid fa-notes-medical text-4xl text-white"></i>
                </div>
                <h2 class="text-3xl font-bold mb-4 leading-tight">Klasifikasi Status Gizi Balita K-NN</h2>
                <p class="text-emerald-100 text-sm leading-relaxed mb-8">
                    Sistem Cerdas berbasis Web untuk memantau status gizi balita di Provinsi Lampung menggunakan algoritma Machine Learning <strong>K-Nearest Neighbor</strong>.
                </p>
                
                <div class="flex items-center justify-center gap-3 text-emerald-200 text-xs font-medium bg-black/20 p-3 rounded-lg border border-white/10">
                    <i class="fa-solid fa-shield-halved"></i> Aman & Terenkripsi
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eye-icon");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
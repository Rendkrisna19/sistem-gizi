<?php
session_start();
require_once 'database/config.php';

// Jika sudah login, lempar ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin/index.php' : 'user/index.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah username sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username sudah terdaftar, silakan gunakan yang lain.";
        } else {
            // Hash password dan simpan dengan role 'user' & foto default
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, password, role, foto) VALUES (?, ?, ?, 'user', 'default.png')");
            
            if ($stmt->execute([$nama, $username, $hashed_password])) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan sistem.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Petugas - SIPEGIZI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style> 
        body { font-family: 'Poppins', sans-serif; } 
        .bg-register-pattern {
            background-color: #065f46;
            background-image: radial-gradient(#10b981 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 sm:p-8">

    <div class="max-w-5xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row-reverse">
        
        <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center bg-white relative">
            
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <div class="bg-emerald-500 text-white p-2 rounded-lg"><i class="fa-solid fa-leaf text-xl"></i></div>
                    <span class="font-bold text-2xl tracking-tight text-emerald-900">SIPEGIZI</span>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-800">Daftar Akun</h2>
                <p class="text-gray-500 mt-2">Bergabung sebagai petugas Posyandu / Bidan.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start gap-3 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mt-1"></i>
                    <span class="text-sm font-medium"><?= $error ?></span>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 px-4 py-3 rounded-lg mb-6 flex items-start gap-3 shadow-sm">
                    <i class="fa-solid fa-circle-check mt-1 text-emerald-600"></i>
                    <span class="text-sm font-medium"><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-id-card text-gray-400"></i>
                        </div>
                        <input type="text" name="nama_lengkap" required placeholder="Nama lengkap & gelar" class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required placeholder="Buat username" class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" required minlength="6" placeholder="••••••" class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-shield-check text-gray-400"></i>
                            </div>
                            <input type="password" name="confirm_password" required minlength="6" placeholder="••••••" class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition bg-gray-50 focus:bg-white text-sm">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex justify-center items-center gap-2 mt-4">
                    Daftar Akun Petugas <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>

            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-600">
                    Sudah memiliki akun? <br>
                    <a href="login.php" class="text-emerald-600 font-bold hover:underline mt-1 inline-block">Masuk ke Dashboard</a>
                </p>
            </div>
        </div>

        <div class="hidden md:flex w-1/2 bg-register-pattern relative flex-col justify-center items-center p-12 text-white">
            <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/95 to-emerald-800/80"></div>
            
            <div class="relative z-10 text-center max-w-md">
                <div class="bg-white/20 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-sm border border-white/30 shadow-2xl">
                    <i class="fa-solid fa-user-nurse text-4xl text-white"></i>
                </div>
                <h2 class="text-3xl font-bold mb-4 leading-tight">Mari Cegah Stunting Bersama!</h2>
                <p class="text-emerald-100 text-sm leading-relaxed mb-8">
                    Dengan mendaftar, Anda berkontribusi dalam memantau kesehatan gizi balita menggunakan teknologi <strong>Machine Learning</strong> demi masa depan Indonesia yang lebih baik.
                </p>
                
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-xs font-medium bg-black/20 p-3 rounded-lg border border-white/10">
                        <i class="fa-solid fa-check-circle text-emerald-400"></i> Akses Panel Bidan
                    </div>
                    <div class="flex items-center gap-3 text-xs font-medium bg-black/20 p-3 rounded-lg border border-white/10">
                        <i class="fa-solid fa-check-circle text-emerald-400"></i> Klasifikasi Otomatis KNN
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
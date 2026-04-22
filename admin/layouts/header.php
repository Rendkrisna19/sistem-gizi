<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIPEGIZI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style> 
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; overflow: hidden; } 
        
        /* Custom Scrollbar untuk Sidebar */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        
        /* Transisi Mulus */
        .sidebar-transition { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Animasi Toast Notifikasi */
        .toast-enter { transform: translateX(0); opacity: 1; }
        .toast-exit { transform: translateX(120%); opacity: 0; }
    </style>
</head>
<body class="flex h-screen">

    <div class="flex flex-1 overflow-hidden relative">
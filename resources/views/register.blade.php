<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Guest House Management System - Register</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .bg-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .glass-effect {
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
        </style>
        <!-- Font Awesome CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </head>
<body class="bg-gradient min-h-screen flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hotel-pattern" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                    <circle cx="50" cy="50" r="2" fill="white" opacity="0.3"/>
                    <rect x="40" y="30" width="20" height="15" fill="none" stroke="white" stroke-width="0.5" opacity="0.2"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hotel-pattern)"/>
        </svg>
    </div>
    <!-- Register Container -->
    <div class="glass-effect rounded-2xl shadow-2xl w-full max-w-md p-8 relative">
        <!-- Logo Area -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
                <i class="fas fa-user-plus text-white text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Buat Akun Baru</h1>
            <p class="text-white text-opacity-80 text-sm">Daftar untuk akses sistem</p>
        </div>
        <!-- Register Form -->
        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf
            <div>
                <label class="block text-white text-sm font-medium mb-2" for="name">Nama</label>
                <input type="text" name="name" id="name" class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent" required>
            </div>
            <div>
                <label class="block text-white text-sm font-medium mb-2" for="email">Email</label>
                <input type="email" name="email" id="email" class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent" required>
            </div>
            <div>
                <label class="block text-white text-sm font-medium mb-2" for="password">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent" required>
            </div>
            <button type="submit" class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">Register</button>
        </form>
        <div class="mt-8 text-center">
            <p class="text-white text-opacity-60 text-xs">
                © 2025 Guest House Management System
            </p>
        </div>
    </div>
    <script></script>
</body>
</html>

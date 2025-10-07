<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Guest House Management System - Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .bg-gradient {
                background: linear-gradient(135deg, #f58b8bff 0%, #b92e2eff 100%);
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

    <!-- Login Container -->
    <div class="glass-effect rounded-2xl shadow-2xl w-full max-w-md p-8 relative">
        <!-- Logo Area -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
                <i class="fas fa-home text-white text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Guest House System</h1>
            <p class="text-white text-opacity-80 text-sm">Sistem Manajemen Operasional</p>
        </div>

        <!-- Login Form -->
        <form id="formAuthentication" class="mb-3 space-y-6" method="POST" action="{{ route('login') }}" onsubmit="return validateLoginForm();">
            @csrf
            <!-- Error Toast -->
            @if ($errors->any())
                <div id="toast-error" class="mb-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg animate-bounce">
                    {{ $errors->first() }}
                </div>
            @endif
            <!-- Username Field -->
            <div>
                <label class="block text-white text-sm font-medium mb-2">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-white text-opacity-60" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <input type="text" 
                           class="w-full pl-10 pr-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent"
                           placeholder="Masukkan username"
                           id="username"
                           name="username"
                           required>
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label class="block text-white text-sm font-medium mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-white text-opacity-60" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <input type="password" 
                           class="w-full pl-10 pr-12 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-transparent"
                           placeholder="Masukkan password"
                           id="password"
                           name="password"
                           required>
                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="h-5 w-5 text-white text-opacity-60 hover:text-white cursor-pointer" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-white bg-white bg-opacity-20 border-white border-opacity-30 rounded focus:ring-white focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-white text-opacity-80">Ingat saya</span>
                </label>
                <a href="#" class="text-sm text-white text-opacity-80 hover:text-white transition-colors">Lupa password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit" 
                    class="w-full bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                    href="{{ route('login') }}">
                Login
            </button>
        </form>
        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-white text-opacity-60 text-xs">
                © 2025 Guest House Management System
            </p>
            <div class="flex justify-center space-x-4 mt-4">
                <div class="flex items-center text-white text-opacity-60 text-xs">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Aman & Terpercaya
                </div>
            </div>
        </div>
    </div>
    <script>
    function validateLoginForm() {
        var username = document.getElementById('username');
        var password = document.getElementById('password');
        var valid = true;
        if (!username.value.trim()) {
            username.classList.add('border-red-500');
            showToast('Username wajib diisi!');
            valid = false;
        } else {
            username.classList.remove('border-red-500');
        }
        if (!password.value.trim()) {
            password.classList.add('border-red-500');
            showToast('Password wajib diisi!');
            valid = false;
        } else {
            password.classList.remove('border-red-500');
        }
        return valid;
    }
    function showToast(message) {
        var toast = document.createElement('div');
        toast.className = 'mb-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg animate-bounce fixed top-6 left-1/2 transform -translate-x-1/2 z-50';
        toast.innerText = message;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.remove();
        }, 3000);
    }
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'973bf3fe1472b5a2',t:'MTc1NTk2NjE5Mi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>

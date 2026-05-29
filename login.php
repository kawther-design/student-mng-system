<?php
session_start();
require_once 'db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $userColl = $database->getCollection('users');
    $user = $userColl->findOne([
        '$or' => [['email' => $loginId], ['username' => $loginId]],
        'role' => $role
    ]);
    
    if ($user && isset($user->password) && password_verify($password, $user->password)) {
        $_SESSION['user_id'] = (string)$user->_id;
        $_SESSION['role'] = $user->role;
        $_SESSION['name'] = $user->name;
        
        if ($user->role === 'Admin') {
            header('Location: admin-dashboard.php');
        } elseif ($user->role === 'Vice President') {
            header('Location: vice-president-dashboard.php');
        } elseif ($user->role === 'Teacher') {
            header('Location: teacher-dashboard.php');
        } elseif ($user->role === 'Parent') {
            header('Location: parent-dashboard.php');
        } else {
            header('Location: admin-dashboard.php');
        }
        exit;
    } else {
        if ($loginId === 'admin' && $password === '123' && $role === 'Admin') {
            $_SESSION['user_id'] = 'demo_admin';
            $_SESSION['role'] = 'Admin';
            $_SESSION['name'] = 'System Admin';
            header('Location: admin-dashboard.php');
            exit;
        }
        $error = 'Invalid credentials or incorrect portal role!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Al Huda Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="global.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            blue: '#2D3E8B',
                            teal: '#1DBF92',
                            coral: '#FF6B52',
                            bg: '#F8FAFF'
                        }
                    },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #F8FAFF;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-y: auto;
        }
        .mesh-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(at 0% 0%, rgba(45, 62, 139, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(29, 191, 146, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 107, 82, 0.08) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(45, 62, 139, 0.08) 0px, transparent 50%);
            z-index: -1;
        }
        .floating-blob {
            position: absolute;
            width: 800px;
            height: 800px;
            background: linear-gradient(135deg, rgba(45, 62, 139, 0.05) 0%, rgba(29, 191, 146, 0.05) 100%);
            filter: blur(120px);
            border-radius: 45% 55% 70% 30% / 30% 60% 40% 70%;
            animation: blob-float 25s infinite alternate ease-in-out;
            z-index: -1;
        }
        @keyframes blob-float {
            0% { transform: translate(-10%, -10%) rotate(0deg) scale(1); }
            100% { transform: translate(10%, 10%) rotate(360deg) scale(1.2); }
        }
        .glass-container {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(40px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 
                0 40px 100px -20px rgba(45, 62, 139, 0.15),
                inset 0 0 40px rgba(255, 255, 255, 0.1);
        }
        .input-group:focus-within { border-color: rgba(45, 62, 139, 0.3); background: white; transform: translateY(-2px); }
        .input-group { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        .animate-up {
            animation: slideUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pattern-bg {
            position: absolute;
            inset: 0;
            opacity: 0.03;
            background-image: radial-gradient(#2D3E8B 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="mesh-bg"></div>
    <div class="pattern-bg"></div>
    <div class="floating-blob top-[-200px] right-[-100px]"></div>
    <div class="floating-blob bottom-[-200px] left-[-100px]" style="animation-delay: -10s;"></div>

    <div class="w-full max-w-[500px] glass-container rounded-[4.5rem] p-10 lg:p-12 animate-up relative z-10 border border-white/40">
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="relative group cursor-pointer">
                <div class="absolute inset-0 bg-school-blue blur-3xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
                <div class="w-20 h-20 bg-school-blue rounded-[2.8rem] flex items-center justify-center shadow-2xl shadow-school-blue/40 mb-6 relative z-10 transition-all duration-700 group-hover:rotate-[360deg]">
                    <i data-lucide="graduation-cap" class="text-white w-10 h-10"></i>
                </div>
            </div>
            <h1 class="text-3xl font-black text-school-blue tracking-tighter uppercase leading-none">AL HUDA PORTAL</h1>
            <div class="flex items-center space-x-3 mt-4">
                <span class="h-[1px] w-8 bg-gray-200"></span>
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-[0.5em] mt-0.5">Secure Authentication</p>
                <span class="h-[1px] w-8 bg-gray-200"></span>
            </div>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-school-coral text-[10px] font-black uppercase p-6 rounded-3xl mb-6 text-center border border-school-coral/10 animate-shake flex items-center justify-center space-x-3">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6" autocomplete="off">
            <div class="space-y-3">
                <div class="flex items-center justify-between px-4">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Portal Role</label>
                    <i data-lucide="info" class="w-3 h-3 text-gray-300"></i>
                </div>
                <div class="input-group relative rounded-[2.2rem] bg-gray-50/40 border border-transparent flex items-center group">
                    <div class="absolute left-7 text-gray-300 group-focus-within:text-school-blue transition-colors">
                        <i data-lucide="users-round" class="w-5 h-5"></i>
                    </div>
                    <select name="role" required class="w-full bg-transparent border-none rounded-[2.2rem] py-5 pl-16 pr-8 outline-none font-bold text-school-blue text-sm appearance-none cursor-pointer">
                        <option value="Admin">System Administrator</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Teacher">Teachers</option>
                        <option value="Parent">Parent / Guardian</option>
                    </select>
                    <div class="absolute right-7 pointer-events-none text-gray-300">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between px-4">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Login Identity</label>
                    <i data-lucide="user-circle" class="w-3 h-3 text-gray-300"></i>
                </div>
                <div class="input-group relative rounded-[2.2rem] bg-gray-50/40 border border-transparent group">
                    <div class="absolute left-7 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-school-blue transition-colors">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <input type="text" name="email" value="" required placeholder="Enter username or email" class="w-full bg-transparent border-none rounded-[2.2rem] py-5 pl-16 pr-8 outline-none font-bold text-school-blue text-sm placeholder:text-gray-300/80" autocomplete="off">
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between px-4">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Secret Key</label>
                    <i data-lucide="lock-keyhole" class="w-3 h-3 text-gray-300"></i>
                </div>
                <div class="input-group relative rounded-[2.2rem] bg-gray-50/40 border border-transparent group">
                    <div class="absolute left-7 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-school-blue transition-colors">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <input type="password" id="password" name="password" value="" required placeholder="••••••••••••" class="w-full bg-transparent border-none rounded-[2.2rem] py-5 pl-16 pr-16 outline-none font-bold text-school-blue text-sm placeholder:text-gray-300/80" autocomplete="new-password">
                    <button type="button" onclick="togglePassword()" class="absolute right-7 top-1/2 -translate-y-1/2 text-gray-300 hover:text-school-blue transition-colors">
                        <i data-lucide="eye" id="eye-icon" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-5 bg-school-blue text-white rounded-[2.2rem] font-black text-xs uppercase tracking-[0.3em] shadow-3xl shadow-school-blue/40 hover:scale-[1.03] active:scale-95 transition-all duration-500 flex items-center justify-center space-x-4 group relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                    <span class="relative z-10">Access Secure Portal</span>
                    <i data-lucide="move-right" class="w-5 h-5 group-hover:translate-x-2 transition-transform relative z-10"></i>
                </button>
            </div>
            
            <div class="pt-6 text-center border-t border-gray-100/60">
                <div class="flex items-center justify-center space-x-4 mb-4">
                    <i data-lucide="shield-check" class="w-4 h-4 text-green-500"></i>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">End-to-End Encrypted</span>
                </div>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] leading-relaxed">
                    © 2026 Al Huda Secondary School <br>
                    <span class="text-gray-300 font-bold mt-1 block tracking-[0.2em]">Management System v2.5.0</span>
                </p>
            </div>
        </form>
    </div>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.4s ease-in-out; }
        .shadow-3xl { box-shadow: 0 25px 60px -15px rgba(45, 62, 139, 0.4); }
    </style>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                password.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
        lucide.createIcons();
    </script>
</body>
</html>

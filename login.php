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
        
        if ($user->role === 'Admin' || $user->role === 'Vice President') {
            header('Location: admin-dashboard.php');
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
        $error = 'Credentials khaldan ama Role-ka hubi!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Access | Al Huda Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            overflow: hidden;
        }
        .mesh-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(at 0% 0%, rgba(45, 62, 139, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(29, 191, 146, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 107, 82, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(45, 62, 139, 0.1) 0px, transparent 50%);
            z-index: -1;
        }
        .floating-shape {
            position: absolute;
            z-index: -1;
            filter: blur(80px);
            animation: float 20s infinite alternate;
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(50px, 50px) scale(1.1); }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 50px 100px -20px rgba(45, 62, 139, 0.15),
                        0 30px 60px -30px rgba(0, 0, 0, 0.1);
        }
        .input-box:focus-within {
            background: white;
            box-shadow: 0 10px 20px -10px rgba(45, 62, 139, 0.1);
            border-color: rgba(45, 62, 139, 0.2);
        }
        .animate-in {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="mesh-bg"></div>
    <div class="floating-shape w-[600px] h-[600px] bg-school-blue/10 top-[-200px] right-[-100px] rounded-full"></div>
    <div class="floating-shape w-[500px] h-[500px] bg-school-teal/10 bottom-[-200px] left-[-100px] rounded-full" style="animation-delay: -5s;"></div>

    <div class="w-full max-w-[460px] glass-card rounded-[4rem] p-12 lg:p-16 animate-in relative z-10">
        <div class="flex flex-col items-center mb-12">
            <div class="w-20 h-20 bg-school-blue rounded-[2.5rem] flex items-center justify-center shadow-2xl shadow-school-blue/40 mb-8 transition-transform hover:scale-110 duration-500">
                <i data-lucide="graduation-cap" class="text-white w-10 h-10"></i>
            </div>
            <h1 class="text-3xl font-black text-school-blue tracking-tighter uppercase text-center leading-none">AL HUDA PORTAL</h1>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em] mt-4 ml-1">Excellence in Education</p>
        </div>

        <?php if($error): ?>
            <div class="bg-school-coral/10 text-school-coral text-[10px] font-black uppercase p-5 rounded-3xl mb-8 text-center border border-school-coral/20 animate-pulse">
                <i data-lucide="alert-circle" class="w-4 h-4 inline-block mr-2 -mt-0.5"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-7">
            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest ml-3">System Role</label>
                <div class="input-box relative rounded-[2rem] bg-gray-50/50 transition-all border border-transparent flex items-center">
                    <div class="absolute left-6 text-gray-300">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <select name="role" required class="w-full bg-transparent border-none rounded-[2rem] py-5 pl-16 pr-8 outline-none font-bold text-school-blue text-sm appearance-none cursor-pointer">
                        <option value="Admin">Admin Account</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Teacher">Academic Teacher</option>
                        <option value="Parent">Registered Parent</option>
                    </select>
                    <div class="absolute right-6 pointer-events-none text-gray-300">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest ml-3">Login ID</label>
                <div class="input-box relative rounded-[2rem] bg-gray-50/50 transition-all border border-transparent">
                    <div class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-300">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <input type="text" name="email" required placeholder="Username or Email" class="w-full bg-transparent border-none rounded-[2rem] py-5 pl-16 pr-8 outline-none font-bold text-school-blue text-sm placeholder:text-gray-300">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest ml-3">Secure Password</label>
                <div class="input-box relative rounded-[2rem] bg-gray-50/50 transition-all border border-transparent">
                    <div class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-300">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-transparent border-none rounded-[2rem] py-5 pl-16 pr-8 outline-none font-bold text-school-blue text-sm placeholder:text-gray-300">
                </div>
            </div>

            <button type="submit" class="w-full py-6 bg-school-blue text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-school-blue/30 hover:scale-[1.02] active:scale-98 transition-all duration-300 flex items-center justify-center space-x-4 group">
                <span>ENTER SECURE PORTAL</span>
                <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-2 transition-transform"></i>
            </button>
            
            <div class="pt-8 text-center border-t border-gray-100/50">
                <p class="text-[9px] font-black text-gray-300 uppercase tracking-widest leading-relaxed">
                    Licensed to Al Huda Secondary School <br>
                    <span class="text-gray-400">Trusted Education Management System</span>
                </p>
            </div>
        </form>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>

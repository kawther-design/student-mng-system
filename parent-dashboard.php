<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Parent') {
    if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Vice President') {
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal | Al Huda School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            coral: '#F97316',
                            orange: '#FB923C',
                            blue: '#2D3E8B',
                            bg: '#FFF7ED'
                        }
                    },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #FFF7ED; }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active { 
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
            color: white; 
            box-shadow: 0 15px 30px -10px rgba(249, 115, 22, 0.4); 
        }
        .glass-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(249, 115, 22, 0.1);
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-coral/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-coral rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-coral/20">
                <i data-lucide="heart" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-coral tracking-tighter">FAMILIES</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Parent Portal</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="parent-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Overview</span>
            </a>
            <a href="#" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-coral hover:bg-school-coral/5 transition-all">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">My Children</span>
            </a>
            <a href="#" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-coral hover:bg-school-coral/5 transition-all">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Report Cards</span>
            </a>
            <a href="#" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-coral hover:bg-school-coral/5 transition-all">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Notices</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:bg-gray-50 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-10">
        <header class="flex justify-between items-center mb-16">
            <div>
                <h2 class="text-3xl font-black text-school-coral tracking-tighter uppercase">Welcome, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mt-1">Parent Portal | Academic Tracking</p>
            </div>
            <div class="w-14 h-14 rounded-[1.5rem] bg-school-coral/10 p-1 border-2 border-school-coral/10 flex items-center justify-center text-school-coral">
                <i data-lucide="user" class="w-7 h-7"></i>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="glass-card rounded-[4rem] p-12">
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-2xl font-black text-school-coral tracking-tighter uppercase">Student Progress</h3>
                    <div class="p-4 bg-school-coral/10 text-school-coral rounded-2xl"><i data-lucide="trending-up" class="w-6 h-6"></i></div>
                </div>
                <div class="space-y-8">
                    <div class="p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h4 class="text-lg font-black text-school-blue uppercase tracking-tight">Abdirahman Ali</h4>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Secondary Form 3A</p>
                            </div>
                            <span class="px-5 py-2 bg-school-coral text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-school-coral/20">Grade: A</span>
                        </div>
                        <div class="w-full bg-gray-200/50 h-3 rounded-full overflow-hidden mb-3">
                            <div class="bg-school-coral h-full w-[85%] rounded-full shadow-[0_0_10px_rgba(249,115,22,0.5)]"></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">Attendance</span>
                            <span class="text-[10px] text-school-coral font-black uppercase tracking-widest">96% Status</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-10">
                <div class="bg-school-coral rounded-[4rem] p-12 text-white shadow-2xl shadow-school-coral/30 relative overflow-hidden group">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black uppercase tracking-tighter mb-1">Financial Overview</h3>
                        <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest">Current Balance Status</p>
                        <p class="text-6xl font-black mt-10 tracking-tighter">$150.00</p>
                        <button class="mt-10 px-10 py-5 bg-white text-school-coral rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:scale-105 transition-all shadow-xl">Secure Online Payment</button>
                    </div>
                    <i data-lucide="credit-card" class="absolute -bottom-10 -right-10 w-56 h-56 text-white/5 opacity-10 group-hover:scale-110 transition-transform duration-1000"></i>
                </div>

                <div class="glass-card rounded-[4rem] p-12">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-black text-school-coral tracking-tighter uppercase">Notice Board</h3>
                        <div class="w-10 h-10 bg-school-coral/10 text-school-coral rounded-xl flex items-center justify-center"><i data-lucide="megaphone" class="w-5 h-5"></i></div>
                    </div>
                    <div class="p-8 border-l-8 border-school-coral bg-school-coral/[0.03] rounded-r-[2.5rem]">
                        <p class="text-md font-bold text-school-blue leading-relaxed">Parents-Teachers Meeting scheduled for coming Saturday at 9:00 AM.</p>
                        <div class="flex items-center space-x-2 mt-4">
                            <span class="w-2 h-2 rounded-full bg-school-coral animate-pulse"></span>
                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Posted 2 hours ago by Office</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>

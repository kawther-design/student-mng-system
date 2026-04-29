<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Vice President') {
        header('Location: login.php');
        exit;
    }
}

$studentColl = $database->getCollection('students');
$totalStudents = $studentColl->countDocuments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal | Al Huda</title>
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
        body { font-family: 'Outfit', sans-serif; background-color: #F8FAFF; }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active { 
            background: linear-gradient(135deg, #2D3E8B 0%, #1a255a 100%);
            color: white; 
            box-shadow: 0 15px 30px -10px rgba(45, 62, 139, 0.4); 
        }
        .glass-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(45, 62, 139, 0.1);
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-blue/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-blue rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-blue/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-blue tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Teacher Portal</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="teacher-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">My Students</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Exams & Results</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-coral hover:bg-school-coral/5 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-10">
        <header class="flex justify-between items-center mb-16">
            <div>
                <h2 class="text-3xl font-black text-school-blue tracking-tighter uppercase">Academic Dashboard</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mt-1">Teaching & Resource Management</p>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-sm font-black text-school-blue uppercase tracking-widest"><?= $_SESSION['name'] ?></p>
                    <p class="text-[9px] font-black text-school-teal uppercase tracking-widest mt-1">Senior Faculty Staff</p>
                </div>
                <div class="w-14 h-14 rounded-[1.5rem] bg-school-blue/5 p-1 border-2 border-school-blue/10">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=2D3E8B&color=fff" class="w-full h-full rounded-[1.2rem] shadow-lg" alt="">
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-12">
            <div class="glass-card p-10 rounded-[4rem]">
                <div class="p-4 bg-school-teal/10 text-school-teal w-fit rounded-2xl mb-8"><i data-lucide="users" class="w-7 h-7"></i></div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Total Students</h4>
                <p class="text-4xl font-black text-school-blue mt-2"><?= $totalStudents ?></p>
            </div>
            <div class="glass-card p-10 rounded-[4rem]">
                <div class="p-4 bg-school-blue/10 text-school-blue w-fit rounded-2xl mb-8"><i data-lucide="book-open" class="w-7 h-7"></i></div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Active Classes</h4>
                <p class="text-4xl font-black text-school-blue mt-2">4 <span class="text-lg text-gray-300 ml-1">Sections</span></p>
            </div>
            <div class="bg-school-coral p-10 rounded-[4rem] text-white shadow-2xl shadow-school-coral/30 border-none">
                <div class="p-4 bg-white/20 text-white w-fit rounded-2xl mb-8"><i data-lucide="alert-circle" class="w-7 h-7"></i></div>
                <h4 class="text-white/60 font-black text-[10px] uppercase tracking-widest">Pending Grading</h4>
                <p class="text-4xl font-black mt-2 tracking-tighter">12 <span class="text-lg text-white/50 ml-1">Entries</span></p>
            </div>
        </div>

        <div class="glass-card p-12 rounded-[4rem]">
            <div class="flex items-center justify-between mb-10">
                <h3 class="text-2xl font-black text-school-blue tracking-tighter uppercase">Academic Schedule</h3>
                <span class="px-6 py-2 bg-school-blue/5 text-school-blue text-[10px] font-black uppercase tracking-widest rounded-full">Term 2 • 2026</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="flex items-center justify-between p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500">
                    <div class="flex items-center space-x-6">
                        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm text-school-blue flex items-center justify-center group-hover:bg-school-blue group-hover:text-white transition-all"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                        <div>
                            <p class="text-lg font-black text-school-blue">Mathematics Midterm</p>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-1">May 15, 2026 • Form 3A</p>
                        </div>
                    </div>
                    <span class="px-5 py-2 bg-school-teal text-white rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg shadow-school-teal/20">Active</span>
                </div>
                <div class="flex items-center justify-between p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500">
                    <div class="flex items-center space-x-6">
                        <div class="w-14 h-14 bg-white rounded-2xl shadow-sm text-school-blue flex items-center justify-center group-hover:bg-school-blue group-hover:text-white transition-all"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                        <div>
                            <p class="text-lg font-black text-school-blue">Physics Practical</p>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-1">May 18, 2026 • Form 4B</p>
                        </div>
                    </div>
                    <span class="px-5 py-2 bg-school-yellow text-school-blue rounded-full text-[9px] font-black uppercase tracking-widest">Drafting</span>
                </div>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>

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

$attendanceColl = $database->getCollection('attendance');
$todayDate = date('Y-m-d');
$todayPresent = $attendanceColl->countDocuments(['date' => $todayDate, 'status' => 'Present']);
$todayTotal = $attendanceColl->countDocuments(['date' => $todayDate]);
$attendanceRate = $todayTotal > 0 ? round(($todayPresent / $todayTotal) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | Al Huda School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            purple: '#8B5CF6',
                            accent: '#8B5CF6',
                            orange: '#F97316',
                            blue: '#2D3E8B',
                            bg: '#F5F3FF'
                        }
                    },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #F5F3FF; }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active { 
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            color: white; 
            box-shadow: 0 15px 30px -10px rgba(139, 92, 246, 0.4); 
        }
        .glass-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(139, 92, 246, 0.12);
        }
        .animate-in {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #8B5CF6; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-purple/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-purple rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-purple/20">
                <i data-lucide="book-open" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-purple tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Faculty Portal v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="teacher-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-purple hover:bg-school-purple/5 transition-all">
                <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">My Students</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-purple hover:bg-school-purple/5 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-500 hover:bg-red-50 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-10 animate-in">
        <header class="flex justify-between items-center mb-16">
            <div>
                <h2 class="text-3xl font-black text-school-purple tracking-tighter uppercase">Academic Dashboard</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mt-1">Teaching & Resource Oversight</p>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-sm font-black text-school-purple uppercase tracking-widest"><?= explode(' ', $_SESSION['name'])[0] ?></p>
                    <p class="text-[9px] font-black text-school-orange uppercase tracking-widest mt-1">Senior Faculty</p>
                </div>
                <div class="w-14 h-14 rounded-[1.5rem] bg-school-purple/5 p-1 border-2 border-school-purple/10 flex items-center justify-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=8B5CF6&color=fff" class="w-full h-full rounded-[1.2rem] shadow-lg" alt="">
                </div>
            </div>
        </header>

        <div class="mb-10">
            <h3 class="text-xl font-black text-school-blue uppercase tracking-widest border-l-4 border-school-purple pl-4">Daily Performance Analytics</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <div class="glass-card p-10 rounded-[3.5rem] relative overflow-hidden group">
                <div class="p-4 bg-school-purple/10 text-school-purple w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-school-purple/10">
                    <i data-lucide="users" class="w-7 h-7"></i>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Total Students</h4>
                <p class="text-5xl font-black text-school-purple mt-2 tracking-tighter"><?= $totalStudents ?></p>
                <i data-lucide="users" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-purple/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
            </div>

            <div class="glass-card p-10 rounded-[3.5rem] relative overflow-hidden group">
                <div class="p-4 bg-school-orange/10 text-school-orange w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-school-orange/10">
                    <i data-lucide="book-open" class="w-7 h-7"></i>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Active Classes</h4>
                <p class="text-5xl font-black text-school-purple mt-2 tracking-tighter">4 <span class="text-sm text-gray-300 font-bold uppercase ml-1">Sections</span></p>
                <i data-lucide="book-open" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-orange/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
            </div>

            <div class="glass-card p-10 rounded-[3.5rem] relative overflow-hidden group">
                <div class="p-4 bg-green-50 text-green-500 w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-green-500/10">
                    <i data-lucide="calendar-check-2" class="w-7 h-7"></i>
                </div>
                <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Daily Presence</h4>
                <p class="text-5xl font-black text-green-500 mt-2 tracking-tighter"><?= $attendanceRate ?><span class="text-xl text-gray-300 ml-1">%</span></p>
                <i data-lucide="calendar-check-2" class="absolute -bottom-6 -right-6 w-32 h-32 text-green-500/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
            </div>

            <div class="stat-card bg-school-purple p-10 rounded-[3.5rem] relative overflow-hidden group border-none shadow-2xl shadow-school-purple/30 text-white">
                <div class="p-4 bg-white/20 text-white w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-black/10">
                    <i data-lucide="alert-circle" class="w-7 h-7"></i>
                </div>
                <h4 class="text-white/60 font-black text-[10px] uppercase tracking-widest">Pending Grading</h4>
                <p class="text-5xl font-black text-white mt-2 tracking-tighter">12 <span class="text-sm text-white/50 font-bold uppercase ml-1">Items</span></p>
                <i data-lucide="alert-circle" class="absolute -bottom-10 -right-10 w-48 h-48 text-white/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-purple/5 border border-gray-100 relative overflow-hidden">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h3 class="text-2xl font-black text-school-purple tracking-tighter uppercase">Academic Schedule</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Upcoming assessments & deadlines</p>
                </div>
                <span class="px-6 py-2 bg-school-purple/5 text-school-purple text-[10px] font-black uppercase tracking-widest rounded-full border border-school-purple/10">Term 2 • 2026</span>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="flex items-center justify-between p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500 cursor-pointer">
                    <div class="flex items-center space-x-6">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-sm text-school-purple flex items-center justify-center group-hover:bg-school-purple group-hover:text-white group-hover:scale-110 transition-all duration-500">
                            <i data-lucide="calendar" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <p class="text-xl font-black text-school-purple uppercase tracking-tighter">Mathematics Midterm</p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">May 15 • Form 3A</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="px-4 py-1.5 bg-school-orange text-white rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg shadow-school-orange/20">Active</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500 cursor-pointer">
                    <div class="flex items-center space-x-6">
                        <div class="w-16 h-16 bg-white rounded-2xl shadow-sm text-school-purple flex items-center justify-center group-hover:bg-school-purple group-hover:text-white group-hover:scale-110 transition-all duration-500">
                            <i data-lucide="flask-conical" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <p class="text-xl font-black text-school-purple uppercase tracking-tighter">Physics Practical</p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">May 18 • Form 4B</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="px-4 py-1.5 bg-yellow-400 text-white rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg shadow-yellow-400/20">Drafting</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

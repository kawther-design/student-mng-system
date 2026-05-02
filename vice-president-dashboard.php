<?php
session_start();
require_once 'db_config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vice President') {
    header('Location: login.php');
    exit;
}

// Fetch dynamic counts from MongoDB
$studentColl = $database->getCollection('students');
$userColl = $database->getCollection('users');
$attColl = $database->getCollection('attendance');

$totalStudents = $studentColl->countDocuments();
$totalTeachers = $userColl->countDocuments(['role' => 'Teacher']);
$activeSections = count($studentColl->distinct('form'));

// Fetch recent students
$recentStudents = $studentColl->find([], ['limit' => 5, 'sort' => ['created_at' => -1]])->toArray();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VP Portal | Al Huda Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            teal: '#1DBF92',
                            emerald: '#059669',
                            blue: '#2D3E8B',
                            bg: '#F0FDFA'
                        }
                    },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #F0FDFA; }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active { 
            background: linear-gradient(135deg, #1DBF92 0%, #065F46 100%);
            color: white; 
            box-shadow: 0 15px 30px -10px rgba(29, 191, 146, 0.4); 
        }
        .glass-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(29, 191, 146, 0.12);
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-teal/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-teal rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-teal/20">
                <i data-lucide="shield" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-teal tracking-tighter">VP PORTAL</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Academic Oversight</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="vice-president-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Student Registration</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Teachers Registration</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Exam & Result</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-teal hover:bg-school-teal/5 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 w-full">
        <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-10 h-24 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h2 class="text-2xl font-black text-school-teal tracking-tighter uppercase">Administrative Oversight</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Registration & Academic Performance</p>
            </div>
            <div class="flex items-center space-x-8">
                <div class="hidden md:flex flex-col items-end">
                    <p class="text-sm font-black text-school-teal uppercase tracking-widest"><?= $_SESSION['name'] ?></p>
                    <p class="text-[9px] font-black text-school-emerald uppercase tracking-widest mt-1">Vice President</p>
                </div>
                <div class="w-14 h-14 rounded-[1.5rem] bg-school-teal/5 p-1 border-2 border-school-teal/10">
                    <img src="https://ui-avatars.com/api/?name=VP&background=1DBF92&color=fff" class="w-full h-full rounded-[1.2rem] shadow-lg" alt="">
                </div>
            </div>
        </header>

        <div class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="glass-card p-10 rounded-[4rem]">
                    <div class="p-4 bg-school-teal/10 text-school-teal w-fit rounded-2xl mb-8"><i data-lucide="graduation-cap" class="w-7 h-7"></i></div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Total Enrollment</h4>
                    <p class="text-4xl font-black text-school-teal mt-2"><?= $totalStudents ?></p>
                </div>
                <div class="glass-card p-10 rounded-[4rem]">
                    <div class="p-4 bg-school-emerald/10 text-school-emerald w-fit rounded-2xl mb-8"><i data-lucide="book-open" class="w-7 h-7"></i></div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Active Levels</h4>
                    <p class="text-4xl font-black text-school-teal mt-2"><?= $activeSections ?> Classes</p>
                </div>
                <div class="glass-card bg-school-teal p-10 rounded-[4rem] text-white shadow-2xl shadow-school-teal/30 border-none">
                    <div class="p-4 bg-white/20 text-white w-fit rounded-2xl mb-8"><i data-lucide="users" class="w-7 h-7"></i></div>
                    <h4 class="text-white/40 font-black text-[10px] uppercase tracking-widest">Total Faculty</h4>
                    <p class="text-5xl font-black text-white mt-2 tracking-tighter"><?= $totalTeachers ?></p>
                </div>
            </div>

            <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-teal/5 border border-gray-50">
                <h3 class="text-2xl font-black text-school-teal tracking-tighter uppercase mb-10">Staff Performance Oversight</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <p class="text-lg font-black text-school-teal">Teacher Attendance</p>
                            <span class="text-sm font-black text-green-500">98% Avg</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-school-teal h-full w-[98%] rounded-full"></div>
                        </div>
                    </div>
                    <div class="p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <p class="text-lg font-black text-school-teal">Syllabus Progress</p>
                            <span class="text-sm font-black text-school-emerald">75% Avg</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-school-emerald h-full w-[75%] rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>

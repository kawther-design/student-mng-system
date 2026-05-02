<?php
session_start();
require_once 'db_config.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
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
    <title>Dashboard | Al Huda Admin</title>
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
        .stat-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .stat-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(45, 62, 139, 0.12);
            border-color: rgba(45, 62, 139, 0.1);
        }
        .mesh-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(45, 62, 139, 0.03) 0%, transparent 70%);
            pointer-events: none;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #2D3E8B; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-blue/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-blue rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-blue/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-blue tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Management v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="admin-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Students</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Exam & Results</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-coral hover:bg-school-coral/5 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 w-full">
        <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-10 h-24 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <div class="lg:hidden w-10 h-10 bg-school-blue/5 rounded-xl flex items-center justify-center text-school-blue mr-2">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-school-blue tracking-tighter">System Overview</h2>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Administrator Portal & Control</p>
                </div>
            </div>
            <div class="flex items-center space-x-8">
                <div class="hidden md:flex flex-col items-end">
                    <p class="text-sm font-black text-school-blue uppercase tracking-widest"><?= $_SESSION['name'] ?></p>
                    <p class="text-[9px] font-black text-school-teal uppercase tracking-widest mt-1">Primary Administrator</p>
                </div>
                <div class="w-14 h-14 rounded-[1.5rem] bg-school-blue/5 p-1 border-2 border-school-blue/10">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=2D3E8B&color=fff" class="w-full h-full rounded-[1.2rem] shadow-lg" alt="">
                </div>
            </div>
        </header>

        <div class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div class="stat-card p-10 rounded-[4rem] relative overflow-hidden group">
                    <div class="mesh-glow"></div>
                    <div class="p-4 bg-school-teal/10 text-school-teal w-fit rounded-2xl mb-8 group-hover:scale-110 transition-transform duration-500"><i data-lucide="layout" class="w-7 h-7"></i></div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.3em]">Active Forms</h4>
                    <p class="text-4xl font-black text-school-blue mt-2"><?= $activeSections ?> <span class="text-lg text-gray-300 ml-1">Classes</span></p>
                </div>
                <div class="stat-card p-10 rounded-[4rem] relative overflow-hidden group">
                    <div class="mesh-glow"></div>
                    <div class="p-4 bg-school-purple/10 text-school-purple w-fit rounded-2xl mb-8 group-hover:scale-110 transition-transform duration-500"><i data-lucide="shield" class="w-7 h-7"></i></div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.3em]">Total Staff</h4>
                    <p class="text-4xl font-black text-school-blue mt-2"><?= $totalTeachers ?> <span class="text-lg text-gray-300 ml-1">Users</span></p>
                </div>
                <div class="stat-card bg-school-blue p-10 rounded-[4rem] relative overflow-hidden group border-none shadow-2xl shadow-school-blue/30 text-white">
                    <div class="p-4 bg-white/10 text-white w-fit rounded-2xl mb-8 group-hover:scale-110 transition-transform duration-500"><i data-lucide="users" class="w-7 h-7"></i></div>
                    <h4 class="text-white/40 font-black text-[10px] uppercase tracking-[0.3em]">Students</h4>
                    <p class="text-5xl font-black text-white mt-2 tracking-tighter"><?= $totalStudents ?></p>
                </div>
                <div class="stat-card p-10 rounded-[4rem] relative overflow-hidden group">
                    <div class="mesh-glow"></div>
                    <div class="p-4 bg-school-yellow/10 text-school-yellow w-fit rounded-2xl mb-8 group-hover:scale-110 transition-transform duration-500"><i data-lucide="activity" class="w-7 h-7"></i></div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.3em]">System Health</h4>
                    <p class="text-4xl font-black text-school-blue mt-2">100<span class="text-lg text-gray-300 ml-1">%</span></p>
                </div>
            </div>

            <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-blue/5 border border-gray-50 relative overflow-hidden">
                <div class="flex items-center justify-between mb-12">
                    <div>
                        <h3 class="text-3xl font-black text-school-blue tracking-tighter">Recent Student Activity</h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-2">Latest additions to the academic record</p>
                    </div>
                    <a href="manage-students.php" class="px-8 py-4 bg-school-blue text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-school-blue/20 hover:scale-105 transition-all">View All Students</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-black text-gray-300 uppercase tracking-[0.3em] border-b border-gray-100">
                                <th class="pb-8 pl-4">Full Student Name</th>
                                <th class="pb-8">Academic Level</th>
                                <th class="pb-8">Resident Area</th>
                                <th class="pb-8 text-right pr-4">Student ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($recentStudents as $s): ?>
                            <tr class="group hover:bg-school-blue/[0.02] transition-all cursor-pointer">
                                <td class="py-8 pl-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-school-blue group-hover:bg-school-blue group-hover:text-white transition-all">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </div>
                                        <span class="font-black text-school-blue text-lg group-hover:translate-x-2 transition-transform"><?= htmlspecialchars($s->name) ?></span>
                                    </div>
                                </td>
                                <td class="py-8">
                                    <span class="px-4 py-1.5 bg-school-teal/10 text-school-teal text-[10px] font-black uppercase tracking-widest rounded-full"><?= htmlspecialchars($s->form ?? 'N/A') ?></span>
                                </td>
                                <td class="py-8">
                                    <span class="px-4 py-1.5 bg-school-purple/10 text-school-purple text-[10px] font-black uppercase tracking-widest rounded-full"><?= htmlspecialchars($s->neighborhood ?? 'N/A') ?></span>
                                </td>
                                <td class="py-8 text-right pr-4">
                                    <span class="text-sm font-black text-gray-400 group-hover:text-school-blue">#<?= $s->student_id ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

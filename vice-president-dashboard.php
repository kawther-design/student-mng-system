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
    <title>VP Dashboard | Al Huda School</title>
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
                            teal: '#1DBF92',
                            emerald: '#059669',
                            blue: '#2D3E8B',
                            purple: '#8B5CF6',
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
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, #1DBF92 0%, #065F46 100%) !important;
            color: white !important; 
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
        .animate-in {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #1DBF92; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-teal/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-teal rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-teal/20">
                <i data-lucide="shield" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-teal tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">VP PORTAL v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="vice-president-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5 transition-all">
                <i data-lucide="user-plus" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Student Registration</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5 transition-all">
                <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Teachers Registration</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-teal hover:bg-school-teal/5 transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Exam & Results</span>
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
                <h2 class="text-2xl font-black text-school-teal tracking-tighter uppercase">Academic Oversight</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Vice President's Control Panel</p>
            </div>
            <div class="flex items-center space-x-8">
                <div class="hidden md:flex flex-col items-end">
                    <p class="text-sm font-black text-school-teal uppercase tracking-widest"><?= explode(' ', $_SESSION['name'])[0] ?></p>
                    <p class="text-[9px] font-black text-school-emerald uppercase tracking-widest mt-1">Vice President</p>
                </div>
                <div class="w-14 h-14 rounded-[1.5rem] bg-school-teal/5 p-1 border-2 border-school-teal/10 flex items-center justify-center">
                    <img src="https://ui-avatars.com/api/?name=VP&background=1DBF92&color=fff" class="w-full h-full rounded-[1.2rem] shadow-lg" alt="">
                </div>
            </div>
        </header>

        <div class="p-10 animate-in">
            <div class="mb-10 flex items-center justify-between">
                <div>
                    <h3 class="text-3xl font-black text-school-teal tracking-tighter uppercase">Institutional Overview</h3>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Monitoring Registration & Staff Performance</p>
                </div>
                <div class="px-6 py-3 bg-white rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-3">
                    <i data-lucide="shield-check" class="w-4 h-4 text-school-teal"></i>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Secure Access</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="glass-card p-10 rounded-[3.5rem] relative overflow-hidden group">
                    <div class="p-4 bg-school-teal/10 text-school-teal w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-school-teal/10">
                        <i data-lucide="graduation-cap" class="w-7 h-7"></i>
                    </div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Total Enrollment</h4>
                    <p class="text-5xl font-black text-school-teal mt-2 tracking-tighter"><?= $totalStudents ?> <span class="text-sm text-gray-300 font-bold uppercase ml-1">Students</span></p>
                    <i data-lucide="graduation-cap" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-teal/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
                </div>

                <div class="glass-card p-10 rounded-[3.5rem] relative overflow-hidden group">
                    <div class="p-4 bg-school-emerald/10 text-school-emerald w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-school-emerald/10">
                        <i data-lucide="book-open" class="w-7 h-7"></i>
                    </div>
                    <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-widest">Active Levels</h4>
                    <p class="text-5xl font-black text-school-teal mt-2 tracking-tighter"><?= $activeSections ?> <span class="text-sm text-gray-300 font-bold uppercase ml-1">Classes</span></p>
                    <i data-lucide="book-open" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-emerald/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
                </div>

                <div class="glass-card bg-school-teal p-10 rounded-[3.5rem] relative overflow-hidden group border-none shadow-2xl shadow-school-teal/30 text-white">
                    <div class="p-4 bg-white/20 text-white w-fit rounded-2xl mb-8 group-hover:scale-110 transition-all duration-500 shadow-lg shadow-black/10">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h4 class="text-white/40 font-black text-[10px] uppercase tracking-widest">Faculty Body</h4>
                    <p class="text-6xl font-black text-white mt-2 tracking-tighter"><?= $totalTeachers ?></p>
                    <i data-lucide="users" class="absolute -bottom-10 -right-10 w-48 h-48 text-white/5 rotate-12 group-hover:scale-110 transition-transform duration-700"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-teal/5 border border-gray-100 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-2xl font-black text-school-teal tracking-tighter uppercase">Academic Performance</h3>
                        <div class="w-10 h-10 bg-school-teal/10 rounded-xl flex items-center justify-center text-school-teal"><i data-lucide="trending-up" class="w-5 h-5"></i></div>
                    </div>
                    <div class="space-y-8">
                        <div class="p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <p class="text-lg font-black text-school-teal">Teachers Attendance</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Average Daily Presence</p>
                                </div>
                                <span class="text-sm font-black text-green-500 bg-green-50 px-4 py-2 rounded-full">98% Avg</span>
                            </div>
                            <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                <div class="bg-school-teal h-full w-[98%] rounded-full shadow-[0_0_10px_rgba(29,191,146,0.3)]"></div>
                            </div>
                        </div>
                        <div class="p-8 bg-gray-50/50 rounded-[2.5rem] border border-gray-100 group hover:bg-white hover:shadow-xl transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <p class="text-lg font-black text-school-teal">Syllabus Coverage</p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Curriculum progress status</p>
                                </div>
                                <span class="text-sm font-black text-school-emerald bg-school-emerald/5 px-4 py-2 rounded-full">75% Avg</span>
                            </div>
                            <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                                <div class="bg-school-emerald h-full w-[75%] rounded-full shadow-[0_0_10px_rgba(5,150,105,0.3)]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-teal/5 border border-gray-100 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-2xl font-black text-school-teal tracking-tighter uppercase">Recent Students</h3>
                        <a href="manage-students.php" class="p-2 text-school-teal hover:bg-school-teal/5 rounded-full transition-all"><i data-lucide="external-link" class="w-5 h-5"></i></a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($recentStudents as $s): ?>
                        <div class="flex items-center justify-between p-4 hover:bg-school-teal/[0.03] rounded-3xl transition-all group">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-school-teal/5 border border-school-teal/5 flex items-center justify-center text-school-teal group-hover:bg-school-teal group-hover:text-white transition-all overflow-hidden">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($s->name) ?>&background=1DBF92&color=fff" class="w-full h-full object-cover" alt="">
                                </div>
                                <div>
                                    <p class="font-black text-school-blue uppercase tracking-tighter"><?= htmlspecialchars($s->name) ?></p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5"><?= htmlspecialchars($s->form ?? 'N/A') ?></p>
                                </div>
                            </div>
                            <span class="text-[10px] font-black text-school-teal italic opacity-40">#<?= $s->student_id ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

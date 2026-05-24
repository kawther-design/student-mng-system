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
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Fetch counts for selected date
$counts = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0
];
$studentLists = [
    'Present' => [],
    'Absent' => [],
    'Late' => []
];

$attendanceRecords = $attendanceColl->find(['date' => $selectedDate])->toArray();
foreach ($attendanceRecords as $record) {
    if (isset($counts[$record->status])) {
        $counts[$record->status]++;
        $studentLists[$record->status][] = $record->student_name ?? 'Unknown Student';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Dashboard | Al Huda School</title>
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
        body { 
            font-family: 'Outfit', sans-serif; 
            background: radial-gradient(circle at top right, #F5F3FF, #ffffff, #F5F3FF);
            background-attachment: fixed;
        }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active { 
            background: rgba(255, 255, 255, 0.15);
            color: white; 
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2); 
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(139, 92, 246, 0.1);
        }
        .glass-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(139, 92, 246, 0.15);
            background: white;
            border-color: rgba(139, 92, 246, 0.3);
        }
        .bg-gradient-purple {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }
        .decorative-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, rgba(139, 92, 246, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            pointer-events: none;
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
        
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(45, 62, 139, 0.15);
            backdrop-filter: blur(12px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .modal-content {
            background: white;
            width: 100%;
            max-width: 900px;
            border-radius: 4rem;
            padding: 4rem;
            box-shadow: 0 50px 100px -20px rgba(45, 62, 139, 0.2);
            animation: modalScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalScale {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #8B5CF6; border-radius: 10px; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <div class="decorative-blob top-[-200px] right-[-200px]"></div>
    <div class="decorative-blob bottom-[-200px] left-[100px]"></div>

    <!-- Sidebar Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-school-purple/20 backdrop-blur-sm hidden transition-opacity duration-300"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-school-purple to-purple-900 border-none flex flex-col p-8 shadow-2xl shadow-purple-900/30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-white rounded-[1.2rem] flex items-center justify-center shadow-xl">
                <i data-lucide="presentation" class="text-school-purple w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-purple-200 uppercase tracking-[0.3em] mt-1">Teachers Portal v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="teacher-dashboard.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest text-white">Dashboard</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-purple-200 hover:text-white hover:bg-white/10 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-white/10">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-300 hover:bg-red-500 hover:text-white transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-6 md:p-10 animate-in">
        <header class="flex flex-col md:flex-row md:justify-between md:items-center mb-16 gap-6">
            <div class="flex items-center space-x-6">
                <!-- Hamburger Menu Button for mobile -->
                <button id="sidebar-toggle" class="lg:hidden p-3 bg-white hover:bg-gray-50 rounded-2xl shadow-md border border-purple-100 flex items-center justify-center text-school-purple transition-all">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="w-12 h-12 md:w-16 md:h-16 bg-gradient-to-br from-school-purple to-purple-800 rounded-[1.2rem] md:rounded-[1.8rem] flex items-center justify-center shadow-2xl shadow-school-purple/30 group">
                    <i data-lucide="presentation" class="text-white w-6 h-6 md:w-8 md:h-8 group-hover:scale-110 transition-transform"></i>
                </div>
                <div>
                    <h2 class="text-2xl md:text-4xl font-black text-school-purple tracking-tighter uppercase leading-none">Dashboard</h2>
                    <p class="text-[9px] md:text-[10px] text-gray-400 font-black uppercase tracking-[0.4em] mt-1.5 flex items-center">
                        <span class="w-2 h-2 bg-school-orange rounded-full mr-2"></span>
                        Teachers Oversight
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-between md:justify-end space-x-6 bg-white md:bg-transparent p-4 md:p-0 rounded-3xl border border-purple-100/50 md:border-none shadow-sm md:shadow-none">
                <div class="text-left md:text-right">
                    <p class="text-sm font-black text-school-purple uppercase tracking-widest leading-none"><?= explode(' ', $_SESSION['name'])[0] ?></p>
                    <p class="text-[9px] font-black text-school-orange uppercase tracking-widest mt-2 flex items-center md:justify-end">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2"></span>
                        Active Teachers
                    </p>
                </div>
                <div class="w-12 h-12 md:w-16 md:h-16 rounded-[1.4rem] md:rounded-[1.8rem] bg-white p-1.5 border-2 border-school-purple/10 flex items-center justify-center shadow-lg">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=8B5CF6&color=fff" class="w-full h-full rounded-[1rem] md:rounded-[1.4rem]" alt="">
                </div>
            </div>
        </header>

        <div class="mb-12">
            <div class="flex items-center space-x-4">
                <h3 class="text-2xl font-black text-school-blue uppercase tracking-tight">Attendance Analytics</h3>
                <div class="h-px flex-1 bg-gradient-to-r from-school-purple/20 to-transparent"></div>
            </div>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-2 italic">Real-time status tracking for current academic cycle</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <div onclick="openStatusModal('Present')" class="glass-card p-8 rounded-[2.5rem] relative group cursor-pointer hover:bg-green-50/50 transition-all border-green-100/50 overflow-hidden flex flex-col justify-between h-48">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i data-lucide="check-circle" class="w-32 h-32 text-green-500 transform translate-x-4 -translate-y-4"></i>
                </div>
                <div class="flex items-start justify-between">
                    <div class="p-4 bg-green-500 text-white w-fit rounded-2xl shadow-xl shadow-green-500/20">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                    <div class="text-right">
                        <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.2em]">Present Students</h4>
                        <p class="text-5xl font-black text-green-500 tracking-tighter mt-1"><?= $counts['Present'] ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-auto">
                    <div class="flex items-center text-[9px] font-black text-green-600 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0">
                        <span class="bg-green-100 px-3 py-1 rounded-full">View Roster</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </div>
            </div>

            <div onclick="openStatusModal('Absent')" class="glass-card p-8 rounded-[2.5rem] relative group cursor-pointer hover:bg-red-50/50 transition-all border-red-100/50 overflow-hidden flex flex-col justify-between h-48">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i data-lucide="x-circle" class="w-32 h-32 text-red-500 transform translate-x-4 -translate-y-4"></i>
                </div>
                <div class="flex items-start justify-between">
                    <div class="p-4 bg-red-500 text-white w-fit rounded-2xl shadow-xl shadow-red-500/20">
                        <i data-lucide="x-circle" class="w-6 h-6"></i>
                    </div>
                    <div class="text-right">
                        <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.2em]">Absent Students</h4>
                        <p class="text-5xl font-black text-red-500 tracking-tighter mt-1"><?= $counts['Absent'] ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-auto">
                    <div class="flex items-center text-[9px] font-black text-red-600 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0">
                        <span class="bg-red-100 px-3 py-1 rounded-full">View Roster</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </div>
            </div>

            <div onclick="openStatusModal('Late')" class="glass-card p-8 rounded-[2.5rem] relative group cursor-pointer hover:bg-orange-50/50 transition-all border-orange-100/50 overflow-hidden flex flex-col justify-between h-48">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i data-lucide="clock" class="w-32 h-32 text-school-orange transform translate-x-4 -translate-y-4"></i>
                </div>
                <div class="flex items-start justify-between">
                    <div class="p-4 bg-school-orange text-white w-fit rounded-2xl shadow-xl shadow-school-orange/20">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                    <div class="text-right">
                        <h4 class="text-gray-400 font-black text-[10px] uppercase tracking-[0.2em]">Late Students</h4>
                        <p class="text-5xl font-black text-school-orange tracking-tighter mt-1"><?= $counts['Late'] ?></p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-auto">
                    <div class="flex items-center text-[9px] font-black text-orange-600 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0">
                        <span class="bg-orange-100 px-3 py-1 rounded-full">View Roster</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 ml-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white/60 backdrop-blur-2xl rounded-[5rem] p-16 shadow-2xl shadow-school-purple/10 border border-white/50 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-96 h-96 bg-school-purple/5 rounded-full -mr-48 -mt-48 transition-transform group-hover:scale-110 duration-1000"></div>
            <div class="flex items-center justify-between mb-16 relative z-10">
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
                </div>
            </div>
        </div>

        <!-- Attendance Detail Modal -->
        <div id="attendanceModal" class="modal-overlay" style="<?= isset($_GET['view']) ? 'display: flex;' : 'display: none;' ?>">
            <div class="modal-content">
                <div class="flex items-center justify-between mb-12">
                    <div>
                        <h3 class="text-3xl font-black text-school-blue uppercase tracking-tighter">
                            Students <span class="text-school-purple"><?= $_GET['view'] ?? '' ?></span>
                        </h3>
                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-1">Direct Calendar & Roster Oversight</p>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="relative group">
                            <span class="absolute -top-6 left-4 text-[8px] font-black text-gray-300 uppercase tracking-widest">Select Date</span>
                            <input type="date" id="modalDate" value="<?= $selectedDate ?>" onchange="updateModalDate(this.value)" class="bg-gray-50 border-2 border-transparent focus:border-school-purple/20 rounded-2xl py-3 px-6 text-xs font-black text-school-purple outline-none cursor-pointer hover:bg-gray-100 transition-all">
                        </div>
                        <button onclick="closeModal()" class="p-4 bg-red-50 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>

                <div class="overflow-y-auto max-h-[400px] pr-4 custom-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                                <th class="pb-6">Student Full Name</th>
                                <th class="pb-6">Status</th>
                                <th class="pb-6 text-right">Reference Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php 
                            $viewStatus = $_GET['view'] ?? '';
                            $modalStudents = $studentLists[$viewStatus] ?? [];
                            foreach ($modalStudents as $name): 
                            ?>
                            <tr class="group hover:bg-school-purple/[0.02] transition-all">
                                <td class="py-6">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-xl bg-school-purple/5 flex items-center justify-center text-school-purple">
                                            <i data-lucide="user" class="w-5 h-5"></i>
                                        </div>
                                        <span class="text-sm font-black text-school-blue uppercase tracking-tight"><?= htmlspecialchars($name) ?></span>
                                    </div>
                                </td>
                                <td class="py-6">
                                    <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest
                                        <?= $viewStatus === 'Present' ? 'bg-green-50 text-green-500' : ($viewStatus === 'Absent' ? 'bg-red-50 text-red-500' : 'bg-orange-50 text-orange-500') ?>">
                                        <?= $viewStatus ?>
                                    </span>
                                </td>
                                <td class="py-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-300"></i>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase"><?= date('M d, Y', strtotime($selectedDate)) ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($modalStudents)): ?>
                                <tr>
                                    <td colspan="3" class="py-20 text-center text-gray-300 font-black uppercase tracking-widest opacity-30">
                                        No <?= strtolower($viewStatus) ?> records found for this date
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (sidebarToggle && sidebar && sidebarOverlay) {
            const toggleSidebar = () => {
                const isOpen = sidebar.classList.contains('translate-x-0');
                if (isOpen) {
                    sidebar.classList.remove('translate-x-0');
                    sidebar.classList.add('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    sidebarOverlay.classList.remove('hidden');
                }
            };

            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        function openStatusModal(status) {
            const url = new URL(window.location);
            url.searchParams.set('view', status);
            window.location.href = url.href;
        }
        function closeModal() {
            const url = new URL(window.location);
            url.searchParams.delete('view');
            window.location.href = url.href;
        }
        function updateModalDate(date) {
            const url = new URL(window.location);
            url.searchParams.set('date', date);
            window.location.href = url.href;
        }
    </script>
</body>
</html>

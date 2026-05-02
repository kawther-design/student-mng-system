<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$collection = $database->getCollection('exams');

// Handle Add Exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_exam') {
    $examData = [
        'name' => $_POST['name'],
        'type' => $_POST['type'],
        'date' => $_POST['date'],
        'status' => 'Upcoming',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    $collection->insertOne($examData);
    header('Location: manage-exams.php?msg=Exam Created');
    exit;
}

// Fetch Exams
$exams = $collection->find([], ['sort' => ['date' => 1]])->toArray();

$dashboardUrl = 'admin-dashboard.php';
$accentColor = '#2D3E8B'; // Default Admin Blue

if ($_SESSION['role'] === 'Teacher') {
    $dashboardUrl = 'teacher-dashboard.php';
    $accentColor = '#8B5CF6'; // Purple
} elseif ($_SESSION['role'] === 'Vice President') {
    $dashboardUrl = 'vice-president-dashboard.php';
    $accentColor = '#1DBF92'; // Teal
} elseif ($_SESSION['role'] === 'Parent') {
    $dashboardUrl = 'parent-dashboard.php';
    $accentColor = '#F97316'; // Orange
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam & Results | Al Huda Control</title>
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
                            purple: '#8B5CF6',
                            orange: '#F97316',
                            accent: '<?= $accentColor ?>',
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
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, <?= $accentColor ?>dd 100%);
            color: white; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
        .glass-card { 
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover { transform: translateY(-8px); box-shadow: 0 30px 60px -12px rgba(45, 62, 139, 0.08); }
        .modal-hidden { opacity: 0; pointer-events: none; transform: scale(0.95); }
        .modal-active { opacity: 1; pointer-events: auto; transform: scale(1); }
        .transition-modal { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <!-- Add Exam Modal -->
    <div id="addExamModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-accent/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-lg rounded-[4rem] p-12 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Schedule Exam</h3>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mb-12">Create a new assessment cycle</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="add_exam">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Examination Title</label>
                    <input type="text" name="name" required placeholder="e.g. Midterm 2026" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                </div>
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Exam Category</label>
                        <select name="type" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer">
                            <option>Midterm</option>
                            <option>Final</option>
                            <option>Monthly</option>
                            <option>Quiz</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Scheduled Date</label>
                        <input type="date" name="date" required class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue">
                    </div>
                </div>
                <button type="submit" class="w-full py-6 bg-school-blue text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-blue/20 hover:scale-[1.02] transition-all">Create Schedule</button>
            </form>
        </div>
    </div>

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-accent/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-accent rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-accent/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-accent tracking-tighter uppercase">Al Huda</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Academy v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="<?= $dashboardUrl ?>" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="<?= $_SESSION['role'] === 'Vice President' ? 'user-plus' : 'users' ?>" class="w-5 h-5"></i>
                <span class="font-bold text-sm"><?= $_SESSION['role'] === 'Vice President' ? 'Student Registration' : 'Students' ?></span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="<?= $_SESSION['role'] === 'Vice President' ? 'users' : 'shield-check' ?>" class="w-5 h-5"></i>
                <span class="font-bold text-sm"><?= $_SESSION['role'] === 'Vice President' ? 'Teachers Registration' : 'Users' ?></span>
            </a>
            <?php if ($_SESSION['role'] !== 'Vice President'): ?>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <?php endif; ?>
            <a href="manage-exams.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest"><?= $_SESSION['role'] === 'Vice President' ? 'Exam & Result' : 'Exam & Results' ?></span>
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
            <div>
                <h2 class="text-2xl font-black text-school-accent tracking-tighter uppercase"><?= $_SESSION['role'] === 'Vice President' ? 'Exam & Result' : 'Exam Management' ?></h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Academic Assessment Cycles</p>
            </div>
            <button onclick="toggleModal()" class="bg-school-accent px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-accent/20 hover:scale-105 transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Schedule Exam</span>
            </button>
        </header>

        <div class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
                <div class="glass-card p-10 rounded-[4rem]">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Total Cycles</h4>
                    <p class="text-4xl font-black text-school-accent tracking-tighter"><?= count($exams) ?></p>
                </div>
                <div class="glass-card p-10 rounded-[4rem]">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Active Assessments</h4>
                    <p class="text-4xl font-black text-school-teal tracking-tighter">1</p>
                </div>
                <div class="glass-card p-10 rounded-[4rem]">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Completion Rate</h4>
                    <p class="text-4xl font-black text-school-coral tracking-tighter">85%</p>
                </div>
            </div>

            <div class="bg-white rounded-[4rem] p-12 shadow-xl shadow-school-accent/5 border border-gray-50">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                                <th class="pb-10 pl-4">Examination Title</th>
                                <th class="pb-10">Category</th>
                                <th class="pb-10">Scheduled Date</th>
                                <th class="pb-10">Status</th>
                                <th class="pb-10 text-right pr-4">Management</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($exams as $exam): ?>
                            <tr class="group hover:bg-school-accent/[0.03] transition-all duration-500">
                                <td class="py-8 pl-4">
                                    <p class="font-black text-school-accent uppercase tracking-tight"><?= htmlspecialchars($exam->name) ?></p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Standard Academic Cycle</p>
                                </td>
                                <td class="py-8">
                                    <span class="px-5 py-2 bg-school-accent/5 text-school-accent rounded-full text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars($exam->type) ?></span>
                                </td>
                                <td class="py-8 text-sm text-gray-400 font-bold"><?= date('M d, Y', strtotime($exam->date)) ?></td>
                                <td class="py-8">
                                    <div class="flex items-center text-[9px] font-black uppercase tracking-widest <?= $exam->status === 'Upcoming' ? 'text-school-accent' : 'text-school-teal' ?>">
                                        <span class="w-2 h-2 rounded-full mr-3 <?= $exam->status === 'Upcoming' ? 'bg-school-accent' : 'bg-school-teal' ?> animate-pulse"></span>
                                        <?= $exam->status ?>
                                    </div>
                                </td>
                                <td class="py-8 text-right pr-4">
                                    <div class="flex items-center justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                                        <button class="w-12 h-12 bg-school-accent/5 text-school-accent rounded-[1.2rem] flex items-center justify-center hover:bg-school-accent hover:text-white transition-all"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                        <button class="w-12 h-12 bg-school-coral/5 text-school-coral rounded-[1.2rem] flex items-center justify-center hover:bg-school-coral hover:text-white transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleModal() {
            const modal = document.getElementById('addExamModal');
            modal.classList.toggle('modal-hidden');
            modal.classList.toggle('modal-active');
        }
    </script>
</body>
</html>

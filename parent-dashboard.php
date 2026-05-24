<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Parent') {
    if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Vice President') {
        header('Location: login.php');
        exit;
    }
}

// Check if search form is submitted
$search_student = null;
$search_error = '';
$attendance_records = [];
$exam_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id_raw = trim($_POST['student_id']);
    $password_raw = $_POST['password'] ?? '';
    
    $studentColl = $database->getCollection('students');
    $student = $studentColl->findOne(['student_id' => $student_id_raw]);
    
    if ($student) {
        $is_valid = false;
        if (isset($student->password)) {
            $is_valid = password_verify($password_raw, $student->password);
        } else {
            $is_valid = ($password_raw === '1234') || ($password_raw === $student->student_id) || ($password_raw === ($student->student_phone ?? ''));
        }
        if ($is_valid) {
            $search_student = $student;
            // Fetch Attendance
            $attColl = $database->getCollection('attendance');
            $attendance_records = $attColl->find(['student_id' => (string)$student->_id], ['sort' => ['date' => -1]])->toArray();
            
            // Fetch Exams
            $resultsColl = $database->getCollection('results');
            $exam_results = $resultsColl->find(['student_id' => (string)$student->_id])->toArray();
        } else {
            $search_error = 'Access Denied. Incorrect password.';
        }
    } else {
        $search_error = 'Student ID not found.';
    }
} else {
    // Fetch children associated with this parent (fallback)
    $collection = $database->getCollection('students');
    $parentName = $_SESSION['name'];
    $children = $collection->find(['parent_name' => $parentName])->toArray();
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
    <link href="global.css" rel="stylesheet">
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
                            teal: '#1DBF92',
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
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <!-- Sidebar Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-school-coral/20 backdrop-blur-sm hidden transition-opacity duration-300"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-coral/5 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
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
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">My Children</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-coral transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-6 md:p-10">
        <header class="flex flex-col md:flex-row md:justify-between md:items-center mb-10 gap-6">
            <div class="flex items-center space-x-4">
                <!-- Hamburger Menu Button for mobile -->
                <button id="sidebar-toggle" class="lg:hidden p-3 bg-white hover:bg-gray-50 rounded-2xl shadow-md border border-orange-100 flex items-center justify-center text-school-coral transition-all">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-school-coral tracking-tighter uppercase leading-none">Welcome, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                    <p class="text-[9px] md:text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mt-1.5">Parent Portal | My Children Tracking</p>
                </div>
            </div>
            <div class="flex items-center justify-between md:justify-end space-x-6 bg-white md:bg-transparent p-4 md:p-0 rounded-3xl border border-orange-100/50 md:border-none shadow-sm md:shadow-none">
                <div class="text-right">
                    <p class="text-sm font-black text-school-coral uppercase tracking-widest leading-none"><?= explode(' ', $_SESSION['name'])[0] ?></p>
                    <p class="text-[9px] font-black text-school-coral uppercase tracking-widest mt-2 flex items-center justify-end">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2"></span>
                        Active Parent
                    </p>
                </div>
                <div class="w-12 h-12 md:w-14 md:h-14 rounded-[1.2rem] md:rounded-[1.5rem] bg-school-coral/10 p-1 border-2 border-school-coral/10 flex items-center justify-center text-school-coral shadow-lg">
                    <i data-lucide="user" class="w-6 h-6 md:w-7 md:h-7"></i>
                </div>
            </div>
        </header>

        <!-- Search Form -->
        <div class="mb-12 bg-white p-8 rounded-[3rem] shadow-xl shadow-school-coral/5 border border-gray-50 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-black text-school-blue tracking-tighter uppercase border-l-4 border-school-coral pl-4">Track Student Progress</h3>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest hidden md:block">Enter details to view attendance & exams</p>
            </div>
            <form method="POST" autocomplete="off" class="flex flex-col lg:flex-row gap-6">
                <div class="flex-1 relative">
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 ml-4">Your ID</label>
                    <i data-lucide="user" class="absolute left-6 top-[38px] text-school-coral w-5 h-5"></i>
                    <input type="text" name="student_id" required value="" autocomplete="off" placeholder="Enter Student ID" class="w-full bg-gray-50 border-none rounded-[2rem] py-4 pl-16 pr-6 outline-none text-sm font-black text-school-blue uppercase tracking-widest focus:ring-2 focus:ring-school-coral/20">
                </div>
                <div class="flex-1 relative">
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 ml-4">Your Password</label>
                    <i data-lucide="lock" class="absolute left-6 top-[38px] text-school-coral w-5 h-5"></i>
                    <input type="password" name="password" required value="" autocomplete="new-password" placeholder="••••" class="w-full bg-gray-50 border-none rounded-[2rem] py-4 pl-16 pr-6 outline-none text-sm font-black text-school-blue uppercase tracking-widest focus:ring-2 focus:ring-school-coral/20">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full lg:w-auto py-4 px-10 h-[52px] bg-school-coral text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-lg shadow-school-coral/30 hover:scale-[1.02] transition-all flex items-center justify-center space-x-3">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>View Reports</span>
                    </button>
                </div>
            </form>
            <?php if ($search_error): ?>
                <div class="mt-6 p-4 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <p class="text-[10px] font-black uppercase tracking-widest"><?= $search_error ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($search_student): ?>
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between animate-fade-in gap-4" style="animation-delay: 0.1s">
                <h3 class="text-2xl font-black text-school-blue uppercase tracking-tighter border-l-4 border-school-coral pl-4 flex items-center">
                    Reports for <span class="text-school-coral ml-2"><?= htmlspecialchars($search_student->name) ?></span>
                </h3>
                <a href="parent-dashboard.php" class="py-2 px-6 bg-white border border-gray-100 rounded-xl text-[9px] font-black text-gray-400 hover:text-school-coral hover:border-school-coral/20 uppercase tracking-widest transition-all text-center">Clear Search</a>
            </div>

            <!-- Student Profile Information -->
            <div class="mb-10 glass-card rounded-[3rem] p-8 flex flex-col md:flex-row items-center md:items-start justify-between gap-6 animate-fade-in" style="animation-delay: 0.15s">
                <div class="flex items-center space-x-6">
                    <div class="w-24 h-24 rounded-[2.5rem] bg-school-coral/10 p-1 border-2 border-school-coral/5 flex items-center justify-center overflow-hidden">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($search_student->name) ?>&background=F97316&color=fff&size=128" alt="<?= htmlspecialchars($search_student->name) ?>" class="w-full h-full rounded-[2.3rem] object-cover">
                    </div>
                    <div>
                        <h4 class="text-2xl font-black text-school-blue tracking-tighter uppercase mb-1"><?= htmlspecialchars($search_student->name) ?></h4>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-1">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Student ID:</span>
                                <span class="text-[10px] font-black text-school-coral">#<?= htmlspecialchars($search_student->student_id ?? 'N/A') ?></span>
                            </div>
                            <div class="w-1 h-1 bg-gray-300 rounded-full"></div>
                            <div class="flex items-center space-x-1">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Class:</span>
                                <span class="text-[10px] font-black text-school-blue uppercase"><?= htmlspecialchars($search_student->form ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="bg-school-coral/5 px-6 py-4 rounded-3xl border border-school-coral/10 text-center">
                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Gender</p>
                        <p class="text-xs font-black text-school-coral uppercase"><?= $search_student->gender ?? 'N/A' ?></p>
                    </div>
                    <div class="bg-school-blue/5 px-6 py-4 rounded-3xl border border-school-blue/10 text-center">
                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</p>
                        <p class="text-xs font-black text-school-blue uppercase"><?= $search_student->status ?? 'Active' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                <!-- Attendance Report -->
                <div class="glass-card rounded-[3rem] p-8 animate-fade-in" style="animation-delay: 0.2s">
                    <div class="flex justify-between items-center mb-8">
                        <h4 class="text-xl font-black text-school-blue uppercase tracking-tighter flex items-center">
                            <div class="w-10 h-10 rounded-xl bg-school-coral/10 text-school-coral flex items-center justify-center mr-4">
                                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                            </div>
                            Attendance History
                        </h4>
                        <div class="flex space-x-6 text-right">
                            <div>
                                <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest">Total Present</p>
                                <p class="text-lg font-black text-school-teal"><?= count(array_filter($attendance_records, fn($a) => $a->status === 'Present')) ?></p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest">Total Absent</p>
                                <p class="text-lg font-black text-red-500"><?= count(array_filter($attendance_records, fn($a) => $a->status === 'Absent')) ?></p>
                            </div>
                            <div>
                                <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest">Total Late</p>
                                <p class="text-lg font-black text-school-orange"><?= count(array_filter($attendance_records, fn($a) => $a->status === 'Late')) ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($attendance_records)): ?>
                        <div class="p-10 text-center bg-gray-50 rounded-3xl border border-gray-100">
                            <i data-lucide="inbox" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">No attendance records found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                            <?php foreach ($attendance_records as $att): ?>
                            <div class="flex justify-between items-center p-5 bg-white border border-gray-100 rounded-2xl hover:border-school-coral/20 transition-colors">
                                <div>
                                    <p class="text-sm font-black text-school-blue uppercase tracking-tighter"><?= date('F d, Y', strtotime($att->date)) ?></p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Marked by: <?= htmlspecialchars($att->marked_by ?? 'Admin') ?></p>
                                </div>
                                <?php 
                                    $bg = $att->status === 'Present' ? 'bg-school-teal/10 text-school-teal' : ($att->status === 'Absent' ? 'bg-red-100 text-red-500' : 'bg-school-coral/10 text-school-coral'); 
                                    $icon = $att->status === 'Present' ? 'check-circle' : ($att->status === 'Absent' ? 'x-circle' : 'clock');
                                ?>
                                <div class="px-4 py-2 rounded-xl flex items-center space-x-2 <?= $bg ?>">
                                    <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest"><?= $att->status ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Examinations Report -->
                <div class="glass-card rounded-[3rem] p-8 animate-fade-in" style="animation-delay: 0.3s">
                    <div class="flex justify-between items-center mb-8">
                        <h4 class="text-xl font-black text-school-blue uppercase tracking-tighter flex items-center">
                            <div class="w-10 h-10 rounded-xl bg-school-blue/10 text-school-blue flex items-center justify-center mr-4">
                                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                            </div>
                            Examinations Result
                        </h4>
                        <div class="text-right">
                            <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest">Exams Taken</p>
                            <p class="text-lg font-black text-school-blue"><?= count($exam_results) ?></p>
                        </div>
                    </div>

                    <?php if (empty($exam_results)): ?>
                        <div class="p-10 text-center bg-gray-50 rounded-3xl border border-gray-100">
                            <i data-lucide="inbox" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">No exam results found.</p>
                        </div>
                    <?php else: 
                        $examColl = $database->getCollection('exams');
                    ?>
                        <div class="space-y-6 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                            <?php foreach ($exam_results as $res): 
                                $exam = $examColl->findOne(['_id' => new MongoDB\BSON\ObjectId($res->exam_id)]);
                            ?>
                            <div class="p-6 bg-white border border-gray-100 rounded-[2rem] hover:border-school-blue/20 transition-all">
                                <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-50">
                                    <div>
                                        <h5 class="text-lg font-black text-school-blue uppercase tracking-tighter"><?= htmlspecialchars($exam->name ?? 'Assessment') ?></h5>
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mt-1">Overall Percentage</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-black text-school-coral"><?= number_format($res->total_marks / 10, 1) ?>%</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-4 sm:grid-cols-5 gap-3">
                                    <?php 
                                    $subjects = [
                                        'arabic' => 'Ara', 'islamic' => 'Isl', 'biology' => 'Bio', 
                                        'physics' => 'Phy', 'mathematics' => 'Mat', 'chemistry' => 'Che', 
                                        'somali' => 'Som', 'english' => 'Eng', 'history' => 'His', 
                                        'geography' => 'Geo'
                                    ];
                                    foreach ($subjects as $key => $label): 
                                        $mark = $res->marks->$key ?? 0;
                                        if ($mark > 0 || true): // Show all to mimic transcript
                                    ?>
                                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1"><?= $label ?></p>
                                        <p class="text-xs font-black <?= $mark >= 50 ? 'text-school-blue' : 'text-school-coral' ?>"><?= $mark ?></p>
                                    </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Fallback: Display Registered Children if not searching -->
            <div class="mb-10">
                <h3 class="text-xl font-black text-school-blue uppercase tracking-widest border-l-4 border-school-coral pl-4">Your Registered Children</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
                <?php if (!empty($children)): ?>
                    <?php foreach ($children as $index => $child): ?>
                    <div class="glass-card rounded-[3rem] p-8 relative overflow-hidden animate-fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-8">
                                <div class="w-20 h-20 rounded-[2rem] bg-school-coral/10 p-1 border-2 border-school-coral/5 flex items-center justify-center overflow-hidden">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($child->name) ?>&background=F97316&color=fff&size=128" alt="<?= htmlspecialchars($child->name) ?>" class="w-full h-full rounded-[1.8rem] object-cover">
                                </div>
                                <span class="px-4 py-2 bg-school-blue text-white rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg"><?= htmlspecialchars($child->form) ?></span>
                            </div>

                            <div class="mb-8">
                                <h4 class="text-xl font-black text-school-blue tracking-tighter uppercase mb-1"><?= htmlspecialchars($child->name) ?></h4>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Student ID:</span>
                                    <span class="text-[10px] font-black text-school-coral italic">#<?= htmlspecialchars($child->student_id ?? 'N/A') ?></span>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-school-coral/5 p-4 rounded-3xl border border-school-coral/10 text-center">
                                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</p>
                                        <p class="text-[10px] font-black text-school-coral uppercase"><?= $child->status ?? 'Active' ?></p>
                                    </div>
                                    <div class="bg-school-blue/5 p-4 rounded-3xl border border-school-blue/10 text-center">
                                        <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Gender</p>
                                        <p class="text-[10px] font-black text-school-blue uppercase"><?= $child->gender ?? 'N/A' ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <i data-lucide="graduation-cap" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-coral/5 rotate-12"></i>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full glass-card rounded-[3rem] p-20 text-center">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="search-x" class="w-12 h-12 text-gray-300"></i>
                        </div>
                        <h4 class="text-2xl font-black text-school-blue uppercase tracking-tighter mb-2">No Children Found</h4>
                        <p class="text-gray-400 text-sm font-medium">Use the tracking form above to search for your child's reports.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
    </script>
</body>
</html>

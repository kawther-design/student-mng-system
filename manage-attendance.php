<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$form = isset($_GET['form']) ? (int)$_GET['form'] : 1;
// Handle Attendance Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    $collection = $database->getCollection('attendance');
    $date = $_POST['date'];
    $form = $_POST['form'];
    
    foreach ($_POST['attendance'] as $studentId => $status) {
        $studentForm = $_POST['student_forms'][$studentId] ?? $form;
        $collection->updateOne(
            ['student_id' => $studentId, 'date' => $date],
            ['$set' => [
                'student_id' => $studentId,
                'student_name' => $_POST['student_names'][$studentId],
                'date' => $date,
                'form' => $studentForm,
                'status' => $status,
                'marked_by' => $_SESSION['name'],
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]],
            ['upsert' => true]
        );
    }
    header("Location: manage-attendance.php?msg=Attendance Saved&form=$form&date=$date");
    exit;
}

// Fetch Students for selected Form
$form = isset($_GET['form']) ? $_GET['form'] : 'FORM 1 A';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$studentColl = $database->getCollection('students');

if (in_array($form, ['FORM 1', 'FORM 2', 'FORM 3', 'FORM 4'])) {
    $regexPattern = '^' . preg_quote($form, '/') . '.*';
} else {
    $regexPattern = '^' . preg_quote($form, '/') . '$';
}

$students = $studentColl->find(['form' => new MongoDB\BSON\Regex($regexPattern, 'i')], ['sort' => ['name' => 1]])->toArray();

// Hardcode all possible classes (Forms 1-4, Sections A-E)
$allClasses = [];
foreach (['FORM 1', 'FORM 2', 'FORM 3', 'FORM 4'] as $baseForm) {
    foreach (['A', 'B', 'C', 'D', 'E'] as $section) {
        $allClasses[] = $baseForm . ' ' . $section;
    }
}

// Fetch existing attendance for the date
$attColl = $database->getCollection('attendance');
$existingAtt = $attColl->find(['form' => new MongoDB\BSON\Regex($regexPattern, 'i'), 'date' => $date])->toArray();
$attStatus = [];
foreach ($existingAtt as $record) {
    $attStatus[$record->student_id] = $record->status;
}

$statusFilter = $_GET['status_filter'] ?? '';
if ($statusFilter) {
    $students = array_filter($students, function($student) use ($attStatus, $statusFilter) {
        $s_id = (string)$student->_id;
        $status = isset($attStatus[$s_id]) ? $attStatus[$s_id] : 'Present';
        return $status === $statusFilter;
    });
}

// Fetch history if requested
$historyRecords = [];
$historyStudent = null;
$historyStats = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
if (isset($_GET['history_id'])) {
    $historyStudent = $studentColl->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['history_id'])]);
    if ($historyStudent) {
        $historyRecords = $attColl->find(['student_id' => (string)$historyStudent->_id], ['sort' => ['date' => -1]])->toArray();
        foreach ($historyRecords as $hr) {
            if (isset($historyStats[$hr->status])) {
                $historyStats[$hr->status]++;
            }
        }
    }
}

// Determine Dashboard URL and Accent Color based on role
$dashboardUrl = 'admin-dashboard.php';
$accentColor = '#2D3E8B'; // Default Blue for Admin
if ($_SESSION['role'] === 'Teacher') {
    $dashboardUrl = 'teacher-dashboard.php';
    $accentColor = '#8B5CF6'; // Purple for Teacher
} elseif ($_SESSION['role'] === 'Parent') {
    $dashboardUrl = 'parent-dashboard.php';
    $accentColor = '#F97316'; // Orange for Parent
} elseif ($_SESSION['role'] === 'Vice President') {
    $dashboardUrl = 'vice-president-dashboard.php';
    $accentColor = '#1DBF92'; // Teal for VP
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Control | Al Huda</title>
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
                            accent: '<?= $accentColor ?>',
                            blue: '#2D3E8B',
                            teal: '#1DBF92',
                            coral: '#FF6B52',
                            purple: '#8B5CF6',
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
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, #1a255a 100%) !important;
            color: white !important; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
        .att-card { 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .att-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 30px 60px -12px rgba(45, 62, 139, 0.08);
        }
        .status-radio:checked + label {
            transform: scale(1.1);
            color: white;
            box-shadow: 0 15px 30px -5px currentColor;
        }
        .status-radio-present:checked + label { background-color: #1DBF92; color: #1DBF92; }
        .status-radio-absent:checked + label { background-color: #FF6B52; color: #FF6B52; }
        .status-radio-late:checked + label { background-color: #F97316; color: #F97316; }
        
        /* Specific label color fix when checked */
        .status-radio-present:checked + label { background-color: #1DBF92; color: white; }
        .status-radio-absent:checked + label { background-color: #FF6B52; color: white; }
        .status-radio-late:checked + label { background-color: #F97316; color: white; }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <?php if ($historyStudent): ?>
    <div id="historyModal" class="fixed inset-0 z-[60] flex items-center justify-center p-6 bg-school-blue/20 backdrop-blur-md">
        <div class="bg-white w-full max-w-3xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button onclick="location.href='manage-attendance.php?form=<?= urlencode($form) ?>&date=<?= $date ?>'" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Attendance History</h3>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mb-8"><?= htmlspecialchars($historyStudent->name) ?></p>

            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-green-50 p-6 rounded-3xl text-center">
                    <p class="text-2xl font-black text-green-500"><?= $historyStats['Present'] ?></p>
                    <p class="text-[9px] font-black text-green-600/50 uppercase tracking-widest mt-1">Present</p>
                </div>
                <div class="bg-red-50 p-6 rounded-3xl text-center">
                    <p class="text-2xl font-black text-red-500"><?= $historyStats['Absent'] ?></p>
                    <p class="text-[9px] font-black text-red-600/50 uppercase tracking-widest mt-1">Absent</p>
                </div>
                <div class="bg-orange-50 p-6 rounded-3xl text-center">
                    <p class="text-2xl font-black text-orange-500"><?= $historyStats['Late'] ?></p>
                    <p class="text-[9px] font-black text-orange-600/50 uppercase tracking-widest mt-1">Late</p>
                </div>
            </div>

            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                        <th class="pb-4">Date</th>
                        <th class="pb-4">Status</th>
                        <th class="pb-4 text-right">Marked By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($historyRecords)): ?>
                    <tr><td colspan="3" class="py-8 text-center text-gray-400 text-sm font-bold">No records found</td></tr>
                    <?php endif; ?>
                    <?php foreach ($historyRecords as $record): ?>
                    <tr>
                        <td class="py-4 text-sm font-bold text-school-accent"><?= htmlspecialchars($record->date) ?></td>
                        <td class="py-4">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest 
                                <?= $record->status === 'Present' ? 'bg-green-100 text-green-600' : ($record->status === 'Absent' ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600') ?>">
                                <?= htmlspecialchars($record->status) ?>
                            </span>
                        </td>
                        <td class="py-4 text-right text-xs font-bold text-gray-400"><?= htmlspecialchars($record->marked_by ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 backdrop-blur-sm hidden transition-opacity duration-300" style="background-color: <?= $accentColor ?>20;"></div>

    <?php if ($_SESSION['role'] === 'Teacher'): ?>
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
            <a href="teacher-dashboard.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-purple-200 hover:text-white hover:bg-white/10 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]" style="background: rgba(255, 255, 255, 0.15); color: white; box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1);">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest text-white">Attendance</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-white/10">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-300 hover:bg-red-500 hover:text-white transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>
    <?php else: ?>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-accent/5 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-accent rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-accent/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-accent tracking-tighter">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Management v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="<?= $dashboardUrl ?>" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            
            <?php if ($_SESSION['role'] === 'Vice President'): ?>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="user-plus" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Student Registration</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Teachers Registration</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Attendance</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] !== 'Teacher' && $_SESSION['role'] !== 'Vice President'): ?>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Exam & Results</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'Vice President'): ?>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Exam & Results</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-500 hover:bg-red-50 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>
    <?php endif; ?>

    <main class="flex-1 lg:ml-72 w-full">
        <form method="POST" class="w-full">
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="date" value="<?= $date ?>">
            <input type="hidden" name="form" value="<?= $form ?>">
            <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-6 md:px-10 h-24 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center space-x-4">
                    <button type="button" id="sidebar-toggle" class="lg:hidden p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl border border-gray-100 flex items-center justify-center transition-all" style="color: <?= $accentColor ?>;">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-school-accent tracking-tighter uppercase leading-none">Attendance</h2>
                        <p class="text-[9px] md:text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Daily Student Registry</p>
                    </div>
                </div>
                <button type="submit" name="save" class="px-6 md:px-10 py-4 bg-school-accent text-white rounded-[1.5rem] text-[9px] md:text-[10px] font-black uppercase tracking-widest shadow-xl shadow-school-accent/20 hover:scale-105 transition-all">
                    <i data-lucide="save" class="w-4 h-4 inline-block mr-2"></i>
                    Save
                </button>
            </header>

            <div class="p-8">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-10">
                    <div class="lg:col-span-2 bg-white p-8 rounded-[3rem] shadow-xl shadow-school-blue/5 border border-gray-50 flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Selected Class</span>
                                <select onchange="location.href='?form='+this.value+'&date=<?= $date ?>'" class="bg-gray-50 border-none rounded-2xl py-4 px-8 text-sm font-black text-school-accent uppercase tracking-tighter outline-none cursor-pointer hover:bg-gray-100 transition-all">
                                    <?php foreach (['FORM 1', 'FORM 2', 'FORM 3', 'FORM 4'] as $baseForm): ?>
                                        <option value="<?= htmlspecialchars($baseForm) ?>" <?= $form === $baseForm ? 'selected' : '' ?> class="font-black text-school-blue bg-school-blue/5">
                                            <?= htmlspecialchars($baseForm) ?> (All Sections)
                                        </option>
                                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $section): ?>
                                            <?php $className = $baseForm . ' ' . $section; ?>
                                            <option value="<?= htmlspecialchars($className) ?>" <?= $form === $className ? 'selected' : '' ?>>
                                                &nbsp;&nbsp;&nbsp;<?= htmlspecialchars($className) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="w-px h-12 bg-gray-100 mx-2"></div>
                            <div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Track Date</span>
                                <input type="date" value="<?= $date ?>" onchange="location.href='?form=<?= $form ?>&date='+this.value" class="bg-gray-50 border-none rounded-2xl py-4 px-8 text-sm font-black text-school-accent outline-none cursor-pointer hover:bg-gray-100 transition-all">
                            </div>
                        </div>
                    </div>

                    <a href="?form=<?= urlencode($form) ?>&date=<?= $date ?>&status_filter=<?= $statusFilter === 'Present' ? '' : 'Present' ?>" class="bg-white p-8 rounded-[3rem] shadow-xl shadow-school-blue/5 border <?= $statusFilter === 'Present' ? 'border-green-500 ring-2 ring-green-500/20' : 'border-gray-50' ?> flex items-center space-x-6 hover:scale-105 transition-all cursor-pointer">
                        <div class="w-14 h-14 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center">
                            <i data-lucide="check-circle-2" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Present</p>
                            <p class="text-2xl font-black text-green-500"><?= count(array_filter($attStatus, fn($s) => $s === 'Present')) ?></p>
                        </div>
                    </a>

                    <a href="?form=<?= urlencode($form) ?>&date=<?= $date ?>&status_filter=<?= $statusFilter === 'Absent' ? '' : 'Absent' ?>" class="bg-white p-8 rounded-[3rem] shadow-xl shadow-school-blue/5 border <?= $statusFilter === 'Absent' ? 'border-red-500 ring-2 ring-red-500/20' : 'border-gray-50' ?> flex items-center space-x-6 hover:scale-105 transition-all cursor-pointer">
                        <div class="w-14 h-14 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Absent</p>
                            <p class="text-2xl font-black text-red-500"><?= count(array_filter($attStatus, fn($s) => $s === 'Absent')) ?></p>
                        </div>
                    </a>

                    <a href="?form=<?= urlencode($form) ?>&date=<?= $date ?>&status_filter=<?= $statusFilter === 'Late' ? '' : 'Late' ?>" class="bg-white p-8 rounded-[3rem] shadow-xl shadow-school-blue/5 border <?= $statusFilter === 'Late' ? 'border-orange-500 ring-2 ring-orange-500/20' : 'border-gray-50' ?> flex items-center space-x-6 hover:scale-105 transition-all cursor-pointer">
                        <div class="w-14 h-14 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center">
                            <i data-lucide="clock" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Late</p>
                            <p class="text-2xl font-black text-orange-500"><?= count(array_filter($attStatus, fn($s) => $s === 'Late')) ?></p>
                        </div>
                    </a>
                </div>

                <div class="bg-white rounded-[3rem] p-6 md:p-10 shadow-xl shadow-school-blue/5 border border-gray-50">
                    <div class="overflow-x-auto w-full">
                        <table class="w-full text-left min-w-[600px]">
                            <thead>
                                <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                                    <th class="pb-8">Student Name</th>
                                    <th class="pb-8">Student Phone</th>
                                    <th class="pb-8 text-center">Status Selection</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="3" class="py-12 text-center font-bold text-gray-300 uppercase tracking-widest">No students found in this form</td></tr>
                                <?php endif; ?>
                                <?php foreach ($students as $student): 
                                    $s_id = (string)$student->_id;
                                    $status = isset($attStatus[$s_id]) ? $attStatus[$s_id] : 'Present';
                                ?>
                                <tr class="group hover:bg-school-accent/[0.02] transition-all">
                                    <td class="py-8">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-2xl bg-school-accent/10 flex items-center justify-center text-school-accent">
                                                <i data-lucide="user-check" class="w-6 h-6"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-black text-school-accent tracking-tighter uppercase"><?= htmlspecialchars($student->name) ?></h4>
                                                <a href="?form=<?= urlencode($form) ?>&date=<?= $date ?>&history_id=<?= $s_id ?>" class="mt-1 inline-flex items-center space-x-1 text-[9px] font-black text-school-purple hover:text-school-coral uppercase tracking-widest transition-all">
                                                    <i data-lucide="history" class="w-3 h-3"></i>
                                                    <span>View History</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-8">
                                        <div class="flex items-center text-gray-400 font-black text-[10px] uppercase tracking-widest">
                                            <i data-lucide="phone-forwarded" class="w-3.5 h-3.5 mr-2 opacity-30"></i>
                                            <?= htmlspecialchars($student->student_phone ?? 'N/A') ?>
                                        </div>
                                    </td>
                                    <td class="py-8">
                                        <div class="flex items-center justify-center space-x-4 lg:space-x-8">
                                            <?php 
                                            $options = [
                                                'Present' => ['S' => 'P', 'I' => 'check-circle-2', 'C' => 'present'], 
                                                'Absent' => ['S' => 'A', 'I' => 'x-circle', 'C' => 'absent'], 
                                                'Late' => ['S' => 'L', 'I' => 'clock', 'C' => 'late']
                                            ];
                                            foreach ($options as $full => $meta): 
                                            ?>
                                            <div class="flex flex-col items-center">
                                                <input type="hidden" name="student_names[<?= $s_id ?>]" value="<?= htmlspecialchars($student->name) ?>">
                                                <input type="hidden" name="student_forms[<?= $s_id ?>]" value="<?= htmlspecialchars($student->form) ?>">
                                                <input type="radio" id="att_<?= $s_id ?>_<?= $meta['S'] ?>" name="attendance[<?= $s_id ?>]" value="<?= $full ?>" <?= $status == $full ? 'checked' : '' ?> class="hidden peer status-radio status-radio-<?= $meta['C'] ?>">
                                                <label for="att_<?= $s_id ?>_<?= $meta['S'] ?>" class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 hover:bg-gray-100 transition-all cursor-pointer peer-checked:shadow-2xl">
                                                    <i data-lucide="<?= $meta['I'] ?>" class="w-6 h-6"></i>
                                                </label>
                                                <span class="text-[8px] font-black text-gray-400 mt-3 uppercase tracking-widest pointer-events-none"><?= $full ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
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

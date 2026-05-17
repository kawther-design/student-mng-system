<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Teacher') {
    header('Location: ' . ($_SESSION['role'] === 'Teacher' ? 'teacher-dashboard.php' : 'login.php'));
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $collection = $database->getCollection('students');
    
    // Combine Level and Section if they exist, otherwise use 'form'
    $formValue = isset($_POST['level']) && isset($_POST['section']) ? $_POST['level'] . ' ' . $_POST['section'] : $_POST['form'];

    $studentData = [
        'student_id' => $_POST['student_id'],
        'name' => $_POST['name'],
        'student_phone' => $_POST['student_phone'],
        'gender' => $_POST['gender'],
        'form' => trim($formValue),
        'neighborhood' => $_POST['neighborhood'],
        'parent_name' => $_POST['parent_name'],
        'parent_phone' => $_POST['parent_phone'],
        'status' => 'Active',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    $collection->insertOne($studentData);
    header('Location: manage-students.php?msg=Student Added');
    exit;
}

// Handle Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student') {
    $collection = $database->getCollection('students');
    $studentId = new MongoDB\BSON\ObjectId($_POST['_id']);
    
    // Combine Level and Section if they exist, otherwise use 'form'
    $formValue = isset($_POST['level']) && isset($_POST['section']) ? $_POST['level'] . ' ' . $_POST['section'] : $_POST['form'];

    $updateData = [
        'name' => $_POST['name'],
        'student_phone' => $_POST['student_phone'],
        'gender' => $_POST['gender'],
        'form' => trim($formValue),
        'neighborhood' => $_POST['neighborhood'],
        'parent_name' => $_POST['parent_name'],
        'parent_phone' => $_POST['parent_phone']
    ];
    
    $collection->updateOne(['_id' => $studentId], ['$set' => $updateData]);
    header('Location: manage-students.php?msg=Student Updated');
    exit;
}

// Handle Delete Student
if (isset($_GET['delete_id'])) {
    $collection = $database->getCollection('students');
    $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($_GET['delete_id'])]);
    header('Location: manage-students.php?msg=Student Deleted');
    exit;
}

// Fetch Students with filtering
$collection = $database->getCollection('students');

$filter = [];
$currentFilter = $_GET['class_filter'] ?? '';
if ($currentFilter) {
    // Match any form starting with the filter (e.g., "Form 1" matches "Form 1 A")
    $filter['form'] = new MongoDB\BSON\Regex('^' . preg_quote($currentFilter, '/'), 'i');
}

$students = $collection->find($filter, ['sort' => ['name' => 1]])->toArray();

// Hardcode classes and sections A-E for filtering dropdowns
$groupedClasses = [
    'Form 1' => ['Form 1 A', 'Form 1 B', 'Form 1 C', 'Form 1 D', 'Form 1 E'],
    'Form 2' => ['Form 2 A', 'Form 2 B', 'Form 2 C', 'Form 2 D', 'Form 2 E'],
    'Form 3' => ['Form 3 A', 'Form 3 B', 'Form 3 C', 'Form 3 D', 'Form 3 E'],
    'Form 4' => ['Form 4 A', 'Form 4 B', 'Form 4 C', 'Form 4 D', 'Form 4 E']
];

// Generate next Student ID (Strictly Sequential 4-digit)
$allStudents = $collection->find(['student_id' => ['$exists' => true]])->toArray();
$maxId = 1000;
foreach ($allStudents as $s) {
    $idVal = (int)$s->student_id;
    if ($idVal > $maxId && $idVal < 10000) { // Focus on 4-digit range
        $maxId = $idVal;
    }
}
$nextStudentId = (string)($maxId + 1);

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
    <title>Student Repository | Al Huda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, #1a255a 100%) !important;
            color: white !important; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
        .student-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .student-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(45, 62, 139, 0.1);
        }
        .modal-hidden { opacity: 0; pointer-events: none; transform: scale(0.95); }
        .modal-active { opacity: 1; pointer-events: auto; transform: scale(1); }
        .transition-modal { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .filter-dropdown { 
            display: none; 
            position: absolute; 
            top: 100%; 
            left: 0; 
            z-index: 50; 
            min-width: 200px; 
            background: white; 
            border-radius: 1.5rem; 
            padding: 1rem; 
            box-shadow: 0 25px 50px -12px rgba(45, 62, 139, 0.25); 
            border: 1px solid rgba(0, 0, 0, 0.05); 
            margin-top: 0.5rem;
        }
        .filter-group { position: relative; }
        .filter-group:hover .filter-dropdown { display: block; animation: slideDown 0.2s ease-out; }
        .filter-dropdown::before {
            content: '';
            position: absolute;
            top: -0.5rem;
            left: 0;
            right: 0;
            height: 0.5rem;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <div id="studentModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-blue/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Student Registration</h3>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mb-12">Enroll a new student into the academic system</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="add_student">
                
                <div class="space-y-2 mb-8">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Student ID / Roll (Auto-Generated)</label>
                    <input type="text" name="student_id" value="<?= $nextStudentId ?>" readonly class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-gray-400 cursor-not-allowed">
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Student Name</label>
                        <input type="text" name="name" required placeholder="Enter full name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Student Phone Number</label>
                        <input type="text" name="student_phone" required placeholder="e.g. 063XXXXXXX" pattern="063[0-9]{7}" title="Phone number must start with 063 followed by 7 digits" maxlength="10" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Academic Level</label>
                        <div class="grid grid-cols-2 gap-4">
                            <select name="level" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                                <option>Form 1</option>
                                <option>Form 2</option>
                                <option>Form 3</option>
                                <option>Form 4</option>
                            </select>
                            <select name="section" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                                <option value="">No Section</option>
                                <option>A</option>
                                <option>B</option>
                                <option>C</option>
                                <option>D</option>
                                <option>E</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Resident Area</label>
                        <select name="neighborhood" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                            <option>Sh Osman</option>
                            <option>Sh Ali</option>
                            <option>Sh Makahiil</option>
                            <option>Sh Ahmed Salaan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Parent Name</label>
                        <input type="text" name="parent_name" required placeholder="Enter parent name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Parent Phone</label>
                        <input type="text" name="parent_phone" required placeholder="e.g. 063XXXXXXX" pattern="063[0-9]{7}" title="Phone number must start with 063 followed by 7 digits" maxlength="10" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <button type="submit" class="w-full py-6 bg-school-accent text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-[1.02] active:scale-98 transition-all">Complete Enrollment</button>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-accent/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleEditModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Modify Student Record</h3>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mb-12">Update academic and contact information</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="edit_student">
                <input type="hidden" name="_id" id="edit-id">
                
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Student Name</label>
                        <input type="text" name="name" id="edit-name" required class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Student Phone Number</label>
                        <input type="text" name="student_phone" id="edit-student-phone" required placeholder="e.g. 063XXXXXXX" pattern="063[0-9]{7}" title="Phone number must start with 063 followed by 7 digits" maxlength="10" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" id="edit-gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Academic Level</label>
                        <div class="grid grid-cols-2 gap-4">
                            <select name="level" id="edit-level" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                                <option>Form 1</option>
                                <option>Form 2</option>
                                <option>Form 3</option>
                                <option>Form 4</option>
                            </select>
                            <select name="section" id="edit-section" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                                <option value="">No Section</option>
                                <option>A</option>
                                <option>B</option>
                                <option>C</option>
                                <option>D</option>
                                <option>E</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Resident Area</label>
                        <select name="neighborhood" id="edit-neighborhood" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer">
                            <option>Sh Osman</option>
                            <option>Sh Ali</option>
                            <option>Sh Makahiil</option>
                            <option>Sh Ahmed Salaan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Parent Name</label>
                        <input type="text" name="parent_name" id="edit-parent-name" required class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Parent Phone</label>
                        <input type="text" name="parent_phone" id="edit-parent-phone" required placeholder="e.g. 063XXXXXXX" pattern="063[0-9]{7}" title="Phone number must start with 063 followed by 7 digits" maxlength="10" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <button type="submit" class="w-full py-6 bg-school-accent text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-[1.02] active:scale-98 transition-all">Update Student Data</button>
            </form>
        </div>
    </div>

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-accent/5">
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
            <a href="<?= $dashboardUrl ?>" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            
            <?php if ($_SESSION['role'] === 'Vice President'): ?>
            <a href="manage-students.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Student Registration</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Teachers Registration</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="shield-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <?php endif; ?>

            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Exam & Results</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-500 hover:bg-red-50 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 w-full">
        <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-10 h-24 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h2 class="text-2xl font-black text-school-accent tracking-tighter uppercase">Academic Directory</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Unified Student Record Management</p>
            </div>
            <?php if ($_SESSION['role'] !== 'Teacher'): ?>
            <button onclick="toggleModal()" class="bg-school-accent px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-accent/20 hover:scale-105 transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Enroll Student</span>
            </button>
            <?php endif; ?>
        </header>

        <div class="px-10 py-6 relative z-40">
            <div class="flex items-center space-x-6 pb-4">
                <a href="manage-students.php" class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all <?= !$currentFilter ? 'bg-school-accent text-white shadow-lg shadow-school-accent/30' : 'bg-white text-gray-400 hover:text-school-accent' ?>">
                    All Students
                </a>
                
                <?php foreach ($groupedClasses as $baseForm => $subs): ?>
                <div class="relative filter-group">
                    <a href="manage-students.php?class_filter=<?= urlencode($baseForm) ?>" class="flex items-center space-x-2 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all <?= (strpos($currentFilter, $baseForm) === 0) ? 'bg-school-accent text-white shadow-lg shadow-school-accent/30' : 'bg-white text-gray-400 hover:text-school-accent' ?>">
                        <span><?= $baseForm ?></span>
                        <?php if (!empty($subs)): ?>
                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (!empty($subs)): ?>
                    <div class="filter-dropdown mt-2">
                        <?php foreach ($subs as $sub): ?>
                        <a href="manage-students.php?class_filter=<?= urlencode($sub) ?>" class="block px-5 py-3 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-school-accent/5 <?= $currentFilter === $sub ? 'text-school-accent' : 'text-gray-400' ?>">
                            <?= htmlspecialchars($sub) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="p-10 pt-0">
            <div class="bg-white rounded-[3rem] p-10 shadow-xl shadow-school-blue/5 border border-gray-50">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                                <th class="pb-8">Student Info</th>
                                <th class="pb-8">Student ID</th>
                                <th class="pb-8">Parent Name</th>
                                <th class="pb-8">Resident Area</th>
                                <th class="pb-8">Gender</th>
                                <th class="pb-8">Status</th>
                                <th class="pb-8 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($students as $student): ?>
                            <tr class="group hover:bg-school-accent/[0.03] transition-all">
                                <td class="py-7">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-2xl bg-school-accent/10 flex items-center justify-center text-school-accent overflow-hidden border border-school-accent/5">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($student->name) ?>&background=<?= str_replace('#', '', $accentColor) ?>&color=fff" alt="">
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-school-accent uppercase tracking-tighter"><?= htmlspecialchars($student->name) ?></h4>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5 tracking-widest"><?= htmlspecialchars($student->student_phone ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-7 text-sm font-black text-school-accent tracking-tighter italic">#<?= htmlspecialchars($student->student_id ?? 'N/A') ?></td>
                                <td class="py-7">
                                    <h4 class="text-xs font-black text-school-accent uppercase"><?= htmlspecialchars($student->parent_name ?? 'N/A') ?></h4>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest"><?= htmlspecialchars($student->parent_phone ?? 'N/A') ?></p>
                                </td>
                                <td class="py-7 text-xs font-black text-gray-400 uppercase tracking-tighter"><?= htmlspecialchars($student->neighborhood ?? 'N/A') ?></td>
                                <td class="py-7">
                                    <span class="px-4 py-1.5 <?= ($student->gender ?? '') === 'Male' ? 'bg-blue-50 text-blue-500' : 'bg-pink-50 text-pink-500' ?> rounded-full text-[9px] font-black uppercase tracking-widest"><?= $student->gender ?? 'N/A' ?></span>
                                </td>
                                <td class="py-7">
                                    <div class="flex items-center text-green-500 font-black text-[9px] uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2 shadow-sm shadow-green-500/50"></span> <?= $student->status ?>
                                    </div>
                                </td>
                                <td class="py-6 text-right">
                                    <?php if ($_SESSION['role'] !== 'Teacher'): ?>
                                    <div class="flex items-center justify-end space-x-2">
                                        <button onclick="openEditModal('<?= $student->_id ?>', '<?= addslashes($student->name) ?>', '<?= $student->student_phone ?>', '<?= $student->gender ?>', '<?= $student->form ?>', '<?= $student->neighborhood ?>', '<?= addslashes($student->parent_name) ?>', '<?= $student->parent_phone ?>')" class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:bg-school-accent hover:text-white transition-all inline-block"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                        <a href="manage-students.php?delete_id=<?= $student->_id ?>" onclick="return confirm('Are you sure you want to delete this student?');" class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:bg-school-coral hover:text-white transition-all inline-block"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">View Only</span>
                                    <?php endif; ?>
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
            const modal = document.getElementById('studentModal');
            if (modal.classList.contains('modal-hidden')) {
                modal.classList.remove('modal-hidden');
                modal.classList.add('modal-active');
            } else {
                modal.classList.add('modal-hidden');
                modal.classList.remove('modal-active');
            }
        }
        function toggleEditModal() {
            const modal = document.getElementById('editStudentModal');
            if (modal.classList.contains('modal-hidden')) {
                modal.classList.remove('modal-hidden');
                modal.classList.add('modal-active');
            } else {
                modal.classList.add('modal-hidden');
                modal.classList.remove('modal-active');
            }
        }
        function openEditModal(id, name, phone, gender, form, neighborhood, p_name, p_phone) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-student-phone').value = phone;
            document.getElementById('edit-gender').value = gender;
            
            // Parse form into Level and Section
            let level = form;
            let section = "";
            if (form.includes(" ")) {
                const parts = form.split(" ");
                level = parts[0] + " " + parts[1]; // "Form 1"
                section = parts[2] || ""; // "A"
            }
            
            document.getElementById('edit-level').value = level;
            document.getElementById('edit-section').value = section;
            
            document.getElementById('edit-neighborhood').value = neighborhood;
            document.getElementById('edit-parent-name').value = p_name;
            document.getElementById('edit-parent-phone').value = p_phone;
            toggleEditModal();
        }
    </script>
</body>
</html>

<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $collection = $database->getCollection('students');
    
    $studentData = [
        'student_id' => $_POST['student_id'],
        'name' => $_POST['name'],
        'student_phone' => $_POST['student_phone'],
        'gender' => $_POST['gender'],
        'form' => $_POST['form'],
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

// Handle Delete Student
if (isset($_GET['delete_id'])) {
    $collection = $database->getCollection('students');
    $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($_GET['delete_id'])]);
    header('Location: manage-students.php?msg=Student Deleted');
    exit;
}

// Fetch Students
$collection = $database->getCollection('students');
$students = $collection->find([], ['sort' => ['name' => 1]])->toArray();

// Generate next Student ID
$lastStudent = $collection->findOne(['student_id' => ['$exists' => true]], ['sort' => ['student_id' => -1]]);
$nextStudentId = '2026001';
if ($lastStudent && isset($lastStudent->student_id)) {
    $lastId = (int)$lastStudent->student_id;
    if ($lastId > 0) {
        $nextStudentId = (string)($lastId + 1);
    }
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
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <div id="studentModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-blue/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-blue mb-2 tracking-tighter uppercase">Student Registration</h3>
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
                        <input type="text" name="name" required placeholder="Enter full name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Student Phone Number</label>
                        <input type="text" name="student_phone" required placeholder="e.g. 061XXXXXXX" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Academic Level</label>
                        <select name="form" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer">
                            <option>Form 1</option>
                            <option>Form 2</option>
                            <option>Form 3</option>
                            <option>Form 4</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Resident Area</label>
                        <select name="neighborhood" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer">
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
                        <input type="text" name="parent_name" required placeholder="Enter parent name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Parent Phone</label>
                        <input type="text" name="parent_phone" required placeholder="e.g. 061XXXXXXX" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                </div>

                <button type="submit" class="w-full py-6 bg-school-blue text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-blue/20 hover:scale-[1.02] active:scale-98 transition-all">Complete Enrollment</button>
            </form>
        </div>
    </div>

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
            <a href="admin-dashboard.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Students</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
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
            <div>
                <h2 class="text-2xl font-black text-school-blue tracking-tighter uppercase">Academic Directory</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Unified Student Record Management</p>
            </div>
            <button onclick="toggleModal()" class="bg-school-blue px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-blue/20 hover:scale-105 transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Enroll Student</span>
            </button>
        </header>

        <div class="p-10">
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
                            <tr class="group hover:bg-school-blue/5 transition-all">
                                <td class="py-6">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-2xl bg-school-blue/10 flex items-center justify-center text-school-blue overflow-hidden">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($student->name) ?>&background=2D3E8B&color=fff" alt="">
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-school-blue"><?= htmlspecialchars($student->name) ?></h4>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5"><?= htmlspecialchars($student->student_phone ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-6 text-sm font-black text-school-blue tracking-tighter"><?= htmlspecialchars($student->student_id ?? 'N/A') ?></td>
                                <td class="py-6">
                                    <h4 class="text-xs font-black text-school-blue"><?= htmlspecialchars($student->parent_name ?? 'N/A') ?></h4>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase"><?= htmlspecialchars($student->parent_phone ?? 'N/A') ?></p>
                                </td>
                                <td class="py-6 text-xs font-black text-gray-400 uppercase tracking-tighter"><?= htmlspecialchars($student->neighborhood ?? 'N/A') ?></td>
                                <td class="py-6">
                                    <span class="px-4 py-1.5 <?= ($student->gender ?? '') === 'Male' ? 'bg-school-blue/10 text-school-blue' : 'bg-school-coral/10 text-school-coral' ?> rounded-full text-[9px] font-black uppercase tracking-widest"><?= $student->gender ?? 'N/A' ?></span>
                                </td>
                                <td class="py-6">
                                    <div class="flex items-center text-green-500 font-black text-[9px] uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2"></span> <?= $student->status ?>
                                    </div>
                                </td>
                                <td class="py-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="#" class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:bg-school-blue hover:text-white transition-all inline-block"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                                        <a href="manage-students.php?delete_id=<?= $student->_id ?>" onclick="return confirm('Are you sure you want to delete this student?');" class="p-2.5 bg-gray-50 text-gray-400 rounded-xl hover:bg-school-coral hover:text-white transition-all inline-block"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
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
            const modal = document.getElementById('studentModal');
            modal.classList.toggle('modal-hidden');
        }
    </script>
</body>
</html>

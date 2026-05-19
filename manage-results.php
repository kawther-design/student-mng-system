<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Teacher') {
    header('Location: ' . ($_SESSION['role'] === 'Teacher' ? 'teacher-dashboard.php' : 'login.php'));
    exit;
}

$examId = $_GET['exam_id'] ?? '';
$examColl = $database->getCollection('exams');
$allExams = $examColl->find([], ['sort' => ['date' => -1]])->toArray();

$exam = null;
if (!empty($examId)) {
    $exam = $examColl->findOne(['_id' => new MongoDB\BSON\ObjectId($examId)]);
}

$subjects = [
    'arabic' => 'Arabic',
    'islamic' => 'Islamic',
    'biology' => 'Biology',
    'physics' => 'Physics',
    'mathematics' => 'Mathematics',
    'chemistry' => 'Chemistry',
    'somali' => 'Somali',
    'english' => 'English',
    'history' => 'History',
    'geography' => 'Geography'
];

$resultsColl = $database->getCollection('results');

// Handle Save Results
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_results') {
    foreach ($_POST['marks'] as $studentId => $studentMarks) {
        $total = 0;
        $count = 0;
        foreach ($studentMarks as $m) {
            if ($m !== '') {
                $total += (float)$m;
                $count++;
            }
        }
        
        $resultsColl->updateOne(
            ['student_id' => $studentId, 'exam_id' => $examId],
            ['$set' => [
                'student_id' => $studentId,
                'exam_id' => $examId,
                'marks' => $studentMarks,
                'total_marks' => $total,
                'average' => $count > 0 ? $total / count($subjects) : 0,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]],
            ['upsert' => true]
        );
    }
    $msg = "Results Updated Successfully";
}

// Handle Delete Result (GET)
if (isset($_GET['delete_student_id'])) {
    $studentId = $_GET['delete_student_id'];
    $resultsColl->deleteOne(['student_id' => $studentId, 'exam_id' => $examId]);
    header("Location: manage-results.php?exam_id=$examId&class=" . urlencode($currentClass) . "&msg=Result Deleted Successfully");
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Handle Excel/CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        $rows = [];

        if (strtolower($ext) === 'xlsx') {
            if ($xlsx = Shuchkin\SimpleXLSX::parse($file)) {
                $rows = $xlsx->rows();
                array_shift($rows); // Skip header
            }
        } else {
            $handle = fopen($file, "r");
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (!empty($rows)) {
            $count = 0;
            $studentColl = $database->getCollection('students');
            foreach ($rows as $data) {
                if (count($data) >= 11) {
                    $s_id_raw = trim($data[0]);
                    $student = $studentColl->findOne(['student_id' => (string)$s_id_raw]);
                    
                    if ($student) {
                        $s_oid = (string)$student->_id;
                        $importMarks = [
                            'arabic' => $data[1],
                            'islamic' => $data[2],
                            'biology' => $data[3],
                            'physics' => $data[4],
                            'mathematics' => $data[5],
                            'chemistry' => $data[6],
                            'somali' => $data[7],
                            'english' => $data[8],
                            'history' => $data[9],
                            'geography' => $data[10]
                        ];
                        
                        $total = 0;
                        foreach ($importMarks as $m) $total += (float)$m;
                        
                        $resultsColl->updateOne(
                            ['student_id' => $s_oid, 'exam_id' => $examId],
                            ['$set' => [
                                'student_id' => $s_oid,
                                'exam_id' => $examId,
                                'marks' => $importMarks,
                                'total_marks' => $total,
                                'average' => $total / count($subjects),
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]],
                            ['upsert' => true]
                        );
                        $count++;
                    }
                }
            }
            $msg = "Imported $count student results successfully.";
        }
    }
}

// Fetch Students by Class
$currentClass = $_GET['class'] ?? '';
$studentColl = $database->getCollection('students');
$allClasses = $studentColl->distinct('form');
sort($allClasses);

$students = [];
if ($currentClass) {
    $students = $studentColl->find(['form' => $currentClass], ['sort' => ['name' => 1]])->toArray();
}

// Fetch existing results for this exam and class
$existingResults = [];
if ($currentClass) {
    $res = $resultsColl->find(['exam_id' => $examId])->toArray();
    foreach ($res as $r) {
        $existingResults[$r->student_id] = $r;
    }
}

// Combine and Sort Students by performance
if ($currentClass && !empty($students)) {
    usort($students, function($a, $b) use ($existingResults) {
        $marksA = $existingResults[(string)$a->_id]->total_marks ?? 0;
        $marksB = $existingResults[(string)$b->_id]->total_marks ?? 0;
        return $marksB <=> $marksA; // Highest marks first
    });
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
    <title>Manage Results | <?= htmlspecialchars($exam->name) ?></title>
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
        .glass-input { 
            background: #F9FAFB;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            border-color: <?= $accentColor ?>;
            background: white;
            box-shadow: 0 10px 25px -5px <?= $accentColor ?>1a;
        }
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, #1a255a 100%) !important;
            color: white !important; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 hidden lg:flex flex-col p-8 shadow-2xl shadow-school-accent/5">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-accent rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-accent/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-accent tracking-tighter uppercase">AL HUDA</h1>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] mt-1">Management v2.0</p>
            </div>
        </div>
        
        <nav class="flex-1 space-y-3">
            <a href="<?= $dashboardUrl ?>" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            
            <?php if ($_SESSION['role'] === 'Vice President'): ?>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-accent hover:bg-school-accent/5 transition-all">
                <i data-lucide="user-plus" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Student Registration</span>
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
                <i data-lucide="calendar-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <?php endif; ?>

            <a href="manage-exams.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem] bg-school-accent text-white shadow-lg shadow-school-accent/20">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Exam & Results</span>
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
            <div class="flex items-center space-x-6">
                <a href="manage-exams.php" class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-400 hover:bg-school-accent hover:text-white transition-all"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
                <div>
                    <h2 class="text-2xl font-black text-school-accent tracking-tighter uppercase"><?= $exam ? htmlspecialchars($exam->name) : 'Academic Results' ?> Control</h2>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Manual Entry & Bulk Excel Import</p>
                </div>
            </div>
            
            <?php if ($exam): ?>
            <div class="flex items-center space-x-4">
                <form method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
                    <input type="hidden" name="action" value="import_csv">
                    <label class="bg-school-accent px-6 py-4 rounded-2xl flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white cursor-pointer hover:scale-105 shadow-lg shadow-school-accent/20 transition-all">
                        <i data-lucide="file-up" class="w-4 h-4"></i>
                        <span>Import Excel (CSV)</span>
                        <input type="file" name="csv_file" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <?php endif; ?>
        </header>

        <div class="p-10">
            <div class="bg-white rounded-[4rem] p-10 shadow-xl shadow-school-accent/5 border border-gray-50 mb-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-4">Step 1: Select Examination Cycle</p>
                        <select onchange="location.href='?exam_id='+this.value" class="w-full bg-gray-50 border-none rounded-2xl py-5 px-8 text-sm font-black text-school-accent uppercase tracking-tighter outline-none cursor-pointer hover:bg-gray-100 transition-all">
                            <option value="">-- Choose Exam --</option>
                            <?php foreach ($allExams as $e): ?>
                            <option value="<?= $e->_id ?>" <?= $examId == (string)$e->_id ? 'selected' : '' ?>><?= htmlspecialchars($e->name) ?> (<?= htmlspecialchars($e->type) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($exam): ?>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-4">Step 2: Filter by Class</p>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($allClasses as $class): ?>
                            <a href="?exam_id=<?= $examId ?>&class=<?= urlencode($class) ?>" class="px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all <?= $currentClass === $class ? 'bg-school-accent text-white shadow-lg shadow-school-accent/30' : 'bg-gray-50 text-gray-400 hover:bg-gray-100 border border-gray-100' ?>">
                                <?= htmlspecialchars($class) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($msg)): ?>
                <div class="mb-8 p-6 bg-green-50 text-green-500 rounded-[2rem] font-black text-[10px] uppercase tracking-widest flex items-center shadow-lg shadow-green-500/10">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-4"></i>
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if (!$exam): ?>
                <div class="text-center py-32 bg-white rounded-[4rem] border-2 border-dashed border-gray-100">
                    <i data-lucide="file-spreadsheet" class="w-20 h-20 mx-auto mb-6 opacity-20"></i>
                    <h3 class="text-2xl font-black uppercase tracking-widest text-school-accent opacity-30">Please select an exam cycle to manage results</h3>
                </div>
            <?php elseif (!$currentClass): ?>
                <div class="text-center py-32 bg-white rounded-[4rem] border-2 border-dashed border-gray-100">
                    <i data-lucide="users" class="w-20 h-20 mx-auto mb-6 opacity-20"></i>
                    <h3 class="text-2xl font-black uppercase tracking-widest text-school-accent opacity-30">Select a class to enter marks</h3>
                </div>
            <?php else: ?>
            <form method="POST" class="space-y-10">
                <input type="hidden" name="action" value="save_results">
                <div class="bg-white rounded-[4rem] p-10 shadow-xl shadow-school-accent/5 border border-gray-50 overflow-x-auto">
                    <table class="w-full text-left min-w-[1200px]">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-300 uppercase tracking-[0.2em] border-b border-gray-50">
                                <th class="pb-8">Student Info</th>
                                <?php foreach ($subjects as $label): ?>
                                <th class="pb-8 px-4 text-center font-black"><?= $label ?></th>
                                <?php endforeach; ?>
                                <th class="pb-8 px-6 text-center text-school-accent">Total / 1000</th>
                                <th class="pb-8 px-6 text-center text-school-coral">% Score</th>
                                <th class="pb-8 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($students as $student): 
                                $s_id = (string)$student->_id;
                                $resObj = $existingResults[$s_id] ?? null;
                                $marks = $resObj->marks ?? array_fill_keys(array_keys($subjects), '');
                            ?>
                            <tr class="group hover:bg-gray-50/50 transition-all">
                                <td class="py-6">
                                    <h4 class="text-sm font-black text-school-accent uppercase tracking-tighter"><?= htmlspecialchars($student->name) ?></h4>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5 italic">ID: <?= htmlspecialchars($student->student_id) ?></p>
                                </td>
                                <?php foreach ($subjects as $key => $label): ?>
                                <td class="py-6 px-2 text-center">
                                    <input type="number" name="marks[<?= $s_id ?>][<?= $key ?>]" value="<?= htmlspecialchars($marks[$key] ?? '') ?>" step="0.5" min="0" max="100" class="w-16 h-12 glass-input rounded-xl text-center text-sm font-black text-school-accent outline-none" placeholder="0">
                                </td>
                                <?php endforeach; ?>
                                <td class="py-6 px-2 text-center">
                                    <span class="text-sm font-black text-school-accent"><?= $resObj->total_marks ?? 0 ?></span>
                                </td>
                                <td class="py-6 px-2 text-center">
                                    <span class="text-sm font-black text-school-coral"><?= isset($resObj->total_marks) ? number_format($resObj->total_marks / 10, 1) : '0' ?>%</span>
                                </td>
                                <td class="py-6 px-2 text-center">
                                    <?php if ($resObj): ?>
                                    <a href="?exam_id=<?= $examId ?>&class=<?= urlencode($currentClass) ?>&delete_student_id=<?= $s_id ?>" 
                                       onclick="return confirm('Are you sure you want to delete this result?')"
                                       class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all mx-auto">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-center">
                    <button type="submit" class="bg-school-accent px-16 py-6 rounded-[2.5rem] text-white font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-105 active:scale-95 transition-all">
                        Save Academic Results
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

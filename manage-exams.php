<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Teacher') {
    header('Location: ' . ($_SESSION['role'] === 'Teacher' ? 'teacher-dashboard.php' : 'login.php'));
    exit;
}

$resultsColl = $database->getCollection('results');
$examsColl = $database->getCollection('exams');
$studentColl = $database->getCollection('students');

$subjects = [
    'arabic' => 'Arabic', 'islamic' => 'Islamic', 'biology' => 'Biology', 
    'physics' => 'Physics', 'mathematics' => 'Mathematics', 'chemistry' => 'Chemistry', 
    'somali' => 'Somali', 'english' => 'English', 'history' => 'History', 'geography' => 'Geography'
];

$msg = '';
$error = '';

// Handle Results Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_results') {
    $examTitle = $_POST['exam_title'] ?? 'General Exam';
    
    if (isset($_FILES['results_file']) && $_FILES['results_file']['error'] == 0) {
        $file = $_FILES['results_file']['tmp_name'];
        $ext = pathinfo($_FILES['results_file']['name'], PATHINFO_EXTENSION);
        $rows = [];

        if (strtolower($ext) === 'xlsx') {
            if ($xlsx = Shuchkin\SimpleXLSX::parse($file)) {
                $rows = $xlsx->rows();
                array_shift($rows); // Skip header
            } else {
                $error = Shuchkin\SimpleXLSX::parseError();
            }
        } else {
            // Handle CSV
            $handle = fopen($file, "r");
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (!empty($rows)) {
            // Create or find the exam
            $exam = $examsColl->findOne(['name' => $examTitle]);
            if (!$exam) {
                $examsColl->insertOne([
                    'name' => $examTitle,
                    'type' => 'General',
                    'date' => date('Y-m-d'),
                    'status' => 'Completed',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]);
                $exam = $examsColl->findOne(['name' => $examTitle]);
            }
            $examId = (string)$exam->_id;

            $count = 0;
            foreach ($rows as $data) {
                if (count($data) >= 11) {
                    $s_id_raw = trim($data[0]);
                    $student = $studentColl->findOne(['student_id' => (string)$s_id_raw]);
                    
                    if ($student) {
                        $s_oid = (string)$student->_id;
                        $marks = [
                            'arabic' => (float)($data[1] ?? 0),
                            'islamic' => (float)($data[2] ?? 0),
                            'biology' => (float)($data[3] ?? 0),
                            'physics' => (float)($data[4] ?? 0),
                            'mathematics' => (float)($data[5] ?? 0),
                            'chemistry' => (float)($data[6] ?? 0),
                            'somali' => (float)($data[7] ?? 0),
                            'english' => (float)($data[8] ?? 0),
                            'history' => (float)($data[9] ?? 0),
                            'geography' => (float)($data[10] ?? 0)
                        ];
                        
                        $total = array_sum($marks);
                        
                        $resultsColl->updateOne(
                            ['student_id' => $s_oid, 'exam_id' => $examId],
                            ['$set' => [
                                'student_id' => $s_oid,
                                'exam_id' => $examId,
                                'marks' => $marks,
                                'total_marks' => $total,
                                'average' => $total / 10,
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]],
                            ['upsert' => true]
                        );
                        $count++;
                    }
                }
            }
            $msg = "Successfully uploaded results for $count students under '$examTitle'";
        } else {
            $error = $error ?: "The file seems to be empty or in an unsupported format.";
        }
    } else {
        $error = "Please select a valid Excel (.xlsx) or CSV file.";
    }
}

// Get recent exams for the list
$recentExams = $examsColl->find([], ['limit' => 10, 'sort' => ['created_at' => -1]])->toArray();

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
    <title>Results Upload | Al Huda Control</title>
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
                            bg: '#F8FAFF'
                        }
                    },
                    fontFamily: { outfit: ['Outfit', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, #1a255a 100%) !important;
            color: white !important; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
    </style>
</head>
    <!-- Sidebar Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 backdrop-blur-sm hidden transition-opacity duration-300" style="background-color: <?= $accentColor ?>20;"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-accent/5 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex items-center space-x-4 mb-16">
            <div class="w-12 h-12 bg-school-accent rounded-[1.2rem] flex items-center justify-center shadow-xl shadow-school-accent/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-school-accent tracking-tighter uppercase">Al Huda</h1>
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
                <i data-lucide="shield-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <?php endif; ?>

            <a href="manage-exams.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Exam & Results</span>
            </a>
        </nav>

        <div class="pt-8 border-t border-gray-100">
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-red-500 hover:bg-red-50 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 w-full p-6 md:p-10">
        <header class="flex flex-col md:flex-row md:justify-between md:items-center mb-12 gap-6">
            <div class="flex items-center space-x-4">
                <button type="button" id="sidebar-toggle" class="lg:hidden p-3 bg-white hover:bg-gray-50 rounded-2xl shadow-md border border-gray-100 flex items-center justify-center transition-all" style="color: <?= $accentColor ?>;">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="text-2xl md:text-4xl font-black text-school-accent tracking-tighter uppercase leading-none">Results Center</h2>
                    <p class="text-[9px] md:text-xs text-gray-400 font-black uppercase tracking-[0.3em] mt-2">Direct Excel Import Portal</p>
                </div>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="mb-10 p-6 bg-green-50 text-green-500 rounded-[2.5rem] flex items-center border border-green-100">
                <i data-lucide="check-circle" class="w-6 h-6 mr-4"></i>
                <span class="text-xs font-black uppercase tracking-widest"><?= $msg ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-10 p-6 bg-red-50 text-red-500 rounded-[2.5rem] flex items-center border border-red-100">
                <i data-lucide="alert-circle" class="w-6 h-6 mr-4"></i>
                <span class="text-xs font-black uppercase tracking-widest"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Upload Section -->
            <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-accent/5 border border-gray-50">
                <h3 class="text-2xl font-black text-school-accent uppercase tracking-tighter mb-8">New Results Upload</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="action" value="upload_results">
                    
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Examination Title</label>
                        <input type="text" name="exam_title" required placeholder="e.g. Final Exam 2026" class="w-full bg-gray-50 border-2 border-transparent focus:border-school-accent/20 focus:bg-white rounded-[2rem] py-6 px-10 outline-none text-sm font-black text-school-accent transition-all uppercase">
                    </div>

                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Select Results File (Excel/CSV)</label>
                        <div class="relative group">
                            <input type="file" name="results_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="w-full bg-gray-50 border-2 border-dashed border-gray-200 rounded-[2rem] py-12 px-10 text-center group-hover:border-school-accent/30 group-hover:bg-school-accent/[0.02] transition-all">
                                <i data-lucide="file-up" class="w-12 h-12 mx-auto mb-4 text-gray-300 group-hover:text-school-accent transition-all"></i>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Click to browse or drag & drop</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-7 bg-school-accent text-white rounded-[2.5rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Process & Import Results
                    </button>
                </form>

                <div class="mt-10 pt-10 border-t border-gray-50">
                    <p class="text-[9px] font-black text-gray-300 uppercase tracking-widest mb-4">CSV Format Requirement:</p>
                    <div class="bg-gray-50 p-6 rounded-2xl text-[8px] font-bold text-gray-400 font-mono overflow-x-auto whitespace-nowrap">
                        StudentID, Arabic, Islamic, Biology, Physics, Math, Chem, Somali, English, History, Geog
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="bg-white rounded-[4rem] p-12 shadow-2xl shadow-school-accent/5 border border-gray-50">
                <h3 class="text-2xl font-black text-school-accent uppercase tracking-tighter mb-8">Recent Uploads</h3>
                <div class="space-y-6">
                    <?php if (empty($recentExams)): ?>
                        <div class="text-center py-20 opacity-20">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4"></i>
                            <p class="text-xs font-black uppercase tracking-widest">No results uploaded yet</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($recentExams as $re): ?>
                        <div class="flex items-center justify-between p-6 bg-gray-50/50 rounded-[2rem] border border-gray-50 hover:bg-white hover:shadow-xl transition-all duration-500 group">
                            <div class="flex items-center space-x-6">
                                <div class="w-12 h-12 bg-school-accent/10 text-school-accent rounded-2xl flex items-center justify-center">
                                    <i data-lucide="check-square" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-school-accent uppercase tracking-tight"><?= htmlspecialchars($re->name) ?></h4>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase mt-0.5"><?= date('M d, Y', $re->created_at->toDateTime()->getTimestamp()) ?></p>
                                </div>
                            </div>
                            <a href="manage-results.php?exam_id=<?= $re->_id ?>" class="px-5 py-3 bg-white text-school-accent rounded-xl text-[8px] font-black uppercase tracking-widest border border-school-accent/10 hover:bg-school-accent hover:text-white transition-all opacity-0 group-hover:opacity-100">View Details</a>
                        </div>
                    <?php endforeach; ?>
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
    </script>
</body>
</html>

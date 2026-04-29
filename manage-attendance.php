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
        $collection->updateOne(
            ['student_id' => $studentId, 'date' => $date],
            ['$set' => [
                'student_id' => $studentId,
                'student_name' => $_POST['student_names'][$studentId],
                'date' => $date,
                'form' => $form,
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
$form = isset($_GET['form']) ? $_GET['form'] : 'Form 1';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$studentColl = $database->getCollection('students');
$students = $studentColl->find(['form' => $form], ['sort' => ['name' => 1]])->toArray();

// Fetch existing attendance for the date
$attColl = $database->getCollection('attendance');
$existingAtt = $attColl->find(['form' => $form, 'date' => $date])->toArray();
$attStatus = [];
foreach ($existingAtt as $record) {
    $attStatus[$record->student_id] = $record->status;
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
            background-color: #2D3E8B;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(45, 62, 139, 0.3);
        }
        .status-radio-present:checked + label { background-color: #1DBF92; box-shadow: 0 10px 20px -5px rgba(29, 191, 146, 0.3); }
        .status-radio-absent:checked + label { background-color: #FF6B52; box-shadow: 0 10px 20px -5px rgba(255, 107, 82, 0.3); }
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
            <a href="admin-dashboard.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Students</span>
            </a>
            <a href="manage-users.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5 transition-all">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Attendance</span>
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
        <form method="POST" class="w-full">
            <input type="hidden" name="action" value="save_attendance">
            <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-10 h-24 flex items-center justify-between sticky top-0 z-30">
                <div>
                    <h2 class="text-2xl font-black text-school-blue tracking-tighter uppercase">Attendance Tracker</h2>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Daily Student Presence</p>
                </div>
                <button type="submit" class="bg-school-blue px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-blue/20 hover:scale-105 transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Records</span>
                </button>
            </header>

            <div class="p-8">
                <div class="bg-white p-8 rounded-[3rem] shadow-xl shadow-school-blue/5 border border-gray-50 mb-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div class="flex items-center space-x-6">
                        <div>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Selected Class</span>
                            <select onchange="location.href='?form='+this.value+'&date=<?= $date ?>'" class="bg-gray-50 border-none rounded-xl py-3 px-6 text-sm font-black text-school-blue uppercase tracking-tighter outline-none cursor-pointer">
                                <?php for($i=1; $i<=4; $i++): ?>
                                    <option value="Form <?= $i ?>" <?= $form === "Form $i" ? 'selected' : '' ?>>Form <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Track Date</span>
                            <input type="date" value="<?= $date ?>" onchange="location.href='?form=<?= $form ?>&date='+this.value" class="bg-gray-50 border-none rounded-xl py-3 px-6 text-sm font-black text-school-blue outline-none cursor-pointer">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[3rem] p-10 shadow-xl shadow-school-blue/5 border border-gray-50">
                    <table class="w-full text-left">
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
                            <tr class="group hover:bg-school-blue/5 transition-all">
                                <td class="py-6">
                                    <span class="text-sm font-black text-school-blue tracking-tighter"><?= htmlspecialchars($student->name) ?></span>
                                </td>
                                <td class="py-6 text-sm font-black text-school-blue"><?= htmlspecialchars($student->student_phone ?? 'N/A') ?></td>
                                <td class="py-6">
                                    <div class="flex items-center justify-center space-x-8">
                                        <?php 
                                        $options = ['Present' => 'P', 'Absent' => 'A', 'Late' => 'L'];
                                        $colors = ['Present' => 'school-teal', 'Absent' => 'school-coral', 'Late' => 'school-yellow'];
                                        foreach ($options as $full => $short): 
                                        ?>
                                        <div class="flex flex-col items-center">
                                            <input type="hidden" name="student_names[<?= $s_id ?>]" value="<?= htmlspecialchars($student->name) ?>">
                                            <input type="radio" id="att_<?= $s_id ?>_<?= $short ?>" name="attendance[<?= $s_id ?>]" value="<?= $full ?>" <?= $status == $full ? 'checked' : '' ?> class="hidden peer status-radio status-radio-<?= strtolower($full) ?>">
                                            <label for="att_<?= $s_id ?>_<?= $short ?>" class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 font-black text-[10px] transition-all cursor-pointer">
                                                <?= $short ?>
                                            </label>
                                            <span class="text-[8px] font-black text-gray-400 mt-2 uppercase tracking-widest pointer-events-none"><?= $full ?></span>
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
        </form>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>

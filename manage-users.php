<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $collection = $database->getCollection('users');
    
    $userData = [
        'name' => $_POST['name'],
        'gender' => $_POST['gender'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'username' => $_POST['username'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'role' => $_POST['role'], // Teacher, Vice President, Admin
        'subject' => $_POST['subject'] ?? 'General',
        'status' => 'Active',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    
    $collection->insertOne($userData);
    header('Location: manage-users.php?msg=User Added Successfully');
    exit;
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $collection = $database->getCollection('users');
    $userId = new MongoDB\BSON\ObjectId($_POST['user_id']);
    
    $updateData = [
        'name' => $_POST['name'],
        'gender' => $_POST['gender'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'subject' => $_POST['subject'] ?? 'General'
    ];

    if (!empty($_POST['username'])) {
        $updateData['username'] = $_POST['username'];
    }

    $collection->updateOne(
        ['_id' => $userId],
        ['$set' => $updateData]
    );
    header('Location: manage-users.php?msg=User Updated Successfully');
    exit;
}

// Handle Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $collection = $database->getCollection('users');
    $userId = new MongoDB\BSON\ObjectId($_POST['user_id']);
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $collection->updateOne(
        ['_id' => $userId],
        ['$set' => ['password' => $newPassword]]
    );
    header('Location: manage-users.php?msg=Password Updated Successfully');
    exit;
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $collection = $database->getCollection('users');
    $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($_GET['delete_id'])]);
    header('Location: manage-users.php?msg=User Deleted Successfully');
    exit;
}

// Fetch Users
$collection = $database->getCollection('users');
$users = $collection->find([], ['sort' => ['name' => 1]])->toArray();

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
// Module-specific sidebar background for admin modules
$moduleBgClass = '';
$scriptName = basename(__FILE__);
if ($scriptName === 'manage-users.php') {
    $moduleBgClass = 'bg-school-purple';
} elseif ($scriptName === 'manage-attendance.php') {
    $moduleBgClass = 'bg-school-teal';
} elseif (in_array($scriptName, ['manage-exams.php', 'manage-results.php'])) {
    $moduleBgClass = 'bg-school-coral';
}
if (!empty($moduleBgClass)) {
    $sidebarBgClass = $moduleBgClass;
}
?>
// Determine sidebar background class based on role
$role = $_SESSION['role'] ?? 'Admin';
if ($role === 'Teacher') {
    $sidebarBgClass = 'bg-school-purple';
} elseif ($role === 'Parent') {
    $sidebarBgClass = 'bg-school-coral';
} elseif ($role === 'Vice President') {
    $sidebarBgClass = 'bg-school-teal';
} else {
    $sidebarBgClass = 'bg-school-blue';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | Al Huda</title>
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
        .sidebar-item { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-item.active, .sidebar-item:hover { 
            background: linear-gradient(135deg, <?= $accentColor ?> 0%, #1a255a 100%) !important;
            color: white !important; 
            box-shadow: 0 15px 30px -10px <?= $accentColor ?>66; 
        }
        .user-table-row { 
            transition: all 0.3s ease;
        }
        .user-table-row:hover { 
            background-color: white;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
            transform: scale(1.005) translateY(-2px);
            z-index: 10;
        }
        .user-table-row { position: relative; }
        th { 
            background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
            border-bottom: 2px solid #f3f4f6;
        }
        .modal-hidden { opacity: 0; pointer-events: none; transform: scale(0.95); }
        .modal-active { opacity: 1; pointer-events: auto; transform: scale(1); }
        .transition-modal { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-accent/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-accent transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Add New System User</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em] mb-12">Create credentials for staff or parents</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="add_user">
                
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Name</label>
                        <input type="text" name="name" required placeholder="Enter full name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer focus:ring-2 focus:ring-school-accent/5">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Phone Number</label>
                        <input type="text" name="phone" required placeholder="e.g. 063xxxxxx" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Email Address</label>
                        <input type="email" name="email" placeholder="user@alhuda.edu" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <div class="h-px bg-gray-100 my-4"></div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Username</label>
                        <input type="text" name="username" required placeholder="Login Username" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Initial Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">System Role</label>
                        <select name="role" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer focus:ring-2 focus:ring-school-accent/5">
                            <option value="Teacher">Academic Teachers</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Parent">Parent / Guardian</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Specialization</label>
                        <input type="text" name="subject" placeholder="e.g. Mathematics" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>
                <button type="submit" class="w-full py-6 bg-school-accent text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-[1.02] active:scale-98 transition-all">Create User Account</button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-accent/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-md rounded-[4rem] p-12 shadow-2xl relative text-center">
            <button onclick="togglePasswordModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-accent"><i data-lucide="x" class="w-8 h-8"></i></button>
            <div class="w-20 h-20 bg-school-accent/10 rounded-[2rem] flex items-center justify-center text-school-accent mx-auto mb-8">
                <i data-lucide="key-round" class="w-10 h-10"></i>
            </div>
            <h3 class="text-2xl font-black text-school-accent mb-2 tracking-tighter uppercase">Security Reset</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-12" id="reset-user-name">For User</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="user_id" id="reset-user-id">
                <div class="space-y-2 text-left">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-6">New Secure Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                </div>
                <button type="submit" class="w-full py-6 bg-school-accent text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-xl">Update Access Credentials</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-accent/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleEditModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-accent transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-accent mb-2 tracking-tighter uppercase">Modify User Profile</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em] mb-12">Update system access & role details</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit-user-id">
                
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Name</label>
                        <input type="text" name="name" id="edit-name" required class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" id="edit-gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer focus:ring-2 focus:ring-school-accent/5">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Phone Number</label>
                        <input type="text" name="phone" id="edit-phone" required class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Email Address</label>
                        <input type="email" name="email" id="edit-email" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Username</label>
                        <input type="text" name="username" id="edit-username" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">System Role</label>
                        <select name="role" id="edit-role" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent cursor-pointer focus:ring-2 focus:ring-school-accent/5">
                            <option value="Teacher">Academic Teachers</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Parent">Parent / Guardian</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Specialization (For Teachers)</label>
                    <input type="text" name="subject" id="edit-subject" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5">
                </div>
                
                <button type="submit" class="w-full py-6 bg-school-accent text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-accent/20 hover:scale-[1.02] active:scale-98 transition-all">Update Account Details</button>
            </form>
        </div>
    </div>

    <!-- Sidebar Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 backdrop-blur-sm hidden transition-opacity duration-300" style="background-color: <?= $accentColor ?>20;"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 <?= $sidebarBgClass ?> border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-accent/5 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
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
            <a href="manage-users.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Teachers Registration</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="manage-users.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="calendar-check" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <?php endif; ?>

            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-school-accent transition-all">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
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
        <header class="bg-white/70 backdrop-blur-xl border-b border-gray-100 px-6 md:px-10 h-24 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <!-- Hamburger Menu Button for mobile -->
                <button type="button" id="sidebar-toggle" class="lg:hidden p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl border border-gray-100 flex items-center justify-center transition-all" style="color: <?= $accentColor ?>;">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="text-xl md:text-2xl font-black text-school-accent tracking-tighter uppercase leading-none"><?= $_SESSION['role'] === 'Vice President' ? 'Faculty Registry' : 'User Repository' ?></h2>
                    <p class="text-[9px] md:text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1.5"><?= $_SESSION['role'] === 'Vice President' ? 'Academic Staff Management' : 'Manage Faculty, Admin & Parents' ?></p>
                </div>
            </div>

            <button onclick="toggleModal()" class="bg-school-accent px-6 md:px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[9px] md:text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-accent/20 hover:scale-105 transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span class="hidden md:inline">Register User</span>
                <span class="md:hidden">Register</span>
            </button>
        </header>

        <div class="p-6 md:p-10">
            <div class="flex flex-col md:flex-row items-stretch md:items-center space-y-4 md:space-y-0 md:space-x-6 mb-8">
                <div class="relative flex-1 group">
                    <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-school-accent transition-colors"></i>
                    <input type="text" id="userSearch" onkeyup="filterUsers()" placeholder="Search by name, role or username..." class="w-full bg-white border border-gray-100 rounded-2xl py-4 pl-14 pr-6 outline-none text-sm font-bold text-school-accent focus:ring-2 focus:ring-school-accent/5 shadow-sm transition-all">
                </div>
                
                <div class="relative w-full md:w-64 group">
                    <i data-lucide="filter" class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 group-focus-within:text-school-accent transition-colors"></i>
                    <select id="roleFilter" onchange="filterUsers()" class="w-full bg-white border border-gray-100 rounded-2xl py-4 pl-14 pr-10 outline-none text-sm font-bold text-school-accent appearance-none focus:ring-2 focus:ring-school-accent/5 shadow-sm transition-all cursor-pointer">
                        <option value="">All Roles</option>
                        <option value="Admin">Administrators</option>
                        <option value="Teacher">Academic Teachers</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Parent">Parents / Guardians</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 pointer-events-none"></i>
                </div>
            </div>

            <div class="bg-white rounded-[3rem] shadow-2xl shadow-school-accent/5 border border-gray-100 overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[800px]" id="userTable">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">User Profile</th>
                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">System Role</th>
                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Contact Info</th>
                            <th class="px-8 py-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($users as $user): ?>
                        <tr class="user-table-row group" data-role="<?= $user->role ?>">
                            <td class="px-8 py-7">
                                <div class="flex items-center space-x-5">
                                    <div class="relative">
                                        <div class="w-14 h-14 rounded-2xl bg-school-accent/5 p-0.5 border-2 border-school-accent/10 group-hover:border-school-accent/30 transition-all duration-500">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user->name) ?>&background=<?= str_replace('#', '', $accentColor) ?>&color=fff" class="w-full h-full rounded-[0.8rem] shadow-sm" alt="">
                                        </div>
                                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="text-[15px] font-black text-school-accent tracking-tight group-hover:translate-x-1 transition-transform"><?= htmlspecialchars($user->name) ?></p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.1em] mt-1 flex items-center">
                                            <i data-lucide="at-sign" class="w-3 h-3 mr-1 text-gray-300"></i>
                                            <?= htmlspecialchars($user->username ?? $user->email) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-7">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-4 py-1.5 bg-school-accent text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-lg shadow-school-accent/10"><?= $user->role ?></span>
                                    </div>
                                    <?php if($user->role === 'Teacher'): ?>
                                        <div class="flex items-center text-[9px] font-black text-gray-400 uppercase tracking-widest pl-1">
                                            <i data-lucide="book" class="w-3 h-3 mr-2 text-school-accent/40"></i>
                                            <?= htmlspecialchars($user->subject) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-8 py-7">
                                <div class="space-y-1.5">
                                    <p class="text-sm font-black text-school-accent flex items-center">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 mr-2 text-school-accent/30"></i>
                                        <?= htmlspecialchars($user->phone ?? 'N/A') ?>
                                    </p>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest flex items-center">
                                        <i data-lucide="user-2" class="w-3 h-3 mr-2 text-gray-200"></i>
                                        <?= htmlspecialchars($user->gender ?? 'N/A') ?>
                                    </p>
                                </div>
                            </td>
                            <td class="px-8 py-7">
                                <?php if ($_SESSION['role'] !== 'Teacher'): ?>
                                <div class="flex items-center justify-end space-x-3">
                                    <button onclick="openEditModal('<?= $user->_id ?>', '<?= addslashes($user->name ?? '') ?>', '<?= $user->gender ?? '' ?>', '<?= $user->phone ?? '' ?>', '<?= $user->email ?? '' ?>', '<?= $user->username ?? '' ?>', '<?= $user->role ?? '' ?>', '<?= $user->subject ?? '' ?>')" class="group/edit p-3 bg-white border border-gray-100 text-gray-400 hover:text-white hover:bg-school-accent hover:border-school-accent rounded-2xl shadow-sm transition-all duration-300" title="Edit Profile">
                                        <i data-lucide="edit-3" class="w-5 h-5 group-hover/edit:scale-110 transition-transform"></i>
                                    </button>
                                    <button onclick="openResetModal('<?= $user->_id ?>', '<?= addslashes($user->name) ?>')" class="group/btn p-3 bg-white border border-gray-100 text-gray-400 hover:text-white hover:bg-school-accent hover:border-school-accent rounded-2xl shadow-sm transition-all duration-300" title="Security Credentials">
                                        <i data-lucide="shield-check" class="w-5 h-5 group-hover/btn:scale-110 transition-transform"></i>
                                    </button>
                                    <button onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='manage-users.php?delete_id=<?= $user->_id ?>'" class="group/del p-3 bg-white border border-gray-100 text-gray-400 hover:bg-red-500 hover:text-white hover:border-red-500 rounded-2xl shadow-sm transition-all duration-300" title="Terminate Access">
                                        <i data-lucide="trash-2" class="w-5 h-5 group-hover/del:scale-110 transition-transform"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center justify-end">
                                    <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest italic">Read Only</span>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        function toggleModal() {
            const modal = document.getElementById('addUserModal');
            if (modal.classList.contains('modal-hidden')) {
                const form = modal.querySelector('form');
                form.reset();
                modal.classList.remove('modal-hidden');
                modal.classList.add('modal-active');
            } else {
                modal.classList.add('modal-hidden');
                modal.classList.remove('modal-active');
            }
        }
        function togglePasswordModal() {
            const modal = document.getElementById('resetPasswordModal');
            if (modal.classList.contains('modal-hidden')) {
                modal.classList.remove('modal-hidden');
                modal.classList.add('modal-active');
            } else {
                modal.classList.add('modal-hidden');
                modal.classList.remove('modal-active');
            }
        }
        function toggleEditModal() {
            const modal = document.getElementById('editUserModal');
            if (modal.classList.contains('modal-hidden')) {
                modal.classList.remove('modal-hidden');
                modal.classList.add('modal-active');
            } else {
                modal.classList.add('modal-hidden');
                modal.classList.remove('modal-active');
            }
        }
        function openResetModal(id, name) {
            document.getElementById('reset-user-id').value = id;
            document.getElementById('reset-user-name').innerText = "For User: " + name;
            togglePasswordModal();
        }
        function openEditModal(id, name, gender, phone, email, username, role, subject) {
            document.getElementById('edit-user-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-gender').value = gender;
            document.getElementById('edit-phone').value = phone;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-role').value = role;
            document.getElementById('edit-subject').value = subject;
            toggleEditModal();
        }

        function filterUsers() {
            const searchInput = document.getElementById('userSearch');
            const roleSelect = document.getElementById('roleFilter');
            
            const searchFilter = searchInput.value.toLowerCase();
            const roleFilter = roleSelect.value.toLowerCase();
            
            const table = document.getElementById('userTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                const role = row.getAttribute('data-role').toLowerCase();
                
                const matchesSearch = text.includes(searchFilter);
                const matchesRole = roleFilter === "" || role === roleFilter;
                
                if (matchesSearch && matchesRole) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>

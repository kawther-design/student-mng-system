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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | Al Huda</title>
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
        .user-card { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .user-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 40px 80px -20px rgba(45, 62, 139, 0.1);
        }
        .modal-hidden { opacity: 0; pointer-events: none; transform: scale(0.95); }
        .modal-active { opacity: 1; pointer-events: auto; transform: scale(1); }
        .transition-modal { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-blue/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-2xl rounded-[4rem] p-12 lg:p-16 shadow-2xl relative">
            <button onclick="toggleModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral transition-all"><i data-lucide="x" class="w-8 h-8"></i></button>
            <h3 class="text-3xl font-black text-school-blue mb-2 tracking-tighter uppercase">Add New System User</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em] mb-12">Create credentials for staff or parents</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="add_user">
                
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Full Name</label>
                        <input type="text" name="name" required placeholder="Enter full name" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Gender</label>
                        <select name="gender" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer focus:ring-2 focus:ring-school-blue/5">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Phone Number</label>
                        <input type="text" name="phone" required placeholder="e.g. 063xxxxxx" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Email Address</label>
                        <input type="email" name="email" placeholder="user@alhuda.edu" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                </div>

                <div class="h-px bg-gray-100 my-4"></div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Username</label>
                        <input type="text" name="username" required placeholder="Login Username" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Initial Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">System Role</label>
                        <select name="role" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue cursor-pointer focus:ring-2 focus:ring-school-blue/5">
                            <option value="Teacher">Academic Teacher</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Parent">Parent / Guardian</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-4">Specialization</label>
                        <input type="text" name="subject" placeholder="e.g. Mathematics" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                    </div>
                </div>
                <button type="submit" class="w-full py-6 bg-school-blue text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-blue/20 hover:scale-[1.02] active:scale-98 transition-all">Create User Account</button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-6 bg-school-blue/20 backdrop-blur-md transition-modal">
        <div class="bg-white w-full max-w-md rounded-[4rem] p-12 shadow-2xl relative text-center">
            <button onclick="togglePasswordModal()" class="absolute top-10 right-10 text-gray-400 hover:text-school-coral"><i data-lucide="x" class="w-8 h-8"></i></button>
            <div class="w-20 h-20 bg-school-blue/10 rounded-[2rem] flex items-center justify-center text-school-blue mx-auto mb-8">
                <i data-lucide="key-round" class="w-10 h-10"></i>
            </div>
            <h3 class="text-2xl font-black text-school-blue mb-2 tracking-tighter uppercase">Security Reset</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-12" id="reset-user-name">For User</p>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="user_id" id="reset-user-id">
                <div class="space-y-2 text-left">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-6">New Secure Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••" class="w-full bg-gray-50 border-none rounded-[1.5rem] py-5 px-8 outline-none text-sm font-bold text-school-blue focus:ring-2 focus:ring-school-blue/5">
                </div>
                <button type="submit" class="w-full py-6 bg-school-blue text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-xl">Update Access Credentials</button>
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
            <a href="admin-dashboard.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a href="manage-students.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Students</span>
            </a>
            <a href="manage-users.php" class="sidebar-item active flex items-center space-x-4 p-4 rounded-[1.5rem]">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-black text-sm uppercase tracking-widest">Users</span>
            </a>
            <a href="manage-attendance.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span class="font-bold text-sm">Attendance</span>
            </a>
            <a href="manage-exams.php" class="sidebar-item group flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-blue hover:bg-school-blue/5">
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
                <h2 class="text-2xl font-black text-school-blue tracking-tighter uppercase">User Repository</h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-1">Manage Faculty, Admin & Parents</p>
            </div>
            <button onclick="toggleModal()" class="bg-school-coral px-10 py-4 rounded-[1.5rem] flex items-center space-x-3 text-[10px] font-black uppercase tracking-widest text-white shadow-xl shadow-school-coral/20 hover:scale-105 transition-all">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Register User</span>
            </button>
        </header>

        <div class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php foreach ($users as $user): ?>
                <div class="user-card p-10 rounded-[4rem] flex flex-col items-center text-center group">
                    <div class="relative mb-8">
                        <div class="w-24 h-24 rounded-[2.5rem] bg-school-blue/5 flex items-center justify-center p-1 border-2 border-school-blue/10 group-hover:border-school-blue/30 transition-all duration-500">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user->name) ?>&background=2D3E8B&color=fff" class="w-full h-full rounded-[2.2rem] shadow-2xl shadow-school-blue/20" alt="">
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-school-teal text-white p-2 rounded-xl shadow-lg border-2 border-white">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    
                    <h4 class="text-xl font-black text-school-blue tracking-tighter mb-4 group-hover:translate-y-[-2px] transition-transform"><?= htmlspecialchars($user->name) ?></h4>
                    
                    <div class="flex items-center space-x-3 mb-8">
                        <span class="px-4 py-1 bg-school-blue text-white text-[9px] font-black uppercase tracking-widest rounded-full shadow-lg shadow-school-blue/10"><?= $user->role ?></span>
                        <span class="px-4 py-1 bg-gray-50 text-gray-400 text-[9px] font-black uppercase tracking-widest rounded-full border border-gray-100"><?= $user->gender ?? 'N/A' ?></span>
                    </div>

                    <div class="w-full space-y-3 pt-6 border-t border-gray-50">
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-gray-300 uppercase tracking-widest">Username</span>
                            <span class="text-school-blue font-black"><?= htmlspecialchars($user->username ?? $user->email) ?></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-gray-300 uppercase tracking-widest">Contact</span>
                            <span class="text-school-blue font-black"><?= htmlspecialchars($user->phone ?? 'N/A') ?></span>
                        </div>
                    </div>

                    <?php if($user->role === 'Teacher'): ?>
                        <p class="text-[10px] font-black text-school-teal uppercase tracking-[0.3em] mt-8 bg-school-teal/5 px-6 py-2 rounded-2xl border border-school-teal/10"><?= htmlspecialchars($user->subject) ?></p>
                    <?php endif; ?>

                    <div class="flex items-center space-x-4 mt-10 w-full">
                        <button onclick="openResetModal('<?= $user->_id ?>', '<?= addslashes($user->name) ?>')" class="flex-1 py-4 bg-gray-50 text-gray-400 hover:text-school-blue hover:bg-school-blue/5 rounded-[1.2rem] flex items-center justify-center space-x-3 transition-all">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            <span class="text-[9px] font-black uppercase tracking-widest">Reset</span>
                        </button>
                        <button onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='manage-users.php?delete_id=<?= $user->_id ?>'" class="w-14 h-14 bg-gray-50 text-gray-300 hover:bg-school-coral hover:text-white rounded-[1.2rem] flex items-center justify-center transition-all">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
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
                form.querySelector('input[name="username"]').value = '';
                form.querySelector('input[name="password"]').value = '';
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
        function openResetModal(id, name) {
            document.getElementById('reset-user-id').value = id;
            document.getElementById('reset-user-name').innerText = "For User: " + name;
            togglePasswordModal();
        }
    </script>
</body>
</html>

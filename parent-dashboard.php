<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Parent') {
    if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Vice President') {
        header('Location: login.php');
        exit;
    }
}

// Fetch children associated with this parent
$collection = $database->getCollection('students');
$parentName = $_SESSION['name'];
$children = $collection->find(['parent_name' => $parentName])->toArray();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal | Al Huda School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        .child-gradient {
            background: linear-gradient(135deg, rgba(255,255,255,1) 0%, rgba(255,247,237,0.5) 100%);
        }
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="text-gray-800 flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-gray-100 flex flex-col p-8 shadow-2xl shadow-school-coral/5">
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
            <a href="login.php" class="flex items-center space-x-4 p-4 rounded-[1.5rem] text-gray-400 hover:text-school-coral hover:bg-school-coral/5 transition-all group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                <span class="font-black text-sm uppercase tracking-widest">Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-72 p-10">
        <header class="flex justify-between items-center mb-16">
            <div>
                <h2 class="text-3xl font-black text-school-coral tracking-tighter uppercase">Welcome, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] mt-1">Parent Portal | My Children Tracking</p>
            </div>
            <div class="w-14 h-14 rounded-[1.5rem] bg-school-coral/10 p-1 border-2 border-school-coral/10 flex items-center justify-center text-school-coral">
                <i data-lucide="user" class="w-7 h-7"></i>
            </div>
        </header>

        <div class="mb-10">
            <h3 class="text-xl font-black text-school-blue uppercase tracking-widest border-l-4 border-school-coral pl-4">Your Registered Children</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">
            <?php if (count($children) > 0): ?>
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
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">Attendance</span>
                                    <span class="text-[10px] text-school-coral font-black uppercase tracking-widest">96%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-school-coral h-full w-[96%] rounded-full shadow-[0_0_10px_rgba(249,115,22,0.3)]"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-school-coral/5 p-4 rounded-3xl border border-school-coral/10 text-center">
                                    <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</p>
                                    <p class="text-[10px] font-black text-school-coral uppercase">Active</p>
                                </div>
                                <div class="bg-school-blue/5 p-4 rounded-3xl border border-school-blue/10 text-center">
                                    <p class="text-[8px] text-gray-400 font-black uppercase tracking-widest mb-1">Grade</p>
                                    <p class="text-[10px] font-black text-school-blue uppercase">A (Dist.)</p>
                                </div>
                            </div>
                        </div>

                        <button class="w-full mt-8 py-4 bg-white border border-gray-100 text-school-blue rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-school-blue hover:text-white hover:scale-[1.02] transition-all flex items-center justify-center space-x-3 group">
                            <span>View Full Report</span>
                            <i data-lucide="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                    <!-- Decorative background icon -->
                    <i data-lucide="graduation-cap" class="absolute -bottom-6 -right-6 w-32 h-32 text-school-coral/5 rotate-12"></i>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full glass-card rounded-[3rem] p-20 text-center">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="search-x" class="w-12 h-12 text-gray-300"></i>
                    </div>
                    <h4 class="text-2xl font-black text-school-blue uppercase tracking-tighter mb-2">No Children Found</h4>
                    <p class="text-gray-400 text-sm font-medium">We couldn't find any students registered under your name.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>


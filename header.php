<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al Huda | Secondary School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="global.css" rel="stylesheet">
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
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F8FAFF;
            overflow-x: hidden;
        }

        .mesh-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            background:
                radial-gradient(circle at 15% 50%, rgba(29, 191, 146, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(45, 62, 139, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(255, 107, 82, 0.05) 0%, transparent 50%);
            background-size: cover;
        }

        .nav-link {
            position: relative;
            font-weight: 800;
            font-size: 14px;
            color: #2D3E8B;
            opacity: 0.75;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: opacity 0.3s ease;
        }

        .nav-link.active,
        .nav-link:hover {
            opacity: 1;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: #2D3E8B;
            transition: width 0.3s ease;
        }

        .nav-link.active::after {
            width: 100%;
        }

        .stat-card {
            background: white;
            border-radius: 2.5rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(45, 62, 139, 0.03);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 40px 80px rgba(45, 62, 139, 0.08);
        }

        .program-card {
            background: white;
            border-radius: 3rem;
            padding: 4rem 3rem;
            box-shadow: 0 20px 40px rgba(45, 62, 139, 0.03);
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .program-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 50px 100px rgba(45, 62, 139, 0.1);
        }

        .program-border-teal { border-top: 6px solid #1DBF92; }
        .program-border-coral { border-top: 6px solid #FF6B52; }
        .program-border-purple { border-top: 6px solid #8B5CF6; }

        .contact-card {
            background: white;
            border-radius: 3.5rem;
            padding: 4rem;
            box-shadow: 0 40px 80px rgba(45, 62, 139, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .bg-pattern {
            background-image: radial-gradient(rgba(45, 62, 139, 0.05) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .btn-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-hover:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="relative">
    <div class="mesh-bg"></div>
    <div class="absolute inset-0 bg-pattern opacity-50 z-[-1] pointer-events-none"></div>

    <!-- Header -->
    <header class="container mx-auto px-6 py-10 flex items-center justify-between z-50 relative">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-school-blue rounded-[1.2rem] flex items-center justify-center shadow-lg shadow-school-blue/20">
                <i data-lucide="graduation-cap" class="text-white w-7 h-7"></i>
            </div>
            <h1 class="text-2xl font-black text-school-blue tracking-tighter uppercase">Al Huda</h1>
        </div>

        <nav class="hidden md:flex items-center space-x-12">
            <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">HOME</a>
            <a href="academics.php" class="nav-link <?= ($current_page == 'academics.php') ? 'active' : '' ?>">ACADEMICS</a>
            <a href="examinations.php" class="nav-link <?= ($current_page == 'examinations.php') ? 'active' : '' ?>">EXAMINATIONS</a>
            <a href="features.php" class="nav-link <?= ($current_page == 'features.php') ? 'active' : '' ?>">FEATURES</a>
            <a href="contact.php" class="nav-link <?= ($current_page == 'contact.php') ? 'active' : '' ?>">CONTACT US</a>
        </nav>

        <a href="login.php"
            class="bg-school-blue text-white px-10 py-4 rounded-full font-black text-[11px] uppercase tracking-[0.2em] hover:bg-opacity-90 transition-all shadow-xl shadow-school-blue/20">
            LOGIN
        </a>
    </header>

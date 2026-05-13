<?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section id="home"
        class="container mx-auto px-6 pt-16 pb-32 grid grid-cols-1 lg:grid-cols-2 gap-24 items-center relative z-10">
        <div class="max-w-2xl">
            <div class="flex items-center space-x-3 mb-8">
                <span class="w-12 h-[2px] bg-school-teal rounded-full"></span>
                <p class="text-school-blue font-black text-[11px] tracking-[0.4em] uppercase opacity-60">EMPOWERING FUTURE LEADERS</p>
            </div>
            <h1 class="text-7xl lg:text-[7.8rem] font-black leading-[0.8] tracking-tighter mb-12">
                <span class="text-school-teal block">Al Huda</span>
                <span class="text-school-blue block">Secondary</span>
                <span class="text-school-blue block">School</span>
            </h1>
            <p class="text-gray-400 text-lg font-medium mb-16 leading-relaxed max-w-[90%]">
                Providing a world-class education focused on excellence, character, and future leadership. Our mission is to nurture minds and build strong foundations for success.
            </p>
            <div class="flex flex-wrap gap-10">
                <a href="contact.php"
                    class="btn-hover bg-school-coral text-white px-14 py-7 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-school-coral/30 inline-block">
                    JOIN OUR CAMPUS
                </a>
                <a href="academics.php"
                    class="btn-hover bg-white text-school-blue px-14 py-7 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] border border-gray-100 shadow-xl shadow-gray-200/40 inline-block">
                    EXPLORE PROGRAMS
                </a>
            </div>
        </div>
        <div class="relative">
            <!-- Decorative Blobs -->
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-school-teal/10 rounded-full blur-[100px] animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-school-blue/5 rounded-full blur-[100px] animate-pulse delay-700"></div>
            
            <div class="relative z-10">
                <div class="rounded-[4rem] overflow-hidden shadow-[0_50px_100px_-20px_rgba(45,62,139,0.15)] hover:scale-[1.02] transition-all duration-1000 ease-out">
                    <img src="school_admission.png" alt="Al Huda Secondary School" class="w-full">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mx-auto px-6 mb-44 relative z-20">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 relative">
            <div class="stat-card p-14 group">
                <div class="w-16 h-16 bg-school-blue/5 rounded-2xl flex items-center justify-center text-school-blue mx-auto mb-8 group-hover:bg-school-blue group-hover:text-white transition-all duration-500">
                    <i data-lucide="users" class="w-8 h-8"></i>
                </div>
                <h3 class="text-5xl font-black text-school-blue mb-2 tracking-tighter">4,250</h3>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">STUDENTS</p>
            </div>
            <div class="stat-card p-14 group">
                <div class="w-16 h-16 bg-school-teal/5 rounded-2xl flex items-center justify-center text-school-teal mx-auto mb-8 group-hover:bg-school-teal group-hover:text-white transition-all duration-500">
                    <i data-lucide="graduation-cap" class="w-8 h-8"></i>
                </div>
                <h3 class="text-5xl font-black text-school-teal mb-2 tracking-tighter">150+</h3>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">TEACHERS</p>
            </div>
            <div class="stat-card p-14 group">
                <div class="w-16 h-16 bg-school-coral/5 rounded-2xl flex items-center justify-center text-school-coral mx-auto mb-8 group-hover:bg-school-coral group-hover:text-white transition-all duration-500">
                    <i data-lucide="trending-up" class="w-8 h-8"></i>
                </div>
                <h3 class="text-5xl font-black text-school-coral mb-2 tracking-tighter">98%</h3>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">SUCCESS</p>
            </div>
            <div class="stat-card p-14 group">
                <div class="w-16 h-16 bg-school-purple/5 rounded-2xl flex items-center justify-center text-school-purple mx-auto mb-8 group-hover:bg-school-purple group-hover:text-white transition-all duration-500">
                    <i data-lucide="clock" class="w-8 h-8"></i>
                </div>
                <h3 class="text-5xl font-black text-school-purple mb-2 tracking-tighter">25+</h3>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">YEARS</p>
            </div>
        </div>
    </section>

    <!-- Features Quick Peek -->
    <section class="py-32 bg-white border-y border-gray-50 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-1/3 h-full bg-school-blue/[0.01] -skew-x-12 transform origin-top-right"></div>
        <div class="container mx-auto px-6 relative z-10">
            <div class="flex flex-col lg:flex-row items-end justify-between mb-24 gap-12">
                <div class="max-w-2xl">
                    <p class="text-school-blue font-black text-xs uppercase tracking-[0.3em] mb-6">Why We Stand Out</p>
                    <h2 class="text-6xl font-black text-school-blue tracking-tighter uppercase mb-8 leading-[0.9]">PREMIUM SCHOOL<br>FEATURES</h2>
                    <p class="text-gray-500 text-base font-medium leading-relaxed">Experience a unique blend of heritage and future-ready education systems.</p>
                </div>
                <a href="features.php" class="btn-hover bg-school-blue text-white px-10 py-5 rounded-full font-black text-[11px] uppercase tracking-widest flex items-center group shadow-2xl shadow-school-blue/20">
                    LEARN MORE <i data-lucide="chevron-right" class="ml-3 w-5 h-5 group-hover:translate-x-2 transition-transform"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-16">
                <div class="group cursor-default">
                    <div class="w-20 h-20 bg-school-teal/5 text-school-teal rounded-[1.8rem] flex items-center justify-center mb-10 group-hover:bg-school-teal group-hover:text-white group-hover:rotate-6 transition-all duration-500 shadow-sm">
                        <i data-lucide="shield-check" class="w-10 h-10"></i>
                    </div>
                    <h4 class="font-black text-school-blue text-2xl mb-5 tracking-tighter uppercase">High Quality</h4>
                    <p class="text-gray-400 text-xs leading-relaxed font-bold uppercase tracking-widest">World-class standards & curriculum</p>
                </div>
                <div class="group cursor-default">
                    <div class="w-20 h-20 bg-school-coral/5 text-school-coral rounded-[1.8rem] flex items-center justify-center mb-10 group-hover:bg-school-coral group-hover:text-white group-hover:-rotate-6 transition-all duration-500 shadow-sm">
                        <i data-lucide="star" class="w-10 h-10"></i>
                    </div>
                    <h4 class="font-black text-school-blue text-2xl mb-5 tracking-tighter uppercase">Professional</h4>
                    <p class="text-gray-400 text-xs leading-relaxed font-bold uppercase tracking-widest">Decades of academic excellence</p>
                </div>
                <div class="group cursor-default">
                    <div class="w-20 h-20 bg-school-purple/5 text-school-purple rounded-[1.8rem] flex items-center justify-center mb-10 group-hover:bg-school-purple group-hover:text-white group-hover:rotate-6 transition-all duration-500 shadow-sm">
                        <i data-lucide="trophy" class="w-10 h-10"></i>
                    </div>
                    <h4 class="font-black text-school-blue text-2xl mb-5 tracking-tighter uppercase">Competitive</h4>
                    <p class="text-gray-400 text-xs leading-relaxed font-bold uppercase tracking-widest">Regional tournaments & events</p>
                </div>
            </div>
        </div>
    </section>

<?php include 'footer.php'; ?>
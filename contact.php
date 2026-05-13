<?php include 'header.php'; ?>

    <section class="py-24 relative">
        <div class="container mx-auto px-6">
            <div class="text-center mb-24">
                <p class="text-school-blue font-black text-xs uppercase tracking-[0.3em] mb-6">Let's Connect</p>
                <h2 class="text-6xl md:text-7xl font-black text-school-blue tracking-tighter uppercase inline-block relative">
                    CONTACT US
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-32 h-2 bg-school-coral rounded-full shadow-lg shadow-school-coral/30"></div>
                </h2>
                <p class="text-gray-500 text-base font-medium mt-12 leading-relaxed max-w-2xl mx-auto">
                    Have questions about admissions, curriculum, or school life? Our team is here to provide all the information you need.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-start">
                <div class="contact-card">
                    <h3 class="text-3xl font-black text-school-blue tracking-tighter uppercase mb-12">Send us a message</h3>
                    <form class="space-y-8">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-6">Your Full Name</label>
                            <input type="text" placeholder="Full Name" 
                                class="w-full bg-gray-50 border-none rounded-[2rem] py-6 px-10 outline-none text-sm font-bold text-school-blue focus:ring-4 focus:ring-school-blue/5 transition-all">
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-6">Email Address</label>
                            <input type="email" placeholder="Email Address" 
                                class="w-full bg-gray-50 border-none rounded-[2rem] py-6 px-10 outline-none text-sm font-bold text-school-blue focus:ring-4 focus:ring-school-blue/5 transition-all">
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-6">Your Inquiry</label>
                            <textarea placeholder="Your Message" 
                                class="w-full bg-gray-50 border-none rounded-[2rem] py-6 px-10 outline-none text-sm font-bold text-school-blue focus:ring-4 focus:ring-school-blue/5 h-48 resize-none transition-all"></textarea>
                        </div>
                        <button type="submit" class="w-full py-7 bg-school-coral text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-school-coral/20 hover:scale-[1.03] active:scale-95 transition-all">
                            SEND MESSAGE NOW
                        </button>
                    </form>
                </div>

                <div class="lg:pt-12 space-y-16">
                    <div>
                        <h3 class="text-4xl font-black text-school-blue tracking-tighter uppercase mb-12 leading-tight">School<br>Information</h3>
                        <div class="space-y-10">
                            <div class="flex items-center space-x-8 group">
                                <div class="w-16 h-16 bg-school-blue/5 rounded-[1.8rem] flex items-center justify-center text-school-blue group-hover:bg-school-blue group-hover:text-white transition-all duration-500">
                                    <i data-lucide="map-pin" class="w-7 h-7"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-school-blue text-sm uppercase tracking-tight">Main Campus</h4>
                                    <p class="text-gray-400 text-xs font-bold mt-1">Borama, Somaliland</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-8 group">
                                <div class="w-16 h-16 bg-school-teal/5 rounded-[1.8rem] flex items-center justify-center text-school-teal group-hover:bg-school-teal group-hover:text-white transition-all duration-500">
                                    <i data-lucide="phone-call" class="w-7 h-7"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-school-blue text-sm uppercase tracking-tight">Contact Phone</h4>
                                    <p class="text-gray-400 text-xs font-bold mt-1">63 4538338</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-8 group">
                                <div class="w-16 h-16 bg-school-purple/5 rounded-[1.8rem] flex items-center justify-center text-school-purple group-hover:bg-school-purple group-hover:text-white transition-all duration-500">
                                    <i data-lucide="mail" class="w-7 h-7"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-school-blue text-sm uppercase tracking-tight">Email Support</h4>
                                    <p class="text-gray-400 text-xs font-bold mt-1">contact@alhuda.edu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

<?php include 'footer.php'; ?>

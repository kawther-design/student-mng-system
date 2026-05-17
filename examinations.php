<?php include 'header.php'; ?>

    <section class="py-24 bg-school-blue/5">
        <div class="container mx-auto px-6">
            <div class="text-center mb-24">
                <p class="text-school-blue font-black text-xs uppercase tracking-[0.3em] mb-6">Standards & Assessment</p>
                <h2 class="text-6xl md:text-7xl font-black text-school-blue tracking-tighter uppercase inline-block relative">
                    EXAMS & RESULTS
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-32 h-2 bg-school-coral rounded-full shadow-lg shadow-school-coral/30"></div>
                </h2>
                <p class="text-gray-500 text-base font-medium mt-12 leading-relaxed max-w-2xl mx-auto">
                    Our rigorous assessment framework ensures students achieve their full potential through fair and transparent evaluation processes.
                </p>
            </div>

            <div class="max-w-4xl mx-auto bg-white rounded-[4rem] p-12 lg:p-20 shadow-2xl shadow-school-blue/5 border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-school-blue/5 rounded-full -mr-32 -mt-32"></div>
                
                <div class="relative z-10 text-center mb-16">
                    <h3 class="text-4xl font-black text-school-blue tracking-tighter uppercase mb-4">Check Your Performance</h3>
                    <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.3em]">Enter your unique Student ID to view your academic transcript</p>
                </div>

                <form method="POST" autocomplete="off" class="relative z-10 flex flex-col space-y-10 mb-16">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black text-school-blue uppercase tracking-[0.4em] ml-4">Your ID</label>
                            <div class="relative w-full">
                                <i data-lucide="user" class="absolute left-8 top-1/2 -translate-y-1/2 text-school-blue w-5 h-5"></i>
                                <input type="text" name="student_id" required autocomplete="off" placeholder="Ex: 1001" class="w-full bg-white border-2 border-gray-100 shadow-xl shadow-school-blue/5 focus:border-school-blue focus:ring-0 rounded-[2rem] py-7 pl-20 pr-8 outline-none text-base font-black text-school-blue transition-all uppercase tracking-widest placeholder:text-gray-200">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black text-school-blue uppercase tracking-[0.4em] ml-4">Your Password</label>
                            <div class="relative w-full">
                                <i data-lucide="lock" class="absolute left-8 top-1/2 -translate-y-1/2 text-school-blue w-5 h-5"></i>
                                <input type="password" name="password" required autocomplete="new-password" placeholder="••••" class="w-full bg-white border-2 border-gray-100 shadow-xl shadow-school-blue/5 focus:border-school-blue focus:ring-0 rounded-[2rem] py-7 pl-20 pr-8 outline-none text-base font-black text-school-blue transition-all uppercase tracking-widest placeholder:text-gray-200">
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-center space-y-6">
                        <button type="submit" class="w-full max-w-md py-7 bg-school-blue text-white rounded-[2.5rem] font-black text-xs uppercase tracking-widest shadow-2xl shadow-school-blue/30 hover:scale-[1.03] active:scale-95 transition-all flex items-center justify-center space-x-4 group">
                            <i data-lucide="shield-check" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                            <span>Securely Retrieve Result</span>
                        </button>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest text-center">Note: Default 4-digit password is 1234 or your Student ID</p>
                    </div>
                </form>

                <?php 
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])): 
                    require_once 'db_config.php';
                    $student_id_raw = trim($_POST['student_id']);
                    $password_raw = $_POST['password'] ?? '';
                    $studentColl = $database->getCollection('students');
                    $student = $studentColl->findOne(['student_id' => $student_id_raw]);
                    
                    if ($student):
                        // Simple Logic: Password matches ID, Phone, or default 1234
                        $is_valid = ($password_raw === '1234') || ($password_raw === $student->student_id) || ($password_raw === ($student->student_phone ?? ''));
                        
                        if ($is_valid):
                            $resultsColl = $database->getCollection('results');
                            $examColl = $database->getCollection('exams');
                            $results = $resultsColl->find(['student_id' => (string)$student->_id])->toArray();
                            
                            if (!empty($results)):
                ?>
                            <div class="space-y-12 animate-in fade-in slide-in-from-bottom-8 duration-700">
                                <?php foreach ($results as $res): 
                                    $exam = $examColl->findOne(['_id' => new MongoDB\BSON\ObjectId($res->exam_id)]);
                                ?>
                                <div class="p-10 bg-gray-50/50 rounded-[3rem] border border-gray-100 relative print:shadow-none print:border-none">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
                                        <div>
                                            <h4 class="text-2xl font-black text-school-blue uppercase tracking-tighter"><?= htmlspecialchars($exam->name ?? 'Assessment Result') ?></h4>
                                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-1 italic"><?= htmlspecialchars($student->name) ?> | <?= htmlspecialchars($student->form) ?></p>
                                        </div>
                                        <div class="flex items-center space-x-6">
                                            <div class="text-right">
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Total & Percentage</p>
                                                <p class="text-3xl font-black text-school-blue"><?= $res->total_marks ?> <span class="text-xs text-gray-300">/ 1000</span> <span class="text-school-coral ml-2"><?= number_format($res->total_marks / 10, 1) ?>%</span></p>
                                            </div>
                                            <div class="w-16 h-16 bg-school-teal text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-school-teal/20">
                                                <?php 
                                                    $avg = $res->total_marks / 10;
                                                    if ($avg >= 90) echo 'A+';
                                                    elseif ($avg >= 80) echo 'A';
                                                    elseif ($avg >= 70) echo 'B';
                                                    elseif ($avg >= 60) echo 'C';
                                                    elseif ($avg >= 50) echo 'D';
                                                    else echo 'F';
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                        <?php 
                                        $subjects = [
                                            'arabic' => 'Arabic', 'islamic' => 'Islamic', 'biology' => 'Biology', 
                                            'physics' => 'Physics', 'mathematics' => 'Math', 'chemistry' => 'Chem', 
                                            'somali' => 'Somali', 'english' => 'English', 'history' => 'History', 
                                            'geography' => 'Geog'
                                        ];
                                        foreach ($subjects as $key => $label): 
                                            $mark = $res->marks->$key ?? 0;
                                        ?>
                                        <div class="bg-white p-5 rounded-[1.5rem] border border-gray-50 flex flex-col items-center justify-center space-y-2">
                                            <span class="text-[8px] font-black text-gray-300 uppercase tracking-widest"><?= $label ?></span>
                                            <span class="text-lg font-black <?= $mark >= 50 ? 'text-school-blue' : 'text-school-coral' ?>"><?= $mark ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <button onclick="window.print()" class="mt-8 text-[9px] font-black text-gray-400 uppercase tracking-widest flex items-center hover:text-school-blue transition-all print:hidden">
                                        <i data-lucide="printer" class="w-4 h-4 mr-2"></i>
                                        Download Official Transcript
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="p-12 bg-red-50 text-red-500 rounded-[3rem] text-center">
                                <i data-lucide="shield-x" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                                <p class="text-sm font-black uppercase tracking-widest">Access Denied. The password you entered is incorrect.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                            <div class="p-12 bg-red-50 text-red-500 rounded-[3rem] text-center">
                                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                                <p class="text-sm font-black uppercase tracking-widest">No examination results found for this student ID yet.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="p-12 bg-red-50 text-red-500 rounded-[3rem] text-center">
                            <i data-lucide="user-x" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                            <p class="text-sm font-black uppercase tracking-widest">Student ID not recognized. Please check your ID and try again.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php include 'footer.php'; ?>

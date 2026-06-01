<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$examId = $_GET['exam_id'] ?? '';
$currentClass = $_GET['class'] ?? '';

if (empty($examId)) {
    die("Exam ID is required.");
}

$examColl = $database->getCollection('exams');
$exam = $examColl->findOne(['_id' => new MongoDB\BSON\ObjectId($examId)]);

if (!$exam) {
    die("Exam not found.");
}

$studentColl = $database->getCollection('students');
$resultsColl = $database->getCollection('results');

$query = [];
if (!empty($currentClass)) {
    $query['form'] = $currentClass;
}

$students = $studentColl->find($query, ['sort' => ['name' => 1]])->toArray();

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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="results_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam->name) . '_' . ($currentClass ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $currentClass) : 'all') . '.csv"');

$output = fopen('php://output', 'w');

// Write header
$header = ['Student ID', 'Name', 'Class'];
foreach ($subjects as $label) {
    $header[] = $label;
}
$header[] = 'Total';
$header[] = 'Average';
fputcsv($output, $header);

foreach ($students as $student) {
    $s_oid = (string)$student->_id;
    $result = $resultsColl->findOne(['student_id' => $s_oid, 'exam_id' => $examId]);
    
    $row = [
        $student->student_id ?? '',
        $student->name ?? '',
        $student->form ?? ''
    ];
    
    // PHP stdClass/MongoDB BSON Array to Array conversion for marks
    $marksArray = [];
    if (isset($result->marks)) {
        if (is_array($result->marks)) {
            $marksArray = $result->marks;
        } elseif (is_object($result->marks) && method_exists($result->marks, 'getArrayCopy')) {
            $marksArray = $result->marks->getArrayCopy();
        } else {
            $marksArray = (array)$result->marks;
        }
    }
    
    foreach ($subjects as $key => $label) {
        $row[] = isset($marksArray[$key]) && $marksArray[$key] !== '' ? $marksArray[$key] : 0;
    }
    
    $row[] = $result->total_marks ?? 0;
    $row[] = $result->average ?? 0;
    
    fputcsv($output, $row);
}

fclose($output);
exit;

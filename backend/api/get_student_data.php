<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT s.*, g.Firstname as GuardianFirstname, g.Lastname as GuardianLastname
                  FROM student s
                  LEFT JOIN guardian g ON s.Guardian_ID = g.Guardian_ID
                  WHERE s.Student_ID = $student_id";
$student_result = $conn->query($student_query);
$student = $student_result->fetch_assoc();

// Get student's enrollment info
$enrollment_query = "SELECT e.*, sec.Section_Name, l.Level_Name
                     FROM enrollment_history e
                     JOIN section sec ON e.Section_ID = sec.Section_ID
                     JOIN level l ON sec.Level_ID = l.Level_ID
                     WHERE e.Student_ID = $student_id
                     ORDER BY e.School_Year DESC LIMIT 1";
$enrollment_result = $conn->query($enrollment_query);
$enrollment = $enrollment_result->fetch_assoc();

// Get all grades grouped by subject
$grades_query = "SELECT g.*, sub.Subject_Name, sub.Subject_ID,
                 t.Firstname as TeacherFirstname, t.Lastname as TeacherLastname,
                 sa.Assignment_ID
                 FROM grade g
                 JOIN subject_assignment sa ON g.Assignment_ID = sa.Assignment_ID
                 JOIN subject sub ON sa.Subject_ID = sub.Subject_ID
                 JOIN teacher t ON sa.Teacher_ID = t.Teacher_ID
                 WHERE g.Student_ID = $student_id
                 ORDER BY sub.Subject_Name";

$grades_result = $conn->query($grades_query);

$subjects = [];
while ($row = $grades_result->fetch_assoc()) {
    $subject = $row['Subject_Name'];

    if (!isset($subjects[$subject])) {
        $subjects[$subject] = [
            'subject' => $subject,
            'subject_id' => $row['Subject_ID'],
            'teacher' => $row['TeacherFirstname'] . ' ' . $row['TeacherLastname'],
            'q1_sum' => 0, 'q1_count' => 0,
            'q2_sum' => 0, 'q2_count' => 0,
            'q3_sum' => 0, 'q3_count' => 0,
            'q4_sum' => 0, 'q4_count' => 0
        ];
    }

    // Sum up grades for each quarter (handle multiple assignments per subject)
    if ($row['Q1'] !== null && $row['Q1'] > 0) {
        $subjects[$subject]['q1_sum'] += $row['Q1'];
        $subjects[$subject]['q1_count']++;
    }
    if ($row['Q2'] !== null && $row['Q2'] > 0) {
        $subjects[$subject]['q2_sum'] += $row['Q2'];
        $subjects[$subject]['q2_count']++;
    }
    if ($row['Q3'] !== null && $row['Q3'] > 0) {
        $subjects[$subject]['q3_sum'] += $row['Q3'];
        $subjects[$subject]['q3_count']++;
    }
    if ($row['Q4'] !== null && $row['Q4'] > 0) {
        $subjects[$subject]['q4_sum'] += $row['Q4'];
        $subjects[$subject]['q4_count']++;
    }
}

// Calculate averages for each subject
$subject_grades = [];
$overall_sum = 0;
$overall_count = 0;

foreach ($subjects as $name => $data) {
    $q1_avg = $data['q1_count'] > 0 ? $data['q1_sum'] / $data['q1_count'] : 0;
    $q2_avg = $data['q2_count'] > 0 ? $data['q2_sum'] / $data['q2_count'] : 0;
    $q3_avg = $data['q3_count'] > 0 ? $data['q3_sum'] / $data['q3_count'] : 0;
    $q4_avg = $data['q4_count'] > 0 ? $data['q4_sum'] / $data['q4_count'] : 0;

    // Calculate subject final average (average of quarters that have grades)
    $quarters_with_grades = 0;
    $quarter_sum = 0;
    if ($q1_avg > 0) { $quarters_with_grades++; $quarter_sum += $q1_avg; }
    if ($q2_avg > 0) { $quarters_with_grades++; $quarter_sum += $q2_avg; }
    if ($q3_avg > 0) { $quarters_with_grades++; $quarter_sum += $q3_avg; }
    if ($q4_avg > 0) { $quarters_with_grades++; $quarter_sum += $q4_avg; }

    $final_avg = $quarters_with_grades > 0 ? $quarter_sum / $quarters_with_grades : 0;

    $subject_grades[] = [
        'subject' => $name,
        'teacher' => $data['teacher'],
        'q1' => round($q1_avg, 2),
        'q2' => round($q2_avg, 2),
        'q3' => round($q3_avg, 2),
        'q4' => round($q4_avg, 2),
        'final_avg' => round($final_avg, 2)
    ];

    if ($final_avg > 0) {
        $overall_sum += $final_avg;
        $overall_count++;
    }
}

// Calculate overall average across all subjects
$overall_avg = $overall_count > 0 ? round($overall_sum / $overall_count, 2) : 0;

// Calculate quarterly averages across all subjects
$q1_total = 0; $q1_cnt = 0;
$q2_total = 0; $q2_cnt = 0;
$q3_total = 0; $q3_cnt = 0;

foreach ($subject_grades as $sg) {
    if ($sg['q1'] > 0) { $q1_total += $sg['q1']; $q1_cnt++; }
    if ($sg['q2'] > 0) { $q2_total += $sg['q2']; $q2_cnt++; }
    if ($sg['q3'] > 0) { $q3_total += $sg['q3']; $q3_cnt++; }
}

$quarterly_averages = [
    'q1' => $q1_cnt > 0 ? round($q1_total / $q1_cnt, 2) : 0,
    'q2' => $q2_cnt > 0 ? round($q2_total / $q2_cnt, 2) : 0,
    'q3' => $q3_cnt > 0 ? round($q3_total / $q3_cnt, 2) : 0,
    'q4' => 0  // Not yet graded
];

echo json_encode([
    'student' => $student,
    'enrollment' => $enrollment,
    'subject_grades' => $subject_grades,
    'overall_average' => $overall_avg,
    'quarterly_averages' => $quarterly_averages,
    'total_subjects' => count($subject_grades)
]);

$conn->close();
?>

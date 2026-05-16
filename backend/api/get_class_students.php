<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as teacher
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if ($assignment_id === 0) {
    echo json_encode(['error' => 'Assignment ID required']);
    exit;
}

// Get students enrolled in this class/section
$students_query = "SELECT s.Student_ID, s.Firstname, s.Lastname, s.Age,
                   e.Section_ID, e.School_Year
                   FROM student s
                   JOIN enrollment_history e ON s.Student_ID = e.Student_ID
                   JOIN subject_assignment sa ON e.Section_ID = sa.Section_ID
                   WHERE sa.Assignment_ID = $assignment_id AND sa.Teacher_ID = $teacher_id
                   ORDER BY s.Lastname, s.Firstname";

$students_result = $conn->query($students_query);

$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

// Get existing grades for these students in this assignment
$grades_query = "SELECT g.*, g.Student_ID FROM grade g
                 WHERE g.Assignment_ID = $assignment_id";
$grades_result = $conn->query($grades_query);

$existing_grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $existing_grades[$row['Student_ID']] = $row;
}

echo json_encode([
    'students' => $students,
    'existing_grades' => $existing_grades
]);

$conn->close();
?>

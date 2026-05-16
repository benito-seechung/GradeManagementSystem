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

// Get all students from teacher's classes
$students_query = "SELECT DISTINCT s.Student_ID, s.Firstname, s.Lastname, s.Age, s.Address,
                   sec.Section_Name, l.Level_Name, sub.Subject_Name,
                   e.School_Year, e.Enrollment_Status
                   FROM student s
                   JOIN enrollment_history e ON s.Student_ID = e.Student_ID
                   JOIN section sec ON e.Section_ID = sec.Section_ID
                   JOIN subject_assignment sa ON e.Section_ID = sa.Section_ID
                   JOIN level l ON sec.Level_ID = l.Level_ID
                   LEFT JOIN subject sub ON sub.Subject_ID = (
                       SELECT Subject_ID FROM subject_assignment WHERE Section_ID = sec.Section_ID AND Teacher_ID = $teacher_id LIMIT 1
                   )
                   WHERE sa.Teacher_ID = $teacher_id
                   ORDER BY s.Lastname, s.Firstname";

$students_result = $conn->query($students_query);

$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['students' => $students, 'total' => count($students)]);

$conn->close();
?>

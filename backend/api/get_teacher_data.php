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

// Get teacher info
$teacher_query = "SELECT * FROM teacher WHERE Teacher_ID = $teacher_id";
$teacher_result = $conn->query($teacher_query);
$teacher = $teacher_result->fetch_assoc();

// Get teacher's classes (subject assignments)
$classes_query = "SELECT sa.*, sub.Subject_Name, sec.Section_Name, l.Level_Name
                  FROM subject_assignment sa
                  JOIN subject sub ON sa.Subject_ID = sub.Subject_ID
                  JOIN section sec ON sa.Section_ID = sec.Section_ID
                  JOIN level l ON sec.Level_ID = l.Level_ID
                  WHERE sa.Teacher_ID = $teacher_id";
$classes_result = $conn->query($classes_query);

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

// Count total students across all classes
$students_query = "SELECT COUNT(DISTINCT e.Student_ID) as total
                   FROM enrollment_history e
                   JOIN subject_assignment sa ON e.Section_ID = sa.Section_ID
                   WHERE sa.Teacher_ID = $teacher_id";
$students_result = $conn->query($students_query);
$total_students = $students_result->fetch_assoc()['total'] ?? 0;

// Get pending grades count
$pending_query = "SELECT COUNT(*) as pending FROM grade_submission gs
                  JOIN subject_assignment sa ON gs.Assignment_ID = sa.Assignment_ID
                  WHERE sa.Teacher_ID = $teacher_id AND gs.Status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_grades = $pending_result->fetch_assoc()['pending'] ?? 0;

// Calculate class average
$avg_query = "SELECT AVG(g.Grade_Avg) as avg FROM grade g
              JOIN subject_assignment sa ON g.Assignment_ID = sa.Assignment_ID
              WHERE sa.Teacher_ID = $teacher_id";
$avg_result = $conn->query($avg_query);
$class_avg = round($avg_result->fetch_assoc()['avg'] ?? 0, 2);

echo json_encode([
    'teacher' => $teacher,
    'classes' => $classes,
    'total_students' => $total_students,
    'pending_grades' => $pending_grades,
    'class_average' => $class_avg
]);

$conn->close();
?>

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

// Get teacher's classes
$classes_query = "SELECT sa.Assignment_ID, sub.Subject_Name, sec.Section_Name, l.Level_Name,
                  sa.School_Year, sa.Day, sa.TimeStart, sa.TimeEnd
                  FROM subject_assignment sa
                  JOIN subject sub ON sa.Subject_ID = sub.Subject_ID
                  JOIN section sec ON sa.Section_ID = sec.Section_ID
                  JOIN level l ON sec.Level_ID = l.Level_ID
                  WHERE sa.Teacher_ID = $teacher_id
                  ORDER BY sub.Subject_Name, sec.Section_Name";

$classes_result = $conn->query($classes_query);

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

echo json_encode(['classes' => $classes]);

$conn->close();
?>

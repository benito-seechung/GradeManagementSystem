<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

// Get all students with guardian info
$students_query = "SELECT s.*, g.Firstname as GuardianFirstname, g.Lastname as GuardianLastname, g.Contact_No,
                   e.Section_ID, e.School_Year, e.Enrollment_Status
                   FROM student s
                   LEFT JOIN guardian g ON s.Guardian_ID = g.Guardian_ID
                   LEFT JOIN enrollment_history e ON s.Student_ID = e.Student_ID
                   ORDER BY s.Lastname, s.Firstname";

$students_result = $conn->query($students_query);

$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['students' => $students, 'total' => count($students)]);

$conn->close();
?>

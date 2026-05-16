<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

// Get all teachers
$teachers_query = "SELECT * FROM teacher ORDER BY Lastname, Firstname";

$teachers_result = $conn->query($teachers_query);

$teachers = [];
while ($row = $teachers_result->fetch_assoc()) {
    $teachers[] = $row;
}

echo json_encode(['teachers' => $teachers, 'total' => count($teachers)]);

$conn->close();
?>

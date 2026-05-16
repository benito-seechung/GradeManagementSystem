<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as teacher
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $grades = $_POST['grades'] ?? [];

    if ($assignment_id === 0 || empty($grades)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }

    $saved_count = 0;
    $errors = [];

    foreach ($grades as $student_id => $data) {
        $student_id = intval($student_id);
        $q1 = floatval($data['Q1'] ?? 0);
        $q2 = floatval($data['Q2'] ?? 0);
        $q3 = floatval($data['Q3'] ?? 0);
        $q4 = floatval($data['Q4'] ?? 0);

        // Check if grade record exists for this assignment
        $check = $conn->query("SELECT Grade_ID FROM grade WHERE Student_ID = $student_id AND Assignment_ID = $assignment_id");

        if ($check->num_rows > 0) {
            // Update all quarters
            $update_sql = "UPDATE grade SET 
                          Q1 = $q1, 
                          Q2 = $q2, 
                          Q3 = $q3, 
                          Q4 = $q4 
                          WHERE Student_ID = $student_id AND Assignment_ID = $assignment_id";

            if ($conn->query($update_sql)) {
                $saved_count++;
            } else {
                $errors[] = "Failed to update grade for student $student_id: " . $conn->error;
            }
        } else {
            // Insert new grade record with all quarters
            $insert_sql = "INSERT INTO grade (Student_ID, Assignment_ID, Q1, Q2, Q3, Q4)
                          VALUES ($student_id, $assignment_id, $q1, $q2, $q3, $q4)";

            if ($conn->query($insert_sql)) {
                $saved_count++;
            } else {
                $errors[] = "Failed to insert grade for student $student_id: " . $conn->error;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'saved_count' => $saved_count,
        'errors' => $errors
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>

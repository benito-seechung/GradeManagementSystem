<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    // First, create guardian record
    $guardian_firstname = $conn->real_escape_string($_POST['guardian_firstname']);
    $guardian_lastname = $conn->real_escape_string($_POST['guardian_lastname']);
    $guardian_relationship = $conn->real_escape_string($_POST['guardian_relationship']);
    $guardian_contact = $conn->real_escape_string($_POST['guardian_contact']);

    // Insert guardian and get ID
    $guardian_sql = "INSERT INTO guardian (Firstname, Lastname, Address, Contact_No)
                     VALUES ('$guardian_firstname', '$guardian_lastname', '', '$guardian_contact')";

    if (!$conn->query($guardian_sql)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create guardian: ' . $conn->error]);
        exit;
    }

    $guardian_id = $conn->insert_id;

    // Now create student record (Student_ID is auto-increment)
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename'] ?? '');
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $address = $conn->real_escape_string($_POST['address']);
    $birthdate = $_POST['birthdate'];
    $grade_level = intval($_POST['grade_level']);
    $section_id = intval($_POST['section_id']);

    $default_password = password_hash('password123', PASSWORD_DEFAULT);
    $student_sql = "INSERT INTO student (Firstname, Middlename, Lastname, Address, Birthdate, Guardian_Relationship, Guardian_ID, Password)
                    VALUES ('$firstname', '$middlename', '$lastname', '$address', '$birthdate', '$guardian_relationship', $guardian_id, '$default_password')";

    if (!$conn->query($student_sql)) {
        // Rollback guardian
        $conn->query("DELETE FROM guardian WHERE Guardian_ID = $guardian_id");
        echo json_encode(['success' => false, 'error' => 'Failed to create student: ' . $conn->error]);
        exit;
    }

    // Create enrollment history record
    $current_year = date('Y') . '-' . (date('Y') + 1);
    $new_student_id = $conn->insert_id;
    $enrollment_sql = "INSERT INTO enrollment_history (Student_ID, Section_ID, School_Year, Enrollment_Status, Date_Enrolled)
                       VALUES ($new_student_id, $section_id, '$current_year', 'Active', NOW())";
    $conn->query($enrollment_sql);

    echo json_encode(['success' => true, 'message' => 'Student added successfully']);

} elseif ($action === 'update') {
    $student_id = intval($_POST['student_id']);
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename'] ?? '');
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $address = $conn->real_escape_string($_POST['address']);
    $birthdate = $_POST['birthdate'];
    $guardian_relationship = $conn->real_escape_string($_POST['guardian_relationship']);
    $guardian_id = intval($_POST['guardian_id']);
    $section_id = intval($_POST['section_id']);

    // Update guardian if guardian_id exists
    if ($guardian_id > 0) {
        $guardian_firstname = $conn->real_escape_string($_POST['guardian_firstname']);
        $guardian_lastname = $conn->real_escape_string($_POST['guardian_lastname']);
        $guardian_contact = $conn->real_escape_string($_POST['guardian_contact']);

        $guardian_sql = "UPDATE guardian SET
                         Firstname='$guardian_firstname', Lastname='$guardian_lastname',
                         Contact_No='$guardian_contact'
                         WHERE Guardian_ID = $guardian_id";
        $conn->query($guardian_sql);
    }

    // Update student
    $student_sql = "UPDATE student SET
                    Firstname='$firstname', Middlename='$middlename', Lastname='$lastname',
                    Address='$address', Birthdate='$birthdate', Guardian_Relationship='$guardian_relationship',
                    Guardian_ID=$guardian_id
                    WHERE Student_ID=$student_id";

    if (!$conn->query($student_sql)) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    // Update enrollment history
    $current_year = date('Y') . '-' . (date('Y') + 1);
    $enrollment_sql = "UPDATE enrollment_history SET Section_ID=$section_id, School_Year='$current_year'
                       WHERE Student_ID=$student_id";
    $conn->query($enrollment_sql);

    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);

} elseif ($action === 'delete') {
    $student_id = intval($_POST['student_id']);

    // Delete enrollment history first (foreign key constraint)
    $conn->query("DELETE FROM enrollment_history WHERE Student_ID = $student_id");

    // Get guardian_id before deleting student
    $result = $conn->query("SELECT Guardian_ID FROM student WHERE Student_ID = $student_id");
    if ($row = $result->fetch_assoc()) {
        $guardian_id = $row['Guardian_ID'];
        // Delete student
        if ($conn->query("DELETE FROM student WHERE Student_ID = $student_id")) {
            // Optionally delete guardian if not used by other students
            $check = $conn->query("SELECT COUNT(*) as cnt FROM student WHERE Guardian_ID = $guardian_id");
            $cnt = $check->fetch_assoc()['cnt'];
            if ($cnt == 0) {
                $conn->query("DELETE FROM guardian WHERE Guardian_ID = $guardian_id");
            }
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>

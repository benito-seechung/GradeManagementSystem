<?php
session_start();
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename'] ?? '');
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $address = $conn->real_escape_string($_POST['address']);
    $birthdate = $_POST['birthdate'];
    $email = $conn->real_escape_string($_POST['email']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $subject_teacher = isset($_POST['subject_teacher']) ? 'Yes' : 'No';
    $adviser = isset($_POST['adviser']) ? 'Yes' : 'No';

    $default_password = password_hash('password123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO teacher (Firstname, Middlename, Lastname, Address, Birthdate, Email, Contact_No, Subject_Teacher, Adviser, Password)
            VALUES ('$firstname', '$middlename', '$lastname', '$address', '$birthdate', '$email', '$contact_no', '$subject_teacher', '$adviser', '$default_password')";

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Teacher added successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($action === 'update') {
    $teacher_id = intval($_POST['teacher_id']);
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename'] ?? '');
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $address = $conn->real_escape_string($_POST['address']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $subject_teacher = isset($_POST['subject_teacher']) ? 'Yes' : 'No';
    $adviser = isset($_POST['adviser']) ? 'Yes' : 'No';

    $sql = "UPDATE teacher SET
            Firstname='$firstname', Middlename='$middlename', Lastname='$lastname',
            Address='$address', Email='$email', Contact_No='$contact_no',
            Subject_Teacher='$subject_teacher', Adviser='$adviser'
            WHERE Teacher_ID=$teacher_id";

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($action === 'delete') {
    $teacher_id = intval($_POST['teacher_id']);

    if ($conn->query("DELETE FROM teacher WHERE Teacher_ID = $teacher_id")) {
        echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>

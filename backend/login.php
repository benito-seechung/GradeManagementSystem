<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $user = null;
    $role = null;

    // Check Admin first
    if ($username === 'admin' && ($password === '' || $password === 'admin')) {
        $_SESSION['user_id'] = '1';
        $_SESSION['user_name'] = 'Admin';
        $_SESSION['user_role'] = 'admin';
        $user = ['Admin_ID' => '1', 'Username' => 'Admin'];
        $role = 'admin';
    }

    // Check Teacher if not admin (teachers login with email only)
    if (!$user && strpos($username, '@') !== false) {
        $sql = "SELECT * FROM TEACHER WHERE Email = '$username'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $teacher = $result->fetch_assoc();
            if (isset($teacher['Password'])) {
                if (password_verify($password, $teacher['Password'])) {
                    $_SESSION['user_id'] = $teacher['Teacher_ID'];
                    $_SESSION['user_name'] = $teacher['Firstname'] . ' ' . $teacher['Lastname'];
                    $_SESSION['user_role'] = 'teacher';
                    $_SESSION['is_adviser'] = $teacher['Adviser'];
                    $user = $teacher;
                    $role = 'teacher';
                }
            }
        }
    }

    // Check Student if not admin or teacher (students login with Student_ID only - numeric)
    if (!$user && is_numeric($username)) {
        $sql = "SELECT * FROM STUDENT WHERE Student_ID = '$username'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            if (isset($student['Password'])) {
                if (password_verify($password, $student['Password'])) {
                    $_SESSION['user_id'] = $student['Student_ID'];
                    $_SESSION['user_name'] = $student['Firstname'] . ' ' . $student['Lastname'];
                    $_SESSION['user_role'] = 'student';
                    $user = $student;
                    $role = 'student';
                }
            }
        }
    }

    if ($user) {
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => getRedirectUrl($role),
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials. Please check your username and password.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

function getRedirectUrl($role) {
    switch($role) {
        case 'admin':
            return '/GradeManagementSystem/Frontend/pages/admin/dashboard-admin.php';
        case 'teacher':
            return '/GradeManagementSystem/Frontend/pages/teacher/dashboard-teacher.php';
        case 'student':
            return '/GradeManagementSystem/Frontend/pages/student/dashboard-student.php';
        default:
            return '/GradeManagementSystem/Frontend/pages/auth/login.html';
    }
}
?>

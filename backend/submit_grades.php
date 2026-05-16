<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as teacher
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_POST['submit_grades'])) {
    $teacher_id = $_SESSION['user_id'];
    $assignment_id = $_POST['assignment_id'];
    $quarter = $_POST['quarter'];
    $school_year = $_POST['school_year'];
    $is_final = isset($_POST['finalize']) && $_POST['finalize'] === 'true';
    $grades = json_decode($_POST['grades'], true); // Array of {student_id, grade, remarks}

    $success = true;
    $errors = [];

    foreach ($grades as $gradeData) {
        $student_id = $conn->real_escape_string($gradeData['student_id']);
        $q1 = (float)($gradeData['q1'] ?? 0);
        $q2 = (float)($gradeData['q2'] ?? 0);
        $q3 = (float)($gradeData['q3'] ?? 0);
        $q4 = (float)($gradeData['q4'] ?? 0);
        $remarks = $conn->real_escape_string($gradeData['remarks'] ?? '');

        // Check if grade already exists for this student and assignment
        $check_sql = "SELECT Grade_ID FROM GRADE
                      WHERE Assignment_ID = '$assignment_id'
                      AND Student_ID = '$student_id'";

        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            // Update all quarters at once
            $update_sql = "UPDATE GRADE
                          SET Q1 = '$q1',
                              Q2 = '$q2',
                              Q3 = '$q3',
                              Q4 = '$q4',
                              Remarks = '$remarks'
                          WHERE Assignment_ID = '$assignment_id'
                          AND Student_ID = '$student_id'";

            if (!$conn->query($update_sql)) {
                $success = false;
                $errors[] = "Failed to update grade for student $student_id: " . $conn->error;
            }
        } else {
            // Insert new grade
            $insert_sql = "INSERT INTO GRADE
                          (Assignment_ID, Student_ID, Q1, Q2, Q3, Q4, Remarks)
                          VALUES
                          ('$assignment_id', '$student_id', '$q1', '$q2', '$q3', '$q4', '$remarks')";

            if (!$conn->query($insert_sql)) {
                $success = false;
                $errors[] = "Failed to insert grade for student $student_id: " . $conn->error;
            }
        }
    }

    if ($success) {
        // Update the overall submission status
        $status = $is_final ? 'Submitted' : 'Draft';
        $date_val = $is_final ? "NOW()" : "NULL";
        
        $check_submission = $conn->query("SELECT * FROM grade_submission WHERE Assignment_ID = '$assignment_id' AND Quarter = '$quarter'");
        
        if ($check_submission->num_rows > 0) {
            $conn->query("UPDATE grade_submission 
                         SET Status = '$status', Date_Submitted = $date_val 
                         WHERE Assignment_ID = '$assignment_id' AND Quarter = '$quarter'");
        } else {
            $conn->query("INSERT INTO grade_submission (Assignment_ID, Quarter, Status, Date_Submitted) 
                         VALUES ('$assignment_id', '$quarter', '$status', $date_val)");
        }

        echo json_encode(['success' => true, 'message' => 'Grades saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Some grades failed to save', 'errors' => $errors]);
    }
}

// Fetch students for a given assignment (class + subject combination)
if (isset($_POST['get_students'])) {
    $assignment_id = $_POST['assignment_id'];

    // Get the section_id and school_year from the assignment itself
    $assign_sql = "SELECT Section_ID, School_Year FROM SUBJECT_ASSIGNMENT WHERE Assignment_ID = '$assignment_id'";
    $assign_result = $conn->query($assign_sql);
    if ($assign_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    $assign = $assign_result->fetch_assoc();
    $section_id = $assign['Section_ID'];
    $school_year = $assign['School_Year'];

    // Get students enrolled in this section
    $sql = "SELECT s.Student_ID, s.Firstname, s.Lastname,
                   COALESCE(g.Q1, 0) as Q1,
                   COALESCE(g.Q2, 0) as Q2,
                   COALESCE(g.Q3, 0) as Q3,
                   COALESCE(g.Q4, 0) as Q4,
                   COALESCE(g.Remarks, '') as Remarks
            FROM STUDENT s
            INNER JOIN ENROLLMENT_HISTORY eh ON s.Student_ID = eh.Student_ID
            LEFT JOIN GRADE g ON s.Student_ID = g.Student_ID
                              AND g.Assignment_ID = '$assignment_id'
            WHERE eh.Section_ID = '$section_id'
            AND eh.School_Year = '$school_year'
            ORDER BY s.Lastname, s.Firstname";

    $result = $conn->query($sql);
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(['success' => true, 'students' => $students]);
}

// Fetch classes for a teacher
if (isset($_POST['get_teacher_classes'])) {
    $teacher_id = $_SESSION['user_id'];

    $sql = "SELECT sa.Assignment_ID, sa.Section_ID,
                   s.Subject_ID, s.Subject_Name,
                   sec.Section_Name, l.Level_Name,
                   sa.School_Year, sa.Day, sa.TimeStart, sa.TimeEnd
            FROM SUBJECT_ASSIGNMENT sa
            INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
            INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
            INNER JOIN LEVEL l ON sec.Level_ID = l.Level_ID
            WHERE sa.Teacher_ID = '$teacher_id'
            ORDER BY l.Level_Name, sec.Section_Name";

    $result = $conn->query($sql);
    $classes = [];

    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    echo json_encode(['success' => true, 'classes' => $classes]);
}
?>

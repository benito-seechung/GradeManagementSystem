<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Get student info by student_id
if (isset($_GET['get_student'])) {
    $student_id = $_GET['student_id'];

    $sql = "SELECT * FROM student WHERE Student_ID = '$student_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
}

// Get all subjects for a student with their grades
if (isset($_GET['get_student_subjects'])) {
    $student_id = $_GET['student_id'];

    // Get subjects from SUBJECT_ASSIGNMENT where student is enrolled in the section
    $sql = "SELECT DISTINCT
            s.Subject_ID, s.Subject_Name as SubjectName,
            CONCAT(t.Firstname, ' ', t.Lastname) as TeacherName,
            sa.Assignment_ID, sa.School_Year
            FROM subject s
            INNER JOIN subject_assignment sa ON s.Subject_ID = sa.Subject_ID
            INNER JOIN teacher t ON sa.Teacher_ID = t.Teacher_ID
            INNER JOIN enrollment_history eh ON sa.Section_ID = eh.Section_ID
            WHERE eh.Student_ID = '$student_id'
            AND eh.School_Year = '2024-2025'
            ORDER BY s.Subject_Name";

    $result = $conn->query($sql);
    $subjects = [];

    while ($row = $result->fetch_assoc()) {
        $assignment_id = $row['Assignment_ID'];

        // Get grades for this subject/assignment
        $grades_sql = "SELECT Q1, Q2, Q3, Q4, Grade_Avg
                       FROM grade
                       WHERE Student_ID = '$student_id'
                       AND Assignment_ID = '$assignment_id'";
        $grades_result = $conn->query($grades_sql);

        $subject_data = $row;
        $subject_data['grades'] = [];

        if ($grades_result && $grades_result->num_rows > 0) {
            $grade = $grades_result->fetch_assoc();
            $subject_data['grades'] = [
                ['Quarter' => 'Q1', 'FinalGrade' => $grade['Q1']],
                ['Quarter' => 'Q2', 'FinalGrade' => $grade['Q2']],
                ['Quarter' => 'Q3', 'FinalGrade' => $grade['Q3']],
                ['Quarter' => 'Q4', 'FinalGrade' => $grade['Q4']]
            ];
            $subject_data['average'] = $grade['Grade_Avg'] ? round($grade['Grade_Avg'], 2) : null;
        } else {
            $subject_data['grades'] = [];
            $subject_data['average'] = null;
        }

        $subjects[] = $subject_data;
    }

    echo json_encode(['success' => true, 'subjects' => $subjects]);
}

// Get student's quarterly summary
if (isset($_GET['get_quarterly_summary'])) {
    $student_id = $_GET['student_id'];

    // Get average per quarter across all assignments
    $sql = "SELECT
            AVG(CASE WHEN Q1 > 0 THEN Q1 END) as avg_grade,
            'Q1' as Quarter FROM grade WHERE Student_ID = '$student_id'
            UNION ALL
            SELECT AVG(CASE WHEN Q2 > 0 THEN Q2 END), 'Q2' FROM grade WHERE Student_ID = '$student_id'
            UNION ALL
            SELECT AVG(CASE WHEN Q3 > 0 THEN Q3 END), 'Q3' FROM grade WHERE Student_ID = '$student_id'
            UNION ALL
            SELECT AVG(CASE WHEN Q4 > 0 THEN Q4 END), 'Q4' FROM grade WHERE Student_ID = '$student_id'";

    $result = $conn->query($sql);
    $quarters = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['avg_grade'] !== null) {
            $quarters[] = $row;
        }
    }

    echo json_encode(['success' => true, 'quarters' => $quarters]);
}

// Get overall GPA/average for student
if (isset($_GET['get_student_stats'])) {
    $student_id = $_GET['student_id'];

    $sql = "SELECT
            COUNT(DISTINCT g.Assignment_ID) as subject_count,
            AVG(g.Grade_Avg) as overall_average,
            COUNT(CASE WHEN g.Grade_Avg >= 75 THEN 1 END) as passing_count,
            COUNT(CASE WHEN g.Grade_Avg < 75 THEN 1 END) as failing_count
            FROM grade g
            WHERE g.Student_ID = '$student_id'";

    $result = $conn->query($sql);
    $stats = $result->fetch_assoc();

    if (!$stats) {
        $stats = ['subject_count' => 0, 'overall_average' => 0, 'passing_count' => 0, 'failing_count' => 0];
    }

    // Calculate GPA (simplified: 4.0 scale based on percentage)
    $avg = $stats['overall_average'] ?? 0;
    if ($avg >= 95) $stats['gpa'] = 4.0;
    elseif ($avg >= 90) $stats['gpa'] = 3.75;
    elseif ($avg >= 85) $stats['gpa'] = 3.5;
    elseif ($avg >= 80) $stats['gpa'] = 3.25;
    elseif ($avg >= 75) $stats['gpa'] = 3.0;
    elseif ($avg >= 70) $stats['gpa'] = 2.5;
    elseif ($avg >= 65) $stats['gpa'] = 2.0;
    elseif ($avg >= 60) $stats['gpa'] = 1.5;
    else $stats['gpa'] = 0;

    echo json_encode(['success' => true, 'stats' => $stats]);
}
?>

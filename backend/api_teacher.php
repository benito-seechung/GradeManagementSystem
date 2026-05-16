<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Get teacher's assigned classes
if (isset($_GET['get_teacher_classes'])) {
    $teacher_id = $_GET['teacher_id'];

    $sql = "SELECT DISTINCT
            sa.Assignment_ID, sa.School_Year, sa.Section_ID,
            sec.Section_Name,
            l.Level_Name,
            subj.Subject_ID, subj.Subject_Name,
            sa.Assignment_ID
            FROM subject_assignment sa
            INNER JOIN subject subj ON sa.Subject_ID = subj.Subject_ID
            INNER JOIN section sec ON sa.Section_ID = sec.Section_ID
            INNER JOIN level l ON sec.Level_ID = l.Level_ID
            WHERE sa.Teacher_ID = '$teacher_id'
            ORDER BY l.Level_Name, sec.Section_Name";

    $result = $conn->query($sql);
    $classes = [];

    while ($row = $result->fetch_assoc()) {
        $assignment_id = $row['Assignment_ID'];
        $section_id = $row['Section_ID'];
        $school_year = $row['School_Year'];

        // Get student count for this class
        $count_sql = "SELECT COUNT(*) as count FROM enrollment_history WHERE Section_ID = '$section_id' AND School_Year = '$school_year'";
        $count_result = $conn->query($count_sql);
        $count = $count_result->fetch_assoc()['count'];

        // Get class average from grades
        $avg_sql = "SELECT AVG(Grade_Avg) as avg_grade
                    FROM grade
                    WHERE Assignment_ID = '$assignment_id'";
        $avg_result = $conn->query($avg_sql);
        $avg = $avg_result->fetch_assoc()['avg_grade'] ?? 0;

        $row['student_count'] = $count;
        $row['class_average'] = round($avg ?? 0, 2);
        $classes[] = $row;
    }

    echo json_encode(['success' => true, 'classes' => $classes]);
}

// Get students in a specific class
if (isset($_GET['get_class_students'])) {
    $assignment_id = isset($_GET['assignment_id']) ? $_GET['assignment_id'] : null;

    // Resolve section and year from the assignment
    $assign_check = $conn->query("SELECT Section_ID, School_Year FROM subject_assignment WHERE Assignment_ID = '$assignment_id'");
    if ($assign_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;
    }
    $assign_info = $assign_check->fetch_assoc();
    $section_id = $assign_info['Section_ID'];
    $school_year = $assign_info['School_Year'];

    $sql = "SELECT e.Enrollment_ID, e.Student_ID,
            CONCAT(s.Firstname, ' ', s.Lastname) as StudentName,
            s.Firstname, s.Lastname
            FROM enrollment_history e
            INNER JOIN student s ON e.Student_ID = s.Student_ID
            WHERE e.Section_ID = '$section_id'
            AND e.School_Year = '$school_year'
            ORDER BY s.Lastname, s.Firstname";

    $result = $conn->query($sql);
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $student_id = $row['Student_ID'];

        if ($assignment_id) {
            $grade_sql = "SELECT Q1, Q2, Q3, Q4, Grade_Avg as FinalGrade, Remarks
                          FROM grade
                          WHERE Student_ID = '$student_id'
                          AND Assignment_ID = '$assignment_id'";
            $grade_result = $conn->query($grade_sql);
            $grades = $grade_result->fetch_assoc();
            $row['grades'] = $grades;
        }

        $students[] = $row;
    }

    echo json_encode(['success' => true, 'students' => $students]);
}

// Get teacher's summary stats
if (isset($_GET['get_teacher_stats'])) {
    $teacher_id = $_GET['teacher_id'];

    // Count classes
    $class_sql = "SELECT COUNT(DISTINCT Assignment_ID) as class_count
                  FROM subject_assignment
                  WHERE Teacher_ID = '$teacher_id'";
    $class_result = $conn->query($class_sql);
    $class_count = $class_result->fetch_assoc()['class_count'];

    // Count total students across all classes
    $student_sql = "SELECT COUNT(DISTINCT e.Student_ID) as student_count
                    FROM enrollment_history e
                    INNER JOIN subject_assignment sa ON e.Section_ID = sa.Section_ID AND e.School_Year = sa.School_Year
                    WHERE sa.Teacher_ID = '$teacher_id'";
    $student_result = $conn->query($student_sql);
    $student_count = $student_result->fetch_assoc()['student_count'];

    // Get overall average across all students
    $avg_sql = "SELECT AVG(g.Grade_Avg) as overall_avg
                FROM grade g
                INNER JOIN subject_assignment sa ON g.Assignment_ID = sa.Assignment_ID
                WHERE sa.Teacher_ID = '$teacher_id'";
    $avg_result = $conn->query($avg_sql);
    $overall_avg = round($avg_result->fetch_assoc()['overall_avg'] ?? 0, 2);

    echo json_encode([
        'success' => true,
        'stats' => [
            'class_count' => $class_count,
            'student_count' => $student_count,
            'overall_average' => $overall_avg
        ]
    ]);
}
?>

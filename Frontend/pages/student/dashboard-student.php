<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../auth/login.html');
    exit;
}
$student_id = $_SESSION['user_id'];
include '../../../backend/db_connect.php';

// Fetch student info
$student_sql = "SELECT * FROM STUDENT WHERE Student_ID = '$student_id'";
$student_result = $conn->query($student_sql);
$student = $student_result->fetch_assoc();

// Fetch subjects and grades from API
$api_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_student_subjects=1&student_id=' . $student_id;
$api_response = file_get_contents($api_url);
$api_data = json_decode($api_response, true);
$subjects = $api_data['subjects'] ?? [];

// Fetch quarterly summary
$quarter_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_quarterly_summary=1&student_id=' . $student_id;
$quarter_response = file_get_contents($quarter_url);
$quarter_data = json_decode($quarter_response, true);
$quarters = $quarter_data['quarters'] ?? [];

// Fetch stats
$stats_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_student_stats=1&student_id=' . $student_id;
$stats_response = file_get_contents($stats_url);
$stats_data = json_decode($stats_response, true);
$stats = $stats_data['stats'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Grades Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../../css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="sidebar-overlay"></div>

        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Grades Portal</span>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-category">Main Menu</div>
                <a href="dashboard-student.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>

                <div class="menu-category">Academics</div>
                <a href="student-grades.php" class="menu-item">
                    <i class="fas fa-poll"></i>
                    <span>My Grades</span>
                </a>

                <div class="menu-category">Settings</div>
                <a href="../profile.php" class="menu-item">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>

                <a href="../auth/login.html" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <nav class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>

                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($student['Firstname'] ?? 'S', 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($student['Firstname'] . ' ' . $student['Lastname']); ?></div>
                            <div class="user-role">Student</div>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/login.html"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Student Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($student['Firstname']); ?>! Here's your academic overview.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card primary">
                            <i class="fas fa-star stat-icon"></i>
                            <div class="stat-title">Overall Average</div>
                            <div class="stat-value"><?php echo round($stats['overall_average'] ?? 0, 1); ?>%</div>
                            <div class="stat-change">
                                <i class="fas fa-book"></i> <?php echo $stats['subject_count'] ?? 0; ?> subjects
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <i class="fas fa-book stat-icon"></i>
                            <div class="stat-title">Subjects</div>
                            <div class="stat-value"><?php echo $stats['subject_count'] ?? 0; ?></div>
                            <div class="stat-change">
                                <i class="fas fa-check"></i> <?php echo $stats['passing_count'] ?? 0; ?> passing
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card info">
                            <i class="fas fa-award stat-icon"></i>
                            <div class="stat-title">GPA</div>
                            <div class="stat-value"><?php echo number_format($stats['gpa'] ?? 0, 2); ?></div>
                            <div class="stat-change">
                                <i class="fas fa-graduation-cap"></i> On track
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <i class="fas fa-exclamation-triangle stat-icon"></i>
                            <div class="stat-title">Needs Attention</div>
                            <div class="stat-value"><?php echo $stats['failing_count'] ?? 0; ?></div>
                            <div class="stat-change">
                                <i class="fas fa-book"></i> Subjects below 75%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quarterly Grades Overview -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title"><i class="fas fa-calendar-alt me-2"></i>Quarterly Grades Overview</h5>
                        <a href="student-grades.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="content-card-body">
                        <div class="row g-3">
                            <?php
                            $quarter_labels = ['Q1' => '1st Quarter', 'Q2' => '2nd Quarter', 'Q3' => '3rd Quarter', 'Q4' => '4th Quarter'];
                            $quarter_icons = ['Q1' => 'fa-check-circle', 'Q2' => 'fa-check-circle', 'Q3' => 'fa-spinner', 'Q4' => 'fa-clock'];
                            $quarter_statuses = ['Q1' => 'success', 'Q2' => 'success', 'Q3' => 'info', 'Q4' => 'secondary'];
                            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q):
                                $q_data = null;
                                foreach ($quarters as $qd) {
                                    if ($qd['Quarter'] === $q) { $q_data = $qd; break; }
                                }
                                $avg = $q_data && $q_data['avg_grade'] ? round($q_data['avg_grade'], 1) : '--';
                                $grade = $avg !== '--' ? getLetterGrade($avg) : 'In Progress';
                                $status_class = $avg !== '--' ? getGradeClass($avg) : 'text-muted';
                            ?>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-light">
                                    <small class="text-muted d-block mb-2"><?php echo $quarter_labels[$q]; ?></small>
                                    <div class="h4 fw-bold <?php echo $status_class; ?> mb-0"><?php echo $avg; ?></div>
                                    <span class="badge badge-soft badge-soft-<?php echo $avg !== '--' ? getBadgeClass($avg) : 'secondary'; ?> mt-2"><?php echo $grade; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Subjects Table -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h5 class="content-card-title">Current Grades by Subject</h5>
                        <a href="student-grades.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="content-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Q1</th>
                                        <th>Q2</th>
                                        <th>Q3</th>
                                        <th>Q4</th>
                                        <th>Average</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No subjects enrolled yet.</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($subjects as $idx => $subj): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded p-2 me-2">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($subj['SubjectName']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($subj['TeacherName']); ?></td>
                                        <?php
                                        $q_grades = [];
                                        foreach ($subj['grades'] as $g) {
                                            $q_grades[$g['Quarter']] = $g['FinalGrade'];
                                        }
                                        ?>
                                        <td><?php echo isset($q_grades['Q1']) && $q_grades['Q1'] ? $q_grades['Q1'] . '%' : '--'; ?></td>
                                        <td><?php echo isset($q_grades['Q2']) && $q_grades['Q2'] ? $q_grades['Q2'] . '%' : '--'; ?></td>
                                        <td><?php echo isset($q_grades['Q3']) && $q_grades['Q3'] ? $q_grades['Q3'] . '%' : '--'; ?></td>
                                        <td><?php echo isset($q_grades['Q4']) && $q_grades['Q4'] ? $q_grades['Q4'] . '%' : '--'; ?></td>
                                        <td><strong><?php echo $subj['average'] ? $subj['average'] . '%' : '--'; ?></strong></td>
                                        <td><span class="badge badge-soft badge-soft-<?php echo getBadgeClass($subj['average']); ?>"><?php echo getLetterGrade($subj['average']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
</body>
</html>

<?php
function getLetterGrade($score) {
    if (!$score) return 'N/A';
    if ($score >= 95) return 'A+';
    if ($score >= 90) return 'A';
    if ($score >= 85) return 'B+';
    if ($score >= 80) return 'B';
    if ($score >= 75) return 'C+';
    if ($score >= 70) return 'C';
    if ($score >= 65) return 'D+';
    if ($score >= 60) return 'D';
    return 'F';
}

function getGradeClass($score) {
    if (!$score) return 'text-muted';
    if ($score >= 90) return 'grade-excellent';
    if ($score >= 80) return 'grade-good';
    if ($score >= 70) return 'grade-fair';
    return 'grade-poor';
}

function getBadgeClass($score) {
    if (!$score) return 'secondary';
    if ($score >= 75) return 'success';
    if ($score >= 60) return 'warning';
    return 'danger';
}
?>

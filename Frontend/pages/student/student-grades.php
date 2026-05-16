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
$student = $conn->query($student_sql)->fetch_assoc();

// Fetch all subjects with grades for this student - using same API as dashboard
$api_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_student_subjects=1&student_id=' . $student_id;
$api_response = file_get_contents($api_url);
$api_data = json_decode($api_response, true);
$subjects = $api_data['subjects'] ?? [];

// Fetch quarterly summary - using same API as dashboard
$quarter_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_quarterly_summary=1&student_id=' . $student_id;
$quarter_response = file_get_contents($quarter_url);
$quarter_data = json_decode($quarter_response, true);
$quarters_raw = $quarter_data['quarters'] ?? [];
$quarters = [];
foreach ($quarters_raw as $q) {
    if ($q['avg_grade']) {
        $quarters[$q['Quarter']] = round($q['avg_grade'], 1);
    }
}

// Fetch stats - using same API as dashboard
$stats_url = 'http://localhost/Grade-Management-System/backend/api_student.php?get_student_stats=1&student_id=' . $student_id;
$stats_response = file_get_contents($stats_url);
$stats_data = json_decode($stats_response, true);
$stats = $stats_data['stats'] ?? [];
// Map overall_average to overall_avg for compatibility
if (isset($stats['overall_average']) && !isset($stats['overall_avg'])) {
    $stats['overall_avg'] = $stats['overall_average'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Grades Management Portal</title>
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
                <a href="dashboard-student.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Academics</div>
                <a href="student-grades.php" class="menu-item active">
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
                    <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard-student.php">Home</a></li>
                            <li class="breadcrumb-item active">My Grades</li>
                        </ol>
                    </nav>
                </div>
                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($student['Firstname'], 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($student['Firstname'] . ' ' . $student['Lastname']); ?></div>
                            <div class="user-role">Student</div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">My Grades</h1>
                        <p class="page-subtitle">View your final grades by quarter for each subject</p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>

                <!-- GPA Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card primary">
                            <i class="fas fa-star stat-icon"></i>
                            <div class="stat-title">Overall Average</div>
                            <div class="stat-value"><?php echo round($stats['overall_avg'] ?? 0, 1); ?>%</div>
                            <div class="stat-change"><i class="fas fa-book"></i> <?php echo count($subjects); ?> subjects</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card success">
                            <i class="fas fa-percent stat-icon"></i>
                            <div class="stat-title">Year Average</div>
                            <div class="stat-value"><?php echo round($stats['overall_avg'] ?? 0, 1); ?>%</div>
                            <div class="stat-change"><i class="fas fa-check"></i> School Year 2024-2025</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card info">
                            <i class="fas fa-book stat-icon"></i>
                            <div class="stat-title">Subjects</div>
                            <div class="stat-value"><?php echo count($subjects); ?></div>
                            <div class="stat-change"><i class="fas fa-check"></i> All enrolled</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card warning">
                            <i class="fas fa-award stat-icon"></i>
                            <div class="stat-title">GPA</div>
                            <div class="stat-value"><?php echo number_format(calculateGPA($stats['overall_avg'] ?? 0), 2); ?></div>
                            <div class="stat-change"><i class="fas fa-graduation-cap"></i> 4.0 scale</div>
                        </div>
                    </div>
                </div>

                <!-- Quarterly Grades Overview -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title"><i class="fas fa-chart-bar me-2"></i>Quarterly Overview</h5>
                    </div>
                    <div class="content-card-body">
                        <div class="row g-4">
                            <?php foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q):
                                $avg = $quarters[$q] ?? null;
                                $grade = $avg ? getLetterGrade($avg) : 'In Progress';
                                $badge = $avg ? getBadgeClass($avg) : 'secondary';
                            ?>
                            <div class="col-md-3">
                                <div class="text-center p-4 border rounded bg-light">
                                    <h6 class="text-muted mb-2"><?php echo getQuarterLabel($q); ?></h6>
                                    <div class="display-6 fw-bold <?php echo $avg ? getGradeClass($avg) : 'text-muted'; ?>">
                                        <?php echo $avg ? $avg . '%' : '--'; ?>
                                    </div>
                                    <span class="badge badge-soft badge-soft-<?php echo $badge; ?> mt-2"><?php echo $grade; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Grades Table -->
                <div class="content-card">
                    <div class="content-card-header d-flex justify-content-between align-items-center">
                        <h5 class="content-card-title"><i class="fas fa-table me-2"></i>Final Grades by Subject & Quarter</h5>
                    </div>
                    <div class="content-card-body p-0">
                        <div class="table-responsive">
                            <table class="data-table" id="gradesTable">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th width="90">Q1 Grade</th>
                                        <th width="90">Q2 Grade</th>
                                        <th width="90">Q3 Grade</th>
                                        <th width="90">Q4 Grade</th>
                                        <th width="100">Final Avg</th>
                                        <th width="80">Letter</th>
                                        <th width="80">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No grades available yet.</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $idx = 0; foreach ($subjects as $subj): $idx++; ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
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
                                        <td><span class="badge badge-soft badge-soft-<?php echo ($subj['average'] && $subj['average'] >= 75) ? 'success' : 'warning'; ?>">
                                            <?php echo ($subj['average'] && $subj['average'] >= 75) ? 'Pass' : 'Incomplete'; ?>
                                        </span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($subjects) && $stats['overall_avg']): ?>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Year Average:</strong></td>
                                        <td colspan="2"><strong class="<?php echo getGradeClass($stats['overall_avg']); ?>"><?php echo round($stats['overall_avg'], 1); ?>%</strong></td>
                                        <td><span class="badge badge-soft badge-soft-<?php echo getBadgeClass($stats['overall_avg']); ?>"><?php echo getLetterGrade($stats['overall_avg']); ?></span></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
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

function getQuarterLabel($q) {
    return ['Q1' => '1st Quarter', 'Q2' => '2nd Quarter', 'Q3' => '3rd Quarter', 'Q4' => '4th Quarter'][$q];
}

function calculateGPA($avg) {
    if (!$avg) return 0;
    if ($avg >= 95) return 4.0;
    if ($avg >= 90) return 3.75;
    if ($avg >= 85) return 3.5;
    if ($avg >= 80) return 3.25;
    if ($avg >= 75) return 3.0;
    if ($avg >= 70) return 2.5;
    if ($avg >= 65) return 2.0;
    if ($avg >= 60) return 1.5;
    return 0;
}
?>

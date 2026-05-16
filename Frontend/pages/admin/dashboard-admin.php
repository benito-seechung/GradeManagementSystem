<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}
include '../../../backend/db_connect.php';

// Get counts
$students_count = $conn->query("SELECT COUNT(*) as cnt FROM STUDENT")->fetch_assoc()['cnt'];
$teachers_count = $conn->query("SELECT COUNT(*) as cnt FROM TEACHER")->fetch_assoc()['cnt'];
$sections_count = $conn->query("SELECT COUNT(*) as cnt FROM SECTION")->fetch_assoc()['cnt'];

// Get overall average from grades
$overall_avg = 0;
$avg_result = $conn->query("SELECT AVG(Grade_Avg) as avg FROM GRADE");
if ($avg_result && $row = $avg_result->fetch_assoc()) {
    $overall_avg = round($row['avg'] ?? 0, 1);
}

// Get quarterly submission status
$quarters = [];
foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
    $sql = "SELECT COUNT(DISTINCT sa.Assignment_ID) as total,
            COUNT(DISTINCT g.Assignment_ID) as submitted
            FROM SUBJECT_ASSIGNMENT sa
            LEFT JOIN GRADE g ON sa.Assignment_ID = g.Assignment_ID AND g.$q > 0
            WHERE sa.School_Year = '2024-2025'";
    $result = $conn->query($sql)->fetch_assoc();
    $total = $result['total'] ?? 0;
    $submitted = $result['submitted'] ?? 0;
    $percent = $total > 0 ? round(($submitted / $total) * 100) : 0;
    $status = $percent >= 100 ? 'Closed' : ($percent > 0 ? 'In Progress' : 'Pending');
    $quarters[$q] = ['total' => $total, 'submitted' => $submitted, 'percent' => $percent, 'status' => $status];
}

// Get enrollment by grade level via enrollment_history and section/level
$enrollment_by_grade = [];
$grade_labels = [];
$level_result = $conn->query("SELECT Level_ID, Level_Name FROM LEVEL ORDER BY Level_Name");
while ($l = $level_result->fetch_assoc()) {
    $grade_labels[$l['Level_ID']] = $l['Level_Name'];
    $cnt = $conn->query("SELECT COUNT(DISTINCT eh.Student_ID) as cnt
                         FROM ENROLLMENT_HISTORY eh
                         INNER JOIN SECTION s ON eh.Section_ID = s.Section_ID
                         WHERE s.Level_ID = '{$l['Level_ID']}' AND eh.School_Year = '2024-2025'")->fetch_assoc()['cnt'];
    $enrollment_by_grade[$l['Level_ID']] = $cnt;
}
$total_enrollment = array_sum($enrollment_by_grade);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grades Management Portal</title>
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
                <a href="dashboard-admin.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Management</div>
                <a href="manage-students.php" class="menu-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <a href="manage-teachers.php" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                </a>
                <a href="manage-classes.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Classes & Subjects</span>
                </a>
                <div class="menu-category">Settings</div>
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar">A</div>
                        <div class="user-info">
                            <div class="user-name">Admin</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../auth/login.html"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card primary">
                            <i class="fas fa-user-graduate stat-icon"></i>
                            <div class="stat-title">Total Students</div>
                            <div class="stat-value"><?php echo $students_count; ?></div>
                            <div class="stat-change"><i class="fas fa-check"></i> Registered</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <i class="fas fa-chalkboard-teacher stat-icon"></i>
                            <div class="stat-title">Total Teachers</div>
                            <div class="stat-value"><?php echo $teachers_count; ?></div>
                            <div class="stat-change"><i class="fas fa-check"></i> Active</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <i class="fas fa-door-open stat-icon"></i>
                            <div class="stat-title">Total Sections</div>
                            <div class="stat-value"><?php echo $sections_count; ?></div>
                            <div class="stat-change"><i class="fas fa-users"></i> Class sections</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card info">
                            <i class="fas fa-poll stat-icon"></i>
                            <div class="stat-title">Average Grade</div>
                            <div class="stat-value"><?php echo $overall_avg; ?>%</div>
                            <div class="stat-change"><i class="fas fa-chart-line"></i> Overall performance</div>
                        </div>
                    </div>
                </div>

                <!-- Quarterly Submission Status -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title"><i class="fas fa-calendar-check me-2"></i>Quarterly Grade Submission Status</h5>
                    </div>
                    <div class="content-card-body">
                        <div class="row g-3">
                            <?php foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q):
                                $q_data = $quarters[$q];
                                $badge = $q_data['status'] === 'Closed' ? 'success' : ($q_data['status'] === 'In Progress' ? 'warning' : 'secondary');
                            ?>
                            <div class="col-md-3">
                                <div class="p-3 border rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?php echo getQuarterLabel($q); ?></h6>
                                        <span class="badge badge-soft badge-soft-<?php echo $badge; ?>"><?php echo $q_data['status']; ?></span>
                                    </div>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $badge; ?>" style="width: <?php echo $q_data['percent']; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $q_data['submitted']; ?>/<?php echo $q_data['total']; ?> classes submitted</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header">
                                <h5 class="content-card-title">Quick Actions</h5>
                            </div>
                            <div class="content-card-body">
                                <div class="d-grid gap-2">
                                    <a href="manage-students.php" class="btn btn-primary text-start">
                                        <i class="fas fa-user-plus me-2"></i> Manage Students
                                    </a>
                                    <a href="manage-teachers.php" class="btn btn-success text-start">
                                        <i class="fas fa-user-plus me-2"></i> Manage Teachers
                                    </a>
                                    <a href="manage-classes.php" class="btn btn-info text-start text-white">
                                        <i class="fas fa-book me-2"></i> Manage Classes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment by Grade -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header">
                                <h5 class="content-card-title">Enrollment by Grade Level</h5>
                            </div>
                            <div class="content-card-body">
                                <?php
                                $colors = ['primary', 'success', 'info', 'warning'];
                                $color_idx = 0;
                                foreach ($enrollment_by_grade as $level_id => $cnt):
                                    $percent = $total_enrollment > 0 ? round(($cnt / $total_enrollment) * 100) : 0;
                                    $grade_name = $grade_labels[$level_id] ?? "Level $level_id";
                                    $color = $colors[$color_idx % count($colors)];
                                    $color_idx++;
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo htmlspecialchars($grade_name); ?></span>
                                        <span class="text-muted"><?php echo $cnt; ?> students</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
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
function getQuarterLabel($q) {
    return ['Q1' => '1st Quarter', 'Q2' => '2nd Quarter', 'Q3' => '3rd Quarter', 'Q4' => '4th Quarter'][$q];
}
?>

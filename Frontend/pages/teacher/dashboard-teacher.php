<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../auth/login.html');
    exit;
}
$teacher_id = $_SESSION['user_id'];
include '../../../backend/db_connect.php';

// Fetch teacher info
$teacher_sql = "SELECT * FROM TEACHER WHERE Teacher_ID = '$teacher_id'";
$teacher = $conn->query($teacher_sql)->fetch_assoc();

// Get classes count - using SUBJECT_ASSIGNMENT with Section_ID
$classes_sql = "SELECT COUNT(DISTINCT sa.Assignment_ID) as count
                FROM SUBJECT_ASSIGNMENT sa
                WHERE sa.Teacher_ID = '$teacher_id' AND sa.School_Year = '2024-2025'";
$classes_count = $conn->query($classes_sql)->fetch_assoc()['count'];

// Get total students across all classes via enrollment_history
$students_sql = "SELECT COUNT(DISTINCT eh.Student_ID) as count
                 FROM ENROLLMENT_HISTORY eh
                 INNER JOIN SUBJECT_ASSIGNMENT sa ON eh.Section_ID = sa.Section_ID
                 WHERE sa.Teacher_ID = '$teacher_id' AND eh.School_Year = '2024-2025'";
$students_count = $conn->query($students_sql)->fetch_assoc()['count'];

// Get overall average
$avg_sql = "SELECT AVG(g.Grade_Avg) as avg
            FROM GRADE g
            INNER JOIN SUBJECT_ASSIGNMENT sa ON g.Assignment_ID = sa.Assignment_ID
            WHERE sa.Teacher_ID = '$teacher_id'";
$overall_avg = 0;
$avg_result = $conn->query($avg_sql);
if ($avg_result && $row = $avg_result->fetch_assoc()) {
    $overall_avg = round($row['avg'] ?? 0, 1);
}

// Get classes with submission status
$classes_result = $conn->query("SELECT DISTINCT
    sa.Assignment_ID, sa.Section_ID,
    s.Subject_ID, s.Subject_Name,
    sec.Section_Name, l.Level_Name,
    sa.School_Year, sa.Day, sa.TimeStart, sa.TimeEnd
    FROM SUBJECT_ASSIGNMENT sa
    INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
    INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
    INNER JOIN LEVEL l ON sec.Level_ID = l.Level_ID
    WHERE sa.Teacher_ID = '$teacher_id' AND sa.School_Year = '2024-2025'
    ORDER BY l.Level_Name, sec.Section_Name");
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $assignment_id = $row['Assignment_ID'];

    // Check if grades submitted
    $check_sql = "SELECT COUNT(*) as cnt FROM GRADE WHERE Assignment_ID = '$assignment_id'";
    $cnt = $conn->query($check_sql)->fetch_assoc()['cnt'];
    $row['grades_submitted'] = $cnt > 0;
    $row['student_count'] = $conn->query("SELECT COUNT(DISTINCT Student_ID) as cnt FROM ENROLLMENT_HISTORY WHERE Section_ID = '{$row['Section_ID']}' AND School_Year = '2024-2025'")->fetch_assoc()['cnt'];
    $classes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Grades Management Portal</title>
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
                <a href="dashboard-teacher.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Classes</div>
                <a href="teacher-classes.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>My Classes</span>
                </a>
                <a href="grade-encoding.php" class="menu-item">
                    <i class="fas fa-edit"></i>
                    <span>Grade Encoding</span>
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($teacher['Firstname'] ?? 'T', 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($teacher['Firstname'] . ' ' . $teacher['Lastname']); ?></div>
                            <div class="user-role">Teacher</div>
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
                    <h1 class="page-title">Teacher Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($teacher['Firstname']); ?>! Here's your teaching overview.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card primary">
                            <i class="fas fa-book stat-icon"></i>
                            <div class="stat-title">My Classes</div>
                            <div class="stat-value"><?php echo $classes_count; ?></div>
                            <div class="stat-change"><i class="fas fa-book"></i> Assigned classes</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <i class="fas fa-user-graduate stat-icon"></i>
                            <div class="stat-title">Total Students</div>
                            <div class="stat-value"><?php echo $students_count; ?></div>
                            <div class="stat-change"><i class="fas fa-check"></i> Across all classes</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <i class="fas fa-clipboard-list stat-icon"></i>
                            <div class="stat-title">Class Average</div>
                            <div class="stat-value"><?php echo $overall_avg; ?>%</div>
                            <div class="stat-change"><i class="fas fa-chart-line"></i> Overall performance</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card info">
                            <i class="fas fa-calendar-alt stat-icon"></i>
                            <div class="stat-title">School Year</div>
                            <div class="stat-value">2024-2025</div>
                            <div class="stat-change"><i class="fas fa-check"></i> Current</div>
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
                                    <a href="grade-encoding.php" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i> Enter Grades
                                    </a>
                                    <a href="teacher-classes.php" class="btn btn-info text-white">
                                        <i class="fas fa-book me-2"></i> View Classes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quarterly Grade Status Summary -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header">
                                <h5 class="content-card-title">Grade Encoding</h5>
                            </div>
                            <div class="content-card-body">
                                <p class="text-muted mb-3">Select a class to encode grades:</p>
                                <div class="list-group">
                                    <?php foreach ($classes as $c): ?>
                                    <a href="grade-encoding.php?assignment_id=<?php echo $c['Assignment_ID']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($c['Level_Name']); ?> - <?php echo htmlspecialchars($c['Section_Name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($c['Subject_Name']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $c['grades_submitted'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $c['grades_submitted'] ? 'Grades Entered' : 'Not Started'; ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                    <?php if (empty($classes)): ?>
                                    <div class="text-center text-muted py-3">No classes assigned yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Classes -->
                <div class="content-card mt-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title">My Classes</h5>
                        <a href="teacher-classes.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="content-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Grade Level</th>
                                        <th>Subject</th>
                                        <th>Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($classes, 0, 5) as $c): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($c['Section_Name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($c['Level_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['Subject_Name']); ?></td>
                                        <td><?php echo $c['student_count']; ?> students</td>
                                        <td>
                                            <a href="grade-encoding.php?assignment_id=<?php echo $c['Assignment_ID']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-1"></i>Grades
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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

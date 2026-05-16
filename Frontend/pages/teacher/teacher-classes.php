<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../auth/login.html');
    exit;
}
$teacher_id = $_SESSION['user_id'];
include '../../../backend/db_connect.php';

$teacher_sql = "SELECT * FROM TEACHER WHERE Teacher_ID = '$teacher_id'";
$teacher = $conn->query($teacher_sql)->fetch_assoc();

// Get all classes for this teacher using SUBJECT_ASSIGNMENT with Section_ID
$classes_sql = "SELECT DISTINCT
    sa.Assignment_ID, sa.Section_ID,
    s.Subject_ID, s.Subject_Name,
    sec.Section_Name, l.Level_Name,
    sa.School_Year, sa.Day, sa.TimeStart, sa.TimeEnd
    FROM SUBJECT_ASSIGNMENT sa
    INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
    INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
    INNER JOIN LEVEL l ON sec.Level_ID = l.Level_ID
    WHERE sa.Teacher_ID = '$teacher_id' AND sa.School_Year = '2024-2025'
    ORDER BY l.Level_Name, sec.Section_Name";

$classes_result = $conn->query($classes_sql);
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $section_id = $row['Section_ID'];
    $assignment_id = $row['Assignment_ID'];

    // Get student count from enrollment_history
    $cnt_sql = "SELECT COUNT(DISTINCT Student_ID) as cnt FROM ENROLLMENT_HISTORY WHERE Section_ID = '$section_id' AND School_Year = '2024-2025'";
    $row['student_count'] = $conn->query($cnt_sql)->fetch_assoc()['cnt'];

    // Get class average from GRADE table
    $avg_sql = "SELECT AVG(Grade_Avg) as avg FROM GRADE g
                INNER JOIN SUBJECT_ASSIGNMENT sa2 ON g.Assignment_ID = sa2.Assignment_ID
                WHERE sa2.Assignment_ID = '$assignment_id'";
    $avg_result = $conn->query($avg_sql);
    $row['class_average'] = $avg_result ? round($avg_result->fetch_assoc()['avg'] ?? 0, 1) : 0;

    $classes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Grades Management Portal</title>
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
                <a href="dashboard-teacher.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Classes</div>
                <a href="teacher-classes.php" class="menu-item active">
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
                            <li class="breadcrumb-item"><a href="dashboard-teacher.php">Home</a></li>
                            <li class="breadcrumb-item active">My Classes</li>
                        </ol>
                    </nav>
                </div>
                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($teacher['Firstname'], 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($teacher['Firstname'] . ' ' . $teacher['Lastname']); ?></div>
                            <div class="user-role">Teacher</div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">My Classes</h1>
                    <p class="page-subtitle">Manage and view all your assigned classes</p>
                </div>

                <!-- Classes Grid -->
                <div class="row g-4">
                    <?php if (empty($classes)): ?>
                    <div class="col-12">
                        <div class="content-card">
                            <div class="content-card-body text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No classes assigned yet.</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($classes as $c): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="content-card h-100">
                            <div class="content-card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="bg-primary text-white rounded p-3">
                                        <i class="fas fa-book fa-2x"></i>
                                    </div>
                                    <span class="badge badge-soft badge-soft-success">Active</span>
                                </div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($c['Subject_Name']); ?></h5>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($c['Level_Name']); ?> - <?php echo htmlspecialchars($c['Section_Name']); ?></p>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Students</small>
                                        <div class="fw-bold"><?php echo $c['student_count']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Schedule</small>
                                        <div class="fw-bold"><?php echo htmlspecialchars($c['Day'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">Class Average</small>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?php echo $c['class_average'] >= 75 ? 'success' : 'warning'; ?>" style="width: <?php echo min($c['class_average'], 100); ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $c['class_average']; ?>%</small>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="grade-encoding.php?assignment_id=<?php echo $c['Assignment_ID']; ?>" class="btn btn-sm btn-primary flex-fill">
                                        <i class="fas fa-edit me-1"></i> Grades
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
</body>
</html>

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

// Get sections for this teacher
$assignment_id = $_GET['assignment_id'] ?? null;

// Get classes for dropdown using SUBJECT_ASSIGNMENT
$classes_sql = "SELECT DISTINCT
    sa.Assignment_ID, sa.Section_ID,
    s.Subject_Name,
    sec.Section_Name, l.Level_Name
    FROM SUBJECT_ASSIGNMENT sa
    INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
    INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
    INNER JOIN LEVEL l ON sec.Level_ID = l.Level_ID
    WHERE sa.Teacher_ID = '$teacher_id' AND sa.School_Year = '2024-2025'
    ORDER BY l.Level_Name, sec.Section_Name";
$classes = $conn->query($classes_sql);

// Get students if section selected
$students = [];
$selected_class = null;
if ($assignment_id) {
    // Get section info
    $section_sql = "SELECT sa.Section_ID, s.Subject_Name, sec.Section_Name, l.Level_Name
                    FROM SUBJECT_ASSIGNMENT sa
                    INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
                    INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
                    INNER JOIN LEVEL l ON sec.Level_ID = l.Level_ID
                    WHERE sa.Assignment_ID = '$assignment_id'";
    $selected_class = $conn->query($section_sql)->fetch_assoc();

    if ($selected_class) {
        $section_id = $selected_class['Section_ID'];
        $students_sql = "SELECT DISTINCT
            eh.Student_ID, CONCAT(st.Firstname, ' ', st.Lastname) as StudentName,
            st.Firstname, st.Lastname, st.MiddleName
            FROM ENROLLMENT_HISTORY eh
            INNER JOIN STUDENT st ON eh.Student_ID = st.Student_ID
            WHERE eh.Section_ID = '$section_id' AND eh.School_Year = '2024-2025'
            ORDER BY st.Lastname, st.Firstname";
        $students = $conn->query($students_sql);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Grades Management Portal</title>
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
                <a href="teacher-classes.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>My Classes</span>
                </a>
                <a href="teacher-students.php" class="menu-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>My Students</span>
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
                            <li class="breadcrumb-item active">My Students</li>
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
                    <h1 class="page-title">My Students</h1>
                    <p class="page-subtitle">View students enrolled in your classes</p>
                </div>

                <!-- Class Selection -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title"><i class="fas fa-filter me-2"></i>Select Class</h5>
                    </div>
                    <div class="content-card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Class</label>
                                <select name="assignment_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Select Class</option>
                                    <?php while ($c = $classes->fetch_assoc()): ?>
                                    <option value="<?php echo $c['Assignment_ID']; ?>" <?php echo $assignment_id == $c['Assignment_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['Level_Name']); ?> - <?php echo htmlspecialchars($c['Section_Name']); ?> | <?php echo htmlspecialchars($c['Subject_Name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Students List -->
                <?php if ($selected_class): ?>
                <div class="content-card">
                    <div class="content-card-header">
                        <h5 class="content-card-title">
                            <i class="fas fa-users me-2"></i>
                            <?php echo htmlspecialchars($selected_class['Level_Name']); ?> - <?php echo htmlspecialchars($selected_class['Section_Name']); ?> | <?php echo htmlspecialchars($selected_class['Subject_Name']); ?>
                        </h5>
                    </div>
                    <div class="content-card-body p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($students && $students->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No students enrolled in this class.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $idx = 0; while ($s = $students->fetch_assoc()): $idx++; ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
                                        <td><strong><?php echo htmlspecialchars($s['Student_ID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($s['StudentName']); ?></td>
                                        <td>
                                            <a href="grade-encoding.php?assignment_id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit me-1"></i>Grade
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
</body>
</html>

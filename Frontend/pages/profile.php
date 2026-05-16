<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.html');
    exit;
}
include '../../backend/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Fetch user info based on role
if ($role === 'student') {
    $sql = "SELECT * FROM STUDENT WHERE Student_ID = '$user_id'";
    $user = $conn->query($sql)->fetch_assoc();
    $display_name = ($user['Firstname'] ?? '') . ' ' . ($user['Lastname'] ?? '');
    $email = '';
    $contact = '';
    $birthdate = $user['Birthdate'] ?? '';
    $address = $user['Address'] ?? '';
} elseif ($role === 'teacher') {
    $sql = "SELECT * FROM TEACHER WHERE Teacher_ID = '$user_id'";
    $user = $conn->query($sql)->fetch_assoc();
    $display_name = ($user['Firstname'] ?? '') . ' ' . ($user['Lastname'] ?? '');
    $email = $user['Email'] ?? '';
    $contact = $user['Contact_No'] ?? '';
    $birthdate = $user['Birthdate'] ?? '';
    $address = $user['Address'] ?? '';
} else {
    // Admin
    $sql = "SELECT * FROM ADMIN WHERE Admin_ID = '$user_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $display_name = $user['Username'] ?? 'Admin';
        $email = $user['Email'] ?? '';
    } else {
        $user = null;
        $display_name = 'Admin';
        $email = '';
    }
    $contact = '';
    $birthdate = '';
    $address = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Grades Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
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
                <?php if ($role === 'admin'): ?>
                <a href="admin/dashboard-admin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <?php elseif ($role === 'teacher'): ?>
                <a href="teacher/dashboard-teacher.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Classes</div>
                <a href="teacher/teacher-classes.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>My Classes</span>
                </a>
                <a href="teacher/grade-encoding.php" class="menu-item">
                    <i class="fas fa-edit"></i>
                    <span>Grade Encoding</span>
                </a>
                <?php elseif ($role === 'student'): ?>
                <a href="student/dashboard-student.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Academics</div>
                <a href="student/student-grades.php" class="menu-item">
                    <i class="fas fa-poll"></i>
                    <span>My Grades</span>
                </a>
                <?php else: ?>
                <a href="admin/dashboard-admin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <?php endif; ?>
                <div class="menu-category">Settings</div>
                <a href="profile.php" class="menu-item active">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
                <a href="auth/login.html" class="menu-item">
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
                            <li class="breadcrumb-item active">Profile</li>
                        </ol>
                    </nav>
                </div>
                <div class="navbar-right">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?php echo strtoupper(substr($display_name, 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($display_name); ?></div>
                            <div class="user-role"><?php echo ucfirst($role); ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">View and manage your account information</p>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="content-card">
                            <div class="content-card-body text-center py-5">
                                <div class="user-avatar mx-auto mb-3" style="width: 120px; height: 120px; font-size: 48px;">
                                    <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                                </div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($display_name); ?></h4>
                                <p class="text-muted mb-3"><?php echo ucfirst($role); ?></p>
                                <span class="badge bg-primary"><?php echo strtoupper($role); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="content-card">
                            <div class="content-card-header">
                                <h5 class="content-card-title"><i class="fas fa-user me-2"></i>Account Information</h5>
                            </div>
                            <div class="content-card-body">
                                <div class="row g-3">
                                    <?php if ($role === 'student'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Student ID</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Student_ID']); ?></p>
                                    </div>
                                    <?php elseif ($role === 'teacher'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Teacher ID</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Teacher_ID']); ?></p>
                                    </div>
                                    <?php elseif ($role === 'admin'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Username</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Username'] ?? 'Admin'); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (isset($user['Firstname'])): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">First Name</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Firstname']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (isset($user['Lastname'])): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Last Name</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Lastname']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($role !== 'admin' && isset($user['Email'])): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Email</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Email']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($role === 'teacher' && isset($user['Contact_No'])): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Contact Number</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($user['Contact_No']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($birthdate): ?>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">Birthdate</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($birthdate); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($address): ?>
                                    <div class="col-md-12">
                                        <label class="form-label text-muted">Address</label>
                                        <p class="fw-bold"><?php echo htmlspecialchars($address); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>

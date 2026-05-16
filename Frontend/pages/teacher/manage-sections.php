<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header('Location: ../auth/login.html');
    exit;
}
$teacher_id = $_SESSION['user_id'];
include '../../../backend/db_connect.php';

$teacher_sql = "SELECT * FROM TEACHER WHERE Teacher_ID = '$teacher_id'";
$teacher = $conn->query($teacher_sql)->fetch_assoc();

// Handle delete section
if (isset($_GET['delete_section'])) {
    $id = $_GET['delete_section'];
    // Check if section has students
    $check = $conn->query("SELECT COUNT(*) as cnt FROM ENROLLMENT_HISTORY WHERE Section_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-sections.php?error=Cannot delete section with enrolled students");
        exit;
    }
    $conn->query("DELETE FROM SECTION WHERE Section_ID = '$id'");
    header("Location: manage-sections.php?msg=deleted");
    exit;
}

// Handle add section
if (isset($_POST['add_section'])) {
    $section_name = $conn->real_escape_string($_POST['section_name']);
    $level_id = $conn->real_escape_string($_POST['level_id']);

    $sql = "INSERT INTO SECTION (Section_Name, Level_ID) VALUES ('$section_name', '$level_id')";
    if ($conn->query($sql)) {
        header("Location: manage-sections.php?msg=added");
        exit;
    }
}

// Handle add level
if (isset($_POST['add_level'])) {
    $level_name = $conn->real_escape_string($_POST['level_name']);

    $sql = "INSERT INTO LEVEL (Level_Name) VALUES ('$level_name')";
    if ($conn->query($sql)) {
        header("Location: manage-sections.php?msg=level_added");
        exit;
    }
}

// Handle delete level
if (isset($_GET['delete_level'])) {
    $id = $_GET['delete_level'];
    // Check if level has sections
    $check = $conn->query("SELECT COUNT(*) as cnt FROM SECTION WHERE Level_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-sections.php?error=Cannot delete level with existing sections");
        exit;
    }
    $conn->query("DELETE FROM LEVEL WHERE Level_ID = '$id'");
    header("Location: manage-sections.php?msg=level_deleted");
    exit;
}

// Get all sections with level info
$sections = $conn->query("SELECT s.Section_ID, s.Section_Name, l.Level_Name
                          FROM SECTION s
                          INNER JOIN LEVEL l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Section_Name");

// Get all levels
$levels = $conn->query("SELECT * FROM LEVEL ORDER BY Level_Name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - Grades Management Portal</title>
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
                <a href="grade-encoding.php" class="menu-item">
                    <i class="fas fa-edit"></i>
                    <span>Grade Encoding</span>
                </a>
                <div class="menu-category">Management</div>
                <a href="manage-students.php" class="menu-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <a href="manage-sections.php" class="menu-item active">
                    <i class="fas fa-door-open"></i>
                    <span>Sections</span>
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
                            <li class="breadcrumb-item active">Manage Sections</li>
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
                    <h1 class="page-title">Manage Sections & Grade Levels</h1>
                    <p class="page-subtitle">Add or remove sections and grade levels</p>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-<?php echo strpos($_GET['msg'], 'deleted') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php
                    $msg = $_GET['msg'];
                    if ($msg === 'deleted') echo 'Section deleted successfully.';
                    elseif ($msg === 'added') echo 'Section added successfully.';
                    elseif ($msg === 'level_added') echo 'Grade level added successfully.';
                    elseif ($msg === 'level_deleted') echo 'Grade level deleted successfully.';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Grade Levels -->
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-layer-group me-2"></i>Grade Levels</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLevelModal">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Level Name</th>
                                            <th width="80">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($l = $levels->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($l['Level_ID']); ?></td>
                                            <td><?php echo htmlspecialchars($l['Level_Name']); ?></td>
                                            <td>
                                                <a href="?delete_level=<?php echo urlencode($l['Level_ID']); ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure? This will delete the grade level if it has no sections.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sections -->
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-door-open me-2"></i>Sections</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Section Name</th>
                                            <th>Grade Level</th>
                                            <th width="80">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($s = $sections->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['Section_ID']); ?></td>
                                            <td><?php echo htmlspecialchars($s['Section_Name']); ?></td>
                                            <td><?php echo htmlspecialchars($s['Level_Name']); ?></td>
                                            <td>
                                                <a href="?delete_section=<?php echo urlencode($s['Section_ID']); ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure? This will delete the section if it has no enrolled students.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Level Modal -->
    <div class="modal fade" id="addLevelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Grade Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Level Name</label>
                            <input type="text" name="level_name" class="form-control" placeholder="e.g., Grade 7, Grade 8, Year 1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_level" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Level
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Section Name</label>
                            <input type="text" name="section_name" class="form-control" placeholder="e.g., A, B, Rizal, Bonifacio" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select Grade Level</option>
                                <?php $levels->data_seek(0); while ($l = $levels->fetch_assoc()): ?>
                                <option value="<?php echo $l['Level_ID']; ?>"><?php echo htmlspecialchars($l['Level_Name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_section" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}
include '../../../backend/db_connect.php';

// Handle delete section
if (isset($_GET['delete_section'])) {
    $id = $conn->real_escape_string($_GET['delete_section']);

    // Check if section has students enrolled
    $check = $conn->query("SELECT COUNT(*) as cnt FROM enrollment_history WHERE Section_ID = '$id'");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['cnt'] > 0) {
            header("Location: manage-classes.php?error=Cannot delete section with enrolled students");
            exit;
        }
    }

    // Check if section has subject assignments
    $check = $conn->query("SELECT COUNT(*) as cnt FROM subject_assignment WHERE Section_ID = '$id'");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['cnt'] > 0) {
            header("Location: manage-classes.php?error=Cannot delete section with assigned subjects");
            exit;
        }
    }

    if ($conn->query("DELETE FROM SECTION WHERE Section_ID = '$id'")) {
        header("Location: manage-classes.php?msg=deleted");
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
    }
    exit;
}

// Handle add section
if (isset($_POST['add_section'])) {
    $section_name = $conn->real_escape_string($_POST['section_name']);
    $level_id = $conn->real_escape_string($_POST['level_id']);

    // Validate inputs
    if (empty($section_name) || empty($level_id)) {
        header("Location: manage-classes.php?error=Section name and grade level are required");
        exit;
    }

    // Check if section name already exists (across ALL grade levels)
    $check = $conn->query("SELECT Section_ID FROM SECTION WHERE Section_Name = '$section_name'");
    if ($check->num_rows > 0) {
        header("Location: manage-classes.php?error=Section name '$section_name' is already in use. Section names must be unique across all grade levels.");
        exit;
    }

    $sql = "INSERT INTO SECTION (Section_Name, Level_ID) VALUES ('$section_name', '$level_id')";
    if ($conn->query($sql)) {
        header("Location: manage-classes.php?msg=added");
        exit;
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
        exit;
    }
}

// Handle add subject
if (isset($_POST['add_subject'])) {
    $code = $conn->real_escape_string($_POST['code'] ?? '');
    $name = $conn->real_escape_string($_POST['subject_name']);
    $level_id = $conn->real_escape_string($_POST['level_id']);

    // Debug: log the values
    error_log("Adding subject: name=$name, level_id=$level_id");

    // Validate level_id
    if (empty($level_id)) {
        header("Location: manage-classes.php?error=Please select a grade level");
        exit;
    }

    // Check if subject already exists for this level
    $check = $conn->query("SELECT Subject_ID FROM SUBJECT WHERE Subject_Name = '$name' AND Level_ID = '$level_id'");
    if ($check->num_rows > 0) {
        header("Location: manage-classes.php?error=Subject already exists for this grade level");
        exit;
    }

    $sql = "INSERT INTO SUBJECT (Subject_Name, Level_ID) VALUES ('$name', '$level_id')";
    if ($conn->query($sql)) {
        header("Location: manage-classes.php?msg=subject_added");
        exit;
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
        exit;
    }
}

// Handle assign subject to teacher
if (isset($_POST['assign_subject'])) {
    $teacher_id = $conn->real_escape_string($_POST['teacher_id']);
    $subject_id = $conn->real_escape_string($_POST['subject_id']);
    $section_id = $conn->real_escape_string($_POST['section_id']);
    $school_year = $conn->real_escape_string($_POST['school_year']);

    // Validate inputs
    if (empty($teacher_id) || empty($subject_id) || empty($section_id) || empty($school_year)) {
        header("Location: manage-classes.php?error=Teacher, Subject, Section, and School Year are required");
        exit;
    }

    // Check if this assignment already exists
    $check = $conn->query("SELECT Assignment_ID FROM subject_assignment
                           WHERE Teacher_ID = '$teacher_id'
                           AND Subject_ID = '$subject_id'
                           AND Section_ID = '$section_id'
                           AND School_Year = '$school_year'");
    if ($check->num_rows > 0) {
        header("Location: manage-classes.php?error=This teacher is already assigned to this subject for this section and school year");
        exit;
    }

    $sql = "INSERT INTO SUBJECT_ASSIGNMENT (Teacher_ID, Section_ID, Subject_ID, School_Year)
            VALUES ('$teacher_id', '$section_id', '$subject_id', '$school_year')";
    if ($conn->query($sql)) {
        header("Location: manage-classes.php?msg=assigned");
        exit;
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
        exit;
    }
}

// Handle delete subject
if (isset($_GET['delete_subject'])) {
    $id = $conn->real_escape_string($_GET['delete_subject']);

    // Check if subject has assignments
    $check = $conn->query("SELECT COUNT(*) as cnt FROM subject_assignment WHERE Subject_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-classes.php?error=Cannot delete subject with existing teacher assignments");
        exit;
    }

    if ($conn->query("DELETE FROM SUBJECT WHERE Subject_ID = '$id'")) {
        header("Location: manage-classes.php?msg=deleted");
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
    }
    exit;
}

// Handle delete assignment
if (isset($_GET['delete_assignment'])) {
    $id = $conn->real_escape_string($_GET['delete_assignment']);

    // First delete any grades associated with this assignment
    $conn->query("DELETE FROM grade WHERE Assignment_ID = '$id'");

    // Then delete the assignment
    if ($conn->query("DELETE FROM subject_assignment WHERE Assignment_ID = '$id'")) {
        header("Location: manage-classes.php?msg=deleted");
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
    }
    exit;
}

// Handle add grade level
if (isset($_POST['add_level'])) {
    $level_name = $conn->real_escape_string($_POST['level_name']);

    // Validate input
    if (empty($level_name)) {
        header("Location: manage-classes.php?error=Level name is required");
        exit;
    }

    // Check if level name already exists (case-insensitive)
    $check = $conn->query("SELECT Level_ID FROM LEVEL WHERE LOWER(Level_Name) = LOWER('$level_name')");
    if ($check->num_rows > 0) {
        header("Location: manage-classes.php?error=Grade Level name already exists");
        exit;
    }

    $sql = "INSERT INTO LEVEL (Level_Name) VALUES ('$level_name')";
    if ($conn->query($sql)) {
        header("Location: manage-classes.php?msg=level_added");
        exit;
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
        exit;
    }
}

// Handle delete grade level
if (isset($_GET['delete_level'])) {
    $id = $conn->real_escape_string($_GET['delete_level']);

    // Check if level has sections
    $check = $conn->query("SELECT COUNT(*) as cnt FROM SECTION WHERE Level_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-classes.php?error=Cannot delete level with existing sections");
        exit;
    }

    // Check if level has subjects
    $check = $conn->query("SELECT COUNT(*) as cnt FROM SUBJECT WHERE Level_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-classes.php?error=Cannot delete level with existing subjects");
        exit;
    }

    if ($conn->query("DELETE FROM LEVEL WHERE Level_ID = '$id'")) {
        header("Location: manage-classes.php?msg=deleted");
    } else {
        header("Location: manage-classes.php?error=" . urlencode($conn->error));
    }
    exit;
}

// Get all sections with level info
$sections = $conn->query("SELECT s.Section_ID, s.Section_Name, l.Level_Name, l.Level_ID
                          FROM SECTION s
                          INNER JOIN LEVEL l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Section_Name");
$sections_for_dropdown = $conn->query("SELECT s.Section_ID, s.Section_Name, l.Level_Name, l.Level_ID
                          FROM SECTION s
                          INNER JOIN LEVEL l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Section_Name");

// Build sections array for JavaScript
$sections_js = [];
$sections_for_dropdown->data_seek(0);
while ($s = $sections_for_dropdown->fetch_assoc()) {
    $sections_js[] = $s;
}

// Get all subjects
$subjects = $conn->query("SELECT s.Subject_ID, s.Subject_Name, l.Level_Name, l.Level_ID
                          FROM SUBJECT s
                          INNER JOIN LEVEL l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Subject_Name");
$subjects_for_dropdown = $conn->query("SELECT s.Subject_ID, s.Subject_Name, l.Level_Name, l.Level_ID
                          FROM SUBJECT s
                          INNER JOIN LEVEL l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Subject_Name");

// Get all teachers
$teachers = $conn->query("SELECT * FROM TEACHER ORDER BY Lastname, Firstname");
$teachers_for_dropdown = $conn->query("SELECT * FROM TEACHER ORDER BY Lastname, Firstname");

// Get all levels
$levels = $conn->query("SELECT * FROM LEVEL ORDER BY Level_Name");
$levels_for_dropdown = $conn->query("SELECT * FROM LEVEL ORDER BY Level_Name");

// Get all subject assignments
$assignments = $conn->query("SELECT sa.Assignment_ID,
                                    CONCAT(t.Firstname, ' ', t.Lastname) as TeacherName,
                                    s.Subject_Name,
                                    sec.Section_Name,
                                    sa.School_Year
                            FROM SUBJECT_ASSIGNMENT sa
                            INNER JOIN TEACHER t ON sa.Teacher_ID = t.Teacher_ID
                            INNER JOIN SUBJECT s ON sa.Subject_ID = s.Subject_ID
                            INNER JOIN SECTION sec ON sa.Section_ID = sec.Section_ID
                            ORDER BY sec.Section_Name, t.Lastname");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes & Subjects - Grades Management Portal</title>
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
                <a href="dashboard-admin.php" class="menu-item">
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
                <a href="manage-classes.php" class="menu-item active">
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
                            <li class="breadcrumb-item"><a href="dashboard-admin.php">Home</a></li>
                            <li class="breadcrumb-item active">Manage Classes & Subjects</li>
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
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Manage Classes & Subjects</h1>
                    <p class="page-subtitle">Add, edit, or remove sections, subjects, and teacher assignments</p>
                </div>

                <?php if (isset($_GET['msg'])):
                    $msg = $_GET['msg'];
                    $msg_text = $msg === 'deleted' ? 'Deleted successfully' :
                               ($msg === 'subject_added' ? 'Subject added' :
                               ($msg === 'assigned' ? 'Subject assigned' :
                               ($msg === 'level_added' ? 'Grade Level added' : 'Added successfully')));
                ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $msg_text; ?>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Grade Levels -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-layer-group me-2"></i>Grade Levels</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLevelModal">
                                    <i class="fas fa-plus me-1"></i>Add Grade Level
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Level ID</th>
                                                <th>Level Name</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 0; $levels->data_seek(0); while ($l = $levels->fetch_assoc()): $idx++; ?>
                                            <tr>
                                                <td><?php echo $idx; ?></td>
                                                <td><?php echo htmlspecialchars($l['Level_ID']); ?></td>
                                                <td><?php echo htmlspecialchars($l['Level_Name']); ?></td>
                                                <td>
                                                    <a href="?delete_level=<?php echo $l['Level_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
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

                    <!-- Sections (Classes) -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-door-open me-2"></i>Sections</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                                    <i class="fas fa-plus me-1"></i>Add Section
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Section Name</th>
                                                <th>Grade Level</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 0; while ($s = $sections->fetch_assoc()): $idx++; ?>
                                            <tr>
                                                <td><?php echo $idx; ?></td>
                                                <td><?php echo htmlspecialchars($s['Section_Name']); ?></td>
                                                <td><?php echo htmlspecialchars($s['Level_Name']); ?></td>
                                                <td>
                                                    <a href="?delete_section=<?php echo $s['Section_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
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

                    <!-- Subjects -->
                    <div class="col-lg-6">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-graduation-cap me-2"></i>Subjects</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                    <i class="fas fa-plus me-1"></i>Add Subject
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Subject Name</th>
                                                <th>Grade Level</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 0; while ($s = $subjects->fetch_assoc()): $idx++; ?>
                                            <tr>
                                                <td><?php echo $idx; ?></td>
                                                <td><?php echo htmlspecialchars($s['Subject_Name']); ?></td>
                                                <td><?php echo htmlspecialchars($s['Level_Name']); ?></td>
                                                <td>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <a href="?delete_subject=<?php echo $s['Subject_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete Subject">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Assignment -->
                    <div class="col-12">
                        <div class="content-card">
                            <div class="content-card-header d-flex justify-content-between align-items-center">
                                <h5 class="content-card-title"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Assignments</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                                    <i class="fas fa-plus me-1"></i>Assign
                                </button>
                            </div>
                            <div class="content-card-body p-0">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Teacher</th>
                                                <th>Subject</th>
                                                <th>Section</th>
                                                <th>School Year</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 0; while ($a = $assignments->fetch_assoc()): $idx++; ?>
                                            <tr>
                                                <td><?php echo $idx; ?></td>
                                                <td><?php echo htmlspecialchars($a['TeacherName']); ?></td>
                                                <td><?php echo htmlspecialchars($a['Subject_Name']); ?></td>
                                                <td><?php echo htmlspecialchars($a['Section_Name']); ?></td>
                                                <td><?php echo htmlspecialchars($a['School_Year']); ?></td>
                                                <td>
                                                    <a href="?delete_assignment=<?php echo $a['Assignment_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Remove Assignment">
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
            </div>
        </main>
    </div>

    <!-- Add Grade Level Modal -->
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
                            <input type="text" name="level_name" class="form-control" placeholder="e.g., Grade 7, Grade 8" required>
                            <small class="text-muted">Level ID will be auto-generated</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_level" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Grade Level
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
                            <input type="text" name="section_name" class="form-control" placeholder="e.g., A, B, C, Rizal, Bonifacio" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select Grade Level</option>
                                <?php $levels_for_dropdown->data_seek(0); while ($l = $levels_for_dropdown->fetch_assoc()): ?>
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" placeholder="e.g., Mathematics 10" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select name="level_id" class="form-select" required>
                                <option value="">Select Grade Level</option>
                                <?php $levels_for_dropdown->data_seek(0); while ($l = $levels_for_dropdown->fetch_assoc()): ?>
                                <option value="<?php echo $l['Level_ID']; ?>"><?php echo htmlspecialchars($l['Level_Name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_subject" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Subject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Subject Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Subject to Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">Select Teacher</option>
                                <?php $teachers_for_dropdown->data_seek(0); while ($t = $teachers_for_dropdown->fetch_assoc()): ?>
                                <option value="<?php echo $t['Teacher_ID']; ?>"><?php echo htmlspecialchars($t['Firstname'] . ' ' . $t['Lastname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" id="subject_select" required>
                                <option value="">Select Subject</option>
                                <?php $subjects_for_dropdown->data_seek(0); while ($s = $subjects_for_dropdown->fetch_assoc()): ?>
                                <option value="<?php echo $s['Subject_ID']; ?>" data-level-id="<?php echo $s['Level_ID']; ?>"><?php echo htmlspecialchars($s['Subject_Name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <select name="section_id" class="form-select" id="section_select" required>
                                <option value="">Select Section</option>
                                <?php $sections_for_dropdown->data_seek(0); while ($s = $sections_for_dropdown->fetch_assoc()): ?>
                                <option value="<?php echo $s['Section_ID']; ?>" data-level-id="<?php echo $s['Level_ID']; ?>"><?php echo htmlspecialchars($s['Section_Name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Year</label>
                            <input type="text" name="school_year" class="form-control" value="2024-2025" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_subject" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
    <script>
        const sectionsData = <?php echo json_encode($sections_js); ?>;

        // Filter sections based on selected subject's grade level
        document.getElementById('subject_select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const levelId = selectedOption.getAttribute('data-level-id');
            const sectionSelect = document.getElementById('section_select');

            // Reset section dropdown
            sectionSelect.innerHTML = '<option value="">Select Section</option>';

            if (levelId) {
                // Filter sections by level ID
                sectionsData.forEach(section => {
                    if (section.Level_ID === levelId) {
                        const opt = document.createElement('option');
                        opt.value = section.Section_ID;
                        opt.textContent = section.Section_Name;
                        sectionSelect.appendChild(opt);
                    }
                });
            }
        });
    </script>
</body>
</html>

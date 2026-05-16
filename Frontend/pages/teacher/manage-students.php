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

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM ENROLLMENT_HISTORY WHERE Student_ID = '$id'");
    $conn->query("DELETE FROM GRADE WHERE Student_ID = '$id'");
    $conn->query("DELETE FROM STUDENT WHERE Student_ID = '$id'");
    header("Location: manage-students.php?msg=deleted");
    exit;
}

// Handle add
if (isset($_POST['add_student'])) {
    $id = $conn->real_escape_string($_POST['student_id']);
    $fn = $conn->real_escape_string($_POST['firstname']);
    $ln = $conn->real_escape_string($_POST['lastname']);
    $mn = $conn->real_escape_string($_POST['middlename'] ?? '');
    $bd = $_POST['birthdate'] ?? null;
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $guardian_id = $_POST['guardian_id'] ?? null;
    $guardian_rel = $conn->real_escape_string($_POST['guardian_relationship'] ?? '');

    // If guardian_id is empty, set to NULL
    $bd_value = $bd ? "'$bd'" : "NULL";
    if (empty($guardian_id)) {
        $sql = "INSERT INTO STUDENT (Student_ID, Firstname, Middlename, Lastname, Address, Birthdate, Guardian_Relationship, Guardian_ID)
                VALUES ('$id', '$fn', '$mn', '$ln', '$address', $bd_value, '$guardian_rel', NULL)";
    } else {
        $guardian_id = $conn->real_escape_string($guardian_id);
        $sql = "INSERT INTO STUDENT (Student_ID, Firstname, Middlename, Lastname, Address, Birthdate, Guardian_Relationship, Guardian_ID)
                VALUES ('$id', '$fn', '$mn', '$ln', '$address', $bd_value, '$guardian_rel', '$guardian_id')";
    }
    if ($conn->query($sql)) {
        header("Location: manage-students.php?msg=added");
        exit;
    } else {
        header("Location: manage-students.php?error=" . urlencode($conn->error));
        exit;
    }
}

// Handle update
if (isset($_POST['update_student'])) {
    $id = $conn->real_escape_string($_POST['student_id']);
    $fn = $conn->real_escape_string($_POST['firstname']);
    $ln = $conn->real_escape_string($_POST['lastname']);
    $mn = $conn->real_escape_string($_POST['middlename'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $bd = $_POST['birthdate'] ?? null;
    $guardian_id = $_POST['guardian_id'] ?? null;
    $guardian_rel = $conn->real_escape_string($_POST['guardian_relationship'] ?? '');

    $bd_value = $bd ? "'$bd'" : "NULL";
    $guardian_value = empty($guardian_id) ? "NULL" : "'$guardian_id'";

    $sql = "UPDATE STUDENT SET Firstname='$fn', Middlename='$mn', Lastname='$ln', Address='$address', Birthdate=$bd_value, Guardian_Relationship='$guardian_rel', Guardian_ID=$guardian_value
            WHERE Student_ID='$id'";
    if ($conn->query($sql)) {
        header("Location: manage-students.php?msg=updated");
        exit;
    }
}

// Get all students
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE CONCAT(Firstname, ' ', Lastname) LIKE '%$search%' OR Student_ID LIKE '%$search%'" : '';
$students = $conn->query("SELECT * FROM STUDENT $search_sql ORDER BY Lastname, Firstname");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Grades Management Portal</title>
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
                <a href="manage-students.php" class="menu-item active">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <a href="manage-sections.php" class="menu-item">
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
                            <li class="breadcrumb-item active">Manage Students</li>
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
                    <h1 class="page-title">Manage Students</h1>
                    <p class="page-subtitle">Add, edit, or remove students from the system</p>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-<?php echo $_GET['msg'] === 'deleted' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    Student <?php echo $_GET['msg'] === 'deleted' ? 'deleted' : 'successfully saved'; ?>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error: <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search -->
                <div class="content-card mb-4">
                    <div class="content-card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-9">
                                        <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-1"></i>Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="fas fa-plus me-2"></i>Add Student
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="content-card">
                    <div class="content-card-body p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Birthdate</th>
                                        <th>Address</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $idx = 0; while ($s = $students->fetch_assoc()): $idx++; ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
                                        <td><strong><?php echo htmlspecialchars($s['Student_ID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($s['Firstname'] . ' ' . $s['Lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($s['Birthdate'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($s['Address'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo urlencode($s['Student_ID']); ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this student? This will also remove all their grades and enrollment records.')">
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
        </main>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian</label>
                            <select name="guardian_id" class="form-select">
                                <option value="">-- No Guardian --</option>
                                <?php
                                $guardians = $conn->query("SELECT Guardian_ID, CONCAT(Firstname, ' ', Lastname) as Name FROM GUARDIAN ORDER BY Lastname, Firstname");
                                while ($g = $guardians->fetch_assoc()):
                                ?>
                                <option value="<?php echo htmlspecialchars($g['Guardian_ID']); ?>"><?php echo htmlspecialchars($g['Name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relationship to Guardian</label>
                            <input type="text" name="guardian_relationship" class="form-control" placeholder="e.g., Father, Mother, Guardian">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_student" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id_display" class="form-control" id="edit_student_id_display" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstname" class="form-control" id="edit_firstname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" class="form-control" id="edit_middlename">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" class="form-control" id="edit_lastname" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control" id="edit_birthdate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" id="edit_address">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_student" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
    <script>
        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.Student_ID;
            document.getElementById('edit_student_id_display').value = student.Student_ID;
            document.getElementById('edit_firstname').value = student.Firstname;
            document.getElementById('edit_middlename').value = student.Middlename || '';
            document.getElementById('edit_lastname').value = student.Lastname;
            document.getElementById('edit_birthdate').value = student.Birthdate || '';
            document.getElementById('edit_address').value = student.Address || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>

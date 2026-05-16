<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}
include '../../../backend/db_connect.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);

    // Check if student has enrollment history
    $check = $conn->query("SELECT COUNT(*) as cnt FROM enrollment_history WHERE Student_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-students.php?error=Cannot delete student with enrollment records. Remove enrollment first.");
        exit;
    }

    // Check if student has grades
    $check = $conn->query("SELECT COUNT(*) as cnt FROM grade WHERE Student_ID = '$id'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] > 0) {
        header("Location: manage-students.php?error=Cannot delete student with grade records");
        exit;
    }

    $conn->query("DELETE FROM STUDENT WHERE Student_ID = '$id'");
    header("Location: manage-students.php?msg=deleted");
    exit;
}

// Handle remove from section (unenroll)
if (isset($_GET['unenroll'])) {
    $student_id = $conn->real_escape_string($_GET['unenroll']);
    $school_year = $_GET['sy'] ?? '2024-2025';

    $conn->query("DELETE FROM enrollment_history WHERE Student_ID = '$student_id' AND School_Year = '$school_year'");
    header("Location: manage-students.php?msg=unenrolled");
    exit;
}

// Handle enroll student in section
if (isset($_POST['enroll_student'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $section_id = $conn->real_escape_string($_POST['section_id']);
    $school_year = $conn->real_escape_string($_POST['school_year']);

    // Remove existing enrollment for this school year if any
    $conn->query("DELETE FROM enrollment_history WHERE Student_ID = '$student_id' AND School_Year = '$school_year'");

    // Insert new enrollment
    $sql = "INSERT INTO enrollment_history (Student_ID, Section_ID, School_Year, Enrollment_Status, Date_Enrolled)
            VALUES ('$student_id', '$section_id', '$school_year', 'Active', NOW())";
    if ($conn->query($sql)) {
        header("Location: manage-students.php?msg=enrolled");
    } else {
        header("Location: manage-students.php?error=" . urlencode($conn->error));
    }
    exit;
}

// Handle add - create guardian first, then student
if (isset($_POST['add_student'])) {
    $fn = $_POST['firstname'];
    $ln = $_POST['lastname'];
    $mn = $_POST['middlename'] ?? '';
    $bd = $_POST['birthdate'] ?? null;
    $address = $_POST['address'] ?? '';
    $guardian_rel = $_POST['guardian_relationship'] ?? '';

    // Create guardian record first
    $g_fn = $_POST['guardian_firstname'] ?? '';
    $g_ln = $_POST['guardian_lastname'] ?? '';
    $g_address = $_POST['guardian_address'] ?? '';
    $g_contact = $_POST['guardian_contact'] ?? '';

    $guardian_sql = "INSERT INTO GUARDIAN (Firstname, Lastname, Address, Contact_No) VALUES ('$g_fn', '$g_ln', '$g_address', '$g_contact')";
    $conn->query($guardian_sql);
    $guardian_id = $conn->insert_id;

    $default_password = password_hash('password123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO STUDENT (Firstname, Middlename, Lastname, Address, Birthdate, Guardian_Relationship, Guardian_ID, Password)
            VALUES ('$fn', '$mn', '$ln', '$address', '$bd', '$guardian_rel', '$guardian_id', '$default_password')";
    if ($conn->query($sql)) {
        header("Location: manage-students.php?msg=added");
        exit;
    }
}

// Handle update
if (isset($_POST['update_student'])) {
    $id = $_POST['student_id'];
    $fn = $_POST['firstname'];
    $ln = $_POST['lastname'];
    $mn = $_POST['middlename'] ?? '';
    $address = $_POST['address'] ?? '';
    $bd = $_POST['birthdate'] ?? null;
    $guardian_rel = $_POST['guardian_relationship'] ?? '';

    // Get current guardian_id for this student
    $result = $conn->query("SELECT Guardian_ID FROM STUDENT WHERE Student_ID='$id'");
    $row = $result->fetch_assoc();
    $guardian_id = $row['Guardian_ID'] ?? null;

    // Update guardian if fields provided
    if ($guardian_id && !empty($_POST['guardian_firstname'])) {
        $g_fn = $_POST['guardian_firstname'] ?? '';
        $g_ln = $_POST['guardian_lastname'] ?? '';
        $g_address = $_POST['guardian_address'] ?? '';
        $g_contact = $_POST['guardian_contact'] ?? '';
        $conn->query("UPDATE GUARDIAN SET Firstname='$g_fn', Lastname='$g_ln', Address='$g_address', Contact_No='$g_contact' WHERE Guardian_ID='$guardian_id'");
    }

    $sql = "UPDATE STUDENT SET Firstname='$fn', Middlename='$mn', Lastname='$ln', Address='$address', Birthdate='$bd', Guardian_Relationship='$guardian_rel'
            WHERE Student_ID='$id'";
    if ($conn->query($sql)) {
        header("Location: manage-students.php?msg=updated");
        exit;
    }
}

// Get all students with enrollment info
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE CONCAT(s.Firstname, ' ', s.Lastname) LIKE '%$search%' OR s.Student_ID LIKE '%$search%'" : '';
$students = $conn->query("SELECT s.*, eh.Section_ID, eh.School_Year, sec.Section_Name, l.Level_Name
                          FROM STUDENT s
                          LEFT JOIN enrollment_history eh ON s.Student_ID = eh.Student_ID AND eh.School_Year = '2024-2025'
                          LEFT JOIN section sec ON eh.Section_ID = sec.Section_ID
                          LEFT JOIN level l ON sec.Level_ID = l.Level_ID
                          $search_sql
                          ORDER BY s.Lastname, s.Firstname");

// Get all sections for enrollment dropdown
$sections = $conn->query("SELECT s.Section_ID, s.Section_Name, l.Level_Name
                          FROM section s
                          INNER JOIN level l ON s.Level_ID = l.Level_ID
                          ORDER BY l.Level_Name, s.Section_Name");
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
                <a href="dashboard-admin.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <div class="menu-category">Management</div>
                <a href="manage-students.php" class="menu-item active">
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
                            <li class="breadcrumb-item"><a href="dashboard-admin.php">Home</a></li>
                            <li class="breadcrumb-item active">Manage Students</li>
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
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">Manage Students</h1>
                        <p class="page-subtitle">Add, edit, or remove student records</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i>Add Student
                    </button>
                </div>

                <?php if (isset($_GET['msg'])):
                    $msg_text = $_GET['msg'] === 'deleted' ? 'Student deleted' :
                               ($_GET['msg'] === 'unenrolled' ? 'Student removed from section' : 'Student successfully saved');
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

                <!-- Search -->
                <div class="content-card mb-4">
                    <div class="content-card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="manage-students.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </form>
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
                                        <th>Section</th>
                                        <th>Guardian</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $idx = 0; while ($s = $students->fetch_assoc()): $idx++; ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
                                        <td><strong><?php echo htmlspecialchars($s['Student_ID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($s['Firstname'] . ' ' . $s['Lastname']); ?></td>
                                        <td>
                                            <?php if ($s['Section_ID']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($s['Level_Name'] ?? ''); ?> - <?php echo htmlspecialchars($s['Section_Name'] ?? ''); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Enrolled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php
                                            if ($s['Guardian_ID']) {
                                                $g = $conn->query("SELECT Firstname, Lastname FROM GUARDIAN WHERE Guardian_ID = '{$s['Guardian_ID']}'")->fetch_assoc();
                                                echo $g ? htmlspecialchars($g['Firstname'] . ' ' . $g['Lastname']) : '-';
                                            } else {
                                                echo '-';
                                            }
                                        ?></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($s['Section_ID']): ?>
                                                <a href="?unenroll=<?php echo $s['Student_ID']; ?>&sy=<?php echo urlencode($s['School_Year'] ?? '2024-2025'); ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Remove this student from the section?')" title="Unenroll from Section">
                                                    <i class="fas fa-user-minus"></i>
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="openEnrollModal('<?php echo $s['Student_ID']; ?>', '<?php echo htmlspecialchars($s['Firstname'] . ' ' . $s['Lastname']); ?>')" title="Enroll in Section">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $s['Student_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete Student">
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
        </main>
    </div>

    <!-- Add Modal -->
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
                            <label class="form-label">Guardian Relationship</label>
                            <input type="text" name="guardian_relationship" class="form-control" placeholder="e.g., Father, Mother" required>
                        </div>
                        <hr class="my-3">
                        <h6>Guardian Information</h6>
                        <div class="mb-3">
                            <label class="form-label">Guardian First Name</label>
                            <input type="text" name="guardian_firstname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Last Name</label>
                            <input type="text" name="guardian_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Address</label>
                            <input type="text" name="guardian_address" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Contact No</label>
                            <input type="text" name="guardian_contact" class="form-control" required>
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

    <!-- Edit Modal -->
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
                            <input type="text" class="form-control" id="edit_student_id_display" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstname" id="edit_firstname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" id="edit_middlename" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" id="edit_lastname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" id="edit_birthdate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Relationship</label>
                            <input type="text" name="guardian_relationship" id="edit_guardian_relationship" class="form-control">
                        </div>
                        <hr class="my-3">
                        <h6>Guardian Information</h6>
                        <div class="mb-3">
                            <label class="form-label">Guardian First Name</label>
                            <input type="text" name="guardian_firstname" id="edit_guardian_firstname" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Last Name</label>
                            <input type="text" name="guardian_lastname" id="edit_guardian_lastname" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Address</label>
                            <input type="text" name="guardian_address" id="edit_guardian_address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Contact No</label>
                            <input type="text" name="guardian_contact" id="edit_guardian_contact" class="form-control">
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

    <!-- Enroll Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enroll Student in Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="enroll_student_id">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="enroll_student_name" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section_id" class="form-select" required>
                                <option value="">Select Section</option>
                                <?php $sections->data_seek(0); while ($sec = $sections->fetch_assoc()): ?>
                                <option value="<?php echo $sec['Section_ID']; ?>">
                                    <?php echo htmlspecialchars($sec['Level_Name']); ?> - <?php echo htmlspecialchars($sec['Section_Name']); ?>
                                </option>
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
                        <button type="submit" name="enroll_student" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Enroll Student
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
            document.getElementById('edit_guardian_relationship').value = student.Guardian_Relationship || '';

            // Fetch guardian data if exists
            if (student.Guardian_ID) {
                fetch(`../../api/get_guardian_data.php?guardian_id=${student.Guardian_ID}`)
                    .then(r => r.json())
                    .then(g => {
                        document.getElementById('edit_guardian_firstname').value = g.Firstname || '';
                        document.getElementById('edit_guardian_lastname').value = g.Lastname || '';
                        document.getElementById('edit_guardian_address').value = g.Address || '';
                        document.getElementById('edit_guardian_contact').value = g.Contact_No || '';
                    });
            }
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openEnrollModal(studentId, studentName) {
            document.getElementById('enroll_student_id').value = studentId;
            document.getElementById('enroll_student_name').value = studentName;
            new bootstrap.Modal(document.getElementById('enrollModal')).show();
        }
    </script>
</body>
</html>

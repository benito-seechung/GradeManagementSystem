<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.html');
    exit;
}
include '../../../backend/db_connect.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM TEACHER WHERE Teacher_ID = '$id'");
    header("Location: manage-teachers.php?msg=deleted");
    exit;
}

// Handle add
if (isset($_POST['add_teacher'])) {
    $fn = $_POST['firstname'];
    $ln = $_POST['lastname'];
    $mn = $_POST['middlename'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact_no'] ?? '';
    $address = $_POST['address'] ?? '';
    $bd = $_POST['birthdate'] ?? null;
    $default_password = password_hash('password123', PASSWORD_DEFAULT);

    $sql = "INSERT INTO TEACHER (Firstname, Middlename, Lastname, Email, Contact_No, Address, Birthdate, Password)
            VALUES ('$fn', '$mn', '$ln', '$email', '$contact', '$address', '$bd', '$default_password')";
    if ($conn->query($sql)) {
        header("Location: manage-teachers.php?msg=added");
        exit;
    }
}

// Handle update
if (isset($_POST['update_teacher'])) {
    $id = $_POST['teacher_id'];
    $fn = $_POST['firstname'];
    $ln = $_POST['lastname'];
    $mn = $_POST['middlename'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact_no'] ?? '';
    $address = $_POST['address'] ?? '';
    $bd = $_POST['birthdate'] ?? null;

    $sql = "UPDATE TEACHER SET Firstname='$fn', Middlename='$mn', Lastname='$ln', Email='$email', Contact_No='$contact', Address='$address', Birthdate='$bd'
            WHERE Teacher_ID='$id'";
    if ($conn->query($sql)) {
        header("Location: manage-teachers.php?msg=updated");
        exit;
    }
}

// Get all teachers
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE CONCAT(Firstname, ' ', Lastname) LIKE '%$search%' OR Teacher_ID LIKE '%$search%'" : '';
$teachers = $conn->query("SELECT * FROM TEACHER $search_sql ORDER BY Lastname, Firstname");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Grades Management Portal</title>
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
                <a href="manage-teachers.php" class="menu-item active">
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
                            <li class="breadcrumb-item active">Manage Teachers</li>
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
                        <h1 class="page-title">Manage Teachers</h1>
                        <p class="page-subtitle">Add, edit, or remove teacher records</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i>Add Teacher
                    </button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-<?php echo $_GET['msg'] === 'deleted' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    Teacher <?php echo $_GET['msg'] === 'deleted' ? 'deleted' : 'successfully saved'; ?>.
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
                                <a href="manage-teachers.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Teachers Table -->
                <div class="content-card">
                    <div class="content-card-body p-0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Teacher ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $idx = 0; while ($t = $teachers->fetch_assoc()): $idx++; ?>
                                    <tr>
                                        <td><?php echo $idx; ?></td>
                                        <td><strong><?php echo htmlspecialchars($t['Teacher_ID']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($t['Firstname'] . ' ' . $t['Lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($t['Email'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($t['Contact_No'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editTeacher(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <a href="?delete=<?php echo $t['Teacher_ID']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash me-1"></i>Delete
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
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
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_no" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_teacher" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Teacher
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
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="edit_teacher_id">
                        <div class="mb-3">
                            <label class="form-label">Teacher ID</label>
                            <input type="text" class="form-control" id="edit_teacher_id_display" disabled>
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
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_no" id="edit_contact_no" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_teacher" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
    <script>
        function editTeacher(teacher) {
            document.getElementById('edit_teacher_id').value = teacher.Teacher_ID;
            document.getElementById('edit_teacher_id_display').value = teacher.Teacher_ID;
            document.getElementById('edit_firstname').value = teacher.Firstname;
            document.getElementById('edit_middlename').value = teacher.Middlename || '';
            document.getElementById('edit_lastname').value = teacher.Lastname;
            document.getElementById('edit_birthdate').value = teacher.Birthdate || '';
            document.getElementById('edit_email').value = teacher.Email || '';
            document.getElementById('edit_contact_no').value = teacher.Contact_No || '';
            document.getElementById('edit_address').value = teacher.Address || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>

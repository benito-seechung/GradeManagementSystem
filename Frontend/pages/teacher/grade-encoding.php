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

// Get all classes and subjects for this teacher
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
    ORDER BY l.Level_Name, sec.Section_Name, s.Subject_Name";
$classes_result = $conn->query($classes_sql);
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

// Get selected assignment from URL
$selected_assignment = $_GET['assignment_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Encoding - Grades Management Portal</title>
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
                <a href="grade-encoding.php" class="menu-item active">
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
                            <li class="breadcrumb-item active">Grade Encoding</li>
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
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/login.html"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <div class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Grade Encoding</h1>
                    <p class="page-subtitle">Enter and manage student final grades per quarter</p>
                </div>

                <!-- Success Alert -->
                <div class="alert alert-success alert-dismissible fade show" id="saveSuccess" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> Grades have been saved successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Error Alert -->
                <div class="alert alert-danger alert-dismissible fade show" id="saveError" style="display: none;">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error!</strong> <span id="errorMessage">Failed to save grades.</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Selection Form -->
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <h5 class="content-card-title"><i class="fas fa-filter me-2"></i>Select Class & Quarter</h5>
                    </div>
                    <div class="content-card-body">
                        <form id="gradeEncodingForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Class <span class="text-danger">*</span></label>
                                    <select class="form-select" id="classSelect" required onchange="updateClassData()">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c['Assignment_ID']; ?>"
                                                data-section-id="<?php echo $c['Section_ID']; ?>"
                                                data-subject-name="<?php echo htmlspecialchars($c['Subject_Name']); ?>"
                                                data-level-name="<?php echo htmlspecialchars($c['Level_Name']); ?>"
                                                data-section-name="<?php echo htmlspecialchars($c['Section_Name']); ?>"
                                                <?php echo $selected_assignment == $c['Assignment_ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['Level_Name']); ?> - <?php echo htmlspecialchars($c['Section_Name']); ?> | <?php echo htmlspecialchars($c['Subject_Name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Quarter <span class="text-danger">*</span></label>
                                    <select class="form-select" id="quarterSelect" required>
                                        <option value="">Select</option>
                                        <option value="1">Q1</option>
                                        <option value="2">Q2</option>
                                        <option value="3">Q3</option>
                                        <option value="4">Q4</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Academic Year</label>
                                    <input type="text" class="form-control" id="academicYear" value="2024-2025" readonly>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100" onclick="loadStudentList()">
                                        <i class="fas fa-search me-1"></i> Load
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Grade Entry Table -->
                <div class="content-card" id="gradeEntryCard" style="display: none;">
                    <div class="content-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="content-card-title"><i class="fas fa-table me-2"></i>Final Grade Entry</h5>
                            <p class="text-muted small mb-0">
                                <span id="selectedClass">--</span> |
                                <span id="selectedQuarter">--</span> |
                                <span id="selectedYear">2024-2025</span>
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger btn-sm" onclick="clearAllGrades()">
                                <i class="fas fa-eraser me-1"></i>Clear All
                            </button>
                            <button class="btn btn-success btn-sm" onclick="saveGrades()">
                                <i class="fas fa-save me-1"></i>Save Grades
                            </button>
                        </div>
                    </div>
                    <div class="content-card-body p-0">
                        <div class="table-responsive">
                            <table class="data-table" id="gradeEntryTable">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th width="150">Final Grade (%)</th>
                                        <th width="100">Letter Grade</th>
                                        <th width="100">Status</th>
                                        <th width="200">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Class Average:</strong></td>
                                        <td><strong id="averageScore">--</strong></td>
                                        <td><strong id="averageGrade">--</strong></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="content-card-footer d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Grading Scale:</strong> A+ (95-100%), A (90-94%), B+ (85-89%), B (80-84%), C+ (75-79%), C (70-74%), D+ (65-69%), D (60-64%), F (0-59%)
                        </div>
                        <div>
                            <span class="badge badge-soft badge-soft-success me-2">Passing (75%+)</span>
                            <span class="badge badge-soft badge-soft-warning me-2">At Risk (60-74%)</span>
                            <span class="badge badge-soft badge-soft-danger">Failing (<60%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/main.js"></script>
    <script>
        let selectedClassData = null;
        let currentStudents = [];

        // Auto-load if assignment_id is in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const assignmentId = urlParams.get('assignment_id');
            if (assignmentId) {
                document.getElementById('classSelect').value = assignmentId;
                updateClassData();
            }
        });

        function updateClassData() {
            const classSelect = document.getElementById('classSelect');
            const selectedOption = classSelect.options[classSelect.selectedIndex];
            if (classSelect.value) {
                selectedClassData = {
                    assignmentId: classSelect.value,
                    sectionId: selectedOption.dataset.sectionId,
                    subjectName: selectedOption.dataset.subjectName,
                    levelName: selectedOption.dataset.levelName,
                    sectionName: selectedOption.dataset.sectionName
                };
            } else {
                selectedClassData = null;
            }
        }

        function loadStudentList() {
            const classSelect = document.getElementById('classSelect');
            const quarterSelect = document.getElementById('quarterSelect');

            if (!classSelect.value || !quarterSelect.value) {
                alert('Please select both class and quarter');
                return;
            }

            const assignmentId = classSelect.value;
            const quarter = quarterSelect.value;
            const schoolYear = document.getElementById('academicYear').value;

            const formData = new FormData();
            formData.append('get_students', '1');
            formData.append('assignment_id', assignmentId);
            formData.append('quarter', quarter);
            formData.append('school_year', schoolYear);

            document.getElementById('gradeEntryCard').style.display = 'none';

            fetch('../../../backend/submit_grades.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentStudents = data.students;
                    renderGradeTable(data.students);
                    document.getElementById('gradeEntryCard').style.display = 'block';

                    const selectedOption = classSelect.options[classSelect.selectedIndex];
                    document.getElementById('selectedClass').textContent =
                        selectedOption.dataset.levelName + ' - ' + selectedOption.dataset.sectionName + ' | ' + selectedOption.dataset.subjectName;
                    document.getElementById('selectedQuarter').textContent = 'Q' + quarter;
                    document.getElementById('selectedYear').textContent = schoolYear;
                    document.getElementById('gradeEntryCard').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Failed to load students: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error loading students:', error);
                alert('Failed to load student list');
            });
        }

        function renderGradeTable(students) {
            const tbody = document.querySelector('#gradeEntryTable tbody');
            tbody.innerHTML = '';

            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No students found in this class.</td></tr>';
                updateClassAverage();
                return;
            }

            students.forEach((student, index) => {
                const tr = document.createElement('tr');
                const gradeValue = student.FinalGrade || '';
                const letterGrade = getLetterGrade(parseFloat(gradeValue) || 0);
                const status = getStatus(parseFloat(gradeValue) || 0);
                const statusClass = getStatusClass(parseFloat(gradeValue) || 0);
                const gradeClass = getGradeClass(parseFloat(gradeValue) || 0);

                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${student.Student_ID}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 14px;">${student.Firstname.charAt(0).toUpperCase()}</div>
                            <span>${escapeHtml(student.Lastname)}, ${escapeHtml(student.Firstname)}</span>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control grade-input-field"
                               min="0" max="100" value="${gradeValue}"
                               data-student-id="${student.Student_ID}"
                               onchange="calculateGrade(this)">
                    </td>
                    <td><span class="grade-calculated ${gradeClass}">${letterGrade}</span></td>
                    <td><span class="badge badge-soft ${statusClass}">${status}</span></td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Optional remarks"
                               value="${escapeHtml(student.Remarks || '')}"
                               data-student-id="${student.Student_ID}">
                    </td>
                `;
                tbody.appendChild(tr);
            });

            updateClassAverage();
        }

        function calculateGrade(input) {
            const score = parseFloat(input.value) || 0;
            const row = input.closest('tr');
            const gradeCalculated = row.querySelector('.grade-calculated');
            const statusBadge = row.querySelector('.badge');

            const letter = getLetterGrade(score);
            const status = getStatus(score);

            gradeCalculated.textContent = letter;
            gradeCalculated.className = 'grade-calculated ' + getGradeClass(score);
            statusBadge.textContent = status;
            statusBadge.className = 'badge badge-soft ' + getStatusClass(score);

            updateClassAverage();
        }

        function getLetterGrade(score) {
            if (score >= 95) return 'A+';
            if (score >= 90) return 'A';
            if (score >= 85) return 'B+';
            if (score >= 80) return 'B';
            if (score >= 75) return 'C+';
            if (score >= 70) return 'C';
            if (score >= 65) return 'D+';
            if (score >= 60) return 'D';
            return 'F';
        }

        function getGradeClass(score) {
            if (score >= 90) return 'grade-excellent';
            if (score >= 80) return 'grade-good';
            if (score >= 70) return 'grade-fair';
            return 'grade-poor';
        }

        function getStatus(score) {
            if (score >= 75) return 'Passing';
            if (score >= 60) return 'At Risk';
            return 'Failing';
        }

        function getStatusClass(score) {
            if (score >= 75) return 'badge-soft-success';
            if (score >= 60) return 'badge-soft-warning';
            return 'badge-soft-danger';
        }

        function updateClassAverage() {
            const inputs = document.querySelectorAll('.grade-input-field');
            let total = 0, count = 0;

            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value) && input.value !== '') {
                    total += value;
                    count++;
                }
            });

            if (count > 0) {
                const average = total / count;
                document.getElementById('averageScore').textContent = average.toFixed(2);
                document.getElementById('averageGrade').textContent = getLetterGrade(average);
            } else {
                document.getElementById('averageScore').textContent = '--';
                document.getElementById('averageGrade').textContent = '--';
            }
        }

        function saveGrades() {
            if (!selectedClassData) {
                alert('Please select a class');
                return;
            }

            const grades = [];
            const rows = document.querySelectorAll('#gradeEntryTable tbody tr');

            rows.forEach(row => {
                const gradeInput = row.querySelector('.grade-input-field');
                const remarksInput = row.querySelector('input[placeholder="Optional remarks"]');

                if (gradeInput && gradeInput.value !== '') {
                    grades.push({
                        student_id: gradeInput.dataset.studentId,
                        grade: parseFloat(gradeInput.value),
                        remarks: remarksInput ? remarksInput.value : ''
                    });
                }
            });

            if (grades.length === 0) {
                alert('Please enter at least one grade');
                return;
            }

            const quarterSelect = document.getElementById('quarterSelect');
            const formData = new FormData();
            formData.append('submit_grades', '1');
            formData.append('assignment_id', selectedClassData.assignmentId);
            formData.append('quarter', quarterSelect.value);
            formData.append('school_year', document.getElementById('academicYear').value);
            formData.append('grades', JSON.stringify(grades));

            const saveBtn = document.querySelector('button[onclick="saveGrades()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';

            fetch('../../../backend/submit_grades.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('saveSuccess').style.display = 'block';
                    document.getElementById('saveError').style.display = 'none';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => {
                        document.getElementById('saveSuccess').style.display = 'none';
                    }, 5000);
                } else {
                    document.getElementById('errorMessage').textContent = data.message || 'Failed to save grades';
                    document.getElementById('saveError').style.display = 'block';
                    document.getElementById('saveSuccess').style.display = 'none';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(error => {
                console.error('Error saving grades:', error);
                document.getElementById('errorMessage').textContent = 'Connection error. Please check your server.';
                document.getElementById('saveError').style.display = 'block';
                document.getElementById('saveSuccess').style.display = 'none';
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
        }

        function clearAllGrades() {
            if (!confirm('Are you sure you want to clear all entered grades?')) return;
            const inputs = document.querySelectorAll('.grade-input-field');
            inputs.forEach(input => {
                input.value = '';
                calculateGrade(input);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

/* ========================================
   Grades Management Portal - Main JavaScript
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {

    // ========================================
    // Sidebar Toggle Functionality
    // ========================================
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mainContent.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    // ========================================
    // Password Toggle Functionality
    // ========================================
    const passwordToggles = document.querySelectorAll('.password-toggle');

    passwordToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const input = this.closest('.password-wrapper').querySelector('input');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // ========================================
    // Role Selection Functionality (Login Page)
    // ========================================
    const roleButtons = document.querySelectorAll('.role-btn');
    const selectedRoleInput = document.getElementById('selected-role');

    roleButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            roleButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            if (selectedRoleInput) {
                selectedRoleInput.value = this.dataset.role;
            }
        });
    });

    // ========================================
    // Search Functionality
    // ========================================
    const searchInputs = document.querySelectorAll('.search-input');

    searchInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const targetTable = this.closest('.content-card').querySelector('.data-table');
            const searchTerm = this.value.toLowerCase();

            if (targetTable) {
                const rows = targetTable.querySelectorAll('tbody tr');

                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });

    // ========================================
    // Grade Calculation (Grade Encoding Page)
    // ========================================
    const gradeInputs = document.querySelectorAll('.grade-input-field');

    gradeInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const row = this.closest('tr');
            const inputs = row.querySelectorAll('.grade-input-field');
            const gradeDisplay = row.querySelector('.grade-calculated');
            const avgDisplay = row.querySelector('.average-calculated');

            let sum = 0;
            let count = 0;

            inputs.forEach(inp => {
                const val = parseFloat(inp.value);
                if (!isNaN(val) && val > 0) {
                    sum += val;
                    count++;
                }
            });

            const average = count > 0 ? (sum / 4) : 0; // Dividing by 4 for standard quarterly weighting

            if (avgDisplay) {
                avgDisplay.textContent = average > 0 ? average.toFixed(2) : '-';
            }

            if (gradeDisplay) {
                if (average >= 75) {
                    gradeDisplay.textContent = 'PASSED';
                    gradeDisplay.className = 'grade-calculated grade-good';
                } else if (average > 0) {
                    gradeDisplay.textContent = 'FAILED';
                    gradeDisplay.className = 'grade-calculated grade-poor';
                }
            }
        });
    });

    // ========================================
    // Form Validation Styling
    // ========================================
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ========================================
    // Alert Auto-Dismiss
    // ========================================
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function(alert) {
        const dismissBtn = alert.querySelector('.btn-close');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                alert.style.display = 'none';
            });
        }
    });

    // ========================================
    // Auto-hide Alerts After 5 Seconds
    // ========================================
    const autoHideAlerts = document.querySelectorAll('.alert-auto-hide');

    autoHideAlerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });

    // ========================================
    // Confirm Delete Actions
    // ========================================
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');

    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(event) {
            const itemName = this.dataset.item || 'this item';
            if (!confirm(`Are you sure you want to delete ${itemName}?`)) {
                event.preventDefault();
            }
        });
    });

    // ========================================
    // Active Menu Item Highlighting
    // ========================================
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.menu-item');

    menuItems.forEach(function(item) {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href)) {
            item.classList.add('active');
        }
    });

    // ========================================
    // Tooltip Initialization
    // ========================================
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ========================================
    // Popover Initialization
    // ========================================
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });

    // ========================================
    // Grade Category Filter (Student Grades)
    // ========================================
    const categoryTabs = document.querySelectorAll('.category-tab');

    categoryTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const category = this.dataset.category;
            const gradeRows = document.querySelectorAll('.grade-row');

            gradeRows.forEach(function(row) {
                if (category === 'all' || row.dataset.category === category) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // ========================================
    // Print Functionality
    // ========================================
    const printButtons = document.querySelectorAll('.btn-print');

    printButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.print();
        });
    });

    // ========================================
    // Export Functionality (Placeholder)
    // ========================================
    const exportButtons = document.querySelectorAll('.btn-export');

    exportButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const format = this.dataset.format || 'csv';
            const table = this.closest('.content-card').querySelector('.data-table');

            if (table) {
                exportTableToCSV(table, format);
            }
        });
    });

    function exportTableToCSV(table, format) {
        const rows = table.querySelectorAll('tr');
        let csv = [];

        rows.forEach(function(row) {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            cols.forEach(function(col) {
                rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
            });
            csv.push(rowData.join(','));
        });

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }

    // ========================================
    // Modal Form Handling
    // ========================================
    const modals = document.querySelectorAll('.modal');

    modals.forEach(function(modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            // Clear form data when modal closes
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
        });
    });

    // ========================================
    // Character Counter for Textareas
    // ========================================
    const textareas = document.querySelectorAll('textarea[data-maxlength]');

    textareas.forEach(function(textarea) {
        const maxLength = textarea.dataset.maxlength;
        const counter = document.createElement('small');
        counter.className = 'text-muted float-end';
        textarea.parentNode.appendChild(counter);

        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = remaining + ' characters remaining';
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });

    // ========================================
    // Auto-save Indicator (Placeholder)
    // ========================================
    const autoSaveForms = document.querySelectorAll('.form-autosave');

    autoSaveForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                showAutoSaveIndicator();
            });
        });
    });

    function showAutoSaveIndicator() {
        let indicator = document.querySelector('.autosave-indicator');

        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator';
            indicator.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#1cc88a;color:white;padding:10px 20px;border-radius:8px;z-index:9999;';
            indicator.textContent = 'Saving...';
            document.body.appendChild(indicator);
        }

        setTimeout(function() {
            indicator.textContent = 'Saved!';
            setTimeout(function() {
                indicator.remove();
            }, 2000);
        }, 1000);
    }

    console.log('Grades Management Portal initialized successfully');
});

// ========================================
// PHP Integration Helper Functions
// ========================================

// These functions can be used for AJAX calls when backend is implemented

/**
 * Teacher Grading UI Logic
 * Handles the flow: Class List -> Full Year Grading Table
 */

// Initialize Teacher Grading View
function initTeacherGrading(teacherId) {
    const container = document.getElementById('grading-container');
    if (!container) return;

    // 1. Fetch Teacher's Classes
    const data = new FormData();
    data.append('get_teacher_classes', 'true');

    fetch('../../../backend/submit_grades.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            renderClassSelector(result.classes);
        }
    });
}

// Render the list of classes for the teacher to choose from
function renderClassSelector(classes) {
    const container = document.getElementById('grading-container');
    let html = `
        <div class="row g-4">
            <div class="col-12">
                <h4 class="mb-4">Select a Class to Encode Grades</h4>
            </div>
    `;

    classes.forEach(cls => {
        html += `
            <div class="col-md-4">
                <div class="content-card class-card h-100" onclick="loadGradingTable(${cls.Assignment_ID}, '${cls.Subject_Name}', '${cls.Section_Name}')" style="cursor:pointer; transition: transform 0.2s;">
                    <div class="content-card-body">
                        <h5 class="mb-1">${cls.Subject_Name}</h5>
                        <p class="text-muted mb-3">${cls.Level_Name} - ${cls.Section_Name}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary">${cls.School_Year}</span>
                            <span class="text-primary">Open Class <i class="fas fa-chevron-right ms-1"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    container.innerHTML = html;
}

// Load the 4-quarter table for all students in the selected class
function loadGradingTable(assignmentId, subjectName, sectionName) {
    const container = document.getElementById('grading-container');
    const data = new FormData();
    data.append('get_students', 'true');
    data.append('assignment_id', assignmentId);

    fetch('../../../backend/submit_grades.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            renderGradingTable(result.students, assignmentId, subjectName, sectionName);
        }
    });
}

// Render the actual table with Q1, Q2, Q3, Q4 inputs
function renderGradingTable(students, assignmentId, subjectName, sectionName) {
    const container = document.getElementById('grading-container');
    let html = `
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <button class="btn btn-outline-secondary btn-sm mb-2" onclick="initTeacherGrading(null)">
                    <i class="fas fa-arrow-left me-1"></i> Back to Classes
                </button>
                <h3 class="mb-0">${subjectName} - ${sectionName}</h3>
            </div>
            <button class="btn btn-success" onclick="saveBulkGrades(${assignmentId})">
                <i class="fas fa-save me-2"></i> Save All Grades
            </button>
        </div>
        <div class="content-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th width="100">Q1</th>
                            <th width="100">Q2</th>
                            <th width="100">Q3</th>
                            <th width="100">Q4</th>
                            <th width="120">Average</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    students.forEach(s => {
        html += `
            <tr class="student-grade-row" data-student-id="${s.Student_ID}">
                <td><strong>${s.Lastname}, ${s.Firstname}</strong></td>
                <td><input type="number" class="form-control grade-input-field q1-input" value="${s.Q1 || ''}" min="0" max="100"></td>
                <td><input type="number" class="form-control grade-input-field q2-input" value="${s.Q2 || ''}" min="0" max="100"></td>
                <td><input type="number" class="form-control grade-input-field q3-input" value="${s.Q3 || ''}" min="0" max="100"></td>
                <td><input type="number" class="form-control grade-input-field q4-input" value="${s.Q4 || ''}" min="0" max="100"></td>
                <td class="average-calculated text-center fw-bold">-</td>
                <td><input type="text" class="form-control remarks-input" value="${s.Remarks || ''}" placeholder="Optional remarks"></td>
            </tr>
        `;
    });

    html += `</tbody></table></div></div>`;
    container.innerHTML = html;

    // Re-initialize grade calculation listeners for new inputs
    const inputs = container.querySelectorAll('.grade-input-field');
    inputs.forEach(input => {
        input.dispatchEvent(new Event('change')); // Trigger initial calculation
        input.addEventListener('input', function() {
            this.dispatchEvent(new Event('change'));
        });
    });
}

// Save the bulk 4-quarter data
function saveBulkGrades(assignmentId) {
    const rows = document.querySelectorAll('.student-grade-row');
    const grades = [];

    rows.forEach(row => {
        const studentId = row.dataset.studentId;
        grades.push({
            student_id: studentId,
            q1: row.querySelector('.q1-input').value || 0,
            q2: row.querySelector('.q2-input').value || 0,
            q3: row.querySelector('.q3-input').value || 0,
            q4: row.querySelector('.q4-input').value || 0,
            remarks: row.querySelector('.remarks-input').value || ''
        });
    });

    const data = new FormData();
    data.append('submit_grades', 'true');
    data.append('assignment_id', assignmentId);
    data.append('grades', JSON.stringify(grades));
    data.append('quarter', '1'); // Default fallback for tracking

    fetch('../../../backend/submit_grades.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(result => {
        if(result.success) {
            alert('All grades for this class have been saved!');
        } else {
            alert('Error: ' + result.message);
        }
    });
}

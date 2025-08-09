<?php
    session_start();
    include_once __DIR__ . '/../../../../../config/config.php';

    // Check if user is logged in and is an admin
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
        header('Location: ' . APP_ADMIN_URL . '/login.php');
        exit();
    }

    $title = "Manage Students";

    // Handle form submission for adding a new notice
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notice'])) {
        $notice_title = $_POST['title'];
        $content = $_POST['content'];

        if (!empty($notice_title) && !empty($content)) {
            $stmt = $pdo->prepare("INSERT INTO notice_board (role, title, content) VALUES ('student', ?, ?)");
            $stmt->execute([$notice_title, $content]);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle form submission for updating a notice
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notice'])) {
        $notice_id = $_POST['notice_id'];
        $notice_title = $_POST['title'];
        $content = $_POST['content'];

        if (!empty($notice_id) && !empty($notice_title) && !empty($content)) {
            $stmt = $pdo->prepare("UPDATE notice_board SET title = ?, content = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$notice_title, $content, $notice_id]);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle multiple notice deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
        if (!empty($_POST['notice_ids']) && is_array($_POST['notice_ids'])) {
            $notice_ids = $_POST['notice_ids'];
            $placeholders = implode(',', array_fill(0, count($notice_ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM notice_board WHERE id IN ($placeholders) AND role = 'student'");
            $stmt->execute($notice_ids);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle notice deletion
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM notice_board WHERE id = ? AND role = 'student'");
        $stmt->execute([$delete_id]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }


    // Function to get all students
    function getAllStudents()
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM user_info WHERE role = 'student'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all student notices from the database
    function getAllStudentNotices()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT id, title, content, created_at FROM notice_board WHERE role = 'student' ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $students = getAllStudents();
    $notices = getAllStudentNotices();
?>
<title><?= htmlspecialchars($title) ?></title>



<?php
include_once APP_HEADER_FILE;
?>

<div class="admin_floatingNav" id="floatingNav">
    <!-- Navigation Group: Left -->
    <div class="nav-items left" id="navItems_1">

        <!-- Notice Board -->
        <div class="nav-item" title="Manage Notice Board">
            <a href="#notice_board" aria-label="Notice Board">
                <i class="fas fa-clipboard-list text-white"></i>
            </a>
        </div>
    </div>

    <!-- Toggle Button -->
    <button class="main-button" id="toggleNav" aria-label="Toggle Navigation" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navigation Group: Right -->
    <div class="nav-items right" id="navItems_2">
        <!-- Assignments -->
        <div class="nav-item" title="Manage Assignments">
            <a href="#assignments" aria-label="Assignments">
                <i class="fas fa-tasks text-white"></i>
            </a>
        </div>
    </div>
</div>

<!-- Main Content Area Start-->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Students</h3>
                    <button class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#studentModal">Add Student</button>
                </div>
                <div class="card-body">
                    <table id="students_table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['user_id']) ?></td>
                                    <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= htmlspecialchars($student['grade']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-student" data-id="<?= $student['user_id'] ?>" data-first-name="<?= htmlspecialchars($student['first_name']) ?>" data-last-name="<?= htmlspecialchars($student['last_name']) ?>" data-email="<?= htmlspecialchars($student['email']) ?>" data-grade="<?= htmlspecialchars($student['grade']) ?>" data-bs-toggle="modal" data-bs-target="#studentModal">Edit</button>
                                        <button class="btn btn-sm btn-danger delete-student" data-id="<?= $student['user_id'] ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Main Content Area End-->


<div class="container-fluid mt-4" id="notice_board" area-expanded="false">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Add Notice Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">Add New Notice for Students</h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="add_notice" class="btn btn-primary">Add Notice</button>
                    </form>
                </div>
            </div>

            <!-- List of Notices -->
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Existing Student Notices</h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="deleteForm">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Published On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notices)) : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No notices found.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($notices as $notice) : ?>
                                        <tr>
                                            <td><input type="checkbox" name="notice_ids[]" value="<?php echo $notice['id']; ?>" class="notice-checkbox"></td>
                                            <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($notice['content'])); ?></td>
                                            <td><?php echo date('F j, Y, g:i a', strtotime($notice['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm update-btn" data-bs-toggle="modal" data-bs-target="#updateNoticeModal" data-id="<?php echo $notice['id']; ?>" data-title="<?php echo htmlspecialchars($notice['title']); ?>" data-content="<?php echo htmlspecialchars($notice['content']); ?>">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?delete_id=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notice?');">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="delete_selected" class="btn btn-danger">Delete Selected</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" role="dialog" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="studentForm">
                    <input type="hidden" name="student_id" id="student_id">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="grade">Grade</label>
                        <input type="text" class="form-control" id="grade" name="grade" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveStudent">Save changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Update Notice Modal -->
<div class="modal fade" id="updateNoticeModal" tabindex="-1" role="dialog" aria-labelledby="updateNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateNoticeModalLabel">Update Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="notice_id" id="update_notice_id">
                    <div class="form-group">
                        <label for="update_title">Title</label>
                        <input type="text" id="update_title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="update_content">Content</label>
                        <textarea id="update_content" name="content" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_notice" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once APP_FOOTER_FILE;
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const dataTable = new simpleDatatables.DataTable("#students_table");

        const studentModalEl = document.getElementById('studentModal');
        const studentModal = new bootstrap.Modal(studentModalEl);

        // Show modal for adding a new student
        studentModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = studentModalEl.querySelector('.modal-title');
            const studentForm = document.getElementById('studentForm');
            const studentIdInput = document.getElementById('student_id');

            if (button.classList.contains('edit-student')) {
                modalTitle.textContent = 'Edit Student';
                studentIdInput.value = button.dataset.id;
                document.getElementById('first_name').value = button.dataset.firstName;
                document.getElementById('last_name').value = button.dataset.lastName;
                document.getElementById('email').value = button.dataset.email;
                document.getElementById('grade').value = button.dataset.grade;
            } else {
                modalTitle.textContent = 'Add Student';
                studentForm.reset();
                studentIdInput.value = '';
            }
        });
// Replace it with proper js or php:
        // // Handle form submission
        // document.getElementById('saveStudent').addEventListener('click', function() {
        //     const formData = {
        //         student_id: document.getElementById('student_id').value,
        //         first_name: document.getElementById('first_name').value,
        //         last_name: document.getElementById('last_name').value,
        //         email: document.getElementById('email').value,
        //         grade: document.getElementById('grade').value
        //     };

        //     fetch('../../../../../api/students.php', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //             },
        //             body: JSON.stringify(formData)
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             studentModal.hide();
        //             location.reload();
        //         })
        //         .catch(error => {
        //             console.error('Error:', error);
        //             alert('Error saving student.');
        //         });
        // });

        // // Handle delete
        // document.getElementById('students_table').addEventListener('click', function(event) {
        //     if (event.target.classList.contains('delete-student')) {
        //         const studentId = event.target.dataset.id;
        //         if (confirm('Are you sure you want to delete this student?')) {
        //             fetch('../../../../../api/students.php', {
        //                     method: 'DELETE',
        //                     headers: {
        //                         'Content-Type': 'application/json',
        //                     },
        //                     body: JSON.stringify({
        //                         student_id: studentId
        //                     })
        //                 })
        //                 .then(response => response.json())
        //                 .then(data => {
        //                     location.reload();
        //                 })
        //                 .catch(error => {
        //                     console.error('Error:', error);
        //                     alert('Error deleting student.');
        //                 });
        //         }
        //     }
        // });
//;
        // Handle click on update button for notices
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('update-btn')) {
                const button = event.target;
                document.getElementById('update_notice_id').value = button.dataset.id;
                document.getElementById('update_title').value = button.dataset.title;
                document.getElementById('update_content').value = button.dataset.content;
            }
        });

        // Handle select all checkbox for notices
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('click', function() {
                document.querySelectorAll('.notice-checkbox').forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        // Handle form submission for deleting selected notices
        const deleteForm = document.getElementById('deleteForm');
        if(deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                const checkedCheckboxes = document.querySelectorAll('.notice-checkbox:checked').length;
                if (checkedCheckboxes === 0) {
                    alert('Please select at least one notice to delete.');
                    e.preventDefault();
                    return;
                }
                if (!confirm('Are you sure you want to delete the selected notices?')) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
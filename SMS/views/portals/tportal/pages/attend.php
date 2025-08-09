<?php 
$filePath = './../../../../config/config.php';
if (file_exists($filePath)) {
    include_once $filePath;
} else {
    echo "File not found: $filePath";
}
//include_once './../../../../config/config.php'; //connection and constants includes here ?>

<title><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?> Attendance</title>

<?php include_once APP_HEADER_FILE; ?>
<?php include_once APP_TPORTAL_MENU; ?>

<?php
// Fetch today's date
$today = date('Y-m-d');

// Fetch the teacher's username (example: hardcoded or fetched from session)
$teacher_username = $_SESSION['user']; // This would be dynamically fetched (e.g., from session or database)

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $attendance_status = $_POST['attendance_status']; // "present", "absent", or "late"
    $error_message = null;
    $success_message = null;

    // Validate if the date is not in the future
    if ($date > $today) {
        $error_message = "You cannot mark attendance for a future date.";
    } else {
        // Insert the attendance record for the day into daily_attendance table
        try {
            $stmt = $pdo->prepare("INSERT INTO daily_attendance (username, date, attendance_status) VALUES (?, ?, ?)");
            $stmt->execute([$teacher_username, $date, $attendance_status]);

            // If insertion is successful
            $success_message = "Attendance for @$teacher_username on $date has been recorded successfully.";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>
<section class="card m-2 w-50 p-3 container mt-5" id="teacher_attendance" title="Mark Today's Attendance">
    <h2>Mark Today's Attendance</h2>

    <!-- Display any error message -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <!-- Display success message -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?= $success_message ?>
        </div>
    <?php endif; ?>

    <!-- Attendance Form -->
    <form action="" method="POST">
        <div class="form-group">
            <label for="teacher_username">Teacher Username:</label>
            <input type="text" class="form-control" id="teacher_username" name="teacher_username" value="<?= $teacher_username ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="date">Date:</label>
            <input type="date" class="form-control" id="date" name="date" value="<?= $today ?>" required readonly>
        </div>

        <div class="form-group">
            <label for="attendance_status">Attendance Status:</label>
            <select name="attendance_status" id="attendance_status" class="form-control" required>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-2">Submit Attendance</button>
    </form>
</section>

<section class="card m-2 w-50 p-3" id="teacher_attendance" title="My Attendance">
    <?php
    function getMyAttendance($teacher_username)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE id = ?");
        $stmt->execute([$teacher_username]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Prepare data for charts
    $my_attendance = getMyAttendance($teacher_username);
    $mymonths = [];
    $myweeks = [];
    $mypresent_days = [];
    $myabsent_days = [];
    $mylate_days = [];

    foreach ($my_attendance as $attendance) {
        $mymonths[] = $attendance['month'];
        $myweeks[] = $attendance['week'];
        $mypresent_days[] = $attendance['present_days'];
        $myabsent_days[] = $attendance['absent_days'];
        $mylate_days[] = $attendance['late_days'];
    }
    ?>
    <p><?= date('F Y') ?></p>
    <canvas id="teacherAttendanceChart"></canvas>
</section>

<?php include_once APP_FOOTER_FILE; ?>
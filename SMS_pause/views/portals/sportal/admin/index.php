<?php
session_start();
include_once __DIR__ . '/../../../../config/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    // header('Location: ' . APP_AUTH_URL . '/admin_login.php');
    header('Location: ' . APP_ADMIN_URL . '/login.php');
    exit();
}

$title = ucfirst($_SESSION['role']) . " Portal";
?>
<title><?= htmlspecialchars($title) ?></title>

<?php
include_once APP_HEADER_FILE;
include_once APP_ADMIN_MENU;
?>

<!-- Main Content Area Start-->
<!--start Left column for attendance, info, timetable, notice-board  -->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Admin Profile Card -->
    <section class="card row w-25 m-2 h-85 p-2" id="admin_info" title="Admin Information">
        <?php
            $admin_username = $_SESSION['user'];
            function getAdminInfo($username) {
                global $pdo;
                $stmt = $pdo->prepare("SELECT * FROM user_info WHERE username = ?");
                $stmt->execute([$username]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $admin_info = getAdminInfo($admin_username);
        ?>
        <div class="m-2">
            <img src="<?= htmlspecialchars($admin_info['profile_picture']) ?>" alt="<?= htmlspecialchars($admin_info['first_name']) ?>" class="rounded-circle img-fluid" title="Admin Profile">
        </div>
        <div>
            <h2><?= htmlspecialchars($admin_info['first_name'] . ' ' . $admin_info['last_name']) ?></h2>
            <h4 class="text-muted">Role: <?= htmlspecialchars(ucfirst($admin_info['role'])) ?></h4>
            <code class="text-dark user-select-none">
                <small>@<?= htmlspecialchars($_SESSION['user']) ?></small>
                <small><?= htmlspecialchars($admin_info['employee_id']) ?></small>
            </code>
        </div>
    </section>
    <!-- Notice Board -->
    <section class="card m-2 p-3 col h-85 notice_board" id="admin_notice-board">
        <h2>Notice Boards</h2>
        <?php
        function getAdminNotices() {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM notice_board");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $notices = getAdminNotices();
        ?>
        <div class="swiper-container h-100">
            <div class="swiper-wrapper">
                <?php if ($notices): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="swiper-slide">
                            <strong><?= htmlspecialchars($notice['title']) ?></strong><br>
                            <small>Date: <strong class="text-muted"><?= htmlspecialchars($notice['created_at']) ?></strong></small><br>
                            <p><q><?= htmlspecialchars($notice['content']) ?></q></p>
                            <button class="copy-notice-btn flex items-center gap-1 px-2 py-1 rounded bg-yellow-100 hover:bg-yellow-200 border border-yellow-300 text-yellow-700 text-xs transition focus:outline-none focus:ring-2 focus:ring-yellow-400" type="button"
                                data-notice="<?= htmlspecialchars(
                                    'Title: ' . $notice['title'] . "\n" .
                                    'Date: ' . $notice['created_at'] . "\n" .
                                    'Content: ' . $notice['content'],
                                    ENT_QUOTES
                                ) ?>"
                                title="Copy notice to clipboard">
                                <span class="inline-block" aria-hidden="true">📋</span>
                                <!-- <span class="sr-only">Copy</span> -->
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="swiper-slide">
                        <p>No notices available.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <!-- Academic Calendar Section -->
    <section class="card w-50 m-2 p-3 col" id="calendar">
        <h2>Academic Calendar</h2>
        <p class="text-muted">Manage events like holidays, exams, and exhibitions.</p>

        <?php
            function getAllEvents() {
                global $pdo;
                $stmt = $pdo->query("SELECT * FROM events");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $events = getAllEvents();
        ?>
        <!-- FullCalendar container -->
        <div id="academic_events" class="tw-bg-white border rounded p-3 tw-overflow-hidden" style="min-height: 600px;"></div>
                <!-- Fallback list view if calendar doesn't render -->
        <div class="tw-hidden" id="fallback_event_list">
            <?php if ($events): ?>
                <ul class="tw-mt-3 tw-space-y-1">
                    <?php foreach ($events as $event): ?>
                        <li><strong><?= htmlspecialchars($event['title']) ?></strong> — (<?= htmlspecialchars($event['start_date']) . ' to ' . htmlspecialchars($event['end_date'] ?? $event['start_date']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No events found.</p>
            <?php endif; ?>
        </div>
    </section>



</div>
<!--end Left column for attendance, info, timetable, notice-board -->
<!--start Right column for * and * -->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Admin Class Attendance Overview -->
    <section class="card m-2 p-3" id="admin_class_attendance">
        <h2>Classwise Attendance Overview</h2>

        <?php
        // Get all unique grades
        $grades = $pdo->query("SELECT DISTINCT grade FROM user_info WHERE grade IS NOT NULL ORDER BY grade")->fetchAll(PDO::FETCH_COLUMN);

        $selected_grade = $_GET['grade'] ?? ($grades[0] ?? null);
        $selected_month = $_GET['month'] ?? date('n');
        $selected_year = $_GET['year'] ?? date('Y');
        $view_mode = $_GET['view'] ?? 'summary';

        function getGradeAttendanceSummary($grade, $month, $year) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS total_present,
                    SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS total_absent,
                    SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS total_late
                FROM daily_attendance
                WHERE username IN (SELECT username FROM user_info WHERE grade = ?)
                AND YEAR(date) = ? AND MONTH(date) = ?
            ");
            $stmt->execute([$grade, $year, $month]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        function getGradeStudentsAttendanceDetails($grade, $month, $year) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT full_name,
                    SUM(CASE WHEN da.attendance_status = 'present' THEN 1 ELSE 0 END) AS present_days,
                    SUM(CASE WHEN da.attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                    SUM(CASE WHEN da.attendance_status = 'late' THEN 1 ELSE 0 END) AS late_days
                FROM user_info u
                LEFT JOIN daily_attendance da ON u.username = da.username AND YEAR(da.date) = ? AND MONTH(da.date) = ?
                WHERE u.grade = ?
                GROUP BY u.username
                ORDER BY u.full_name
            ");
            $stmt->execute([$year, $month, $grade]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $summary = $selected_grade ? getGradeAttendanceSummary($selected_grade, $selected_month, $selected_year) : null;
        $students = $selected_grade ? getGradeStudentsAttendanceDetails($selected_grade, $selected_month, $selected_year) : [];
        ?>

        <!-- Filters -->
        <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
            <select name="grade" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ($grades as $grade): ?>
                    <option value="<?= $grade ?>" <?= $selected_grade == $grade ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grade) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="month" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $m=>$n): ?>
                    <option value="<?= $m ?>" <?= $selected_month==$m ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>

            <select name="year" class="form-select w-auto" onchange="this.form.submit()">
                <?php for($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $selected_year==$y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <select name="view" class="form-select w-auto" onchange="this.form.submit()">
                <option value="summary" <?= $view_mode == 'summary' ? 'selected' : '' ?>>Summary Only</option>
                <option value="full" <?= $view_mode == 'full' ? 'selected' : '' ?>>Show Full</option>
            </select>
        </form>

        <?php if (!$selected_grade): ?>
            <p>No grades found.</p>
        <?php elseif (array_sum($summary) == 0): ?>
            <p class="text-danger">No attendance data found for this grade and month.</p>
        <?php else: ?>
            <canvas id="adminAttendanceChart" style="max-width:400px;"></canvas>

            <?php if ($view_mode == 'full'): ?>
                <h3>Student Breakdown (<?= htmlspecialchars($selected_grade) ?>)</h3>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr><th>Name</th><th>Present</th><th>Absent</th><th>Late</th><th>%</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s):
                            $total = $s['present_days'] + $s['absent_days'] + $s['late_days'];
                            $percent = $total ? round(($s['present_days'] / $total) * 100, 1).'%' : '-';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($s['full_name']) ?></td>
                                <td><?= (int)$s['present_days'] ?></td>
                                <td><?= (int)$s['absent_days'] ?></td>
                                <td><?= (int)$s['late_days'] ?></td>
                                <td><?= $percent ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Chart -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            new Chart(document.getElementById("adminAttendanceChart"), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [<?= $summary['total_present'] ?>, <?= $summary['total_absent'] ?>, <?= $summary['total_late'] ?>],
                        backgroundColor: ['#4caf50','#f44336','#ff9800']
                    }]
                }
            });
            </script>
        <?php endif; ?>
    </section>

    <!-- Admin: View Teacher Attendance -->
    <section class="card m-2 p-3" id="admin_teacher_attendance">
        <h2>Teacher Attendance Overview</h2>

        <?php
        // Get list of teachers
        $teachers = $pdo->query("SELECT username, full_name FROM user_info WHERE role = 'teacher' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

        $selected_teacher = $_GET['teacher'] ?? ($teachers[0]['username'] ?? null);
        $selected_month = $_GET['month'] ?? date('n');
        $selected_year = $_GET['year'] ?? date('Y');

        function getTeacherAttendance($username, $month, $year) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT present_days, absent_days, late_days FROM attendance WHERE username = ? AND month = ? AND year = ?");
            $stmt->execute([$username, $month, $year]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $attendance = $selected_teacher ? getTeacherAttendance($selected_teacher, $selected_month, $selected_year) : null;
        $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        ?>

        <!-- Filter Form -->
        <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
            <select name="teacher" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['username'] ?>" <?= $selected_teacher == $t['username'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="month" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>

            <select name="year" class="form-select w-auto" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>

        <?php if (!$attendance): ?>
            <p style="color:red;">No attendance data found for this teacher and selected period.</p>
        <?php else: ?>
            <canvas id="TeacherAttendanceChart" style="max-width:400px;"></canvas>

            <!-- Summary -->
            <table class="table table-bordered mt-3 w-50">
                <thead class="table-light">
                    <tr><th>Status</th><th>Days</th></tr>
                </thead>
                <tbody>
                    <tr><td>Present</td><td><?= $attendance['present_days'] ?></td></tr>
                    <tr><td>Absent</td><td><?= $attendance['absent_days'] ?></td></tr>
                    <tr><td>Late</td><td><?= $attendance['late_days'] ?></td></tr>
                </tbody>
            </table>

            <!-- Chart -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            new Chart(document.getElementById("TeacherAttendanceChart"), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [<?= $attendance['present_days'] ?>, <?= $attendance['absent_days'] ?>, <?= $attendance['late_days'] ?>],
                        backgroundColor: ['#4caf50','#f44336','#ff9800']
                    }]
                }
            });
            </script>
        <?php endif; ?>
    </section>

    <!-- Admin View: All Grades Timetables -->
    <section class="card p-3 m-2 w-100" id="all_grades_timetable">
        <h2>All Grades Timetable Viewer</h2>

        <?php
            // Get all distinct grades
            $stmt = $pdo->query("SELECT DISTINCT grade FROM user_info WHERE grade IS NOT NULL");
            $grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $selectedGrade = $_GET['grade'] ?? $grades[0] ?? null;

            if (!$grades) {
                echo "<p style='color:red;'>No grades found.</p>";
            } else {
        ?>
            <!-- Grade Selector -->
            <form method="GET" class="mb-3">
                <label for="grade" class="form-label">Select Grade:</label>
                <select name="grade" id="grade" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                    <?php foreach ($grades as $grade): ?>
                        <option value="<?= htmlspecialchars($grade) ?>" <?= $grade == $selectedGrade ? 'selected' : '' ?>>
                            <?= htmlspecialchars($grade) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php
                // Fetch all timetable entries
                $allTimetables = $pdo->query("SELECT * FROM timetable")->fetchAll(PDO::FETCH_ASSOC);

                // Build subject-teacher map
                $stmt = $pdo->query("SELECT username, subjects_taught, first_name, last_name FROM user_info");
                $subjectMap = [];
                $usernameToName = [];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $username = $row['username'];
                    $usernameToName[$username] = $row['first_name'] . ' ' . $row['last_name'];

                    $pairs = array_map('trim', explode(',', $row['subjects_taught']));
                    foreach ($pairs as $pair) {
                        if (preg_match('/(.+?)\s+Grade\s+(.+)/i', $pair, $matches)) {
                            $subject = trim($matches[1]);
                            $gradeKey = 'Grade ' . trim($matches[2]);
                            $subjectMap[$gradeKey][$username] = $subject;
                        }
                    }
                }

                // Filter timetable by selected grade
                $periodKeys = ['period_1','period_2','period_3','break_time','period_4','period_5','period_6'];
                $allDays = array_unique(array_column($allTimetables, 'day'));
                sort($allDays);
                $days = [];

                foreach ($allDays as $day) {
                    $periods = [];
                    foreach ($periodKeys as $period) {
                        if ($period === 'break_time') {
                            $periods[] = 'Break';
                            continue;
                        }

                        // Find teacher assigned for this period, grade, and day
                        $teacherUsername = null;
                        foreach ($allTimetables as $row) {
                            if ($row['day'] === $day && $row[$period] === $selectedGrade) {
                                $teacherUsername = $row['username'];
                                break;
                            }
                        }

                        if ($teacherUsername) {
                            $subject = $subjectMap[$selectedGrade][$teacherUsername] ?? 'Subject';
                            $name = $usernameToName[$teacherUsername] ?? $teacherUsername;
                            $periods[] = "$subject<br><small>($name)</small>";
                        } else {
                            $periods[] = '-';
                        }
                    }
                    $days[$day] = $periods;
                }
            ?>

            <?php if (empty($days)): ?>
                <p>No timetable data available for <?= htmlspecialchars($selectedGrade) ?>.</p>
            <?php else: ?>
                <div class="table-responsive border border-dark-subtle rounded mb-4">
                    <table class="table table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Day</th>
                                <th>Period 1</th>
                                <th>Period 2</th>
                                <th>Period 3</th>
                                <th>Break</th>
                                <th>Period 4</th>
                                <th>Period 5</th>
                                <th>Period 6</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day => $periods): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($day) ?></strong></td>
                                    <?php foreach ($periods as $period): ?>
                                        <td><?= $period ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php } ?>
    </section>

</div>
<!--end Right column for * and * -->
<!-- Main Content Area End-->

<?php include_once APP_FOOTER_FILE; ?>
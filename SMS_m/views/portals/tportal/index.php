<?php
    session_start();
    include_once __DIR__ . '/../../../config/config.php';
    // Check if the user is logged in and is a Teacher
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'teacher') {
        header('Location: ' . APP_AUTH_URL . '/login.php');
        exit();
    }
?>
<title><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?> Portal</title>
<?php
    include_once APP_HEADER_FILE;
    // Include the float-nav file
    include_once APP_TPORTAL_MENU;
?>

<!-- Main Content Area Start-->
<!--start Left column for attendance, info, timetable, notice-board  -->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Teacher Information -->
    <section class="card row w-25 m-2 p-2" id="teacher_info" title="User Information">
        <?php
        $teacher_username = $_SESSION['user'];
        function getTeacherInfo($teacher_username)
        {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM user_info WHERE username = ?");
            $stmt->execute([$teacher_username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $teacher_info = getTeacherInfo($teacher_username);
        ?>
        <!-- Photo;Name;Position;Head of class -->
        <div class="m-2">
            <img src="<?= htmlspecialchars($teacher_info['profile_picture']) ?>" alt="<?= htmlspecialchars($teacher_info['first_name']) ?>" class="rounded-circle img-fluid" title="User Profile">
        </div>
        <div class="">
            <h2 title="User Name"><?= htmlspecialchars(ucfirst($teacher_info['first_name'] . ' ' . $teacher_info['last_name'])) ?></h2>
            <h4 class="text-muted">Class Incharge: <?= htmlspecialchars($teacher_info['grade_incharge']) ?></h4>
            <p class="text-muted">Position: <?= htmlspecialchars(ucfirst($teacher_info['subjects_taught']) . ' ' . ucfirst($_SESSION['role'])) ?></p>
            <code class="text-dark user-select-none" title="username"><small>@<?= htmlspecialchars($_SESSION['user']) ?></small></code>
        </div>
        <!-- Update/Edit Teacher Information Button -->
    </section>

    <!-- Teacher Attendance -->
    <section class="card m-2 w-50 p-3" id="teacher_attendance">

        <h2>My Attendance</h2>
        <?php
        function getMyAttendance($teacher_username, $selected_month = null, $selected_year = null)
        {
            global $pdo;
            $query = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = ?";

            // Add month and year filtering if provided
            if ($selected_month && $selected_year) {
                $query .= " AND month = ? AND year = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$teacher_username, $selected_month, $selected_year]);
            } else {
                $stmt = $pdo->prepare($query);
                $stmt->execute([$teacher_username]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Default month and year if none selected
        $selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
        $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

        // Prepare data for charts
        $my_attendance = getMyAttendance($teacher_username, $selected_month, $selected_year);

        // Check if no attendance data is found
        if (empty($my_attendance)) {
            $error = "No attendance data found for the selected month and year.";
        } else {
            // If data is found, process it for chart
            $mymonths = [];
            $mypresent_days = [];
            $myabsent_days = [];
            $mylate_days = [];
            // Mapping of month number to month name
            $months = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            ];

            foreach ($my_attendance as $attendance) {
                $mymonths[] = $months[$attendance['month']];
                $mypresent_days[] = $attendance['present_days'];
                $myabsent_days[] = $attendance['absent_days'];
                $mylate_days[] = $attendance['late_days'];
            }
        }
        ?>

        <!-- Dropdown for selecting month and year -->
        <form method="GET">
            <select class="border rounded p-1" name="month" onchange="this.form.submit()">
                <option value="1" <?= $selected_month == 1 ? 'selected' : '' ?>>January</option>
                <option value="2" <?= $selected_month == 2 ? 'selected' : '' ?>>February</option>
                <option value="3" <?= $selected_month == 3 ? 'selected' : '' ?>>March</option>
                <option value="4" <?= $selected_month == 4 ? 'selected' : '' ?>>April</option>
                <option value="5" <?= $selected_month == 5 ? 'selected' : '' ?>>May</option>
                <option value="6" <?= $selected_month == 6 ? 'selected' : '' ?>>June</option>
                <option value="7" <?= $selected_month == 7 ? 'selected' : '' ?>>July</option>
                <option value="8" <?= $selected_month == 8 ? 'selected' : '' ?>>August</option>
                <option value="9" <?= $selected_month == 9 ? 'selected' : '' ?>>September</option>
                <option value="10" <?= $selected_month == 10 ? 'selected' : '' ?>>October</option>
                <option value="11" <?= $selected_month == 11 ? 'selected' : '' ?>>November</option>
                <option value="12" <?= $selected_month == 12 ? 'selected' : '' ?>>December</option>
            </select>

            <select class="border rounded p-1" name="year" onchange="this.form.submit()">
                <?php
                $current_year = date('Y');
                for ($i = $current_year - 5; $i <= $current_year + 5; $i++) {
                    echo '<option value="' . $i . '" ' . ($selected_year == $i ? 'selected' : '') . '>' . $i . '</option>';
                }
                ?>
            </select>
        </form>

        <?php if (isset($error)) : ?>
            <p style="color: red"><?= $error ?></p>
        <?php endif; ?>

        <!-- Canvas for the attendance chart -->
        <canvas id="MyAttendanceChart" title="My Attendance"></canvas>
    </section>

    <!-- Teacher Timetable -->
    <section class="card m-2  p-3" id="teacher_time-table">
        <h2>My Time Table</h2>
        <p><strong>Today is: </strong> <?= date('d, D, F, Y') ?></p>
        <?php
        function getMytimetable($teacher_username)
        {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM timetable WHERE username = ?");
            $stmt->execute([$teacher_username]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Fetch the timetable data
        $mytimetable = getMytimetable($teacher_username);

        ?>
        <div class="border border-bottom-0 border-dark-subtle rounded table-responsive ">
            <table id="my-timetable" class="display">
                <thead>
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
                    <?php foreach ($mytimetable as $row): ?>
                        <tr>
                            <td class="border"><?= htmlspecialchars($row['day']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_1_time']) ?>"><?= htmlspecialchars($row['period_1']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_2_time']) ?>"><?= htmlspecialchars($row['period_2']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_3_time']) ?>"><?= htmlspecialchars($row['period_3']) ?></td>
                            <td class="border" title="Break"><?= htmlspecialchars($row['break_time']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_4_time']) ?>"><?= htmlspecialchars($row['period_4']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_5_time']) ?>"><?= htmlspecialchars($row['period_5']) ?></td>
                            <td class="border" title="<?= htmlspecialchars($row['period_6_time']) ?>"><?= htmlspecialchars($row['period_6']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Teachers Notice Board -->
    <section class="card m-2 p-3 notice_board" id="teachers_notice-board">

        <h2><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?><span style="font-family:'Times New Roman', Times, serif;">'</span>s Notice Board</h2>

        <?php
        function getNoticesByRole($role)
        {
            global $pdo;

            // Prepare the SQL statement based on the user's role
            $stmt = $pdo->prepare("SELECT * FROM notice_board WHERE role = :role");
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get role and username from session if set, else set defaults
        $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';  // Default to 'guest' if role is not set
        $username = isset($_SESSION['user']) ? $_SESSION['user'] : 'Unknown User';

        // Fetch notices based on the user's role
        $notices = getNoticesByRole($role);
        ?>

        <!-- Swiper Container -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php if ($notices): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="swiper-slide">
                            <div>
                                <strong><?= htmlspecialchars($notice['title']) ?></strong><br>
                                <small>Date: <strong class="text-muted"><?= htmlspecialchars($notice['created_at']) ?></strong></small><br>
                                <p>
                                    <!-- <i class="fas fa-quote-left fa-xs"></i> -->
                                    <span><q><?= htmlspecialchars($notice['content']) ?></q></spa>
                                        <!-- <i class="fas fa-quote-right fa-xs"></i> -->
                                </p>
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
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="swiper-slide">
                        <p>No notices available.</p>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Pagination -->
            <div class="swiper-pagination"></div>
        </div>

    </section>
</div>
<!--end Left column for attendance, info, timetable, notice-board -->
<!--start Right column for * and * -->
<div class="container-fluid d-flex flex-wrap justify-content-center">

    <!-- Academic Calendar -->
    <section class="card w-50 m-2 p-3" id="academic_calendar">
        <?php
        function getEvents()
        {
            global $pdo;
            $stmt = $pdo->query("SELECT * FROM events");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $events = getEvents();
        ?>

        <div>
            <h2>Academic Calendar</h2>
            <p class="text-muted">Events: Holidays, Tests, Exhibition Day.</p>
            <div id="academic_events" class="border w-auto rounded p-3"></div>
        </div>
    </section>

    <!-- Teacher's Class Attendance -->
    <section class="card m-2 p-3" id="ClassAttendanceChart">
        <h2>My Class Attendance</h2>
        <?php
        // === Helper Functions ===
        function getTeacherClassIncharge($username) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT grade_incharge FROM user_info WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetchColumn();
        }

        function getClassAttendanceSummary($grade, $month, $year) {
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

        function getStudentsAttendanceDetails($grade, $month, $year) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT u.full_name,
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

        function getDailyAttendanceTrend($grade, $month, $year) {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT DAY(date) AS day,
                    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS late
                FROM daily_attendance
                WHERE username IN (SELECT username FROM user_info WHERE grade = ?)
                AND YEAR(date) = ? AND MONTH(date) = ?
                GROUP BY day ORDER BY day
            ");
            $stmt->execute([$grade, $year, $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // === Main Logic ===
        $selected_month = $_GET['month'] ?? date('n');
        $selected_year = $_GET['year'] ?? date('Y');
        $view_mode = $_GET['view'] ?? 'summary';

        $grade_incharge = getTeacherClassIncharge($teacher_username);
        $error = '';

        if (!$grade_incharge) {
            $error = "Class incharge not found.";
        } else {
            $summary = getClassAttendanceSummary($grade_incharge, $selected_month, $selected_year);
            $students = getStudentsAttendanceDetails($grade_incharge, $selected_month, $selected_year);
            $trend = getDailyAttendanceTrend($grade_incharge, $selected_month, $selected_year);
            if (array_sum($summary) == 0) $error = "No attendance data for this period.";
        }
        ?>

        <!-- Filters -->
        <form method="GET" style="margin-bottom:1em;">
            <select name="month" onchange="this.form.submit()">
                <?php foreach ([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $m=>$n)
                    echo "<option value=\"$m\"".($selected_month==$m?" selected":"").">$n</option>"; ?>
            </select>
            <select name="year" onchange="this.form.submit()">
                <?php for($y = date('Y') - 5; $y <= date('Y') + 5; $y++)
                    echo "<option value=\"$y\"".($selected_year==$y?" selected":"").">$y</option>"; ?>
            </select>
            <select name="view" onchange="this.form.submit()">
                <option value="summary" <?= $view_mode === 'summary' ? 'selected' : '' ?>>Summary Only</option>
                <option value="full" <?= $view_mode === 'full' ? 'selected' : '' ?>>Show Full</option>
            </select>
        </form>

        <?php if ($error): ?>
            <p style="color:red;font-weight:bold;"><?= htmlspecialchars($error) ?></p>
        <?php else: ?>
            <canvas id="attendanceSummaryChart" style="max-width:400px;"></canvas>

            <?php if ($view_mode === 'full'): ?>
                <h3>Attendance Trend</h3>
                <canvas id="attendanceTrendChart" style="max-width:700px;"></canvas>

                <h3>Details by Student</h3>
                <table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;">
                    <thead style="background:#f0f0f0;">
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

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            new Chart(document.getElementById("attendanceSummaryChart"), {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [<?= $summary['total_present'] ?>, <?= $summary['total_absent'] ?>, <?= $summary['total_late'] ?>],
                        backgroundColor: ['#4caf50','#f44336','#ff9800']
                    }]
                }
            });

            <?php if ($view_mode === 'full'): ?>
            const trend = <?= json_encode($trend) ?>;
            new Chart(document.getElementById("attendanceTrendChart"), {
                type: 'bar',
                data: {
                    labels: trend.map(d => 'Day ' + d.day),
                    datasets: [
                        { label: 'Present', data: trend.map(d => d.present), backgroundColor: '#4caf50' },
                        { label: 'Absent', data: trend.map(d => d.absent), backgroundColor: '#f44336' },
                        { label: 'Late', data: trend.map(d => d.late), backgroundColor: '#ff9800' }
                    ]
                },
                options: { responsive: true }
            });
            <?php endif; ?>
            </script>
        <?php endif; ?>
    </section>

    <!-- Teachers Class Timetable -->
    <section class="card p-3" id="class_timetable">
        <h2><?= htmlspecialchars($grade_incharge) ?> Timetable (Subjects)</h2>

        <?php
        // 1. Get logged-in teacher's grade incharge
        $stmt = $pdo->prepare("SELECT grade_incharge FROM user_info WHERE username = ?");
        $stmt->execute([$teacher_username]);
        $grade_incharge = $stmt->fetchColumn();

        if (!$grade_incharge) {
            echo "<p style='color:red;'>You're not assigned as a grade incharge.</p>";
            return;
        }

        // 2. Build subject map: grade -> username -> subject
        $stmt = $pdo->query("SELECT username, subjects_taught FROM user_info");
        $subjectMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pairs = array_map('trim', explode(',', $row['subjects_taught']));
            foreach ($pairs as $pair) {
                if (preg_match('/(.+?)\s+Grade\s+(.+)/i', $pair, $matches)) {
                    $subject = $matches[1];
                    $grade = 'Grade ' . $matches[2];
                    $subjectMap[$grade][$row['username']] = $subject;
                }
            }
        }

        // 3. Get all timetable rows
        $timetableRows = $pdo->query("SELECT * FROM timetable")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Prepare timetable data by day
        $periodKeys = ['period_1','period_2','period_3','break_time','period_4','period_5','period_6'];
        $allDays = array_unique(array_column($timetableRows, 'day'));
        sort($allDays);

        $days = [];
        foreach ($allDays as $day) {
            $periods = [];
            foreach ($periodKeys as $period) {
                if ($period === 'break_time') {
                    $periods[] = 'Break';
                    continue;
                }

                // Find teacher for this day, period, and grade
                $teacherForPeriod = null;
                foreach ($timetableRows as $row) {
                    if ($row['day'] === $day && $row[$period] === $grade_incharge) {
                        $teacherForPeriod = $row['username'];
                        break;
                    }
                }

                if ($teacherForPeriod) {
                    $subject = $subjectMap[$grade_incharge][$teacherForPeriod] ?? $teacherForPeriod;
                    $periods[] = $subject;
                } else {
                    $periods[] = '-';
                }
            }
            $days[$day] = $periods;
        }
        ?>

        <?php if (empty($days)): ?>
            <p>No timetable data found for grade <?= htmlspecialchars($grade_incharge) ?>.</p>
        <?php else: ?>
            <div class="table-responsive border border-dark-subtle rounded">
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
                                <?php foreach ($periods as $subject): ?>
                                    <td><?= htmlspecialchars($subject) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Teachers Class top Students -->
    <?php
        $top_students = [
            ['rank'=>1,'name'=>'Alice Johnson','avg'=>'95.60%','remark'=>'Excellent','badge'=>'success'],
            ['rank'=>2,'name'=>'Bob Smith','avg'=>'93.20%','remark'=>'Excellent','badge'=>'success'],
            ['rank'=>3,'name'=>'Charlie Lee','avg'=>'91.45%','remark'=>'Very Good','badge'=>'warning text-dark'],
            ['rank'=>4,'name'=>'Diana Evans','avg'=>'90.10%','remark'=>'Very Good','badge'=>'warning text-dark'],
            ['rank'=>5,'name'=>'Eric Young','avg'=>'89.75%','remark'=>'Good','badge'=>'info text-dark'],
        ];
    ?>
    <section class="card p-4 shadow-sm" style="max-width:280px;margin:2rem auto" id="top_students">
    <h2 class="h4 mb-3 text-center">Top Students - Grade <span>10th</span></h2>
    <div id="slider" style="overflow:hidden; position:relative;">
        <div id="slides" style="display:flex; transition:transform 0.4s ease;">
        <?php foreach ($top_students as $s): ?>
            <div style="min-width:100%; box-sizing:border-box; padding:1rem; text-align:center;">
            <h5><?= htmlspecialchars("{$s['rank']}. {$s['name']}") ?></h5>
            <p class="fw-semibold mb-1"><?= htmlspecialchars($s['avg']) ?></p>
            <span class="badge bg-<?= htmlspecialchars($s['badge']) ?>"><?= htmlspecialchars($s['remark']) ?></span>
            </div>
        <?php endforeach; ?>
        </div>
        <button id="prev" style="position:absolute;top:50%;left:0;transform:translateY(-50%);">‹</button>
        <button id="next" style="position:absolute;top:50%;right:0;transform:translateY(-50%);">›</button>
    </div>
    </section>

</div>
<!--end Right column for * and * -->
<!-- Main Content Area End-->
<script>
    (function(){
        const slides = document.getElementById('slides');
        const total = slides.children.length;
        let index = 0;
        const update = () => {
        slides.style.transform = `translateX(-${index * 100}%)`;
        };
        document.getElementById('prev').onclick = () => {
        index = (index > 0) ? index - 1 : total - 1;
        update();
        };
        document.getElementById('next').onclick = () => {
        index = (index + 1) % total;
        update();
        };
    })();
</script>
<?php
    if (defined('APP_FOOTER_FILE') && file_exists(APP_FOOTER_FILE)) {
        include_once APP_FOOTER_FILE;
    } elseif (defined('APP_FALLBACK_FOOTER') && file_exists(APP_FALLBACK_FOOTER)) {
        include_once APP_FALLBACK_FOOTER;
    } else {
        echo '<p style="color: red;">Footer not able to load. Please contact support.</p>';
    }
?>
<?php
    session_start();
    include_once __DIR__ . '/../../../config/config.php';
    // Check if the user is logged in and is a student
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
        header('Location: ' . APP_AUTH_URL . '/login.php');
        exit();
    }
?>
<title><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?> Portal</title>
<?php
    // Include the header
    include_once APP_HEADER_FILE;
    // Include the float-nav file
    include_once APP_STPORTAL_MENU;
?>
<!-- Main Content Area Start-->
<!--start Left column for --  -->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Student Information -->
    <section class="card w-25 m-2 p-2" id="student_info" title="User Information">
            <?php
            $student_username = $_SESSION['user'];
            function getstudentInfo($student_username)
            {
                global $pdo;
                $stmt = $pdo->prepare("SELECT * FROM user_info WHERE username = ?");
                $stmt->execute([$student_username]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $student_info = getstudentInfo($student_username);
            ?>
            <div class="m-2">
                <img src="<?= htmlspecialchars($student_info['profile_picture']) ?>" alt="<?= htmlspecialchars($student_info['first_name']) ?>" class="rounded-circle img-fluid" title="User Profile">
            </div>
            <div>
                <h2 title="User Name"><?= htmlspecialchars(ucfirst($student_info['first_name'] . ' ' . $student_info['last_name'])) ?></h2>
                <h4 class="text-muted">Class: <?= htmlspecialchars($student_info['grade']) ?></h4>
                <p class="text-muted">Section: <?= htmlspecialchars(ucfirst($student_info['section'])) ?></p>
                <code class="text-dark user-select-none"><small title="username">@<?= htmlspecialchars($_SESSION['user']) ?></small>|<small title="student id"><?= htmlspecialchars($student_info['student_id']) ?></small></code>
            </div>
    </section>

    <!-- Student Attendance -->
    <section class="card m-2 w-50 p-3" id="student_attendance">
        <h2>My Attendance</h2>
        <?php
        function getMyAttendance($student_username, $selected_month = null, $selected_year = null)
        {
            global $pdo;
            $query = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = ?";

            // Add month and year filtering if provided
            if ($selected_month && $selected_year) {
                $query .= " AND month = ? AND year = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$student_username, $selected_month, $selected_year]);
            } else {
                $stmt = $pdo->prepare($query);
                $stmt->execute([$student_username]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Default month and year if none selected
        $selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
        $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

        // Prepare data for charts
        $my_attendance = getMyAttendance($student_username, $selected_month, $selected_year);

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
    </section>

    <!-- Student Notice Board -->
    <section class="card m-2 p-3 notice_board" id="students_notice-board">
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
                                    <span><q><?= htmlspecialchars($notice['content']) ?></q></span>
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

    <!-- Student Assignments -->
    <section class="card m-2 p-3 assignments" id="student_class-assignments">
        <h2><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?><span style="font-family:'Times New Roman', Times, serif;">'</span>s Assignments Board</h2>
        <?php
            function getassignments($role)
            {
                global $pdo;

                // Prepare the SQL statement based on the user's role
                $stmt = $pdo->prepare("SELECT * FROM assignments WHERE role = :role");
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Get role and username from session if set, else set defaults
            $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';  // Default to 'guest' if role is not set
            $username = isset($_SESSION['user']) ? $_SESSION['user'] : 'Unknown User';

            // Fetch notices based on the user's role
            $notices = getassignments($role);
        ?>
        <!-- Swiper Container -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php if ($notices): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="swiper-slide">
                            <div>
                                <strong><?= htmlspecialchars($notice['title']) ?></strong><br>
                                <small>Deadline: <strong class="text-muted"><?= htmlspecialchars($notice['deadline']) ?></strong></small><br>
                                <p>
                                    <!-- <i class="fas fa-quote-left fa-xs"></i> -->
                                    <span><q><?= htmlspecialchars($notice['description']) ?></q></span>
                                        <!-- <i class="fas fa-quote-right fa-xs"></i> -->
                                </p>
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
<!--start Right column for --  -->
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

    <!-- Student Class Timetable -->
    <section class="card p-3" id="class_timetable">
        <h2><?= htmlspecialchars($student_info['grade']) ?> Timetable (Subjects)</h2>

        <?php
            // 1. Get logged-in student's grade incharge
            $stmt = $pdo->prepare("SELECT grade FROM user_info WHERE username = ?");
            $stmt->execute([$student_username]);
            $grade = $stmt->fetchColumn();

            if (!$grade) {
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
                        if ($row['day'] === $day && $row[$period] === $grade) {
                            $teacherForPeriod = $row['username'];
                            break;
                        }
                    }

                    if ($teacherForPeriod) {
                        $subject = $subjectMap[$grade][$teacherForPeriod] ?? $teacherForPeriod;
                        $periods[] = $subject;
                    } else {
                        $periods[] = '-';
                    }
                }
                $days[$day] = $periods;
            }
        ?>
        <?php if (empty($days)): ?>
            <p>No timetable data found for grade <?= htmlspecialchars($grade) ?>.</p>
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

    <!-- Class top Students -->
    <?php
        $top_students = [
            ['rank'=>1,'name'=>'Alice Johnson','avg'=>'95.60%','remark'=>'Excellent','badge'=>'success','text'=>''],
            ['rank'=>2,'name'=>'Bob Smith','avg'=>'93.20%','remark'=>'Excellent','badge'=>'success','text'=>''],
            ['rank'=>3,'name'=>'Charlie Lee','avg'=>'91.45%','remark'=>'Very Good','badge'=>'warning','text'=>'text-dark'],
            ['rank'=>4,'name'=>'Diana Evans','avg'=>'90.10%','remark'=>'Very Good','badge'=>'warning','text'=>'text-dark'],
            ['rank'=>5,'name'=>'Eric Young','avg'=>'89.75%','remark'=>'Good','badge'=>'info','text'=>'text-dark'],
        ];
    ?>
    <section class="card p-4 shadow-sm" style="max-width:280px;margin:2rem auto" id="top_students">
        <h2 class="h4 mb-3 text-center">Top Students - Grade <span><?= htmlspecialchars($student_info['grade']) ?></span></h2>
        <div id="slider" style="overflow:hidden; position:relative;">
            <div id="slides" style="display:flex; transition:transform 0.4s ease;">
            <?php foreach ($top_students as $s): ?>
                <div style="min-width:100%; box-sizing:border-box; padding:1rem; text-align:center;">
                <h5><?= htmlspecialchars("{$s['rank']}. {$s['name']}") ?></h5>
                <p class="fw-semibold mb-1"><?= htmlspecialchars($s['avg']) ?></p>
                <span class="badge bg-<?= htmlspecialchars($s['badge']) ?> <?= htmlspecialchars($s['text']) ?>"><?= htmlspecialchars($s['remark']) ?></span>
                </div>
            <?php endforeach; ?>
            </div>
            <button id="prev" style="position:absolute;top:50%;left:0;transform:translateY(-50%);">‹</button>
            <button id="next" style="position:absolute;top:50%;right:0;transform:translateY(-50%);">›</button>
        </div>
    </section>
</div>

<!-- Main Content Area End-->

<?php include_once APP_FOOTER_FILE; ?>
<?php
    session_start();
    include_once __DIR__ . '/../../../config/config.php';
    // Check if the user is logged in and is a parent
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'parent') {
        header('Location: ' . APP_AUTH_URL . '/login.php');
        exit();
    }
?>
<title><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?> Portal</title>
<?php
    // Include the header
    include_once APP_HEADER_FILE;
    // Include the float-nav file
    include_once APP_PPORTAL_MENU;
?>
<!-- Main Content Area Start-->
<!--start Left column for --  -->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- parent Information -->
    <section class="card row w-25 m-2 p-2" id="parent_info" title="User Information">
            <?php
            $parent_username = $_SESSION['user'];
            function getparentInfo($parent_username)
            {
                global $pdo;
                $stmt = $pdo->prepare("SELECT * FROM user_info WHERE username = ?");
                $stmt->execute([$parent_username]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $parent_info = getparentInfo($parent_username);
            ?>
            <div class="m-2">
                <img src="<?= htmlspecialchars($parent_info['profile_picture']) ?>" alt="<?= htmlspecialchars($parent_info['first_name']) ?>" class="rounded-circle img-fluid" title="User Profile">
            </div>
            <div>
                <h2 title="User Name"><?= htmlspecialchars(ucfirst($parent_info['full_name'])) ?></h2>
                <h4 class="text-muted"><?= htmlspecialchars($parent_info['relationship_to_student']).' of '.htmlspecialchars($parent_info['children']) ?></h4>
                <!-- <p class="text-muted">Section: <?= htmlspecialchars(ucfirst($parent_info['section'])) ?></p> -->
                <code class="text-dark user-select-none"><small title="username">@<?= htmlspecialchars($_SESSION['user']) ?></small>|<small title="parent id"><?= htmlspecialchars($parent_info['parent_id']) ?></small></code>
            </div>
    </section>

    <!-- child's Attendance -->
    <section class="card m-2 w-50 p-3" id="student_attendance">
        <h2>My Child's Attendance</h2>
        <?php
        $student_username = $parent_info['child_user'];
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
            // example of associative array
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

    <!-- Notice Board -->
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

    <!-- child's Class Timetable -->
    <!-- <section class="card p-3" id="class_timetable">
        <h2>My Child's Timetable (Subjects)</h2>

        <?php
            // 1. Get the logged-in parent's child username
            $stmt = $pdo->prepare("SELECT children FROM user_info WHERE username = ?");
            $stmt->execute([$parent_username]);
            $children = $stmt->fetchColumn();

            if (!$children) {
                echo "<p style='color:red;'>No child linked to your account.</p>";
            } else {
                $child_usernames = json_decode($children, true);

                if (empty($child_usernames)) {
                    echo "<p style='color:red;'>No valid child data found.</p>";
                } else {
                    // Assuming the parent wants to view the timetable for the first child
                    $child_username = $child_usernames[0];

                    // 2. Get the child's grade
                    $stmt = $pdo->prepare("SELECT grade FROM user_info WHERE username = ?");
                    $stmt->execute([$child_username]);
                    $student_grade = $stmt->fetchColumn();

                    if (!$student_grade) {
                        echo "<p style='color:red;'>Your child is not assigned to a grade.</p>";
                    } else {
                        // 3. Build subject map: grade -> username -> subject
                        $stmt = $pdo->query("SELECT username, subjects_taught FROM user_info");
                        $subjectMap = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $pairs = array_map('trim', explode(',', $row['subjects_taught']));
                            foreach ($pairs as $pair) {
                                if (preg_match('/(.+?)\s+Grade\s+(.+)/i', $pair, $matches)) {
                                    $subject = $matches[1];
                                    $grade_key = 'Grade ' . $matches[2];
                                    $subjectMap[$grade_key][$row['username']] = $subject;
                                }
                            }
                        }

                        // 4. Get all timetable rows
                        $timetableRows = $pdo->query("SELECT * FROM timetable")->fetchAll(PDO::FETCH_ASSOC);

                        // 5. Prepare timetable data by day
                        $periodKeys = ['period_1', 'period_2', 'period_3', 'break_time', 'period_4', 'period_5', 'period_6'];
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
                                    if ($row['day'] === $day && $row[$period] === $student_grade) {
                                        $teacherForPeriod = $row['username'];
                                        break;
                                    }
                                }

                                if ($teacherForPeriod) {
                                    $grade_key = $student_grade;
                                    $subject = $subjectMap[$grade_key][$teacherForPeriod] ?? $teacherForPeriod;
                                    $periods[] = $subject;
                                } else {
                                    $periods[] = '-';
                                }
                            }
                            $days[$day] = $periods;
                        }
                    }
                }
            }
        ?>
        <?php if (empty($student_grade) || empty($days)): ?>
            <p>No timetable data found for grade <?= isset($student_grade) ? htmlspecialchars($student_grade) : '' ?>.</p>
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
    </section> -->
    <section class="card p-3" id="class_timetable">
        <h2>My Child's Timetable (Subjects)</h2>

        <?php
            // 1. Get logged-in student's grade
            $stmt = $pdo->prepare("SELECT grade FROM user_info WHERE username = ?");
            $stmt->execute([$student_username]);
            $student_grade = $stmt->fetchColumn();

            if (!$student_grade) {
                echo "<p style='color:red;'>Your child is not assigned to a grade.</p>";
            } else {
                // 2. Build subject map: grade -> username -> subject
                $stmt = $pdo->query("SELECT username, subjects_taught FROM user_info");
                $subjectMap = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pairs = array_map('trim', explode(',', $row['subjects_taught']));
                    foreach ($pairs as $pair) {
                        if (preg_match('/(.+?)\s+Grade\s+(.+)/i', $pair, $matches)) {
                            $subject = $matches[1];
                            $grade_key = 'Grade ' . $matches[2];
                            $subjectMap[$grade_key][$row['username']] = $subject;
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
                            if ($row['day'] === $day && $row[$period] === $student_grade) {
                                $teacherForPeriod = $row['username'];
                                break;
                            }
                        }

                        if ($teacherForPeriod) {
                            $grade_key = $student_grade;
                            $subject = $subjectMap[$grade_key][$teacherForPeriod] ?? $teacherForPeriod;
                            $periods[] = $subject;
                        } else {
                            $periods[] = '-';
                        }
                    }
                    $days[$day] = $periods;
                }
            }
        ?>
        <?php if (empty($student_grade) || empty($days)): ?>
            <p>No timetable data found for grade <?= isset($student_grade) ? htmlspecialchars($student_grade) : '' ?>.</p>
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

    <!-- child's Assignments -->
    <section class="card m-2 p-3 assignments" id="student_class-assignments">
        <h2>My Child's Assignments Board</h2>
        <?php
            $role = 'student';
            function getAssignments($role)
            {
                global $pdo;

                // Prepare the SQL statement based on the user's role
                $stmt = $pdo->prepare("SELECT * FROM assignments WHERE role = :role");
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Fetch assignments based on the user's role
            $assignments = getAssignments($role);
        ?>
        <!-- Swiper Container -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php if ($assignments): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="swiper-slide">
                            <div>
                                <strong><?= htmlspecialchars($assignment['title']) ?></strong><br>
                                <small>Deadline: <strong class="text-muted"><?= htmlspecialchars($assignment['deadline']) ?></strong></small><br>
                                <p>
                                    <!-- <i class="fas fa-quote-left fa-xs"></i> -->
                                    <span><q><?= htmlspecialchars($assignment['description']) ?></q></span>
                                        <!-- <i class="fas fa-quote-right fa-xs"></i> -->
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="swiper-slide">
                        <p>No assignments available.</p>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Pagination -->
            <div class="swiper-pagination"></div>
        </div>
    </section>
</div>

<!-- Main Content Area End-->

<?php include_once APP_FOOTER_FILE; ?>
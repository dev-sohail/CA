<?php
    session_start();
    include_once __DIR__ . '/../../../config/config.php';
    // Check if the user is logged in and is a Staff
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'staff') {
        header('Location: ' . APP_AUTH_URL . '/login.php');
        exit();
    }
?>
<title><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?> Portal</title>
<?php
    // Include the header
    include_once APP_HEADER_FILE;
    // Include the float-nav file
    include_once APP_SPORTAL_MENU;
?>
<!-- Main Content Area Start-->
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Staff Information -->
    <section class="card row w-25 m-2" id="staff_info" title="User Information">
        <?php
        $staff_username = $_SESSION['user'];
        function getstaffInfo($staff_username)
        {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM user_info WHERE username = ?");
            $stmt->execute([$staff_username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $staff_info = getstaffInfo($staff_username);
        ?>
        <div class="m-2">
            <img src="<?= htmlspecialchars($staff_info['profile_picture']) ?>" alt="<?= htmlspecialchars($staff_info['first_name']) ?>" class="rounded-circle img-fluid" title="User Profile">
        </div>
        <div>
            <h2 title="User Name"><?= htmlspecialchars(ucfirst($staff_info['first_name'] . ' ' . $staff_info['last_name'])) ?></h2>
            <h4 class="text-muted">Department: <?= htmlspecialchars($staff_info['department']) ?></h4>
            <p class="text-muted">Designation: <?= htmlspecialchars(ucfirst($staff_info['designation']) . ' ' . ucfirst($_SESSION['role'])) ?></p>
            <code class="text-dark user-select-none"><small title="username">@<?= htmlspecialchars($_SESSION['user']) ?></small>|<small title="employee id"><?= htmlspecialchars($staff_info['employee_id']) ?></small></code>
        </div>
    </section>
    <!-- Staff Attendance -->
    <section class="card m-2 w-50 p-3" id="staff_attendance">
        <h2>My Attendance</h2>
        <?php
            function getMyAttendance($u, $m = null, $y = null) {
                global $pdo;
                $q = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = ?";
                $p = [$u];
                if ($m && $y) {
                    $q .= " AND month = ? AND year = ?";
                    $p[] = $m;
                    $p[] = $y;
                }
                $s = $pdo->prepare($q);
                $s->execute($p);
                return $s->fetchAll(PDO::FETCH_ASSOC);
            }
            $m = $_GET['month'] ?? date('n');
            $y = $_GET['year'] ?? date('Y');
            $d = getMyAttendance($staff_username, $m, $y);
            $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
            // Initialize arrays to avoid undefined variable warnings
            $mymonths = [];
            $mypresent_days = [];
            $myabsent_days = [];
            $mylate_days = [];
            if (!$d) echo '<p style="color:red">No attendance data found.</p>';
            else {
                foreach ($d as $r) {
                    $mymonths[] = $months[$r['month']];
                    $mypresent_days[] = $r['present_days'];
                    $myabsent_days[] = $r['absent_days'];
                    $mylate_days[] = $r['late_days'];
                }
            }
        ?>
        <form method="GET">
            <select name="month" class="border rounded p-1" onchange="this.form.submit()">
                <?php foreach ($months as $i => $n) echo "<option value='$i'" . ($m == $i ? " selected" : "") . ">$n</option>"; ?>
            </select>
            <select name="year" class="border rounded p-1" onchange="this.form.submit()">
                <?php foreach (range(date('Y') - 5, date('Y') + 5) as $i) echo "<option value='$i'" . ($y == $i ? " selected" : "") . ">$i</option>"; ?>
            </select>
        </form>
        <canvas id="MyAttendanceChart" title="My Attendance"></canvas>
    </section>
</div>
<div class="container-fluid d-flex flex-wrap justify-content-center">
    <!-- Staff Notice Board -->
    <section class="card m-2 p-3 h-85 col notice_board" id="staff_notice-board">
        <h2><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'guest'), ENT_QUOTES) ?><span style="font-family:'Times New Roman', Times, serif;">'</span>s Notice Board</h2>
        <?php
        function getNoticesByRole($role)
        {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM notice_board WHERE role = :role");
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $role = $_SESSION['role'] ?? 'guest';
        $notices = getNoticesByRole($role);
        ?>
        <div class="swiper-container h-100">
            <div class="swiper-wrapper">
                <?php if ($notices): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="swiper-slide">
                            <strong><?= htmlspecialchars($notice['title'], ENT_QUOTES) ?></strong><br>
                            <small>Date: <strong class="text-muted"><?= htmlspecialchars($notice['created_at'], ENT_QUOTES) ?></strong></small><br>
                            <p><q><?= htmlspecialchars($notice['content'], ENT_QUOTES) ?></q></p>
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
    <!-- Calendar -->
    <section class="card w-50 m-2 p-3 col" id="calendar">
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
            <p class="text-muted">Events: Holidays, Exhibition Day, etc.</p>
            <div id="academic_events" class="border w-auto rounded p-3"></div>
        </div>
    </section>

</div>
<?php include_once APP_FOOTER_FILE; ?>
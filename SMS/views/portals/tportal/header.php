<?php include_once './../../../config/config.php'; ?>
<div class="floating-nav" id="floatingNav">
    <!-- Bootstrap Flex Utility for the Nav Items -->
    <div class="nav-items left" id="navItems_1">
        <div class="nav-item" title="Attendance">
            <!-- locationed attendence; application request -->
            <a href="pages/attend.php" aria-label="Home"><i class="fas ca-attendance-icon"></i></a>
        </div>
        <div class="nav-item" title="Report">
            <!-- salary report; attendence report -->
            <a href="pages/report.php" aria-label="Report"><i class="fas ca-report-icon"></i></a>
        </div>
    </div>

    <!-- Main Button to Toggle Navigation -->
    <button class="main-button" id="toggleNav" aria-label="Toggle Navigation" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Bootstrap Flex Utility for the Nav Items -->
    <div class="nav-items right" id="navItems_2">
        <div class="nav-item" title="Lesson Plan">
            <a href="pages/planning.php" aria-label="Lesson Plan"><i class="fas ca-lesson-plan-icon"></i></a>
        </div>
        <div class="nav-item" title="My Class">
            <!-- timetable;attendence(manual, RFID);exams;assignments;test managment(marks entry(my tests, class tests));student list(student Profile view);homework diary -->
            <a href="pages/class.php" aria-label="My Class"><i class="fas ca-my-class-icon"></i></a>
        </div>
    </div>
</div>
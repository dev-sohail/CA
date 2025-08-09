<?php
// Use absolute path for config include for reliability
include_once __DIR__ . '/../../../config/config.php';
?>
<div class="floating-nav" id="floatingNav">
    <!-- Navigation Group: Left -->
    <div class="nav-items left" id="navItems_1">
        <!-- Student Attendance -->
        <div class="nav-item" title="Student Attendance">
            <a href="pages/student_attendance.php" aria-label="Student Attendance">
                <i class="fas ca-attendance-icon"></i>
            </a>
        </div>
        <!-- Progress Report -->
        <div class="nav-item" title="Progress Report">
            <a href="pages/progress_report.php" aria-label="Progress Report">
                <i class="fas ca-report-icon"></i>
            </a>
        </div>
    </div>

    <!-- Toggle Button -->
    <button class="main-button" id="toggleNav" aria-label="Toggle Navigation" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navigation Group: Right -->
    <div class="nav-items right" id="navItems_2">
        <!-- Parent Resources -->
        <div class="nav-item" title="Parent Resources">
            <a href="pages/parent_resources.php" aria-label="Parent Resources">
                <i class="fas ca-parent-resources-icon"></i>
            </a>
        </div>
        <!-- Communication Center -->
        <div class="nav-item" title="Communication Center">
            <a href="pages/communication.php" aria-label="Communication Center">
                <i class="fas ca-engage-icon"></i>
            </a>
        </div>
    </div>
</div>

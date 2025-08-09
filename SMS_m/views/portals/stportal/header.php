<?php
// Use absolute path for config include for reliability
include_once __DIR__ . '/../../../config/config.php';
?>
<div class="floating-nav" id="floatingNav">
    <!-- Bootstrap Flex Utility for the Nav Items -->
    <div class="nav-items left" id="navItems_1">
        <div class="nav-item" title="My Attendance">
            <!-- 
              locationed attendence
              RFID
              application request
            -->
            <a href="pages/attend.php" aria-label="My Attendance"><i class="fas ca-attendance-icon"></i></a>
        </div>
        <div class="nav-item" title="LMS">
            <!--  -->
            <a href="pages/lms.php" aria-label="LMS"><i class="fas ca-lms-icon"></i></a>
        </div>
    </div>

    <!-- Main Button to Toggle Navigation -->
    <button class="main-button" id="toggleNav" aria-label="Toggle Navigation" aria-expanded="false">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Bootstrap Flex Utility for the Nav Items -->
    <div class="nav-items right" id="navItems_2">
        <div class="nav-item" title="Library">
            <!-- ebooks; wiki; encyclopedia; dictionary; study material(links); study websites; -->
            <a href="pages/library.php" aria-label="Library"><i class="fas ca-library-icon"></i></a>
        </div>
        <div class="nav-item" title="Chat">
            <!-- Student communication with teacher and adminestrations -->
            <a href="pages/chat.php" aria-label="Chat"><i class="fas ca-engage-icon"></i></a>
        </div>
    </div>
</div>
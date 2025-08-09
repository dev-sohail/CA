<?php include_once 'config/config.php'; ?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the name of the current file
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- echo APP_ADMIN_URL; -->
<!-- Header -->
<?php include_once APP_HEADER_FILE; ?>
<!-- <hr> -->

<body>
    <main>
        <!-- Intro Section -->
        <section id="intro">
            <?php include APP_SEC_INTRO_FILE; ?>
        </section>
        <hr>
        <!-- Count and Portals Section -->
        <!-- <section id="port" class="pb-3">
            <?php //include APP_SEC_CP_FILE; 
            ?>
        </section>
        <hr> -->
        <!-- Quote Section -->
        <!-- <section id="quote">
            <?php //include APP_SEC_QUO_FILE; 
            ?>
        </section>
        <hr> -->
        <!-- Features Section -->
        <section id="features" class="pb-3">
            <?php include APP_SEC_FEAT_FILE; ?>
        </section>
        <hr>
        <!-- Visitor Count -->
        <!-- Reviews Section -->
        <section id="reviews">
            <?php include APP_SEC_REVI_FILE; ?>
        </section>
        <hr>
        <!-- contact Section -->
        <section id="form" class="pb-3">
            <?php include APP_SEC_CONT_FILE; ?>
        </section>
        <!-- Academic Calender Section -->
        <!-- <section id="" class="">
            <?php //include ; ?>
        </section> -->
        <!-- Notice Board Section -->
        <!-- <section id="" class="">
            <?php //include ; ?>
        </section> -->
        <!-- <hr> -->
        <!-- Popups Section -->
        <section id="popups">
            <?php include 'views/sections/popups.php'; ?>
        </section>
    </main>
</body>
<!-- Footer -->
<?php include_once APP_FOOTER_FILE; ?>

</html>

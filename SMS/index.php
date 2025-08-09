<?php

// Define core constants
$try1 = realpath(__DIR__);
$try2 = realpath(__DIR__ . '../../');
if ($try1 && is_dir($try1) && file_exists($try1 . '/config/config.php') && is_dir($try1 . '/config')) {
    define('CONF_FILE', $try1);
} elseif ($try2 && is_dir($try2) && file_exists($try2 . '/config/config.php') && is_dir($try2 . '/config')) {
    define('CONF_FILE', $try2);
} else {
    die("❌ Unable to determine ROOT path." . ROOT);
}

try {
    require_once(CONF_FILE);
} catch (Exception $e) {
    exit('config file loading error.'.CONF_FILE);
}
?>

<!DOCTYPE html>
<html lang="en">

<!-- Header -->
<?php require_once APP_HEADER_FILE; ?>

<body>
    <main>
        <!-- Intro Section -->
        <section id="intro">
            <?php require_once APP_SEC_INTRO; ?>
        </section>
        <hr>
        <!-- Features Section -->
        <section id="features" class="pb-3">
            <?php require APP_SEC_FEATURES; ?>
        </section>
        <hr>
        <!-- Visitor Count -->
        <!-- Reviews Section -->
        <section id="reviews">
            <?php require APP_SEC_REVIEW; ?>
        </section>
        <hr>
        <!-- contact Section -->
        <section id="form" class="pb-3">
            <?php require APP_SEC_CONTACT; ?>
        </section>
        <!-- Popups Section -->
        <section id="popups">
            <?php require 'views/sections/popups.php'; ?>
        </section>
    </main>
</body>
<!-- Footer -->
<?php require_once APP_FOOTER_FILE; ?>

</html>
<?php require_once __DIR__ . '/config/config.php'; ?>
<!-- Header -->
<?php require_once APP_HEADER_URL; ?>
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
<?php require_once APP_FOOTER_URL; ?>

</html>
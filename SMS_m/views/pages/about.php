<?php include_once './../../config/config.php';?>
<!-- <head> -->
    <title>About</title>
<!-- </head> -->
<?php if (defined('APP_HEADER_FILE')) include_once APP_HEADER_FILE; ?>
<!-- <hr> -->
<section class="sec-about">
    <div class="container">
        <section class="sec-int">
            <div class="container pt-5 int-main">
                <div class="row align-items-center">
                    <div class="col-md-7 d-flex flex-column justify-content-center shadow-lg sec-int-box">
                        <h1 class="display-6 fw-bold text-center text-light text-uppercase co-text-shadow">About</h1>
                        <p class="text-muted text-center">
                            Welcome to CyberAfridi School – a tech-driven learning hub where students excel through innovative education. We empower young minds with digital skills, STEM programs, and personalized learning, preparing them to thrive in a rapidly evolving world.
                        </p>
                    </div>
                    <div class="col-md-5">
                        <img src="images/logo.png" alt="logo" class="image-fluid float-end">
                    </div>
                </div>
            </div>
        </section>
<hr>
        <!-- Achievements Section -->
        <section class="achievements-section py-4">
            <h2 class="display-6 fw-medium mb-3">Achievements</h2>
            <div class="d-flex justify-content-center">
                <div class="achievement-img mx-3">
                Image
                    <div class="overlay">
                        <h4 class=>Achievement 1</h4>
                        <!-- <p>Short description here.</p> -->
                    </div>
                </div>
                <div class="achievement-img mx-3">
                    Image
                    <div class="overlay">
                        <h4>Achievement 2</h4>
                        <!-- <p>Short description here.</p> -->
                    </div>
                </div>
                <div class="achievement-img mx-3">
                    Image
                    <div class="overlay">
                        <h4>Achievement 3</h4>
                        <!-- <p>Short description here.</p> -->
                    </div>
                </div>
            </div>
        </section>
<hr>
        <!-- Gallery Section -->
        <section class="gallery-section py-4">
            <h2 class="display-6 fw-medium mb-3">Gallery</h2>
            <div class="d-flex justify-content-center">
                <div class="gallery-img mx-3"><img src="https://random.imagecdn.app/500/150"></div>
                <div class="gallery-img mx-3">Image</div>
                <div class="gallery-img mx-3">Image</div>
            </div>
        </section>

    </div>
</section>
<!-- <hr> -->
<!-- Footer -->
<?php if (defined('APP_FOOTER_FILE')) include_once APP_FOOTER_FILE; ?>
<footer id="footer">
    <div class="footer-container">
        <div class="footer-section">
            <div class="footer-logo">
                <img src="<?php echo APP_IMAGES_URL.'/e-logo.png';?>" alt="cyberafridi" style="padding: 0px; margin:-35px;">
                <p> Welcome to CyberAfridi School – a tech-driven learning hub where students excel through innovative
                    education. We empower young minds with digital skills, STEM programs, and personalized learning,
                    preparing them to thrive in a rapidly evolving world.</p>
                <p>&copy; <?php echo date('Y'); ?> CyberAfridi Ltd. - All rights reserved.</p>
				<small>version:<?= '2025.06.30';?></small>
            </div>
        </div>
        <div class="footer-section">
            <h3 class="fw-bold text-uppercase">CyberAfridi</h3>
            <ul>
                <li><a class="nav-link btn" href="<?php echo APP_ROOT_URL; ?>">Home</a></li>
                <li><a class="nav-link btn ms-2" href="<?php echo APP_PAGES_URL . '/about.php' ?>">About</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Terms</h3>
            <ul>
                <li><a class="nav-link btn" href="<?php echo APP_PAGES_URL . '/privacy-policy.php'; ?>">Privacy Policy</a></li>
                <li><a class="nav-link btn ms-2" href="<?php echo APP_PAGES_URL . '/terms-of-service.php'; ?>">Terms of Service</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Portals</h3>
            <div class="porting-buttons">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == true): ?>
                <!-- Show Logout button if user is logged in -->
                <li class="nav-item mb-2">
                    <a class="btn-primary btn me-2" href="<?php echo APP_AUTH_URL . '/logout.php'; ?>">Logout</a>
                </li>
                <li class="nav-item">
                    <a class="btn-primary btn me-2" href="<?php 
                        // Check if the user is already logged in
                        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                            // Redirect based on role
                            if ($_SESSION['role'] === 'teacher') {
                                echo(APP_TPORTAL_URL);
                            } elseif ($_SESSION['role'] === 'student') {
                                echo(APP_STPORTAL_URL);
                            } elseif ($_SESSION['role'] === 'admin') {
                                echo(APP_ADMIN_URL);
                            } elseif ($_SESSION['role'] === 'parent') {
                                echo(APP_PPORTAL_URL);
                            } elseif ($_SESSION['role'] === 'staff') {
                                echo(APP_SPORTAL_URL);
                            } else {
                                // Handle undefined roles or redirect to a default page
                                echo('Location: ' . APP_HOST_ROOT);
                            }
                        }
                    ?>">Goto Portal</a>
                </li>
                <?php else: ?>
                    <!-- Show Login button only if not on login.php -->
                    <?php if ($currentPage !== 'login.php'): ?>
                        <li class="nav-item">
                        <a class="btn-primary btn me-2" href="<?php echo APP_AUTH_URL . '/login.php'; ?>">Login</a>
                        </li>
                    <?php endif; ?>
                    <!-- Show Register button only if not on register.php -->
                    <?php if ($currentPage !== 'register.php'): ?>
                        <li class="nav-item">
                        <a class="btn-primary btn mt-2" href="<?php echo APP_AUTH_URL . '/register.php'; ?>">Register</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="footer-container">
                <div class="footer-section social-icons">
                <ul class="d-flex">
                <li class="list-unstyled"><a href="https://www.facebook.com/CyberAfridi" class="text-muted" target="_blank"><i class="fa-brands fa-facebook fa-2x"></i></a></li>
                <li class="list-unstyled"><a href="https://pk.linkedin.com/in/dev-sohail" class="text-muted" target="_blank"><i class="fa-brands fa-linkedin fa-2x"></i></a></li>
                <li class="list-unstyled"><a href="https://www.youtube.com/@cyberafridi" class="text-muted" target="_blank"><i class="fa-brands fa-youtube fa-2x"></i></a></li>
                <li class="list-unstyled"><a href="https://github.com/dev-sohail" class="text-muted" target="_blank"><i class="fa-brands fa-github fa-2x"></i></a></li>
                </ul>
                </div>
            </div>
            <div class="scroll-up" id="scroll-up">
                <a href="#"><i class="fas fa-chevron-up"></i></a>
            </div>
        </div>

    </div>
</footer>

<!-- WhatsApp Chat Button -->
<!-- <script type="text/javascript">
    (function () {
        var options = {
            whatsapp: "+923149416858", // Replace with your WhatsApp number
            message: "Hello! 👋 How can we help you?",
            call_to_action: "Message us on WhatsApp", // Call to action
            position: "right", // Position: 'right' or 'left'
        };
        var proto = document.location.protocol, host = "getbutton.io", url = proto + "//static." + host;
        var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = url + '/widget-send-button/js/init.js';
        s.onload = function () { WhWidgetSendButton.init(host, proto, options); };
        var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(s, x);
    })();
</script> -->

<?php include_once APP_JS.'/scripts.php'; ?>
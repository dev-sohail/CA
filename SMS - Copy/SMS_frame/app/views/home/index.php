<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Welcome to <?= APP_NAME ?></h1>
                <p class="lead">A comprehensive school management system designed to streamline educational administration and enhance learning experiences.</p>
                <div class="mt-4">
                    <a href="<?= APP_ROOT_URL ?>/auth/login" class="btn btn-light btn-lg me-3">Login</a>
                    <a href="<?= APP_ROOT_URL ?>/auth/register" class="btn btn-outline-light btn-lg">Register</a>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <img src="<?= APP_IMAGES_URL ?>/hero-image.png" alt="School Management" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5">Key Features</h2>
                <p class="lead text-muted">Discover what makes our system special</p>
            </div>
        </div>
        
        <div class="row">
            <?php if (!empty($features)): ?>
                <?php foreach ($features as $feature): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="feature-icon mb-3">
                                    <i class="<?= htmlspecialchars($feature['icon_class']) ?> fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($feature['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($feature['details']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No features available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section bg-light py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 text-primary fw-bold">500+</h3>
                    <p class="text-muted">Students</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 text-primary fw-bold">50+</h3>
                    <p class="text-muted">Teachers</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 text-primary fw-bold">20+</h3>
                    <p class="text-muted">Classes</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 text-primary fw-bold">95%</h3>
                    <p class="text-muted">Satisfaction</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-4">Ready to Get Started?</h2>
                <p class="lead mb-4">Join thousands of schools already using our management system.</p>
                <a href="<?= APP_ROOT_URL ?>/auth/register" class="btn btn-primary btn-lg">Get Started Today</a>
            </div>
        </div>
    </div>
</section> 
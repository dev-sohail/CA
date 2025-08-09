<div class="container-fluid d-flex justify-content-center align-items-center">
    <!-- Service Section -->
    <div class="d-flex">

        <!-- Our Service Section -->
        <div class="services-section p-4 flex-fill rounded-5" style="background: #3b82f6; width: 60rem;">
            <h2 class="dasplay-1 mb-4">Our Portals</h2>

            <!-- Services List -->
            <div class="container services-list mb-4 position-relative">
                <div class="row">
                    <div class="col-4 d-flex align-items-center mb-3">
                        <div class="border border-2 rounded-circle circle d-flex justify-content-center align-items-center text-white rounded-circle me-3"
                            style="min-width: 50px; height: 50px; font-weight: bold; font-size: 1.2em;">01</div>
                        <p class="mb-0">Parents</p>
                    </div>
                    <div class="col-4 d-flex align-items-center mb-3">
                        <div class="border border-2 rounded-circle circle d-flex justify-content-center align-items-center text-white rounded-circle me-3"
                            style="min-width: 50px; height: 50px; font-weight: bold; font-size: 1.2em;">02</div>
                        <p class="mb-0">Teachers</p>
                    </div>
                    <div class="col-4 d-flex align-items-center mb-3">
                        <div class="border border-2 rounded-circle circle d-flex justify-content-center align-items-center  text-white rounded-circle me-3"
                            style="min-width: 50px; height: 50px; font-weight: bold; font-size: 1.2em;">03</div>
                        <p class="mb-0">Students</p>
                    </div>
                    <!-- Arrow -->
                    <div class="arrow position-absolute"
                        style="left: 65px; top: 55px; width: 2px; height: 60px; background-color: var(--accent-orange); transform: rotate(45deg);">
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <h2 class="dasplay-1 mb-4">Our Success</h2>
            <div class="info-stats d-flex justify-content-between bg-dark p-3 rounded-4 mt-4">
                <div class="stat text-center text-info me-3">
                    <h3 class="count co-color-orange" data-target="6">0</h3>
                    <p class="small text-uppercase">Total Teachers</p>
                </div>
                <div class="stat text-center text-info me-3">
                    <h3 class="count co-color-orange" data-target="12">0</h3>
                    <p class="small text-uppercase">Total Students</p>
                </div>
                <div class="stat text-center text-info me-3">
                    <h3 class="count co-color-orange" data-target="5">0</h3>
                    <p class="small text-uppercase">Total Innovators</p>
                </div>
                <div class="stat text-center text-info">
                    <h3 class="count co-color-orange" data-target="4">0</h3>
                    <p class="small text-uppercase">Total Coaches</p>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Function to animate counting for each element with a data-target
    function animateCounts() {
        const counters = document.querySelectorAll('.co-color-orange');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            let count = 0;
            const increment = target / 100; // Divide target into 100 increments

            const updateCount = () => {
                count += increment;
                if (count < target) {
                    counter.textContent = Math.ceil(count);
                    requestAnimationFrame(updateCount);
                } else {
                    counter.textContent = target;
                }
            };

            updateCount();
        });
    }

    // Run the animation when the page loads
    window.addEventListener('load', animateCounts);
</script>
<style>

</style>

<?php
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Fetch features from the database
  $sql = "SELECT * FROM features_sms";
  $result = $conn->query($sql);

  $features = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $features[] = $row;
    }
  }
?>
<div class="container features-section">
  <div class="row">
    <!-- <div class="d-grid"> -->
    <h5 class="text-uppercase" style="display: none;">Features</h5>
    <h2 class="display-4  co-text-shadow">Features of Our School Management System</h2>
    <p class="lead text-light">Our management system is built with powerful features.</p>
    <!--  -->
    <div class="align-items-center col-12 d-flex justify-content-center">
      <div id="FeaturesCarousel" class="carousel slide align-items-center flex-column" data-bs-ride="carousel" data-bs-interval="2000" style="width: 100%; max-width: 24rem; min-width: 24rem;  height: 100%; max-height: 15rem; min-height: 12rem;">
        
        <!-- Carousel Inner -->
        <div class="carousel-inner feature-card h-85 w-100" style="min-height: 12rem; max-height: 15rem; max-width: 24rem; min-width: 24rem;">
          <?php
            if (!empty($features)) {
              $isActive = true;
              foreach ($features as $index => $feature) {
                $activeClass = ($isActive) ? ' active' : '';
                echo '<div class="carousel-item' . $activeClass . '">';
                echo '<div class="d-block w-100 text-center">';
                echo '<i class="feature-icon ' . htmlspecialchars($feature['icon_class']) . '" style="font-size: 50px;"></i>';
                echo '<h3>' . htmlspecialchars($feature['title']) . '</h3>';
                echo '<p>' . htmlspecialchars($feature['details']) . '</p>';
                echo '</div>';
                echo '</div>';
                $isActive = false;
              }
            } else {
              echo '<p>No features found.</p>';
            }
          ?>
        </div>
        <!-- Carousel Indicators with Proper Spacing -->
        <div class="carousel-indicators mt-5">
          <?php
          if (!empty($features)) {
            foreach ($features as $index => $feature) {
              $activeIndicator = ($index == 0) ? ' active' : '';
              echo '<button type="button" data-bs-target="#FeaturesCarousel" data-bs-slide-to="' . $index . '" class="' . $activeIndicator . '" aria-current="' . ($index == 0 ? 'true' : 'false') . '"></button>';
            }
          }
          ?>
        </div>
      </div>
    </div>

  </div>

  <!--  -->
  <!--  -->
  <div class="co-div-inqui">
    <button class="btn btn-dark co-btn-inqui" data-bs-toggle="modal" data-bs-target="#popupaddm1">Inquiry Now</button>
  </div>
</div>
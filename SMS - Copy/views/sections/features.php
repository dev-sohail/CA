<style>
  /* Features Section */
/* #features .features-section {
  background-color: var(--primary-blue);
  /* Semi-transparent black */
  border-radius: 20px;
  padding: 50px !important;
  box-shadow: 0 0px 30px gray;
} */

#features .feature-card {
  background-color: var(--accent-orange);
  border-radius: 15px;
  padding: 20px;
  transition: transform 0.3s;
  box-shadow: 0 5px 15px gray;
}

/* #features .feature-card:hover {
    transform: translateY(-10px);
} */

#features .feature-card h5,
#features .feature-card p {
  transition: transform 0.5s;
}

#features .feature-card:hover h5 {
  color: white;
  opacity: 0.7;
}

#features .feature-card:hover p {
  color: black !important;
  opacity: 0.7;
}

#features .feature-icon {
  font-size: 2rem;
  color: gray;
  /* Gray for feature icons */
}

#features .start-btn {
  background: linear-gradient(
    90deg,
    rgba(75, 75, 75, 1) 0%,
    rgba(150, 150, 150, 1) 100%
  );
  border: none;
  color: white;
  /* Button text is white */
}

#features button.co-btn-inqui {
  border: none;
  padding: 0.7rem;
  border-radius: 0.6rem;
  font-weight: bold;
}

#FeaturesCarousel .carousel-indicators button {
  background-color: var(--accent-orange);
  border-radius: 50%;
  width: 0.5rem;
  height: 0.5rem;
}

#FeaturesCarousel .carousel-indicators button:hover {
  background-color: var(--bg-white);
}
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
    <!-- <div class="align-items-center col-12 d-flex justify-content-center"> -->
    <div id="FeaturesCarousel" class="carousel slide align-items-center col-12 d-flex justify-content-center" data-bs-ride="carousel" data-bs-interval="2000" style="height: 12rem;">
      <!-- Carousel inner (items) -->
      <div class="carousel-inner h-85 w-45">
        <?php
        if (!empty($features)) {
          $isActive = true;  // First item should have the 'active' class
          $totalFeatures = count($features);
          foreach ($features as $index => $feature) {
            // Adding the 'active' class to the first item
            $activeClass = ($isActive) ? ' active' : '';
            echo '<div class="carousel-item' . $activeClass . '">';
            echo '<div class="d-block w-100 text-center">';
            echo '<i class="' . htmlspecialchars($feature['icon_class']) . '" style="font-size: 50px;"></i>';
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

      <!-- Carousel controls (Next/Prev) -->
      <!-- <a class="carousel-control-prev border border-dark h-25 rounded-3 w-auto" href="#FeaturesCarousel" role="button" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
          </a>
          <a class="carousel-control-next border border-dark h-25 rounded-3 w-auto" href="#FeaturesCarousel" role="button" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a> -->

      <!-- Carousel indicators -->
      <div class="carousel-indicators d-inline" style="justify-self:center;">
        <?php
        if (!empty($features)) {
          foreach ($features as $index => $feature) {
            // Set the first indicator as active
            $activeIndicator = ($index == 0) ? ' active' : '';
            echo '<button type="button" data-bs-target="#FeaturesCarousel" data-bs-slide-to="' . $index . '" class="' . $activeIndicator . '" aria-current="' . ($index == 0 ? 'true' : 'false') . '"></button>';
          }
        }
        ?>
      </div>
    </div>
  </div>

  <!--  -->
  <!--  -->
  <div class="co-div-inqui">
    <button class="btn btn-dark co-btn-inqui" data-bs-toggle="modal" data-bs-target="#popupaddm1">Inquiry Now</button>
  </div>
</div>
</div>
<style>
  /* Review Section */
  #reviews .col-6 {
    justify-items: center;
    display: grid;
  }


  #carouselInnovators {
    border: solid var(--primary-blue);
    border-radius: 2rem;
    max-width: 30rem;
    padding: 2rem;
    min-width: 30rem;
  }

  #carouselInnovators .review-card img {
    max-width: 100px;
    border-radius: 50%;
    margin-top: 10px;
  }

  #carouselInnovators .carousel-indicators [data-bs-target] {
    background-color: var(--accent-orange);
  }

  #carouselInnovators .carousel-item {
    min-width: 15rem;
    max-width: 30rem;
  }

  #carouselParents {
    border: solid var(--accent-orange);
    border-radius: 2rem;
    max-width: 30rem;
    padding: 2rem;
    min-width: 30rem;
  }

  #carouselParents .review-card img {
    max-width: 100px;
    border-radius: 50%;
    margin-top: 10px;
  }

  #carouselParents .carousel-indicators [data-bs-target] {
    background-color: var(--primary-blue);
  }

  #carouselParents #carouselInnovators .carousel-item {
    min-width: 15rem;
    max-width: 30rem;
  }
</style>

<div class="container">
  <div class="row gx-5 justify-content-between align-content-center align-items-center d-flex text-center">
  <div class="col-6">
      <h2 class="text-center co-text-shadow co-und-hov">Innovators</h2>
      <div id="carouselInnovators" class="carousel slide d-flex" data-bs-ride="carousel" data-bs-interval="2000">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#carouselInnovators" data-bs-slide-to="0" class="active" aria-current="true"></button>
          <button type="button" data-bs-target="#carouselInnovators" data-bs-slide-to="1" class=""></button>
          <button type="button" data-bs-target="#carouselInnovators" data-bs-slide-to="2" class=""></button>
        </div>
        <div class="carousel-inner">
  <div class="carousel-item active">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/31.jpg" alt="Innovator 1">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">Innovation distinguishes between a leader and a follower.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">Steve Jobs</figcaption>
        </figure>
      </div>
      <div class="reviewer-title">
        <h6 class="text-muted">Executive Engineer</h6>
      </div>
    </div>
  </div>
  <div class="carousel-item">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Innovator 2">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">The best way to predict the future is to invent it.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">Alan Kay</figcaption>
        </figure>
      </div>
      <div class="reviewer-title">
        <h6 class="text-muted">Software Architect</h6>
      </div>
    </div>
  </div>
  <div class="carousel-item">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/33.jpg" alt="Innovator 3">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">Creativity is thinking up new things. Innovation is doing new things.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">Theodore Levitt</figcaption>
        </figure>
      </div>
      <div class="reviewer-title">
        <h6 class="text-muted">Innovation Strategist</h6>
      </div>
    </div>
  </div>
</div>

      </div>
    </div>

    <div class="col-6">
      <h2 class=" text-center co-text-shadow co-und-hov">Parents</h2>
      <div id="carouselParents" class="carousel slide d-flex" data-bs-ride="carousel" data-bs-interval="2000">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#carouselParents" data-bs-slide-to="0" class="active" aria-current="true"></button>
          <button type="button" data-bs-target="#carouselParents" data-bs-slide-to="1" class=""></button>
          <button type="button" data-bs-target="#carouselParents" data-bs-slide-to="2" class=""></button>
        </div>
        <div class="carousel-inner">
  <div class="carousel-item active">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/36.jpg" alt="parent 1">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">The beautiful thing about learning is that no one can take it away from you.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">B.B. King</figcaption>
        </figure>
      </div>
    </div>
  </div>
  <div class="carousel-item">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/23.jpg" alt="parent 2">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">An investment in knowledge pays the best interest.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">Benjamin Franklin</figcaption>
        </figure>
      </div>
    </div>
  </div>
  <div class="carousel-item">
    <div class="review-card">
      <img src="https://randomuser.me/api/portraits/men/21.jpg" alt="parent 3">
      <div class="review-content">
        <figure>
          <blockquote>
            <p>
              <i class="fas fa-quote-left fa-xs text-muted"></i>
              <span class="lead font-italic">Education is the most powerful weapon which you can use to change the world.</span>
              <i class="fas fa-quote-right fa-xs text-muted"></i>
            </p>
          </blockquote>
          <figcaption class="blockquote-footer text-muted">Nelson Mandela</figcaption>
        </figure>
      </div>
    </div>
  </div>
</div>

      </div>
      </div>

  </div>
</div>
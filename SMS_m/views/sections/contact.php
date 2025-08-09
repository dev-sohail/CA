<div class="contact-container">
    <!-- Contact Form -->
    <form class="contact-form">
        <div class="form-group">
            <input type="text" name="first_name" placeholder="First name *" required>
            <input type="text" name="last_name" placeholder="Last name *" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email *" required>
            <input type="tel" name="phone" placeholder="Phone">
        </div>
        <textarea name="message" rows="4" placeholder="Message *" required></textarea>
        <button type="submit">Send Message</button>
    </form>

    <!-- Tabbed Contact Info -->
    <div class="contact-tabs">
        <div class="tab-menu">
            <button onclick="showTab('email')" id="email-tab" class="active">Email</button>
            <button onclick="showTab('location')" id="location-tab">Location</button>
            <button onclick="showTab('phone')" id="phone-tab">Phone</button>
        </div>
        <div class="tab-content">
            <div id="email" class="tab-item active">
                <i class="icon fas fa-envelope"></i>
                <p>Contact us by email</p>
                <a href="mailto:mail@ca.com">mail@ca.com</a>
            </div>
            <div id="location" class="tab-item">
                <i class="icon fas fa-map-marker-alt"></i>
                <p>Visit us @ our Office</p>
                <a href="https://www.google.com/maps?q=1+Rowan+Lodge,+1092+Chester+Road+Stratford,+Manchester,+UK"
                    target="_blank">
                    1 Rowan Lodge, 1092 Chester Road Stratford, Manchester, UK
                </a>
            </div>
            <div id="phone" class="tab-item">
                <i class="icon fas fa-phone"></i>
                <p>Call us at</p>
                <a href="tel:+123456789">+123456789</a>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabId) {
      const tabs = document.querySelectorAll('.tab-item');
      const buttons = document.querySelectorAll('.tab-menu button');
      tabs.forEach(tab => tab.classList.remove('active'));
      buttons.forEach(btn => btn.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');
      document.getElementById(tabId + '-tab').classList.add('active');
    }
  </script>
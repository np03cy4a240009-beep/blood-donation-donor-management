<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BloodLine Home</title>

  <!-- fonts from google -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
  
  <!-- styles -->
  <link rel="stylesheet" href="assets/css/index.css"/>
</head>
<body>

<!-- HEADER -->

<nav>
  <a href="index.php" class="nav-brand">
    <div class="nav-logo-icon">
      <img src="assets/images/logo.png" alt="BloodLine Logo" />
    </div>
    <div class="nav-brand-text">
      <div class="nav-brand-name">BloodLine Home</div>
      <div class="nav-brand-tagline">Saving Lives Together</div>
    </div>
  </a>
  <div class="nav-actions">
    <a href="login.php" class="btn-link">Login</a>
    <a href="register.php" class="btn-primary">Register</a>
  </div>
</nav>

<!-- HERO SECTION -->

<div class="hero">
  <video class="hero-video" autoplay muted loop>
    <source src="assets/video/Blood Donar.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>
  <div class="hero-icon-wrap">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191
               5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447
               5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136
               8.625-11 14.402z"/>
    </svg>
  </div>
  <h1>Welcome to BloodLine Home</h1>
  <p>A comprehensive blood donation and management system connecting donors with
     patients in need. Every drop counts in saving lives.</p>
  <div class="hero-btns">
    <a href="#" class="btn-primary">Become a Donor</a>
    <a href="#" class="btn-outline">Learn More</a>
  </div>
</div>

<!-- ABOUT SECTION -->

<section class="about">
  <h2 class="section-title">About Our Blood Bank</h2>
  <div class="about-grid">

    <!-- mission card -->
    <div class="about-card">
      <div class="about-icon blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <h3>Our Mission</h3>
      <p>To provide a safe, reliable, and efficient blood donation and distribution
         system that saves lives and serves our community with excellence.</p>
    </div>

    <!-- safety card -->
    <div class="about-card">
      <div class="about-icon green">
        <svg viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
      </div>
      <h3>Safety First</h3>
      <p>All donated blood undergoes rigorous testing and screening to ensure
         the highest standards of safety for both donors and recipients.</p>
    </div>

    <!-- 24/7 card -->
    <div class="about-card">
      <div class="about-icon pink">
        <svg viewBox="0 0 24 24" fill="none" stroke="#e0002b" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <h3>24/7 Availability</h3>
      <p>Our blood bank operates round the clock to ensure that life-saving blood
         is available whenever and wherever it's needed.</p>
    </div>

  </div>
</section>

<!-- FEATURES SECTION -->

<section class="features">
  <h2 class="section-title">Key Features</h2>
  <div class="features-grid">

    <!-- registration feature -->
    <div class="feature-card">
      <div class="feature-icon red">
        <svg viewBox="0 0 24 24" fill="none" stroke="#e0002b" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <div>
        <h4>Easy Registration</h4>
        <p>Quick and simple registration process for donors. Provide basic
           information, medical history, and you're ready to start saving lives.</p>
      </div>
    </div>

    <!-- appointments feature -->
    <div class="feature-card">
      <div class="feature-icon blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8"  y1="2" x2="8"  y2="6"/>
          <line x1="3"  y1="10" x2="21" y2="10"/>
        </svg>
      </div>
      <div>
        <h4>Appointment Booking</h4>
        <p>Schedule donation appointments at your convenience. Request blood
           for patients with flexible scheduling options.</p>
      </div>
    </div>

    <!-- inventory feature -->
    <div class="feature-card">
      <div class="feature-icon green">
        <svg viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
      </div>
      <div>
        <h4>Smart Inventory Management</h4>
        <p>FIFO-based inventory system ensures proper blood usage. Special
           tracking for rare blood groups to prevent wastage.</p>
      </div>
    </div>

    <!-- rare blood groups feature -->
    <div class="feature-card">
      <div class="feature-icon purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6"  y1="20" x2="6"  y2="14"/>
        </svg>
      </div>
      <div>
        <h4>Rare Blood Group Support</h4>
        <p>Special handling for rare blood groups with frozen storage. Requests
           are stored and processed when inventory becomes available.</p>
      </div>
    </div>

    <!-- stock alerts feature -->
    <div class="feature-card">
      <div class="feature-icon yellow">
        <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
      </div>
      <div>
        <h4>Low Stock Alerts</h4>
        <p>Automatic alerts when blood inventory falls below critical levels.
           Expiry date tracking to minimize waste.</p>
      </div>
    </div>

    <!-- admin feature -->
    <div class="feature-card">
      <div class="feature-icon teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
      </div>
      <div>
        <h4>Admin Approval System</h4>
        <p>Secure admin dashboard to review and approve donation and request
           appointments. Complete user and inventory management.</p>
      </div>
    </div>

  </div>
</section>

<!-- WHY DONATE SECTION -->

<section class="why">
  <h2 class="section-title">Why Donate Blood?</h2>
  <div class="why-list">

    <!-- save lives reason -->
    <div class="why-card red-bg">
      <div class="why-emoji">❤️</div>
      <div>
        <h4>Save Lives</h4>
        <p>One blood donation can save up to three lives. Your contribution makes
           a real difference in emergency situations, surgeries, and for patients
           with chronic illnesses.</p>
      </div>
    </div>

    <!-- health benefits reason -->
    <div class="why-card blue-bg">
      <div class="why-emoji">🏥</div>
      <div>
        <h4>Health Benefits</h4>
        <p>Regular blood donation can help maintain healthy iron levels, reduce
           the risk of heart disease, and provide a free health screening.</p>
      </div>
    </div>

    <!-- community impact reason -->
    <div class="why-card green-bg">
      <div class="why-emoji">🌟</div>
      <div>
        <h4>Community Impact</h4>
        <p>Blood cannot be manufactured – it can only come from donors like you.
           Your donation helps ensure a stable supply for your community.</p>
      </div>
    </div>

  </div>
</section>

<!-- CALL TO ACTION -->

<div class="cta-banner">
  <h2>Ready to Make a Difference?</h2>
  <p>Join thousands of donors who are saving lives every day. Register now and
     become a hero.</p>
  <div class="cta-btns">
    <a href="#" class="btn-white">Register as Donor</a>
    <a href="#" class="btn-outline-white">Login</a>
  </div>
</div>

<!-- FOOTER -->

<footer>
  <div class="footer-grid">

    <!-- brand info -->
    <div>
      <div class="footer-brand-name">LifeSaver Blood Bank</div>
      <p class="footer-brand-desc">Committed to saving lives through safe and
         efficient blood donation services.</p>
    </div>

    <!-- quick links -->
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul class="footer-links">
        <li><a href="#">Become a Donor</a></li>
        <li><a href="#">Login</a></li>
        <li><a href="#">About Us</a></li>
      </ul>
    </div>

    <!-- contact info -->
    <div class="footer-col">
      <h4>Contact</h4>
      <div class="footer-contact">
        <div class="footer-contact-item">
          <span>📞</span><span>Emergency: 911</span>
        </div>
        <div class="footer-contact-item">
          <span>✉️</span><span>Email: info@lifesaver.com</span>
        </div>
        <div class="footer-contact-item">
          <span>📍</span><span>Location: Kathmandu, Nepal</span>
        </div>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    &copy; 2026 LifeSaver Blood Bank. All rights reserved.
  </div>
</footer>

</body>
</html>

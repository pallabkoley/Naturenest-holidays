<?php
/* =========================================================
   ABOUT US — Nature Nest Holidays
   ========================================================= */
if (!defined('BASE_URL')) {
    // Try common locations for config.php
    $__configCandidates = [
        __DIR__ . '/../config.php',
        __DIR__ . '/../includes/config.php',
        __DIR__ . '/../inc/config.php',
        __DIR__ . '/../admin/config.php',
        __DIR__ . '/../../config.php',
    ];
    foreach ($__configCandidates as $__cfg) {
        if (file_exists($__cfg)) { require_once $__cfg; break; }
    }
    if (!defined('BASE_URL')) {
        die('Config file not found. Please check where your config.php is located and update the $__configCandidates array in about.php.');
    }
}

$pageTitle       = 'About Us';
$pageDescription = 'Learn about Nature Nest Holidays — a team of travel designers, mountain lovers and hospitality specialists crafting premium Indian journeys since 2014.';
$currentPage     = 'about';

// Locate header.php
$__headerCandidates = [
    __DIR__ . '/../header.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../inc/header.php',
    __DIR__ . '/../partials/header.php',
];
$__headerFile = null;
foreach ($__headerCandidates as $__h) {
    if (file_exists($__h)) { $__headerFile = $__h; break; }
}
if (!$__headerFile) {
    die('header.php not found. Please check its location and update the $__headerCandidates array in about.php.');
}
require_once $__headerFile;
?>

<main>

  <!-- ============================================================
       PAGE HERO
       ============================================================ -->
  <section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=80');">
    <div class="container page-hero-inner">
      <span class="eyebrow eyebrow--light">Who We Are</span>
      <h1>About Nature Nest Holidays</h1>
      <p>Curating unforgettable Indian journeys — from Himalayan escapes to coastal retreats — since 2014.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep">/</span>
        <span>About Us</span>
      </nav>
    </div>
  </section>

  <!-- ============================================================
       OUR STORY — SPLIT
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="split reveal">
        <div class="split-media">
          <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1200&q=80"
               alt="Himalayan peaks reflected in a still lake at sunrise" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Our Story</span>
          <h2>Journeys Designed With Heart, Not Templates</h2>
          <p>Nature Nest Holidays was born in a small Kolkata studio in 2014 with one simple belief — a great journey should feel personal, never packaged.</p>
          <p>What began as a two-person operation helping friends plan Himalayan treks has grown into a full-service travel company trusted by thousands of travellers across India and abroad. Yet our promise remains unchanged: every itinerary is handcrafted, every stay personally vetted, and every trip backed by a real human who cares.</p>
          <ul>
            <li>Over 12 years of curated travel expertise</li>
            <li>Handpicked stays, verified local partners</li>
            <li>Dedicated travel manager for every journey</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       STATS
       ============================================================ -->
  <section class="section section--tint">
    <div class="container">
      <div class="section-head reveal">
        <span class="eyebrow">By The Numbers</span>
        <h2>Trusted By Travellers Across India</h2>
        <p>A decade of crafting memorable journeys — here is what that looks like in numbers.</p>
      </div>

      <div class="stats-grid reveal">
        <div class="stat">
          <strong>12+</strong>
          <span>Years of Experience</span>
        </div>
        <div class="stat">
          <strong>8,500+</strong>
          <span>Happy Travellers</span>
        </div>
        <div class="stat">
          <strong>60+</strong>
          <span>Destinations Covered</span>
        </div>
        <div class="stat">
          <strong>450+</strong>
          <span>Weddings Planned</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       OUR VALUES / WHY CHOOSE US
       ============================================================ -->
  <section class="section why">
    <div class="container">
      <div class="section-head reveal">
        <span class="eyebrow">What Drives Us</span>
        <h2>Our Values &amp; Promise</h2>
        <p>Four principles guide every journey we design — from a weekend escape to a grand destination wedding.</p>
      </div>

      <div class="why-grid">
        <article class="why-card reveal">
          <div class="why-icon">
            <i class="fa-solid fa-compass"></i>
          </div>
          <h3>Expertly Curated Journeys</h3>
          <p>Every itinerary is personally researched, route-tested and refined so you spend time experiencing — not figuring things out.</p>
        </article>

        <article class="why-card reveal">
          <div class="why-icon">
            <i class="fa-solid fa-heart"></i>
          </div>
          <h3>Personalized Experiences</h3>
          <p>Honeymoon, family, solo or a group of twenty — we tailor pace, stays and experiences around the people travelling.</p>
        </article>

        <article class="why-card reveal">
          <div class="why-icon">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <h3>Trusted Travel Support</h3>
          <p>A dedicated travel manager on WhatsApp throughout your trip, plus verified partners on the ground at every destination.</p>
        </article>

        <article class="why-card reveal">
          <div class="why-icon">
            <i class="fa-solid fa-star"></i>
          </div>
          <h3>Memorable Moments</h3>
          <p>Private sunrise viewpoints, home-cooked local meals, surprise celebrations — the moments you'll talk about for years.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ============================================================
       MISSION SPLIT (reverse)
       ============================================================ -->
  <section class="section section--tint">
    <div class="container">
      <div class="split reverse reveal">
        <div class="split-media">
          <img src="https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?auto=format&fit=crop&w=1200&q=80"
               alt="Traditional houseboat cruising through Kerala backwaters" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Our Mission</span>
          <h2>Making Indian Travel Effortless &amp; Enriching</h2>
          <p>We exist to help travellers discover the true depth of India — its landscapes, cultures, cuisine and people — through journeys that feel effortless and enriching.</p>
          <p>Whether it's a honeymoon in Kashmir, a family reunion in Kerala or a Himalayan adventure in Sikkim, we obsess over the small details so you can focus on the big memories.</p>
          <ul>
            <li>Authentic local experiences over tourist checklists</li>
            <li>Responsible travel that supports local communities</li>
            <li>Transparent pricing with zero hidden surprises</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       HOW WE WORK
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="section-head reveal">
        <span class="eyebrow">How We Work</span>
        <h2>Four Simple Steps To Your Journey</h2>
        <p>From the first conversation to the moment you return home, we keep the process simple and personal.</p>
      </div>

      <div class="steps reveal">
        <div class="step">
          <div class="step-num">01</div>
          <div class="step-content">
            <h3>Share Your Vision</h3>
            <p>Tell us where you've been dreaming of — or let our team suggest the perfect fit.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">02</div>
          <div class="step-content">
            <h3>Get A Custom Plan</h3>
            <p>Receive a tailored itinerary with handpicked stays, experiences and transparent pricing.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">03</div>
          <div class="step-content">
            <h3>Refine Together</h3>
            <p>Adjust hotels, duration and experiences until the plan feels completely yours.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">04</div>
          <div class="step-content">
            <h3>Travel &amp; Enjoy</h3>
            <p>We handle every detail on the ground while you simply soak in the journey.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       TEAM SPLIT
       ============================================================ -->
  <section class="section section--tint">
    <div class="container">
      <div class="split reveal">
        <div class="split-media">
          <img src="https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80"
               alt="Travel team planning a journey" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Our Team</span>
          <h2>Small Team. Big Passion.</h2>
          <p>We're a tight-knit crew of travel designers, mountain guides, hospitality specialists and storytellers based out of Kolkata. Most of us have spent years on the road — and it shows in the journeys we craft.</p>
          <p>When you plan with Nature Nest, you're not talking to a call centre. You're talking to a real person who has stood at that sunrise point, slept in that houseboat and eaten at that little roadside stall.</p>
          <ul>
            <li>Personal travel managers for every booking</li>
            <li>On-ground partners in every destination</li>
            <li>24×7 support throughout your journey</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       CTA
       ============================================================ -->
  <section class="cta">
    <div class="container">
      <div class="cta-inner reveal">
        <span class="eyebrow eyebrow--light">Let's Plan Together</span>
        <h2>Ready To Start Your Journey?</h2>
        <p>Tell us where you want to go — we'll handle the rest.</p>
        <div class="cta-actions">
          <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-primary btn-lg">Explore Tours</a>
          <a href="<?= PAGES_URL ?>contact.php" class="btn btn-ghost btn-lg">Talk To An Expert</a>
        </div>
      </div>
    </div>
  </section>

</main>

<?php
// Locate footer.php
$__footerCandidates = [
    __DIR__ . '/../footer.php',
    __DIR__ . '/../includes/footer.php',
    __DIR__ . '/../inc/footer.php',
    __DIR__ . '/../partials/footer.php',
];
$__footerFile = null;
foreach ($__footerCandidates as $__f) {
    if (file_exists($__f)) { $__footerFile = $__f; break; }
}
if ($__footerFile) {
    require_once $__footerFile;
}
?>
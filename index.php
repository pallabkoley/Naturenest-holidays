<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Home';
$pageDescription = $SITE['site_name'] . ' — premium Indian travel, tourism and holiday booking.';

/* =========================================================
   HERO SLIDER — 3 slides
   ========================================================= */
$heroSlides = [

    [
        'eyebrow'  => 'HIMALAYAN ESCAPES',
        'title'    => 'Wake Up In The Clouds',
        'desc'     => 'From the tea gardens of Darjeeling to the monasteries of Sikkim — breathe in the mountains with journeys designed around you.',
        'image'    => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=80',
        'alt'      => 'Himalayan snow-capped mountains with pine forest valley',
        'btn1_txt' => 'View Tour Packages',
        'btn1_url' => PAGES_URL . 'tour-packages.php',
        'btn2_txt' => 'Travel Experiences',
        'btn2_url' => PAGES_URL . 'tour-packages.php',
    ],

    [
        'eyebrow'  => 'CULTURE & HERITAGE',
        'title'    => 'Journeys That Stay With You',
        'desc'     => 'Palaces, backwaters, deserts and coastlines — discover the many moods of India through thoughtfully paced, premium itineraries.',
        'image'    => BASE_URL . 'assets/images/slider/Banner 4.jpg', 
        'alt'      => 'Scenic travel banner',
        'btn1_txt' => 'Start Exploring',
        'btn1_url' => PAGES_URL . 'tour-packages.php',
        'btn2_txt' => 'Plan With An Expert',
        'btn2_url' => PAGES_URL . 'contact.php',
    ],

    [
        'eyebrow'  => 'WELCOME TO NATURE NEST HOLIDAYS',
        'title'    => 'Discover Beautiful India',
        'desc'     => 'Explore unforgettable destinations, curated experiences and memorable journeys — handcrafted for travellers who want more than a holiday.',
        'image'    => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1920&q=80',
        'alt'      => 'Himalayan peaks above the clouds at sunrise',
        'btn1_txt' => 'Explore Destinations',
        'btn1_url' => PAGES_URL . 'tour-packages.php',
        'btn2_txt' => 'Book Your Journey',
        'btn2_url' => PAGES_URL . 'booking.php',
    ],

];

/* ---- Other homepage data ---- */
$destinations = [];
$packages = [];
$services = [];
$testimonials = [];
$treks = [];

// ১. এখানে পরিবর্তন করা হয়েছে: tour_package_lists এর বদলে tour_packages ব্যবহার করা হয়েছে।
try { $destinations = $pdo->query("SELECT * FROM destinations WHERE status='active' ORDER BY display_order ASC, id ASC LIMIT 6")->fetchAll(); } catch (Exception $e) {}
try { $packages = $pdo->query("SELECT * FROM tour_packages WHERE status='active' ORDER BY position ASC, id ASC LIMIT 8")->fetchAll(); } catch (Exception $e) {}
try { $services = $pdo->query("SELECT * FROM services WHERE status='active' ORDER BY display_order ASC LIMIT 4")->fetchAll(); } catch (Exception $e) {}
try { $testimonials = $pdo->query("SELECT * FROM testimonials WHERE status='approved' AND featured=1 ORDER BY id DESC LIMIT 3")->fetchAll(); } catch (Exception $e) {}
try { $treks = $pdo->query("SELECT * FROM treks WHERE status=1 ORDER BY display_order ASC, id DESC LIMIT 3")->fetchAll(); } catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';

/* ---------- Image fallback maps ---------- */
$destFallbacks = [
  'Darjelling' => '1544735716-392fe2489ffa',
  'Darjeeling' => '1544735716-392fe2489ffa',
  'Sikkim'     => '1508672019048-805c876b67e2',
  'Kulu'       => '1626621341517-bbf3d9990a23',
  'Kullu'      => '1626621341517-bbf3d9990a23',
  'Manali'     => '1626621341517-bbf3d9990a23',
  'Kashmir'    => '1566837945700-30057527ade0',
  'Goa'        => '1477587458883-47145ed94245',
  'Kerala'     => '1602216056096-3b40cc0c9944',
  'Rajasthan'  => '1524492412937-b28074a5d7da',
];

/* ---------- Per-slug image fallbacks for tour packages ---------- */
$pkgImgBySlug = [
  'sikkim'                => '1508672019048-805c876b67e2',
  'darjeeling'            => '1544735716-392fe2489ffa',
  'himachal-pradesh'      => '1626621341517-bbf3d9990a23',
  'spiti-valley-trip'     => '1580285973203-e3dfbfb5c0e2',
  'kashmir'               => '1583212292454-1fe6229603b7',
  'bhutan'                => '1553853504-de0d1937d2b0',
  'puri'                  => '1507525428034-b723cf961d3e',
  'pilgrimage-tour'       => '1564507592333-c60657eea523',
  'offbeat'               => '1516426122078-c23e76319801',
  'shimla'                => '1597074866923-dc0589150358',
  'kullu'                 => '1626621341517-bbf3d9990a23',
  'manali'                => '1605649487212-47bdab064df7',
  'offbeat-north-bengal'  => '1544735716-392fe2489ffa',
  'offbeat-sikkim'        => '1508672019048-805c876b67e2',
  'offbeat-dooars'        => '1516426122078-c23e76319801',
];

$pkgFallback  = '1464822759023-fed622ff2c3b';
$trekFallback = '1551632811-561732d1e306';
?>

<!-- ============================================================
     HERO SLIDER
     ============================================================ -->
<section class="hero" id="home" aria-label="Featured travel destinations">

  <?php foreach ($heroSlides as $i => $s): ?>
    <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>">
      <img class="hero-img"
           src="<?= e($s['image']) ?>"
           alt="<?= e($s['alt']) ?>"
           <?= $i === 0 ? '' : 'loading="lazy"' ?>>
      <div class="hero-body">
        <div class="container">
          <div class="hero-content">
            <span class="eyebrow"><?= e($s['eyebrow']) ?></span>
            <h1><?= e($s['title']) ?></h1>
            <p><?= e($s['desc']) ?></p>
            <div class="hero-actions">
              <a href="<?= e($s['btn1_url']) ?>" class="btn btn-primary btn-lg"><?= e($s['btn1_txt']) ?></a>
              <a href="<?= e($s['btn2_url']) ?>" class="btn btn-ghost btn-lg"><?= e($s['btn2_txt']) ?></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <button class="hero-nav prev" type="button" aria-label="Previous slide">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
  </button>
  <button class="hero-nav next" type="button" aria-label="Next slide">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
  </button>
</section>

<!-- ============================================================
     POPULAR DESTINATIONS
     ============================================================ -->
<?php if (!empty($destinations)): ?>
<section class="section" id="destinations">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Destinations</span>
      <h2>Explore Popular Destinations</h2>
      <p>Discover India's most beautiful destinations with unforgettable experiences.</p>
    </div>

    <div class="dest-grid">
      <?php foreach ($destinations as $d):
        $fb = $destFallbacks[$d['name']] ?? '1506905925346-21bda4d32df4';
        $fallbackUrl = 'https://images.unsplash.com/photo-' . $fb . '?auto=format&fit=crop&w=900&q=80';
      ?>
        <a class="dest-card reveal"
           href="<?= PAGES_URL ?>destination-detail.php?slug=<?= urlencode($d['slug']) ?>">
          <div class="dest-media">
            <img src="<?= e(img_url($d['featured_image'], 'destinations')) ?>"
                 alt="<?= e($d['name']) ?>"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='<?= $fallbackUrl ?>'">
            <?php if (!empty($d['best_time_to_visit'])): ?>
              <span class="dest-tag"><?= e($d['best_time_to_visit']) ?></span>
            <?php endif; ?>
          </div>
          <div class="dest-body">
            <h3><?= e($d['name']) ?></h3>
            <p><?= e(plain_text($d['description'], 130)) ?></p>
            <span class="card-btn">
              Explore
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     FEATURED TOUR PACKAGES (FULLY DYNAMIC IMAGES FIXED)
     ============================================================ -->
<?php if (!empty($packages)): ?>
<section class="section section--tint" id="packages">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Curated Itineraries</span>
      <h2>Featured Tour Packages</h2>
      <p>Thoughtfully paced journeys with handpicked stays, private transfers and local experts by your side.</p>
    </div>

    <div class="pkg-grid">
      <?php foreach ($packages as $p):
        // --- DYNAMIC IMAGE LOGIC ---
        $pImg = $p['featured_image'] ?? '';
        
        if (preg_match('#^https?://#', $pImg)) { 
            // 1. External link (http:// or https://)
            $imgUrl = $pImg; 
            $fallbackUrl = $pImg;
        } elseif (strpos($pImg, 'admin1/') === 0 || strpos($pImg, 'uploads/') === 0) { 
            // 2. Local database path (e.g., admin1/assets/upload/...)
            $imgUrl = BASE_URL . $pImg; 
            $fallbackUrl = $imgUrl;
        } elseif (!empty($pImg)) { 
            // 3. Just filename (e.g., image.jpg)
            $imgUrl = img_url($pImg, 'tour-packages'); 
            $fallbackId  = $pkgImgBySlug[$p['slug']] ?? $pkgFallback;
            $fallbackUrl = 'https://images.unsplash.com/photo-' . $fallbackId . '?auto=format&fit=crop&w=800&q=80';
        } else { 
            // 4. Empty - use Unsplash fallback
            $fallbackId  = $pkgImgBySlug[$p['slug']] ?? $pkgFallback;
            $imgUrl      = 'https://images.unsplash.com/photo-' . $fallbackId . '?auto=format&fit=crop&w=800&q=80';
            $fallbackUrl = $imgUrl;
        }
        // --- END DYNAMIC IMAGE LOGIC ---

        $days   = (int)($p['duration_days']   ?? 0);
        $nights = (int)($p['duration_nights'] ?? 0);
        $diff   = $p['difficulty_level'] ?? '';
        
        // ২. এখানে পরিবর্তন করা হয়েছে: tour_packages টেবিলে tour_overview নেই, তাই description ব্যবহার করা হয়েছে।
        $desc   = $p['description'] ?? ($p['tour_overview'] ?? '');
      ?>
        <article class="pkg-card reveal">
          <div class="pkg-media">
            <img src="<?= e($imgUrl) ?>"
                 alt="<?= e($p['title']) ?>"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='<?= e($fallbackUrl) ?>'">
          </div>
          <div class="pkg-body">
            <h3><?= e($p['title']) ?></h3>
            <div class="pkg-meta">
              <?php if ($days > 0): ?>
                <span>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                  <?= $days ?>D<?= $nights > 0 ? ' / ' . $nights . 'N' : '' ?>
                </span>
              <?php endif; ?>
              <?php if ($diff): ?>
                <span>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                  <?= e(ucfirst($diff)) ?>
                </span>
              <?php endif; ?>
            </div>
            <p class="pkg-desc"><?= e(plain_text($desc, 120)) ?></p>
            <div class="pkg-actions">
              <a href="<?= PAGES_URL ?>tour-detail.php?slug=<?= urlencode($p['slug']) ?>"
                 class="btn btn-outline btn-xs">View Details</a>
              <a href="<?= PAGES_URL ?>booking.php?package=<?= urlencode($p['slug']) ?>"
                 class="btn btn-primary btn-xs">Book Now</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     WHY CHOOSE US
     ============================================================ -->
<section class="section why" id="about">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">About Nature Nest</span>
      <h2>Why Choose Nature Nest Holidays?</h2>
      <p>We are a small team of travel designers, mountain lovers and hospitality specialists who believe a great journey should feel personal — never packaged.</p>
    </div>

    <div class="why-grid">
      <article class="why-card reveal">
        <div class="why-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-2 5-5 2 2-5 5-2z"/>
          </svg>
        </div>
        <h3>Expertly Curated Journeys</h3>
        <p>Every itinerary is personally researched, route-tested and refined so you spend time experiencing — not figuring things out.</p>
      </article>

      <article class="why-card reveal">
        <div class="why-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.8 5.6a5 5 0 00-7.1 0L12 7.3l-1.7-1.7a5 5 0 10-7.1 7.1l8.8 8.8 8.8-8.8a5 5 0 000-7.1z"/>
          </svg>
        </div>
        <h3>Personalized Travel Experiences</h3>
        <p>Honeymoon, family, solo or a group of twenty — we tailor pace, stays and experiences around the people travelling.</p>
      </article>

      <article class="why-card reveal">
        <div class="why-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l7 3v6c0 4.4-3 8.2-7 9-4-.8-7-4.6-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>
          </svg>
        </div>
        <h3>Trusted Travel Support</h3>
        <p>A dedicated travel manager on WhatsApp through your trip — plus verified partners on the ground at every destination.</p>
      </article>

      <article class="why-card reveal">
        <div class="why-icon">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.7l5.9-.8L12 3.5z"/>
          </svg>
        </div>
        <h3>Memorable Experiences</h3>
        <p>Private sunrise viewpoints, home-cooked local meals, surprise celebrations — the moments you'll talk about for years.</p>
      </article>
    </div>
  </div>
</section>

<!-- ============================================================
     SERVICES
     ============================================================ -->
<?php if (!empty($services)): ?>
<section class="section section--tint">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">What We Offer</span>
      <h2>Our Travel Services</h2>
      <p>End-to-end travel services so you can focus on the journey, not the logistics.</p>
    </div>

    <div class="why-grid">
      <?php foreach ($services as $s): ?>
        <article class="why-card reveal">
          <div class="why-icon">
            <i class="<?= e($s['icon'] ?: 'fas fa-star') ?>" style="font-size:22px"></i>
          </div>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e(plain_text($s['description'], 140)) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     DESTINATION WEDDING BANNER
     ============================================================ -->
<section class="wedding" id="wedding">
  <div class="wedding-overlay"></div>
  <div class="container wedding-inner">
    <div class="wedding-copy reveal">
      <span class="eyebrow eyebrow--light">Destination Weddings</span>
      <h2>Celebrate Your Love in Extraordinary Places</h2>
      <p>From breathtaking mountains to luxurious resorts, create a wedding experience your guests will remember forever.</p>
      <a href="<?= PAGES_URL ?>destination-wedding.php" class="btn btn-primary">Explore Destination Weddings</a>

      <ul class="wedding-stats">
        <li><strong>450+</strong><span>Weddings Planned</span></li>
        <li><strong>60+</strong><span>Curated Venues</span></li>
        <li><strong>12</strong><span>Years of Expertise</span></li>
      </ul>
    </div>
  </div>
</section>

<!-- ============================================================
     EXPERIENCES
     ============================================================ -->
<section class="section" id="experiences">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Experiences</span>
      <h2>Experiences Beyond Travel</h2>
      <p>Choose the kind of journey that fits your mood — every experience below can be woven into a custom itinerary.</p>
    </div>

    <div class="exp-grid">
      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=900&q=80"
             alt="Trekker walking along a Himalayan ridge" loading="lazy">
        <div class="exp-body">
          <h3>Mountain Adventures</h3>
          <span>Trekking · Rafting · Camping</span>
        </div>
      </a>

      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80"
             alt="Couple enjoying a romantic sunset getaway" loading="lazy">
        <div class="exp-body">
          <h3>Romantic Getaways</h3>
          <span>Honeymoons · Anniversaries</span>
        </div>
      </a>

      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=900&q=80"
             alt="Historic Indian monument reflecting cultural heritage" loading="lazy">
        <div class="exp-body">
          <h3>Cultural Experiences</h3>
          <span>Heritage · Festivals · Food</span>
        </div>
      </a>

      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=900&q=80"
             alt="Wildlife on safari in a national park" loading="lazy">
        <div class="exp-body">
          <h3>Wildlife &amp; Nature</h3>
          <span>Safaris · Sanctuaries · Forests</span>
        </div>
      </a>

      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=900&q=80"
             alt="Luxury resort suite with a scenic view" loading="lazy">
        <div class="exp-body">
          <h3>Luxury Escapes</h3>
          <span>Palace Stays · Private Villas</span>
        </div>
      </a>

      <a class="exp-card reveal" href="<?= PAGES_URL ?>tour-packages.php">
        <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=900&q=80"
             alt="Family road trip through scenic countryside" loading="lazy">
        <div class="exp-body">
          <h3>Family Holidays</h3>
          <span>Kid Friendly · Relaxed Pace</span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     HOW IT WORKS
     ============================================================ -->
<section class="section section--tint" id="how">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Simple Process</span>
      <h2>Plan Your Journey With Us</h2>
      <p>Four easy steps from first idea to final boarding pass.</p>
    </div>

    <div class="steps">
      <div class="step reveal">
        <div class="step-num">01</div>
        <div class="step-content">
          <h3>Choose Your Destination</h3>
          <p>Tell us where you've been dreaming of — or let our team suggest the perfect fit.</p>
        </div>
      </div>
      <div class="step reveal">
        <div class="step-num">02</div>
        <div class="step-content">
          <h3>Select Your Package</h3>
          <p>Pick a curated itinerary or a theme that matches your travel style and budget.</p>
        </div>
      </div>
      <div class="step reveal">
        <div class="step-num">03</div>
        <div class="step-content">
          <h3>Customize Your Journey</h3>
          <p>Adjust hotels, duration and experiences until the plan feels completely yours.</p>
        </div>
      </div>
      <div class="step reveal">
        <div class="step-num">04</div>
        <div class="step-content">
          <h3>Travel &amp; Enjoy</h3>
          <p>We handle the details on the ground while you simply soak in the journey.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     TESTIMONIALS
     ============================================================ -->
<?php if (!empty($testimonials)): ?>
<section class="section" id="testimonials">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Testimonials</span>
      <h2>What Our Travelers Say</h2>
      <p>Real journeys, real families, real memories — shared by the people who travelled with us.</p>
    </div>

    <div class="testi-grid">
      <?php foreach ($testimonials as $t): ?>
        <article class="testi-card reveal">
          <span class="quote-mark" aria-hidden="true">&rdquo;</span>
          <div class="testi-head">
            <?php if (!empty($t['client_image'])): ?>
              <img class="avatar"
                   src="<?= e(img_url($t['client_image'], 'testimonials')) ?>"
                   alt="<?= e($t['client_name']) ?>" loading="lazy"
                   onerror="this.onerror=null;this.src='https://i.pravatar.cc/120?u=<?= urlencode($t['client_name']) ?>'">
            <?php else: ?>
              <div class="avatar" style="display:grid;place-items:center;background:var(--forest-light);color:var(--forest);font-weight:700;font-size:1.2rem">
                <?= e(strtoupper(substr($t['client_name'], 0, 1))) ?>
              </div>
            <?php endif; ?>
            <div>
              <h4><?= e($t['client_name']) ?></h4>
              <?php if (!empty($t['client_location'])): ?>
                <span class="testi-place"><?= e($t['client_location']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="stars" aria-label="Rated <?= (int)$t['rating'] ?> out of 5">
            <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
          </div>
          <p><?= e($t['testimonial_text']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     FEATURED TREKS
     ============================================================ -->
<?php if (!empty($treks)): ?>
<section class="section section--tint">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Trekking</span>
      <h2>Featured Treks</h2>
      <p>Expert-led mountain treks for every experience level.</p>
    </div>

    <div class="pkg-grid">
      <?php foreach ($treks as $t):
        if (!empty($t['featured_image'])) {
            $trekImg = img_url($t['featured_image'], 'treks');
        } else {
            $trekImg = 'https://images.unsplash.com/photo-' . $trekFallback . '?auto=format&fit=crop&w=800&q=80';
        }
      ?>
        <article class="pkg-card reveal">
          <div class="pkg-media">
            <img src="<?= e($trekImg) ?>"
                 alt="<?= e($t['name']) ?>"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-<?= $trekFallback ?>?auto=format&fit=crop&w=800&q=80'">
          </div>
          <div class="pkg-body">
            <h3><?= e($t['name']) ?></h3>
            <div class="pkg-meta">
              <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <?= e($t['duration']) ?> days
              </span>
              <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <?= e($t['difficulty_level']) ?>
              </span>
            </div>
            <p class="pkg-desc"><?= e(plain_text($t['description'], 120)) ?></p>
            <div class="pkg-actions">
              <a href="<?= PAGES_URL ?>trek-detail.php?slug=<?= urlencode($t['slug']) ?>"
                 class="btn btn-outline btn-xs">View Details</a>
              <a href="<?= PAGES_URL ?>trek-booking.php?trek_id=<?= (int)$t['id'] ?>"
                 class="btn btn-primary btn-xs">Book Now</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CALL TO ACTION
     ============================================================ -->
<section class="cta">
  <div class="container">
    <div class="cta-inner reveal">
      <span class="eyebrow eyebrow--light">Let's Plan Together</span>
      <h2>Your Next Adventure Starts Here</h2>
      <p>Let us help you create a journey worth remembering.</p>
      <div class="cta-actions">
        <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-primary btn-lg">Explore Tours</a>
        <a href="<?= PAGES_URL ?>booking.php" class="btn btn-ghost btn-lg">Book Now</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
/* =========================================================
   DESTINATION WEDDING — Nature Nest Holidays (DYNAMIC)
   ========================================================= */

if (!defined('BASE_URL')) {
    $__configCandidates = [
        __DIR__ . '/../config.php',
        __DIR__ . '/../includes/config.php',
        __DIR__ . '/../inc/config.php',
        __DIR__ . '/../../config.php',
    ];
    foreach ($__configCandidates as $__cfg) {
        if (file_exists($__cfg)) { require_once $__cfg; break; }
    }
    if (!defined('BASE_URL')) die('config.php not found.');
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = null;
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $__dbCandidates = [
            __DIR__ . '/../includes/db.php',
            __DIR__ . '/../inc/db.php',
            __DIR__ . '/../config/db.php',
            __DIR__ . '/../db.php',
        ];
        foreach ($__dbCandidates as $__d) {
            if (file_exists($__d)) {
                try {
                    require_once $__d;
                    foreach (['pdo','db','conn','connection'] as $k) {
                        if (isset($GLOBALS[$k]) && $GLOBALS[$k] instanceof PDO) { $pdo = $GLOBALS[$k]; break; }
                    }
                    if (!$pdo && class_exists('Database')) {
                        try {
                            $obj = new Database();
                            if (method_exists($obj, 'getConnection')) {
                                $c = $obj->getConnection();
                                if ($c instanceof PDO) $pdo = $c;
                            }
                        } catch (Throwable $e) { error_log($e->getMessage()); }
                    }
                } catch (Throwable $e) {
                    error_log('db.php load failed: ' . $e->getMessage());
                    $pdo = null;
                }
                break;
            }
        }
    }
}

$destinationWeddings = [];
if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT * FROM destination_weddings WHERE status = 1 ORDER BY sort_order ASC, id ASC");
        if ($stmt) $destinationWeddings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Fetch destination_weddings failed: ' . $e->getMessage());
    }
}

$dw_img_base = (defined('ASSET_URL') ? ASSET_URL : '../assets/') . 'uploads/destination-weddings/';

$dw_fallbacks = [
    'udaipur'       => 'https://images.unsplash.com/photo-1599661046289-e31897846e41?auto=format&fit=crop&w=900&q=80',
    'darjeeling'    => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=900&q=80',
    'kullu'         => 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?auto=format&fit=crop&w=900&q=80',
    'manali'        => 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?auto=format&fit=crop&w=900&q=80',
    'pelling'       => 'https://images.unsplash.com/photo-1508672019048-805c876b67e2?auto=format&fit=crop&w=900&q=80',
    'shantiniketan' => 'https://images.unsplash.com/photo-1609920658906-8223bd289001?auto=format&fit=crop&w=900&q=80',
    'puri'          => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80',
];
$dw_default_fallback = 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80';

$pageTitle       = 'Destination Wedding';
$pageDescription = 'Celebrate your love in extraordinary places — premium destination wedding planning by Nature Nest Holidays.';
$currentPage     = 'destination-wedding';

$__headerCandidates = [
    __DIR__ . '/../header.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../inc/header.php',
];
$__headerFile = null;
foreach ($__headerCandidates as $__h) if (file_exists($__h)) { $__headerFile = $__h; break; }
if (!$__headerFile) die('header.php not found.');
require_once $__headerFile;
?>

<style>
main .reveal,
main .reveal.is-visible{
  opacity:1 !important;
  transform:none !important;
}
.dest-empty{
  grid-column:1/-1;
  text-align:center;
  padding:60px 20px;
  background:#fff;
  border-radius:14px;
  border:1px dashed #d8e0d8;
  color:#6b7a72;
}
.dest-empty i{
  font-size:2.6rem;
  color:#b9c7bd;
  margin-bottom:14px;
  display:block;
}
.dest-empty h3{
  font-size:1.15rem;
  margin-bottom:6px;
  color:#3b4a41;
}

/* 9 services — 3×3 grid */
.why-grid--services{
  grid-template-columns: repeat(3, 1fr);
}
@media (max-width: 991px){
  .why-grid--services{ grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px){
  .why-grid--services{ grid-template-columns: 1fr; }
}
.why-grid--services .why-card{
  text-align: left;
}
</style>

<main>

  <!-- ============ PAGE HERO ============ -->
  <section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=1920&q=80');">
    <div class="container page-hero-inner">
      <span class="eyebrow eyebrow--light">Destination Weddings</span>
      <h1>Celebrate Your Love in Extraordinary Places</h1>
      <p>From Himalayan retreats to royal palaces and coastal shores — we craft weddings your guests will remember forever.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep">/</span>
        <span>Destination Wedding</span>
      </nav>
    </div>
  </section>

  <!-- ============ INTRO SPLIT ============ -->
  <section class="section">
    <div class="container">
      <div class="split">
        <div class="split-media">
          <img src="https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=80"
               alt="Romantic destination wedding setup" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Your Dream Wedding</span>
          <h2>A Wedding As Unique As Your Love Story</h2>
          <p>Every couple has a story. Our job is to find the perfect backdrop — a royal Rajasthani palace, a misty Darjeeling tea estate, a Himalayan meadow in Kullu-Manali, or a serene beach in Puri — and turn it into the setting for your celebration.</p>
          <p>From intimate gatherings of 30 to grand celebrations of 300+, we handle venue scouting, décor, catering, guest logistics, entertainment and every small detail in between.</p>
          <ul>
            <li>450+ weddings successfully planned since 2014</li>
            <li>Handpicked venues across <?php echo max(7, count($destinationWeddings)); ?> signature destinations</li>
            <li>Dedicated wedding manager from day one</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ STATS ============ -->
  <section class="section section--tint">
    <div class="container">
      <div class="stats-grid">
        <div class="stat"><strong>450+</strong><span>Weddings Planned</span></div>
        <div class="stat"><strong><?php echo count($destinationWeddings) ?: 7; ?></strong><span>Signature Destinations</span></div>
        <div class="stat"><strong>12</strong><span>Years of Expertise</span></div>
        <div class="stat"><strong>100%</strong><span>Happy Couples</span></div>
      </div>
    </div>
  </section>

  <!-- ============ WHAT WE HANDLE (9 services) ============ -->
  <section class="section why">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">What We Handle</span>
        <h2>End-To-End Wedding Planning</h2>
        <p>You focus on the moment — we handle everything else.</p>
      </div>

      <div class="why-grid why-grid--services">

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-seedling"></i></div>
          <h3>Venue Décor &amp; Floral Arrangement</h3>
          <p>Mandap design, floral themes, stage aesthetics, lighting and every decorative detail crafted around your vision.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-calendar-check"></i></div>
          <h3>Event Management</h3>
          <p>Complete coordination of mehendi, haldi, sangeet, wedding and reception — every ritual, every timeline, managed.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-hotel"></i></div>
          <h3>Accommodation &amp; Guest Coordination</h3>
          <p>Room blocks, welcome kits, check-in support and on-ground coordination for every guest, from arrival to farewell.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-car-side"></i></div>
          <h3>Vehicle Management</h3>
          <p>Airport transfers, luxury cars for the couple, guest shuttles and on-call vehicles throughout the wedding days.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-utensils"></i></div>
          <h3>Catering &amp; Menu Customization</h3>
          <p>Curated multi-cuisine menus, regional specialties, live counters and tasting sessions — tailored to your taste.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-camera-retro"></i></div>
          <h3>Photography &amp; Entertainment</h3>
          <p>Cinematic films, candid photography, drone coverage, live bands, DJs, anchors and firework shows.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-map-location-dot"></i></div>
          <h3>Prime Wedding Venues</h3>
          <p>Access to handpicked palaces, resorts, private estates and heritage properties across India's finest destinations.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
          <h3>Wedding Packages &amp; Planning Support</h3>
          <p>Flexible packages for every budget with transparent pricing, no hidden charges and a dedicated wedding manager.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <h3>And Many More</h3>
          <p>Mehendi artists, makeup, invitations, return gifts, marriage registration assistance — just ask, we'll arrange it.</p>
        </article>

      </div>
    </div>
  </section>

  <!-- ============ POPULAR WEDDING DESTINATIONS (DYNAMIC) ============ -->
  <section class="section section--tint">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Where Dreams Unfold</span>
        <h2>Popular Wedding Destinations</h2>
        <p>Handpicked destinations that make every moment unforgettable.</p>
      </div>

      <div class="dest-grid">
        <?php if (!empty($destinationWeddings)): ?>
          <?php foreach ($destinationWeddings as $dw): ?>
            <?php
              $dw_name = $dw['destination'] ?? '';
              $dw_desc = $dw['wedding_style'] ?? '';

              if (!empty($dw['image'])) {
                  $dw_img = $dw_img_base . rawurlencode($dw['image']);
              } else {
                  $key = strtolower(trim($dw_name));
                  $dw_img = $dw_fallbacks[$key] ?? $dw_default_fallback;
              }
            ?>
            <a class="dest-card" href="<?= PAGES_URL ?>contact.php?dest=<?= urlencode($dw_name) ?>">
              <div class="dest-media">
                <img src="<?= htmlspecialchars($dw_img) ?>"
                     alt="<?= htmlspecialchars($dw_name) ?> wedding"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='<?= htmlspecialchars($dw_default_fallback) ?>';">
              </div>
              <div class="dest-body">
                <h3><?= htmlspecialchars($dw_name) ?></h3>
                <p><?= htmlspecialchars($dw_desc) ?></p>
                <span class="card-btn">Enquire Now <i class="fa-solid fa-arrow-right"></i></span>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dest-empty">
            <i class="fa-solid fa-ring"></i>
            <h3>Destination Weddings Coming Soon</h3>
            <p>We are curating our wedding destinations. Please check back shortly.</p>
            <p style="margin-top:12px;">
              <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:8px;background:#1a7a4c;color:#fff;text-decoration:none;font-weight:600;">
                <i class="fa-solid fa-phone"></i> Enquire Now
              </a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ============ PROCESS ============ -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">How It Works</span>
        <h2>Your Journey To "I Do"</h2>
        <p>Four simple steps from the first call to the final farewell.</p>
      </div>

      <div class="steps">
        <div class="step">
          <div class="step-num">01</div>
          <div class="step-content">
            <h3>Discovery Call</h3>
            <p>We learn your story, style, guest count and budget.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">02</div>
          <div class="step-content">
            <h3>Venue &amp; Concept</h3>
            <p>We shortlist venues and present mood boards and package options.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">03</div>
          <div class="step-content">
            <h3>Plan &amp; Coordinate</h3>
            <p>We lock every vendor, design every detail and rehearse the flow.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">04</div>
          <div class="step-content">
            <h3>Celebrate</h3>
            <p>You relax and enjoy — we run the show behind the scenes.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ CTA ============ -->
  <section class="cta">
    <div class="container">
      <div class="cta-inner">
        <span class="eyebrow eyebrow--light">Let's Begin</span>
        <h2>Plan Your Dream Wedding With Us</h2>
        <p>Tell us your vision — we'll craft the celebration you've always imagined.</p>
        <div class="cta-actions">
          <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Start Planning</a>
          <a href="tel:+919832468096" class="btn btn-ghost btn-lg">
            <i class="fa-solid fa-phone"></i> Call Us
          </a>
        </div>
      </div>
    </div>
  </section>

</main>

<?php
$__footerCandidates = [
    __DIR__ . '/../footer.php',
    __DIR__ . '/../includes/footer.php',
    __DIR__ . '/../inc/footer.php',
];
$__footerFile = null;
foreach ($__footerCandidates as $__f) if (file_exists($__f)) { $__footerFile = $__f; break; }
if ($__footerFile) require_once $__footerFile;
?>
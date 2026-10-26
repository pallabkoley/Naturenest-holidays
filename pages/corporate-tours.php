<?php
/* =========================================================
   CORPORATE TOURS — Nature Nest Holidays
   Pan-India corporate offsites, retreats & MICE
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

$pageTitle       = 'Corporate Tours';
$pageDescription = 'Pan-India corporate offsites, team retreats, MICE events and incentive tours — curated by Nature Nest Holidays.';
$currentPage     = 'corporate-tours';

$regions = [
    [
        'name'    => 'North India',
        'tagline' => 'Mountains · Heritage · Adventure',
        'image'   => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Himachal Pradesh', 'Uttarakhand', 'Rishikesh', 'Amritsar', 'Agra', 'Delhi NCR'],
    ],
    [
        'name'    => 'South India',
        'tagline' => 'Backwaters · Beaches · Hills',
        'image'   => 'https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Kerala', 'Goa', 'Coorg', 'Ooty', 'Munnar', 'Andaman'],
    ],
    [
        'name'    => 'East India',
        'tagline' => 'Himalayas · Tea Estates · Coast',
        'image'   => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Darjeeling', 'Sikkim', 'Puri', 'Kolkata', 'Shillong', 'Kaziranga'],
    ],
    [
        'name'    => 'West India',
        'tagline' => 'Palaces · Desert · Coastline',
        'image'   => 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Rajasthan', 'Mumbai', 'Lonavala', 'Kutch', 'Udaipur', 'Rann of Kutch'],
    ],
    [
        'name'    => 'Central India',
        'tagline' => 'Wildlife · Forts · Forests',
        'image'   => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Khajuraho', 'Bandhavgarh', 'Kanha', 'Pachmarhi', 'Orchha', 'Ujjain'],
    ],
    [
        'name'    => 'Northeast India',
        'tagline' => 'Offbeat · Culture · Wilderness',
        'image'   => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=900&q=80',
        'cities'  => ['Meghalaya', 'Arunachal', 'Nagaland', 'Assam', 'Manipur', 'Mizoram'],
    ],
];

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

/* ============================================================
   SECTION SPACING OVERRIDES (tighten the excess gaps)
   ============================================================ */
.corp-services-section{
  padding: 62px 0 68px;              /* tighter than default 96px */
}
.corp-services-section .section-head{
  margin-bottom: 32px;               /* was 56px */
}
.corp-services-section .section-head p{
  max-width: 560px;
  margin-inline: auto;
}

/* ============================================================
   5 SERVICE CARDS — corporate services grid (compact)
   ============================================================ */
.why-grid--corp{
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 18px;
  align-items: stretch;
}
@media (max-width: 1199px){
  .why-grid--corp{ grid-template-columns: repeat(3, 1fr); gap: 20px; }
}
@media (max-width: 767px){
  .why-grid--corp{ grid-template-columns: repeat(2, 1fr); gap: 16px; }
}
@media (max-width: 500px){
  .why-grid--corp{ grid-template-columns: 1fr; gap: 14px; }
}

.why-grid--corp .why-card{
  text-align: left;
  padding: 22px 20px 20px;           /* was 30px 22px 28px */
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: 16px;
}
.why-grid--corp .why-icon{
  width: 44px;                        /* was 52px */
  height: 44px;
  margin-bottom: 14px;                /* was 18px */
  font-size: 17px;
  border-radius: 12px;
}
.why-grid--corp .why-card h3{
  font-size: .98rem;
  line-height: 1.3;
  margin-bottom: 8px;                 /* was 10px */
  min-height: 2.6em;                  /* force equal title height for tidy rows */
}
.why-grid--corp .why-card p{
  font-size: .84rem;
  line-height: 1.55;
  margin: 0;
  flex: 1;
}

/* ============================================================
   REGION GRID — pan-India coverage
   ============================================================ */
.region-grid{
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
@media (max-width: 991px){ .region-grid{ grid-template-columns: repeat(2, 1fr); gap: 22px; } }
@media (max-width: 640px){ .region-grid{ grid-template-columns: 1fr; gap: 20px; } }

.region-card{
  background:#fff;
  border:1px solid #ece6d4;
  border-radius: 18px;
  overflow:hidden;
  box-shadow: 0 4px 18px rgba(15,61,46,.05);
  transition: transform .35s cubic-bezier(.22,.61,.36,1),
              box-shadow .35s cubic-bezier(.22,.61,.36,1),
              border-color .35s;
  position:relative;
  display:flex;
  flex-direction:column;
}
.region-card:hover{
  transform: translateY(-6px);
  box-shadow: 0 18px 42px rgba(15,61,46,.12);
  border-color: #d9cf9f;
}

.region-media{
  position:relative;
  aspect-ratio: 16/10;
  overflow:hidden;
  background:#eef2ee;
}
.region-media img{
  width:100%; height:100%;
  object-fit:cover;
  display:block;
  transition: transform .9s cubic-bezier(.22,.61,.36,1);
}
.region-card:hover .region-media img{
  transform: scale(1.09);
}
.region-media::after{
  content:'';
  position:absolute;
  inset:0;
  background: linear-gradient(to top,
              rgba(10,44,33,.75) 0%,
              rgba(10,44,33,.25) 45%,
              rgba(10,44,33,.05) 100%);
  pointer-events:none;
}
.region-media-num{
  position:absolute;
  top:14px; left:14px;
  z-index:2;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width: 38px;
  height: 30px;
  padding: 0 10px;
  background: rgba(255,255,255,.95);
  color: var(--forest);
  font-family: var(--font-display);
  font-size:.8rem;
  font-weight:700;
  letter-spacing:.08em;
  border-radius: 999px;
  backdrop-filter: blur(6px);
  box-shadow: 0 2px 8px rgba(0,0,0,.12);
}

.region-body{
  padding: 22px 22px 20px;
  display:flex;
  flex-direction:column;
  flex:1;
}
.region-name{
  font-family: var(--font-display);
  font-size: 1.3rem;
  color: var(--forest);
  margin: 0 0 5px;
  line-height:1.2;
}
.region-tag{
  font-size:.8rem;
  color: #8a8a7a;
  margin: 0 0 16px;
  letter-spacing:.01em;
}
.region-chips{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
  margin-bottom: 18px;
}
.region-chip{
  display:inline-block;
  padding: 6px 12px;
  background:#faf7ed;
  border:1px solid #ece3c9;
  border-radius: 999px;
  font-size:.78rem;
  color:#294536;
  font-weight:500;
  transition: background .18s, border-color .18s, color .18s;
}
.region-card:hover .region-chip{
  background:#f5efd9;
  border-color:#d9cf9f;
}
.region-cta{
  display:inline-flex;
  align-items:center;
  gap:8px;
  font-size:.85rem;
  font-weight:600;
  color: var(--forest);
  text-decoration:none;
  margin-top:auto;
  transition: gap .2s, color .2s;
}
.region-cta:hover{ gap:12px; color: var(--gold-dark); }

.pan-india-note{
  max-width: 720px;
  margin: 0 auto 34px;
  text-align:center;
}
.pan-india-note p{
  font-size:.98rem;
  line-height:1.7;
  color:#6b7a72;
}
</style>

<main>

  <!-- ============ PAGE HERO ============ -->
  <section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1920&q=80');">
    <div class="container page-hero-inner">
      <span class="eyebrow eyebrow--light">Corporate Offsites · All India</span>
      <h1>Corporate Tours &amp; Team Retreats</h1>
      <p>From Himalayan retreats to beachside resorts, desert camps to backwater houseboats — we design offsites across every corner of India.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep">/</span>
        <span>Corporate Tours</span>
      </nav>
    </div>
  </section>

  <!-- ============ INTRO SPLIT ============ -->
  <section class="section">
    <div class="container">
      <div class="split">
        <div class="split-media">
          <!-- UPDATED IMAGE PATH HERE -->
          <img src="<?= BASE_URL ?>assets/images/corporate/corporate 1st.jpeg"
               alt="Corporate team meeting in a scenic retreat" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Why Corporate Offsites Matter</span>
          <h2>Build Stronger Teams Beyond The Boardroom</h2>
          <p>The best ideas don't happen in cubicles. They happen on mountain trails, around bonfires, on houseboats and in heritage courtyards. We help companies design meaningful offsites that build culture, boost morale and re-energize teams.</p>
          <p>From 10-member leadership retreats to 500-person annual offsites — anywhere in India — we handle destinations, logistics, activities, catering and on-ground coordination, so your team just shows up and unwinds.</p>
          <ul>
            <li>Pan-India operations across 25+ states &amp; UTs</li>
            <li>Custom team-building activities at every destination</li>
            <li>Transparent corporate pricing &amp; GST invoicing</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ STATS ============ -->
  <section class="section section--tint">
    <div class="container">
      <div class="stats-grid">
        <div class="stat"><strong>250+</strong><span>Corporate Offsites</span></div>
        <div class="stat"><strong>150+</strong><span>Companies Served</span></div>
        <div class="stat"><strong>All India</strong><span>Coverage</span></div>
        <div class="stat"><strong>12</strong><span>Years of Expertise</span></div>
      </div>
    </div>
  </section>

  <!-- ============ WHAT WE HANDLE (5 corporate services) ============ -->
  <section class="section why corp-services-section">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">What We Handle</span>
        <h2>End-To-End Corporate Travel</h2>
        <p>From single-city meetings to multi-day offsites — anywhere in India — we take care of everything.</p>
      </div>

      <div class="why-grid--corp">

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-hotel"></i></div>
          <h3>Accommodation &amp; Guest Coordination</h3>
          <p>Room blocks, welcome kits, check-in support and on-ground coordination for every team member.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-car-side"></i></div>
          <h3>Vehicle Management</h3>
          <p>Airport transfers, luxury cars, buses, cabs and on-call vehicles throughout the offsite.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-microphone-lines"></i></div>
          <h3>Conferences &amp; Meetings Arrangement</h3>
          <p>AV setup, breakout rooms, stage design, speaker coordination and registration support.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-crown"></i></div>
          <h3>VIP Services</h3>
          <p>Priority check-ins, dedicated concierge, private transfers and premium experiences for leadership teams.</p>
        </article>

        <article class="why-card">
          <div class="why-icon"><i class="fa-solid fa-utensils"></i></div>
          <h3>Catering &amp; Event Management</h3>
          <p>Multi-cuisine menus, live counters, gala dinners, cultural nights and complete event production.</p>
        </article>

      </div>
    </div>
  </section>

  <!-- ============ PAN-INDIA REGIONS ============ -->
  <section class="section section--tint">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">All India Coverage</span>
        <h2>Where Teams Go To Recharge</h2>
        <p>Six regions. Hundreds of destinations. One team planning it all for you.</p>
      </div>

      <div class="pan-india-note">
        <p>From Himalayan retreats to coastal resorts, desert camps to backwater houseboats — we operate across every region of India. Whether you want a 2-day leadership retreat near your city or a 7-day multi-city team journey, we'll design it.</p>
      </div>

      <div class="region-grid">
        <?php foreach ($regions as $i => $r): ?>
          <article class="region-card reveal">
            <div class="region-media">
              <img src="<?= htmlspecialchars($r['image']) ?>"
                   alt="<?= e($r['name']) ?> corporate offsite"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=900&q=80';">
              <span class="region-media-num">0<?= $i + 1 ?></span>
            </div>

            <div class="region-body">
              <h3 class="region-name"><?= e($r['name']) ?></h3>
              <p class="region-tag"><?= e($r['tagline']) ?></p>

              <div class="region-chips">
                <?php foreach ($r['cities'] as $city): ?>
                  <span class="region-chip"><?= e($city) ?></span>
                <?php endforeach; ?>
              </div>

              <a class="region-cta" href="<?= PAGES_URL ?>contact.php?region=<?= urlencode($r['name']) ?>">
                Plan in <?= e(explode(' ', $r['name'])[0]) ?>
                <i class="fa-solid fa-arrow-right"></i>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ PROCESS ============ -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">How It Works</span>
        <h2>From Brief To Boarding Pass</h2>
        <p>Four simple steps from your first call to the team's return home.</p>
      </div>

      <div class="steps">
        <div class="step">
          <div class="step-num">01</div>
          <div class="step-content">
            <h3>Share Your Brief</h3>
            <p>Team size, dates, budget, goals and preferred destinations.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">02</div>
          <div class="step-content">
            <h3>Get A Proposal</h3>
            <p>Curated itinerary, venue options, cost sheet and activity plan.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">03</div>
          <div class="step-content">
            <h3>Confirm &amp; Coordinate</h3>
            <p>We lock every booking, share a detailed run-sheet and stay in sync.</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">04</div>
          <div class="step-content">
            <h3>Team Travels</h3>
            <p>Dedicated on-ground coordinator handles everything — you just join in.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ BENEFITS SPLIT ============ -->
  <section class="section section--tint">
    <div class="container">
      <div class="split reverse">
        <div class="split-media">
          <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80"
               alt="Team building in nature" loading="lazy">
        </div>
        <div class="split-body">
          <span class="eyebrow">Why Companies Choose Us</span>
          <h2>Offsites That Actually Deliver ROI</h2>
          <p>We don't just book hotels — we design experiences that build trust, spark creativity and re-energize teams after months of grind.</p>
          <ul>
            <li>Pan-India presence — 25+ states &amp; union territories</li>
            <li>Companies from startups to Fortune 500</li>
            <li>Custom team-building modules designed by facilitators</li>
            <li>Dedicated corporate account manager</li>
            <li>24×7 helpline during travel</li>
            <li>Post-trip report with photos, feedback and highlights</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ CTA ============ -->
  <section class="cta">
    <div class="container">
      <div class="cta-inner">
        <span class="eyebrow eyebrow--light">Let's Plan Your Offsite</span>
        <h2>Ready To Take Your Team Off-Site?</h2>
        <p>Tell us your team size and travel window — anywhere in India — we'll send a proposal within 24 hours.</p>
        <div class="cta-actions">
          <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Request A Proposal</a>
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
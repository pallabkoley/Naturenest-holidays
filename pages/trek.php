<?php
/* =========================================================
   TREKS — Nature Nest Holidays
   Dynamic listing + detail view
   ========================================================= */

if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    die('config.php not found.');
}

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Database connection failed. Please check config.php.');
}

/* ---------- SMART IMAGE PATH RESOLVER ---------- */
if (!function_exists('trekImageUrl')) {
    function trekImageUrl($path, $fallback = '') {
        $path = trim((string)$path);
        if ($path === '') return $fallback;
        if (preg_match('#^https?://#', $path)) return $path;
        // New admin upload path (admin1/assets/upload/treks/...)
        if (strpos($path, 'admin1/') === 0) {
            return BASE_URL . $path;
        }
        // Legacy path (uploads/treks/...)
        if (strpos($path, 'uploads/') === 0) {
            return BASE_URL . $path;
        }
        // Just a filename — assume new admin upload folder
        return BASE_URL . 'admin1/assets/upload/treks/' . $path;
    }
}

/* ---------- ROUTING ---------- */
$trekSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$isDetail = ($trekSlug !== '');
$trek = null;
$treks = [];

$pageDescription = 'Explore our curated trekking adventures across the Himalayas.';

if ($isDetail) {
    $stmt = $pdo->prepare("SELECT * FROM treks WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $trekSlug]);
    $trek = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trek) {
        header('HTTP/1.0 404 Not Found');
        $pageTitle = 'Trek Not Found';
        $pageDescription = 'The trek you are looking for could not be found.';
    } else {
        $pageTitle = (!empty($trek['meta_title'])) ? $trek['meta_title'] : $trek['name'] . ' - Trek';
        if (!empty($trek['meta_description'])) {
            $pageDescription = $trek['meta_description'];
        }
    }
} else {
    $stmt = $pdo->query("SELECT * FROM treks ORDER BY display_order ASC, created_at DESC");
    $treks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pageTitle = 'Trekking Adventures';
}

$currentPage = 'treks';

/* ---------- HEADER ---------- */
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
.trek-grid{ display:grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
@media (max-width: 991px){ .trek-grid{ grid-template-columns: repeat(2, 1fr); gap: 22px; } }
@media (max-width: 640px){ .trek-grid{ grid-template-columns: 1fr; gap: 20px; } }

.trek-card{
  background:#fff; border:1px solid #ece6d4; border-radius: 18px; overflow:hidden;
  box-shadow: 0 4px 18px rgba(15,61,46,.05);
  transition: transform .35s cubic-bezier(.22,.61,.36,1), box-shadow .35s cubic-bezier(.22,.61,.36,1), border-color .35s;
  display:flex; flex-direction:column;
}
.trek-card:hover{ transform: translateY(-6px); box-shadow: 0 18px 42px rgba(15,61,46,.12); border-color: #d9cf9f; }
.trek-media{ position:relative; aspect-ratio: 16/10; overflow:hidden; background:#eef2ee; }
.trek-media img{ width:100%; height:100%; object-fit:cover; display:block; transition: transform .9s cubic-bezier(.22,.61,.36,1); }
.trek-card:hover .trek-media img{ transform: scale(1.09); }
.trek-badge{
  position:absolute; top:14px; left:14px; z-index:2;
  display:inline-flex; align-items:center; justify-content:center;
  padding: 0 12px; height: 30px;
  background: rgba(255,255,255,.95); color: var(--forest);
  font-size:.78rem; font-weight:700; letter-spacing:.05em;
  border-radius: 999px; backdrop-filter: blur(6px);
  box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.trek-body{ padding: 22px 22px 20px; display:flex; flex-direction:column; flex:1; }
.trek-name{ font-family: var(--font-display); font-size: 1.3rem; color: var(--forest); margin: 0 0 6px; line-height:1.2; }
.trek-meta{ display:flex; flex-wrap:wrap; gap:8px 16px; font-size:.82rem; color:#6b7a72; margin-bottom:14px; }
.trek-meta span{ display:inline-flex; align-items:center; gap:5px; }
.trek-desc{ font-size:.88rem; line-height:1.6; color:#555; margin:0 0 16px; flex:1; }
.trek-footer{ display:flex; align-items:center; justify-content:center; margin-top:auto; padding-top:14px; border-top:1px solid #f0ece0; }
.trek-btn{
  display:inline-flex; align-items:center; gap:6px;
  padding:9px 18px; background: var(--forest); color:#fff;
  font-size:.82rem; font-weight:600; border-radius: 999px;
  text-decoration:none; transition: background .2s, gap .2s; justify-content: center;
}
.trek-btn:hover{ background: var(--gold-dark); gap:9px; }
.trek-empty{ text-align:center; padding: 60px 20px; }
.trek-empty h2{ font-family:var(--font-display); color:var(--forest); font-size:1.4rem; margin:0 0 10px; }
.trek-empty p{ color:#6b7a72; margin:0 0 20px; }
.trek-cta{ background: var(--forest); border-radius: 20px; padding: 40px 32px; text-align:center; color:#fff; margin-top: 40px; }
.trek-cta h2{ font-family:var(--font-display); font-size:1.6rem; margin:0 0 10px; }
.trek-cta p{ opacity:.85; margin:0 0 22px; }
.trek-cta a{
  display:inline-flex; align-items:center; gap:8px;
  padding: 13px 28px; background: var(--gold-dark); color:#fff;
  border-radius: 999px; text-decoration:none; font-weight:600; font-size:.9rem;
  transition: background .2s, transform .2s;
}
.trek-cta a:hover{ background:#c49a2e; transform:translateY(-2px); }
</style>

<main>

  <!-- ================= LISTING VIEW ================= -->
  <section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=1920&q=80');">
    <div class="container page-hero-inner">
      <span class="eyebrow eyebrow--light">Himalayan Adventures</span>
      <h1>Trekking Packages</h1>
      <p>Curated treks across the Himalayas — from easy walks to challenging high-altitude expeditions.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep">/</span>
        <span>Treks</span>
      </nav>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <?php if (empty($treks)): ?>
        <div class="trek-empty">
          <h2>No Treks Available Right Now</h2>
          <p>We're currently updating our trekking packages. Please check back soon or contact us for custom trek planning.</p>
          <a href="<?= PAGES_URL ?>contact.php" class="trek-btn">Contact Us</a>
        </div>
      <?php else: ?>
        <div class="trek-grid">
          <?php foreach ($treks as $t): 
            $img = trekImageUrl(
              $t['featured_image'] ?? '',
              'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=900&q=80'
            );
            $difficultyColors = [
              'Easy' => '#27ae60',
              'Moderate' => '#f39c12',
              'Moderate to Difficult' => '#e67e22',
              'Difficult' => '#c0392b',
              'Very Difficult' => '#8e44ad',
            ];
            $diffColor = $difficultyColors[$t['difficulty_level']] ?? '#6b7a72';
          ?>
            <article class="trek-card reveal">
              <div class="trek-media">
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($t['name']) ?>" loading="lazy"
                     onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=900&q=80';">
                <?php if (!empty($t['featured']) && $t['featured']): ?>
                  <span class="trek-badge">★ Featured</span>
                <?php endif; ?>
              </div>
              <div class="trek-body">
                <h3 class="trek-name"><?= htmlspecialchars($t['name']) ?></h3>
                <div class="trek-meta">
                  <span><i class="fa-solid fa-clock" style="color:var(--gold-dark);"></i> <?= htmlspecialchars($t['duration'] ?: '—') ?></span>
                  <span><i class="fa-solid fa-mountain" style="color:<?= htmlspecialchars($diffColor) ?>;"></i> <?= htmlspecialchars($t['difficulty_level'] ?: '—') ?></span>
                  <?php if (!empty($t['max_altitude'])): ?>
                    <span><i class="fa-solid fa-arrow-up" style="color:var(--gold-dark);"></i> <?= htmlspecialchars($t['max_altitude']) ?>m</span>
                  <?php endif; ?>
                </div>
                <p class="trek-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($t['description']), 0, 120, '…')) ?></p>
                <div class="trek-footer">
                  <a href="trek-detail.php?slug=<?= urlencode($t['slug']) ?>" class="trek-btn" style="width: 100%;">
                    View Details <i class="fa-solid fa-arrow-right"></i>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="trek-cta">
        <h2>Can't Find Your Perfect Trek?</h2>
        <p>We design custom trekking itineraries tailored to your fitness level, dates, and bucket list.</p>
        <a href="<?= PAGES_URL ?>contact.php">
          <i class="fa-solid fa-compass"></i> Plan A Custom Trek
        </a>
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
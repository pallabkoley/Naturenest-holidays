<?php
/* =========================================================
   TOUR DETAIL — Nature Nest Holidays
   Handles BOTH main packages (tour_packages)
   AND sub-packages (tour_package_lists).
   ========================================================= */

require_once __DIR__ . '/../includes/config.php';

if (!defined('SITE_URL')) {
    $__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $__scheme . '://' . $__host);
}

/* ---- Get slug ---- */
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    header('Location: ' . PAGES_URL . 'tour-packages.php');
    exit;
}

$tour     = null;   /* the record we will display */
$isMain   = false;  /* true = tour_packages, false = tour_package_lists */
$parent   = null;   /* for sub-packages: the parent record */
$subs     = [];     /* for main packages: list of sub-packages */

/* ---- Try main packages first (tour_packages) ---- */
try {
    $stmt = $pdo->prepare("SELECT * FROM tour_packages WHERE slug = ? AND status='active' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $tour   = $row;
        $isMain = true;

        /* Fetch ONLY this package's children (parent_slug = current $slug) */
        $stmt = $pdo->prepare("SELECT id, title, slug, tour_overview,
                                      duration_days, duration_nights,
                                      difficulty_level, featured_image
                               FROM tour_package_lists
                               WHERE parent_slug = ? AND status = 'active'
                               ORDER BY position ASC, id ASC");
        $stmt->execute([$slug]);
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { error_log('tour-detail main: ' . $e->getMessage()); }

/* ---- If not main, try sub-packages (tour_package_lists) ---- */
if (!$tour) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tour_package_lists WHERE slug = ? AND status='active' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $tour   = $row;
            $isMain = false;

            if (!empty($row['parent_slug'])) {
                $stmt = $pdo->prepare("SELECT id, title, slug FROM tour_packages WHERE slug = ? AND status='active' LIMIT 1");
                $stmt->execute([$row['parent_slug']]);
                $parent = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
    } catch (Throwable $e) { error_log('tour-detail sub: ' . $e->getMessage()); }
}

/* ---- Image fallback map ---- */
$imgFallbacks = [
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
    'nagaland'              => '1580285973203-e3dfbfb5c0e2',
];
$defaultImg = '1464822759023-fed622ff2c3b';

/* ---- ১. ইমেজ URL রিজলভার ফাংশন (সব ধরনের পাথ হ্যান্ডেল করার জন্য) ---- */
if (!function_exists('resolve_image_url')) {
    function resolve_image_url($path, $slug, $fallbacks, $default, $folder) {
        if (empty($path)) {
            $fb = $fallbacks[$slug] ?? $default;
            return 'https://images.unsplash.com/photo-' . $fb . '?auto=format&fit=crop&w=1600&q=80';
        }
        
        $path = ltrim($path, '/');
        
        // 1. এক্সটার্নাল লিংক (http:// বা https://)
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }
        
        // 2. লোকাল ডাটাবেস পাথ (admin1/... বা uploads/...)
        if (strpos($path, 'admin1/') === 0 || strpos($path, 'uploads/') === 0) {
            return BASE_URL . $path;
        }
        
        // 3. শুধু ফাইলনেম (e.g., image.jpg)
        return PAGES_URL . 'assets/uploads/' . $folder . '/' . $path;
    }
}

/* ---- Page meta ---- */
if ($tour) {
    $pageTitle       = $tour['title'] ?? 'Tour Detail';
    $pageDescription = isset($tour['description']) ? strip_tags($tour['description'])
                     : (isset($tour['tour_overview']) ? strip_tags($tour['tour_overview']) : '');
} else {
    $pageTitle       = 'Tour Not Found';
    $pageDescription = 'The requested tour could not be found.';
}
$currentPage = 'tour-packages';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ============================================================
   TOUR DETAIL PAGE
   ============================================================ */
.td-section { background: var(--cream); padding: 60px 0 90px; }

.td-crumb{
  padding: 14px 0;
  background: #FAF7ED;
  border-bottom: 1px solid #efe5c7;
  font-size: .86rem;
  color: #6b7a72;
}
.td-crumb ol{ display:flex; flex-wrap:wrap; gap:6px; list-style:none; margin:0; padding:0; }
.td-crumb li{display:inline-flex; align-items:center; gap:6px;}
.td-crumb li:not(:last-child)::after{content:'/'; color:#c9c2a8;}
.td-crumb a{color:#294536; text-decoration:none; transition:color .18s;}
.td-crumb a:hover{color:#C9A04A;}
.td-crumb li.active{color:#8a8a7a;}

.td-layout{
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 44px;
  align-items: start;
}
@media (max-width: 991px){
  .td-layout{ grid-template-columns: 1fr; gap: 32px; }
}

.td-main{
  background: #fff;
  border: 1px solid #ece6d4;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 18px rgba(15,61,46,.05);
}

.td-hero{
  position: relative;
  aspect-ratio: 16/8;
  overflow: hidden;
  background: #eef2ee;
}
.td-hero img{
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.td-hero::after{
  content:'';
  position:absolute;
  inset:0;
  background: linear-gradient(to top,
              rgba(10,44,33,.65) 0%,
              rgba(10,44,33,.15) 55%,
              transparent 100%);
  pointer-events:none;
}
.td-hero-tag{
  position: absolute;
  top: 18px; left: 18px;
  z-index: 2;
  padding: 6px 14px;
  background: rgba(255,255,255,.95);
  color: var(--forest);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  border-radius: 999px;
  backdrop-filter: blur(6px);
  box-shadow: 0 3px 10px rgba(0,0,0,.12);
}

.td-header{
  padding: 32px 36px 22px;
  border-bottom: 1px dashed #efe5c7;
}
.td-header h1{
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 3.4vw, 2.2rem);
  color: var(--forest);
  margin: 0 0 12px;
  line-height: 1.15;
}
.td-header p.lead{
  font-size: 1rem;
  color: #6b7a72;
  margin: 0;
  line-height: 1.65;
}

.td-meta{
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: #efe5c7;
  border-bottom: 1px dashed #efe5c7;
}
@media (max-width: 700px){
  .td-meta{ grid-template-columns: repeat(2, 1fr); }
}
.td-meta-item{
  padding: 20px 16px;
  text-align: center;
  background: #fbf8ee;
}
.td-meta-item i{
  display: block;
  font-size: 1.2rem;
  color: #C9A04A;
  margin-bottom: 8px;
}
.td-meta-item .k{
  display: block;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: #8a8a7a;
  margin-bottom: 5px;
}
.td-meta-item .v{
  font-size: .95rem;
  color: #294536;
  font-weight: 600;
}

.td-body{ padding: 32px 36px 36px; }

.td-block{ margin-bottom: 34px; }
.td-block:last-child{ margin-bottom: 0; }

.td-block h2{
  font-family: var(--font-display);
  font-size: 1.3rem;
  color: var(--forest);
  margin: 0 0 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.td-block h2::before{
  content:'';
  width: 6px; height: 24px;
  background: #C9A04A;
  border-radius: 3px;
}
.td-block p{
  font-size: .95rem;
  line-height: 1.75;
  color: #3b4a41;
  margin: 0 0 12px;
}
.td-block ul{
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.td-block ul li{
  position: relative;
  padding-left: 26px;
  font-size: .93rem;
  line-height: 1.6;
  color: #3b4a41;
}
.td-block ul li::before{
  content: '\2713';
  position: absolute;
  left: 0; top: 0;
  color: #C9A04A;
  font-weight: 700;
}

/* Itinerary accordion */
.td-itinerary{ display:flex; flex-direction:column; gap:10px; }
.td-it-item{
  border: 1px solid #ece6d4;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}
.td-it-head{
  padding: 14px 18px;
  background: #faf7ed;
  font-weight: 600;
  color: var(--forest);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
}
.td-it-day{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  height: 30px;
  padding: 0 10px;
  background: var(--forest);
  color: #C9A04A;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 700;
}
.td-it-body{
  padding: 0 18px;
  max-height: 0;
  overflow: hidden;
  transition: max-height .35s ease, padding .35s ease;
}
.td-it-item.is-open .td-it-body{
  padding: 14px 18px 18px;
  max-height: 900px;
}
.td-it-body p{
  font-size: .92rem;
  line-height: 1.7;
  color: #3b4a41;
  margin: 0;
  white-space: pre-line;
}

/* Sub-packages grid */
.td-subs{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
@media (max-width: 700px){ .td-subs{ grid-template-columns: 1fr; } }

.td-sub-card{
  display: block;
  text-decoration: none;
  background: #fff;
  border: 1px solid #ece6d4;
  border-radius: 14px;
  overflow: hidden;
  transition: transform .25s, box-shadow .25s, border-color .25s;
}
.td-sub-card:hover{
  transform: translateY(-3px);
  border-color: #d9cf9f;
  box-shadow: 0 10px 22px rgba(15,61,46,.08);
}
.td-sub-media{
  aspect-ratio: 16/10;
  overflow: hidden;
  background: #eef2ee;
}
.td-sub-media img{
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .6s;
}
.td-sub-card:hover .td-sub-media img{ transform: scale(1.07); }
.td-sub-body{
  padding: 14px 16px 16px;
}
.td-sub-body h4{
  font-family: var(--font-display);
  font-size: 1.02rem;
  color: var(--forest);
  margin: 0 0 4px;
}
.td-sub-body span{
  font-size: .78rem;
  color: #8a8a7a;
}

/* Sidebar */
.td-side{
  position: sticky;
  top: 100px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
@media (max-width: 991px){ .td-side{ position: static; } }

.td-side-card{
  background: #fff;
  border: 1px solid #ece6d4;
  border-radius: 18px;
  padding: 24px 22px;
  box-shadow: 0 4px 18px rgba(15,61,46,.05);
}
.td-side-card h3{
  font-family: var(--font-display);
  font-size: 1.15rem;
  color: var(--forest);
  margin: 0 0 6px;
}
.td-side-card p{
  font-size: .87rem;
  color: #6b7a72;
  line-height: 1.55;
  margin: 0 0 18px;
}
.td-side-btn{
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 14px 20px;
  border-radius: 999px;
  font-size: .94rem;
  font-weight: 600;
  text-decoration: none;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all .22s cubic-bezier(.22,.61,.36,1);
  margin-bottom: 10px;
  font-family: inherit;
}
.td-side-btn:last-child{ margin-bottom: 0; }
.td-side-btn-primary{
  background: #D4A13A;
  color: #173A2D;
  box-shadow: 0 6px 18px rgba(80,60,20,.15);
}
.td-side-btn-primary:hover{
  background: #C08E30;
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(80,60,20,.22);
}
.td-side-btn-ghost{
  background: #fff;
  border-color: #d8d0b4;
  color: #294536;
}
.td-side-btn-ghost:hover{
  border-color: var(--forest);
  color: var(--forest);
  transform: translateY(-1px);
}

.td-side-help{
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.td-side-help a{
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: .88rem;
  color: #294536;
  text-decoration: none;
  padding: 8px 0;
  border-bottom: 1px dashed #efe5c7;
  transition: color .18s;
}
.td-side-help a:last-child{ border-bottom: 0; }
.td-side-help a:hover{ color: #C9A04A; }
.td-side-help a i{
  width: 26px; height: 26px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: #faf7ed;
  color: #C9A04A;
  font-size: .75rem;
  flex-shrink: 0;
}

.td-notfound{
  text-align: center;
  padding: 60px 20px;
}
.td-notfound i{
  font-size: 3rem;
  color: #c9c2a8;
  margin-bottom: 18px;
  display: block;
}
.td-notfound h1{
  font-family: var(--font-display);
  font-size: 1.8rem;
  color: var(--forest);
  margin: 0 0 10px;
}
.td-notfound p{
  color: #6b7a72;
  margin: 0 0 24px;
}
</style>

<!-- Breadcrumb -->
<section class="td-crumb">
  <div class="container">
    <ol>
      <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
      <li><a href="<?= PAGES_URL ?>tour-packages.php">Tour Packages</a></li>
      <?php if ($tour && !$isMain && $parent): ?>
        <li><a href="<?= PAGES_URL ?>tour-detail.php?slug=<?= urlencode($parent['slug']) ?>"><?= e($parent['title']) ?></a></li>
      <?php endif; ?>
      <li class="active"><?= $tour ? e($tour['title']) : 'Not Found' ?></li>
    </ol>
  </div>
</section>

<section class="td-section">
  <div class="container">

    <?php if (!$tour): ?>

      <div class="td-main td-notfound">
        <i class="fas fa-map-marked-alt"></i>
        <h1>Tour Not Found</h1>
        <p>We couldn't find the tour you were looking for.</p>
        <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-primary">Back to All Packages</a>
      </div>

    <?php else: ?>

      <?php
        /* Normalize fields */
        $title       = $tour['title'] ?? '';
        
        // ২. এখানে ইমেজ URL রিজলভার ব্যবহার করা হয়েছে
        $imgUrl      = resolve_image_url($tour['featured_image'] ?? '', $slug, $imgFallbacks, $defaultImg, 'tour-packages');
        
        $description = $isMain ? ($tour['description'] ?? '') : ($tour['tour_overview'] ?? '');
        $overview    = $isMain ? ($tour['description'] ?? '') : ($tour['tour_overview'] ?? '');
        $days        = (int)($tour['duration_days']   ?? 0);
        $nights      = (int)($tour['duration_nights'] ?? 0);
        $difficulty  = $tour['difficulty_level'] ?? '';
        $bestSeason  = $tour['best_season'] ?? ($tour['season'] ?? '');
        $groupSize   = $tour['group_size'] ?? '';
        $destination = $tour['destination'] ?? '';
        $itinerary   = $tour['tour_itinerary'] ?? ($tour['itinerary'] ?? '');
        $inclusions  = $tour['inclusions'] ?? '';
        $exclusions  = $tour['exclusions'] ?? '';
      ?>

      <div class="td-layout">

        <!-- ==================== MAIN ==================== -->
        <div class="td-main">

          <div class="td-hero">
            <img src="<?= htmlspecialchars($imgUrl) ?>"
                 alt="<?= e($title) ?>"
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-<?= $defaultImg ?>?auto=format&fit=crop&w=1600&q=80';">
            <?php if ($isMain && !empty($subs)): ?>
              <span class="td-hero-tag"><?= count($subs) ?> Tour-packages</span>
            <?php elseif ($destination): ?>
              <span class="td-hero-tag"><?= e($destination) ?></span>
            <?php endif; ?>
          </div>

          <div class="td-header">
            <h1><?= e($title) ?></h1>
            <?php if ($description): ?>
              <p class="lead"><?= e($description) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($days > 0 || $nights > 0 || $difficulty || $bestSeason || $groupSize): ?>
            <div class="td-meta">
              <?php if ($days > 0): ?>
                <div class="td-meta-item">
                  <i class="fa-regular fa-clock"></i>
                  <span class="k">Duration</span>
                  <span class="v"><?= $days ?>D<?= $nights > 0 ? ' / ' . $nights . 'N' : '' ?></span>
                </div>
              <?php endif; ?>
              <?php if ($difficulty): ?>
                <div class="td-meta-item">
                  <i class="fa-solid fa-mountain"></i>
                  <span class="k">Difficulty</span>
                  <span class="v"><?= ucfirst(e($difficulty)) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($bestSeason): ?>
                <div class="td-meta-item">
                  <i class="fa-regular fa-sun"></i>
                  <span class="k">Best Season</span>
                  <span class="v"><?= e($bestSeason) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($groupSize): ?>
                <div class="td-meta-item">
                  <i class="fa-solid fa-users"></i>
                  <span class="k">Group Size</span>
                  <span class="v"><?= e($groupSize) ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="td-body">

            <?php if ($overview): ?>
              <div class="td-block">
                <h2>Overview</h2>
                <p><?= nl2br(e($overview)) ?></p>
              </div>
            <?php endif; ?>

            <!-- Sub-packages — ONLY current parent's children -->
            <?php if ($isMain && !empty($subs)): ?>
              <div class="td-block">
                <h2>Sub-Packages</h2>
                <div class="td-subs">
                  <?php foreach ($subs as $sub):
                    $subSlug  = $sub['slug'] ?? '';
                    $subTitle = $sub['title'] ?? '';
                    $subDays  = (int)($sub['duration_days'] ?? 0);
                    
                    // ৩. সাব-প্যাকেজের জন্যও ইমেজ URL রিজলভার ব্যবহার করা হয়েছে
                    $subImg = resolve_image_url($sub['featured_image'] ?? '', $subSlug, $imgFallbacks, $defaultImg, 'tour-packages');
                  ?>
                    <a href="<?= PAGES_URL ?>tour-detail.php?slug=<?= urlencode($subSlug) ?>" class="td-sub-card">
                      <div class="td-sub-media">
                        <img src="<?= htmlspecialchars($subImg) ?>" alt="<?= e($subTitle) ?>" loading="lazy"
                             onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-<?= $defaultImg ?>?auto=format&fit=crop&w=600&q=80';">
                      </div>
                      <div class="td-sub-body">
                        <h4><?= e($subTitle) ?></h4>
                        <span><?= $subDays > 0 ? $subDays . ' days' : 'Explore' ?></span>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Itinerary -->
            <?php if (!empty($itinerary)):
              $itArr = json_decode($itinerary, true);
              if (is_array($itArr) && !empty($itArr)):
            ?>
              <div class="td-block">
                <h2>Itinerary</h2>
                <div class="td-itinerary">
                  <?php foreach ($itArr as $i => $day): ?>
                    <div class="td-it-item<?= $i === 0 ? ' is-open' : '' ?>">
                      <div class="td-it-head">
                        <span class="td-it-day">Day <?= e($day['day'] ?? ($i + 1)) ?></span>
                        <span><?= e($day['title'] ?? '') ?></span>
                      </div>
                      <div class="td-it-body">
                        <p><?= e(strip_tags($day['content'] ?? '')) ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php elseif (is_string($itinerary) && strlen(trim($itinerary)) > 20): ?>
              <div class="td-block">
                <h2>Itinerary</h2>
                <p><?= nl2br(e(strip_tags($itinerary))) ?></p>
              </div>
            <?php endif; endif; ?>

            <!-- Inclusions -->
            <?php if (!empty($inclusions)): ?>
              <div class="td-block">
                <h2>Inclusions</h2>
                <?php
                  $inc = strip_tags($inclusions, '<br><li>');
                  $incItems = preg_split('/<br\s*\/?>|<\/li>|\n/i', $inc, -1, PREG_SPLIT_NO_EMPTY);
                ?>
                <ul>
                  <?php foreach ($incItems as $it): $it = trim($it); if ($it === '') continue; ?>
                    <li><?= e($it) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Exclusions -->
            <?php if (!empty($exclusions)): ?>
              <div class="td-block">
                <h2>Exclusions</h2>
                <?php
                  $exc = strip_tags($exclusions, '<br><li>');
                  $excItems = preg_split('/<br\s*\/?>|<\/li>|\n/i', $exc, -1, PREG_SPLIT_NO_EMPTY);
                ?>
                <ul>
                  <?php foreach ($excItems as $it): $it = trim($it); if ($it === '') continue; ?>
                    <li><?= e($it) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

          </div>
        </div>

        <!-- ==================== SIDEBAR ==================== -->
        <aside class="td-side">

          <div class="td-side-card">
            <h3>Book This Tour</h3>
            <p>Share your travel dates and we'll confirm availability within 24 hours.</p>

            <a href="<?= PAGES_URL ?>booking.php?package=<?= urlencode($slug) ?>" class="td-side-btn td-side-btn-primary">
              <i class="fas fa-paper-plane"></i> Enquire &amp; Book
            </a>
            <a href="<?= PAGES_URL ?>contact.php?tour=<?= urlencode($title) ?>" class="td-side-btn td-side-btn-ghost">
              <i class="fas fa-comments"></i> Talk to an Expert
            </a>
          </div>

          <div class="td-side-card">
            <h3>Need Help?</h3>
            <div class="td-side-help">
              <a href="tel:+919832468096">
                <i class="fas fa-phone"></i> +91 98324 68096
              </a>
              <a href="tel:+918420958514">
                <i class="fas fa-phone"></i> +91 84209 58514
              </a>
              <a href="mailto:<?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>">
                <i class="fas fa-envelope"></i> <?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>
              </a>
              <a href="<?= PAGES_URL ?>tour-packages.php">
                <i class="fas fa-arrow-left"></i> Back to All Packages
              </a>
            </div>
          </div>

        </aside>

      </div>

    <?php endif; ?>

  </div>
</section>

<script>
/* Accordion toggle for itinerary */
document.querySelectorAll('.td-it-item .td-it-head').forEach(function (head) {
  head.addEventListener('click', function () {
    head.parentElement.classList.toggle('is-open');
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
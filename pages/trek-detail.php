<?php
/* =========================================================
   TREK DETAILS — Nature Nest Holidays (Clean Version)
   Path: /pages/trek-detail.php
   ========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!defined('SITE_NAME')) define('SITE_NAME', $SITE['site_name'] ?? 'Nature Nest Holidays');
if (!defined('SITE_URL'))  define('SITE_URL', BASE_URL);

/* =========================================================
   AGGRESSIVE TEXT CLEANER
   Removes: � (U+FFFD), all emojis, unicode symbols, BOM,
   zero-width chars, control characters, and any other garbage.
   Keeps: ASCII Latin, Bengali, common punctuation, ₹
   ========================================================= */
if (!function_exists('cleanText')) {
    function cleanText($text) {
        if ($text === null || $text === '') return '';
        $text = (string)$text;

        // 1. Decode HTML entities first
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Remove Unicode Replacement Character in all forms
        $text = str_replace(
            ["\xEF\xBF\xBD", "\xEF\xBF\xBE", "\xEF\xBF\xBF", '�', '&#65533;', '&#xFFFD;'],
            ' ',
            $text
        );

        // 3. Remove BOM, ZWSP, ZWNJ, ZWJ, LRM, RLM, LRE, RLE, PDF, LRO, RLO
        $text = preg_replace(
            '/[\x{FEFF}\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{202A}\x{202B}\x{202C}\x{202D}\x{202E}]/u',
            '',
            $text
        );

        // 4. Remove ALL emoji / symbol ranges (diamonds, arrows, checkmarks, etc.)
        //    Ranges covered:
        //    - 2000-206F General Punctuation extras
        //    - 2190-21FF Arrows
        //    - 2200-22FF Math Operators
        //    - 2300-23FF Misc Technical
        //    - 2460-24FF Enclosed Alphanumerics
        //    - 25A0-25FF Geometric Shapes (◆ ■ ▲)
        //    - 2600-27BF Misc Symbols + Dingbats
        //    - 2B00-2BFF Misc Symbols and Arrows
        //    - 1F000-1FAFF Emojis
        //    - FE00-FE0F Variation Selectors
        $text = preg_replace(
            '/[\x{2000}-\x{206F}\x{2190}-\x{21FF}\x{2200}-\x{22FF}\x{2300}-\x{23FF}\x{2460}-\x{24FF}\x{25A0}-\x{25FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}]+/u',
            ' ',
            $text
        );

        // 5. Remove any remaining control characters except \n, \r, \t
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // 6. Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\s*\n\s*/', "\n", $text);
        $text = trim($text);

        return $text;
    }
}

/* =========================================================
   SMART IMAGE PATH RESOLVER
   ========================================================= */
if (!function_exists('trekImageUrl')) {
    function trekImageUrl($path, $fallback = '') {
        $path = trim((string)$path);
        if ($path === '') return $fallback;
        if (preg_match('#^https?://#', $path)) return $path;
        if (strpos($path, 'admin1/') === 0) return BASE_URL . $path;
        if (strpos($path, 'uploads/') === 0) return BASE_URL . $path;
        return BASE_URL . 'admin1/assets/upload/treks/' . $path;
    }
}

/* =========================================================
   LIST PARSER — handles JSON, text, HTML, comma-separated
   ========================================================= */
if (!function_exists('parseList')) {
    function parseList($data) {
        if (empty($data)) return [];
        $data = trim((string)$data);

        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(function($v) {
                return cleanText(is_string($v) ? strip_tags($v) : '');
            }, $decoded), function($v) { return $v !== ''; }));
        }

        $clean = preg_replace('/<\/?(p|br|div|li|ul|ol)[^>]*>/i', "\n", $data);
        $clean = strip_tags($clean);
        $clean = cleanText($clean);

        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $clean)));
        if (count($lines) <= 1 && strpos($clean, ',') !== false) {
            $lines = array_filter(array_map('trim', explode(',', $clean)));
        }

        $out = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^[\-\*\•\·\–\—\d+\.]+\s*/u', '', trim($line));
            $line = cleanText($line);
            if ($line !== '') $out[] = $line;
        }
        return $out;
    }
}

/* =========================================================
   FAQ PARSER
   ========================================================= */
if (!function_exists('parseFaq')) {
    function parseFaq($data) {
        if (empty($data)) return [];
        $data = trim((string)$data);

        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $item) {
                if (is_array($item) && !empty($item['question'])) {
                    $out[] = [
                        'question' => cleanText(trim($item['question'])),
                        'answer'   => cleanText(trim($item['answer'] ?? ''))
                    ];
                }
            }
            return $out;
        }

        $clean = preg_replace('/<\/?(p|br|div)[^>]*>/i', "\n", $data);
        $clean = cleanText(strip_tags($clean));
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $clean)));

        $faqs = [];
        $currentQ = null;
        foreach ($lines as $line) {
            if (preg_match('/^(?:Q|Question)\s*[\:\.\-]?\s*(.+)$/i', $line, $m)) {
                if ($currentQ !== null) $faqs[] = $currentQ;
                $currentQ = ['question' => cleanText(trim($m[1])), 'answer' => ''];
            } elseif (preg_match('/^(?:A|Ans|Answer)\s*[\:\.\-]?\s*(.+)$/i', $line, $m) && $currentQ !== null) {
                $currentQ['answer'] = cleanText(trim($m[1]));
            } elseif (strpos($line, '?') !== false && $currentQ === null) {
                $currentQ = ['question' => cleanText(trim($line)), 'answer' => ''];
            } elseif ($currentQ !== null) {
                $currentQ['answer'] = cleanText($currentQ['answer'] . ' ' . trim($line));
            }
        }
        if ($currentQ !== null) $faqs[] = $currentQ;
        return $faqs;
    }
}

/* =========================================================
   ITINERARY PARSER
   ========================================================= */
if (!function_exists('parseItinerary')) {
    function parseItinerary($data) {
        if (empty($data)) return [];
        $data = trim((string)$data);

        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $i => $item) {
                if (is_array($item)) {
                    $out[] = [
                        'day'     => $item['day'] ?? ($i + 1),
                        'title'   => cleanText(trim($item['title'] ?? '')),
                        'content' => cleanText(trim($item['content'] ?? ''))
                    ];
                }
            }
            if (!empty($out)) return $out;
        }

        $clean = preg_replace('/<\/?(p|br|div)[^>]*>/i', "\n", $data);
        $clean = strip_tags($clean);
        $clean = cleanText($clean);

        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $clean)));

        $itinerary = [];
        $currentDay = null;
        foreach ($lines as $line) {
            $line = cleanText($line);
            if ($line === '') continue;

            if (preg_match('/^Day\s+(\d+)\s*[\:\-\–\—]?\s*(.*)$/i', $line, $m)) {
                if ($currentDay !== null) $itinerary[] = $currentDay;
                $rest = cleanText(trim($m[2]));
                $title = '';
                $content = $rest;
                if (preg_match('/^(.+?)\s*[\–\—\-]\s*(.+)$/u', $rest, $tm)) {
                    $title = cleanText(trim($tm[1]));
                    $content = cleanText(trim($tm[2]));
                }
                $currentDay = [
                    'day'     => (int)$m[1],
                    'title'   => $title,
                    'content' => $content
                ];
            } elseif ($currentDay !== null) {
                $currentDay['content'] = cleanText($currentDay['content'] . ' ' . $line);
            }
        }
        if ($currentDay !== null) $itinerary[] = $currentDay;
        return $itinerary;
    }
}

/* =========================================================
   ROUTING
   ========================================================= */
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$slug && !$id) { header('Location: trek.php'); exit; }

$conn = $db->getConnection();

// Force utf8mb4 for the connection
if (method_exists($conn, 'set_charset')) {
    @$conn->set_charset('utf8mb4');
}

if ($slug) {
    $stmt = $conn->prepare("SELECT * FROM treks WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
} else {
    $stmt = $conn->prepare("SELECT * FROM treks WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$trek = $stmt->get_result()->fetch_assoc();

if (!$trek) {
    die('<div style="text-align:center;padding:80px 20px;font-family:sans-serif">
        <h2 style="color:#c66b05">Trek Not Found</h2>
        <p>The trek you are looking for does not exist.</p>
        <a href="trek.php" style="color:#2c5f2d;font-weight:bold">← Back to Treks</a>
    </div>');
}

/* ---------- Parse all fields with cleaning ---------- */
$gallery = json_decode($trek['gallery_images'] ?? '', true);
if (!is_array($gallery)) $gallery = [];

$highlights = parseList($trek['highlights'] ?? '');
$inclusions = parseList($trek['inclusions'] ?? '');
$exclusions = parseList($trek['exclusions'] ?? '');
$faq        = parseFaq($trek['faq'] ?? '');
$itinerary  = parseItinerary($trek['itinerary'] ?? '');

/* ---------- Similar Treks ---------- */
$similar = [];
$q = $conn->prepare("SELECT id, name, slug, difficulty_level, duration, featured_image FROM treks WHERE id != ? AND status = 1 ORDER BY RAND() LIMIT 3");
$q->bind_param("i", $trek['id']);
$q->execute();
$similar = $q->get_result()->fetch_all(MYSQLI_ASSOC);

/* ---------- Page Meta ---------- */
$pageTitle       = cleanText($trek['name']) . ' | ' . SITE_NAME;
$pageDescription = !empty($trek['meta_description']) ? cleanText($trek['meta_description']) : substr(cleanText(strip_tags($trek['description'] ?? '')), 0, 160);
$currentPage     = 'treks';

$heroImg = trekImageUrl(
    $trek['featured_image'] ?? '',
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=80'
);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.td-hero{ position:relative; min-height:500px; background-size:cover; background-position:center; display:flex; align-items:flex-end; }
.td-hero::after{ content:''; position:absolute; inset:0; background:linear-gradient(to bottom, rgba(10,44,33,.35) 0%, rgba(10,44,33,.75) 70%, rgba(10,44,33,.92) 100%); }
.td-hero-inner{ position:relative; z-index:2; width:100%; padding:60px 0 40px; }
.td-hero .eyebrow{ color:#d4af37; font-size:.8rem; letter-spacing:.2em; text-transform:uppercase; font-weight:700; margin-bottom:12px; display:inline-block; }
.td-hero h1{ color:#fff; font-family:var(--font-display); font-size:clamp(2rem, 5vw, 3.4rem); line-height:1.15; margin:0 0 18px; font-weight:700; max-width:900px; text-shadow:0 2px 20px rgba(0,0,0,.4); }
.td-hero .td-pills{ display:flex; flex-wrap:wrap; gap:10px; margin-top:8px; }
.td-pill{ display:inline-flex; align-items:center; gap:7px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.25); color:#fff; padding:8px 16px; border-radius:999px; font-size:.82rem; font-weight:500; }
.td-pill i{ color:#d4af37; }

.td-wrap{ padding:50px 0 70px; background:#fbfaf5; }
.td-grid{ display:grid; grid-template-columns:1fr 360px; gap:40px; align-items:start; }
@media (max-width:960px){ .td-grid{ grid-template-columns:1fr; gap:30px; } }

.td-card{ background:#fff; border-radius:18px; padding:30px 32px; margin-bottom:26px; border:1px solid #ece6d4; box-shadow:0 4px 24px rgba(15,61,46,.04); }
.td-card h2{ font-family:var(--font-display); color:var(--forest, #1e4634); font-size:1.5rem; margin:0 0 18px; display:flex; align-items:center; gap:12px; }
.td-card h2 i{ color:#d4af37; font-size:1.15rem; }
.td-card p{ color:#555; line-height:1.8; margin:0 0 12px; font-size:.95rem; }
.td-card p:last-child{ margin-bottom:0; }
.td-desc{ color:#555; line-height:1.85; font-size:.95rem; }
.td-desc p{ margin:0 0 12px; }
.td-desc p:last-child{ margin-bottom:0; }

.td-highlights{ display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px; }
.td-highlight{ display:flex; align-items:flex-start; gap:10px; padding:14px 16px; background:#f8f6ee; border-radius:12px; border-left:3px solid #d4af37; font-size:.88rem; color:#294536; line-height:1.5; font-weight:500; }
.td-highlight i{ color:#d4af37; margin-top:3px; flex-shrink:0; }

.td-timeline{ position:relative; padding-left:8px; }
.td-itin{ display:flex; gap:20px; padding-bottom:26px; position:relative; }
.td-itin:not(:last-child)::before{ content:''; position:absolute; left:23px; top:52px; bottom:0; width:2px; background:linear-gradient(to bottom, #d4af37, #ece6d4); }
.td-itin-num{ flex-shrink:0; width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, #1e4634, #2c5f2d); color:#d4af37; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.72rem; font-family:var(--font-display); box-shadow:0 4px 12px rgba(30,70,52,.25); position:relative; z-index:2; text-align:center; line-height:1.1; }
.td-itin-body{ background:#f8f6ee; border-radius:14px; padding:16px 20px; flex:1; }
.td-itin-body h4{ margin:0 0 6px; color:#1e4634; font-size:.98rem; font-weight:600; }
.td-itin-body p{ margin:0; font-size:.9rem; color:#555; line-height:1.7; }

.td-inc-exc{ display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media (max-width:640px){ .td-inc-exc{ grid-template-columns:1fr; } }
.td-inc-exc > div{ background:#f8f6ee; border-radius:14px; padding:20px 22px; }
.td-inc-exc h3{ margin:0 0 14px; font-size:1rem; font-family:var(--font-display); display:flex; align-items:center; gap:10px; color:#1e4634; }
.td-inc-exc ul{ list-style:none; padding:0; margin:0; }
.td-inc-exc li{ display:flex; gap:10px; font-size:.88rem; color:#555; padding:6px 0; line-height:1.55; }
.td-inc-exc li::before{ content:'\f00c'; font-family:'Font Awesome 6 Free'; font-weight:900; color:#27ae60; flex-shrink:0; margin-top:2px; }
.td-excl li::before{ content:'\f00d'; color:#c0392b; }

.td-gallery{ display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px; }
.td-gallery img{ width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:12px; cursor:pointer; transition:transform .3s, box-shadow .3s; }
.td-gallery img:hover{ transform:translateY(-4px); box-shadow:0 12px 30px rgba(15,61,46,.15); }

.td-faq-item{ border-bottom:1px solid #ece6d4; padding:16px 0; }
.td-faq-item:last-child{ border-bottom:none; }
.td-faq-q{ font-weight:600; color:#1e4634; font-size:.95rem; margin:0 0 8px; display:flex; gap:10px; }
.td-faq-q i{ color:#d4af37; margin-top:3px; flex-shrink:0; }
.td-faq-a{ margin:0; font-size:.9rem; color:#555; line-height:1.7; padding-left:26px; }

.td-sidebar{ position:sticky; top:90px; display:flex; flex-direction:column; gap:20px; }
.td-side-card{ background:#fff; border-radius:18px; padding:24px 26px; border:1px solid #ece6d4; box-shadow:0 4px 24px rgba(15,61,46,.05); }
.td-side-card h3{ font-family:var(--font-display); color:#1e4634; font-size:1.1rem; margin:0 0 16px; padding-bottom:12px; border-bottom:2px solid #d4af37; display:inline-block; }
.td-info-list{ list-style:none; padding:0; margin:0; }
.td-info-list li{ display:flex; justify-content:space-between; align-items:flex-start; padding:10px 0; border-bottom:1px dashed #f0ece0; font-size:.88rem; gap:14px; }
.td-info-list li:last-child{ border-bottom:none; }
.td-info-list li span:first-child{ color:#8a8a7a; display:flex; align-items:center; gap:8px; flex-shrink:0; }
.td-info-list li span:first-child i{ color:#d4af37; width:14px; }
.td-info-list li span:last-child{ color:#294536; font-weight:600; text-align:right; }

.td-btn-primary{ display:flex; align-items:center; justify-content:center; gap:9px; width:100%; padding:14px 20px; background:linear-gradient(135deg, #1e4634, #2c5f2d); color:#fff; border-radius:999px; text-decoration:none; font-weight:600; font-size:.92rem; transition:all .25s; border:none; cursor:pointer; }
.td-btn-primary:hover{ transform:translateY(-2px); box-shadow:0 12px 28px rgba(30,70,52,.3); color:#fff; }
.td-btn-secondary{ display:flex; align-items:center; justify-content:center; gap:9px; width:100%; padding:13px 20px; background:#fff; color:#1e4634; border:2px solid #1e4634; border-radius:999px; text-decoration:none; font-weight:600; font-size:.9rem; transition:all .25s; margin-top:10px; }
.td-btn-secondary:hover{ background:#1e4634; color:#fff; }

.td-similar{ display:flex; gap:12px; padding:12px; border-radius:12px; background:#f8f6ee; margin-bottom:12px; text-decoration:none; color:inherit; transition:all .25s; }
.td-similar:hover{ background:#f3efdd; transform:translateX(4px); }
.td-similar img{ width:70px; height:70px; border-radius:10px; object-fit:cover; flex-shrink:0; }
.td-similar-info{ flex:1; min-width:0; }
.td-similar-info h4{ margin:0 0 6px; font-size:.9rem; color:#1e4634; font-weight:600; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.td-similar-meta{ font-size:.75rem; color:#8a8a7a; display:flex; flex-direction:column; gap:3px; }
.td-similar-meta i{ color:#d4af37; margin-right:4px; }

.td-breadcrumb{ padding:16px 0; font-size:.82rem; color:#8a8a7a; background:#fbfaf5; }
.td-breadcrumb a{ color:#1e4634; text-decoration:none; font-weight:500; }
.td-breadcrumb a:hover{ color:#d4af37; }
.td-breadcrumb span{ margin:0 8px; opacity:.5; }
</style>

<main>
  <!-- Hero -->
  <section class="td-hero" style="background-image:url('<?= htmlspecialchars($heroImg) ?>')">
    <div class="td-hero-inner">
      <div class="container">
        <span class="eyebrow">Trekking Adventure</span>
        <h1><?= htmlspecialchars(cleanText($trek['name'])) ?></h1>
        <div class="td-pills">
          <?php if (!empty($trek['duration'])): ?>
            <span class="td-pill"><i class="fas fa-clock"></i> <?= htmlspecialchars(cleanText($trek['duration'])) ?></span>
          <?php endif; ?>
          <?php if (!empty($trek['difficulty_level'])): ?>
            <span class="td-pill"><i class="fas fa-mountain"></i> <?= htmlspecialchars(cleanText($trek['difficulty_level'])) ?></span>
          <?php endif; ?>
          <?php if (!empty($trek['max_altitude'])): ?>
            <span class="td-pill"><i class="fas fa-arrow-up"></i> <?= htmlspecialchars(cleanText($trek['max_altitude'])) ?></span>
          <?php endif; ?>
          <?php if (!empty($trek['starting_point'])): ?>
            <span class="td-pill"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(cleanText($trek['starting_point'])) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Breadcrumb -->
  <div class="td-breadcrumb">
    <div class="container">
      <a href="<?= BASE_URL ?>index.php">Home</a>
      <span>›</span>
      <a href="trek.php">Treks</a>
      <span>›</span>
      <?= htmlspecialchars(cleanText($trek['name'])) ?>
    </div>
  </div>

  <!-- Content -->
  <section class="td-wrap">
    <div class="container">
      <div class="td-grid">

        <!-- MAIN CONTENT -->
        <div>
          <!-- About -->
          <?php if (!empty($trek['description'])): ?>
            <div class="td-card">
              <h2><i class="fas fa-info-circle"></i> About This Trek</h2>
              <div class="td-desc">
                <?php 
                  $desc = $trek['description'];
                  if (strpos($desc, '<') === false) {
                      $paras = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $desc)));
                      foreach ($paras as $p) {
                          $cp = cleanText($p);
                          if ($cp !== '') echo '<p>' . htmlspecialchars($cp) . '</p>';
                      }
                  } else {
                      echo $desc;
                  }
                ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Gallery -->
          <?php if (count($gallery)): ?>
            <div class="td-card">
              <h2><i class="fas fa-images"></i> Gallery</h2>
              <div class="td-gallery">
                <?php foreach ($gallery as $g): 
                  $gSrc = trekImageUrl($g, '');
                  if (!$gSrc) continue;
                ?>
                  <img src="<?= htmlspecialchars($gSrc) ?>" 
                       alt="<?= htmlspecialchars(cleanText($trek['name'])) ?>"
                       loading="lazy"
                       onclick="openLightbox(this.src)"
                       onerror="this.onerror=null;this.parentNode.removeChild(this);">
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Highlights -->
          <?php if (count($highlights)): ?>
            <div class="td-card">
              <h2><i class="fas fa-star"></i> Trek Highlights</h2>
              <div class="td-highlights">
                <?php foreach ($highlights as $h): ?>
                  <div class="td-highlight">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($h) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Itinerary -->
          <?php if (count($itinerary)): ?>
            <div class="td-card">
              <h2><i class="fas fa-route"></i> Detailed Itinerary</h2>
              <div class="td-timeline">
                <?php foreach ($itinerary as $it): ?>
                  <div class="td-itin">
                    <div class="td-itin-num">DAY<br><?= htmlspecialchars($it['day']) ?></div>
                    <div class="td-itin-body">
                      <?php if (!empty($it['title'])): ?>
                        <h4><?= htmlspecialchars($it['title']) ?></h4>
                      <?php endif; ?>
                      <p><?= nl2br(htmlspecialchars($it['content'])) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Inclusions / Exclusions -->
          <?php if (count($inclusions) || count($exclusions)): ?>
            <div class="td-card">
              <h2><i class="fas fa-list-check"></i> What's Included</h2>
              <div class="td-inc-exc">
                <?php if (count($inclusions)): ?>
                  <div>
                    <h3><i class="fas fa-check-circle" style="color:#27ae60"></i> Inclusions</h3>
                    <ul>
                      <?php foreach ($inclusions as $inc): ?>
                        <li><?= htmlspecialchars($inc) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
                <?php if (count($exclusions)): ?>
                  <div>
                    <h3><i class="fas fa-times-circle" style="color:#c0392b"></i> Exclusions</h3>
                    <ul class="td-excl">
                      <?php foreach ($exclusions as $exc): ?>
                        <li><?= htmlspecialchars($exc) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- FAQ -->
          <?php if (count($faq)): ?>
            <div class="td-card">
              <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
              <?php foreach ($faq as $f): ?>
                <?php if (!empty($f['question'])): ?>
                  <div class="td-faq-item">
                    <p class="td-faq-q"><i class="fas fa-question"></i> <?= htmlspecialchars($f['question']) ?></p>
                    <p class="td-faq-a"><?= nl2br(htmlspecialchars($f['answer'] ?? '')) ?></p>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <aside class="td-sidebar">
          <div class="td-side-card">
            <h3>Quick Info</h3>
            <ul class="td-info-list">
              <?php if (!empty($trek['duration'])): ?>
                <li><span><i class="fas fa-clock"></i> Duration</span><span><?= htmlspecialchars(cleanText($trek['duration'])) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($trek['difficulty_level'])): ?>
                <li><span><i class="fas fa-mountain"></i> Difficulty</span><span><?= htmlspecialchars(cleanText($trek['difficulty_level'])) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($trek['max_altitude'])): ?>
                <li><span><i class="fas fa-arrow-up"></i> Max Altitude</span><span><?= htmlspecialchars(cleanText($trek['max_altitude'])) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($trek['starting_point'])): ?>
                <li><span><i class="fas fa-map-marker-alt"></i> Start Point</span><span><?= htmlspecialchars(cleanText($trek['starting_point'])) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($trek['best_season'])): ?>
                <li><span><i class="fas fa-calendar-alt"></i> Best Season</span><span><?= htmlspecialchars(cleanText($trek['best_season'])) ?></span></li>
              <?php endif; ?>
            </ul>
            <div style="margin-top: 20px;">
              <a href="contact.php?trek=<?= urlencode($trek['slug']) ?>" class="td-btn-primary">
                <i class="fas fa-paper-plane"></i> Enquire Now
              </a>
              <?php if (!empty($SITE['contact_phone1'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $SITE['contact_phone1'])) ?>" class="td-btn-secondary">
                  <i class="fas fa-phone"></i> Call Us
                </a>
              <?php endif; ?>
            </div>
          </div>

          <?php if (count($similar)): ?>
            <div class="td-side-card">
              <h3>Similar Treks</h3>
              <?php foreach ($similar as $s): 
                $sImg = trekImageUrl($s['featured_image'] ?? '', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=300&q=70');
              ?>
                <a href="trek-detail.php?slug=<?= urlencode($s['slug']) ?>" class="td-similar">
                  <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars(cleanText($s['name'])) ?>" loading="lazy"
                       onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=300&q=70';">
                  <div class="td-similar-info">
                    <h4><?= htmlspecialchars(cleanText($s['name'])) ?></h4>
                    <div class="td-similar-meta">
                      <?php if (!empty($s['duration'])): ?>
                        <span><i class="fas fa-clock"></i> <?= htmlspecialchars(cleanText($s['duration'])) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($s['difficulty_level'])): ?>
                        <span><i class="fas fa-mountain"></i> <?= htmlspecialchars(cleanText($s['difficulty_level'])) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </aside>

      </div>
    </div>
  </section>
</main>

<!-- Lightbox -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;padding:20px" onclick="this.style.display='none'">
  <img id="lightbox-img" src="" style="max-width:100%;max-height:100%;border-radius:12px">
</div>
<script>
function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
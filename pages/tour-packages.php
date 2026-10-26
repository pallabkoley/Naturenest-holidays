<?php
/* =========================================================
   TOUR PACKAGES — Nature Nest Holidays (100% DB Driven)
   Multi-level hierarchy: Main → Sub → Destination → ...
   Respects: status, show_in_menu, position, parent_slug
   ========================================================= */

require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Tour Packages';
require_once __DIR__ . '/../includes/header.php';

/* =========================================================
   IMAGE URL RESOLVER — supports admin1/assets/upload/
   ========================================================= */
if (!function_exists('nnImageUrl')) {
    function nnImageUrl($path, $fallbackSlug = '', $fallbacks = [], $default = '') {
        $path = trim((string)$path);

        if ($path !== '') {
            if (preg_match('#^https?://#i', $path)) return $path;

            $path = ltrim($path, '/');

            if (strpos($path, 'admin1/') === 0) {
                return rtrim(SITE_URL, '/') . '/' . $path;
            }

            if (strpos($path, 'assets/upload/') === 0) {
                return rtrim(SITE_URL, '/') . '/admin1/' . $path;
            }

            return rtrim(SITE_URL, '/') . '/' . $path;
        }

        $fb = $fallbacks[$fallbackSlug] ?? $default;
        if ($fb) {
            return 'https://images.unsplash.com/photo-' . $fb . '?auto=format&fit=crop&w=1200&q=80';
        }
        return '';
    }
}

/* =========================================================
   1. Fetch MAIN packages (tour_packages)
   ========================================================= */
$mainPackages = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM tour_packages
        WHERE status = 'active'
        ORDER BY position ASC, id ASC
    ");
    if ($stmt) $mainPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Main packages fetch failed: ' . $e->getMessage());
}

/* =========================================================
   2. Fetch ALL list items + build lookup maps
   ========================================================= */
$allLists       = [];
$childrenOf     = [];
$menuLists      = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM tour_package_lists
        WHERE status = 'active'
        ORDER BY position ASC, id ASC
    ");
    if ($stmt) {
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $allLists[$row['slug']] = $row;
            $parent = trim((string)($row['parent_slug'] ?? ''));
            if ($parent !== '') {
                $childrenOf[$parent][] = $row;
            }
            if (!empty($row['show_in_menu'])) {
                $menuLists[$row['slug']] = $row;
            }
        }
    }
} catch (Throwable $e) {
    error_log('Sub packages fetch failed: ' . $e->getMessage());
}

/* =========================================================
   3. Image fallbacks (Unsplash IDs)
   ========================================================= */
$imgFallbacks = [
    'sikkim'                => '1464822759023-fed622ff2c3b',
    'darjeeling'            => '1544735716-392fe2489ffa',
    'himachal-pradesh'      => '1519681393784-d120267933ba',
    'spiti-valley-trip'     => '1483728642387-6c3bdd6c93e5',
    'spiti-valley-trip-chandratal' => '1589199021934-e67cf3d2fb68',
    'kashmir'               => '1506905925346-21bda4d32df4',
    'bhutan'                => '1553853504-de0d1937d2b0',
    'puri'                  => '1507525428034-b723cf961d3e',
    'nagaland'              => '1516426122078-c23e76319801',
    'hornbill-festival-dzukou-valley-escape' => '1544735716-392fe2489ffa',
    'pilgrimage-tour'       => '1564507592333-c60657eea523',
    'offbeat-sikkim'        => '1464822759023-fed622ff2c3b',
    'offbeat-north-bengal'  => '1441974231531-c6227db76b6e',
    'shimla'                => '1597074866923-dc0589150358',
    'kullu'                 => '1519681393784-d120267933ba',
    'manali'                => '1483728642387-6c3bdd6c93e5',
    'shimla-kullu-manali-tour' => '1597074866923-dc0589150358',
];
$defaultImg = '1464822759023-fed622ff2c3b';

/* =========================================================
   4. Image helper
   ========================================================= */
if (!function_exists('pkgImage')) {
    function pkgImage($row, $fallbacks, $default) {
        $slug = $row['slug'] ?? '';
        return nnImageUrl($row['featured_image'] ?? '', $slug, $fallbacks, $default);
    }
}

/* =========================================================
   5. RESOLVE ACTIVE PACKAGE
   ========================================================= */
$activePackage  = isset($_GET['package']) ? trim($_GET['package']) : '';
$activeMain     = null;
$activeNode     = null;
$activeChain    = [];
$packagesToShow = $mainPackages;

if ($activePackage !== '') {
    foreach ($mainPackages as $p) {
        if ($p['slug'] === $activePackage) {
            $activeMain = $p;
            $packagesToShow = [$p];
            break;
        }
    }

    if ($activeMain === null && isset($allLists[$activePackage])) {
        $activeNode = $allLists[$activePackage];
        $chain      = [];
        $current    = $activeNode;
        $guard      = 0;

        while ($guard < 25 && $current) {
            $chain[] = $current;
            $parentSlug = trim((string)($current['parent_slug'] ?? ''));
            if ($parentSlug === '') break;

            $isMain = false;
            foreach ($mainPackages as $p) {
                if ($p['slug'] === $parentSlug) {
                    $activeMain = $p;
                    $isMain = true;
                    break;
                }
            }
            if ($isMain) break;

            if (isset($allLists[$parentSlug])) {
                $current = $allLists[$parentSlug];
            } else {
                break;
            }
            $guard++;
        }
        $activeChain = array_reverse($chain);
        $packagesToShow = $activeMain ? [$activeMain] : [];
    }
}

/* =========================================================
   6. Field parsers
   ========================================================= */

/**
 * FIX: MySQL single-quoted strings turn `\r\n` into raw CR/LF bytes,
 * which breaks json_decode() because raw control chars are not
 * allowed inside JSON strings. This helper escapes them back to
 * literal `\n` / `\t` before decoding.
 */
if (!function_exists('tpdFixJsonControlChars')) {
    function tpdFixJsonControlChars($raw) {
        return str_replace(
            ["\r\n", "\r", "\n", "\t", "\f", "\b"],
            ['\\n',  '\\n', '\\n', '\\t', '\\f', '\\b'],
            (string)$raw
        );
    }
}

if (!function_exists('tpdParseItinerary')) {
    function tpdParseItinerary($raw) {
        if (empty($raw)) return [];

        $cleaned = tpdFixJsonControlChars($raw);
        $decoded = json_decode($cleaned, true);

        if (!is_array($decoded)) {
            $decoded = json_decode($raw, true);
        }

        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $i => $it) {
                if (is_array($it)) {
                    $out[] = [
                        'day'     => $it['day'] ?? ($i + 1),
                        'title'   => trim($it['title'] ?? ''),
                        'content' => trim($it['content'] ?? ''),
                    ];
                }
            }
            return $out;
        }

        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', strip_tags($raw))));
        $out = [];
        $cur = null;
        foreach ($lines as $ln) {
            if (preg_match('/^Day\s+(\d+)\s*[\:\-\–\—]?\s*(.*)$/i', $ln, $m)) {
                if ($cur) $out[] = $cur;
                $cur = ['day' => (int)$m[1], 'title' => '', 'content' => trim($m[2])];
            } elseif ($cur) {
                $cur['content'] .= ($cur['content'] ? ' ' : '') . $ln;
            }
        }
        if ($cur) $out[] = $cur;
        return $out;
    }
}

if (!function_exists('tpdParseFaq')) {
    function tpdParseFaq($raw) {
        if (empty($raw)) return [];

        $cleaned = tpdFixJsonControlChars($raw);
        $decoded = json_decode($cleaned, true);
        if (!is_array($decoded)) {
            $decoded = json_decode($raw, true);
        }

        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $it) {
                if (is_array($it) && !empty($it['question'])) {
                    $out[] = [
                        'question' => trim($it['question']),
                        'answer'   => trim($it['answer'] ?? '')
                    ];
                }
            }
            return $out;
        }
        return [];
    }
}

if (!function_exists('tpdParseLines')) {
    function tpdParseLines($raw) {
        if (empty($raw)) return [];
        $clean = preg_replace('/<\/?(p|br|div|li|ul|ol)[^>]*>/i', "\n", $raw);
        $clean = strip_tags($clean);
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $clean)));
        $out = [];
        foreach ($lines as $ln) {
            $ln = preg_replace('/^[\-\*\•\·\–\—\d+\.]+\s*/u', '', $ln);
            if (trim($ln) !== '') $out[] = trim($ln);
        }
        return $out;
    }
}

if (!function_exists('tpdPara')) {
    function tpdPara($text) {
        if (empty($text)) return [];
        if (strpos($text, '<') !== false) return [$text];
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)));
        return $lines;
    }
}

/* =========================================================
   7. Parse ACTIVE data
   ========================================================= */
$activeItinerary    = [];
$activeFaqs         = [];
$activeIncl         = [];
$activeExcl         = [];
$activeGallery      = [];
$activeFeatured     = '';
$activeHighlights   = [];
$activeSpecialNote  = '';
$activeImportant    = '';
$activeDocuments    = '';
$activeWhatToPack   = '';
$activePermitInfo   = '';
$activeCancellation = '';
$activeImportantNotes = '';

$dataSource = $activeNode ?: $activeMain;

if ($dataSource) {
    $itineraryRaw    = $dataSource['tour_itinerary'] ?? ($dataSource['itinerary'] ?? '');
    $activeItinerary = tpdParseItinerary($itineraryRaw);
    $activeFaqs      = tpdParseFaq($dataSource['faqs'] ?? '');
    $activeIncl      = tpdParseLines($dataSource['inclusions'] ?? '');
    $activeExcl      = tpdParseLines($dataSource['exclusions'] ?? '');

    if (!empty($dataSource['gallery_images'])) {
        $g = json_decode($dataSource['gallery_images'], true);
        if (is_array($g)) $activeGallery = $g;
    }

    if (!empty($dataSource['featured_image'])) {
        $activeFeatured = nnImageUrl($dataSource['featured_image'], $dataSource['slug'] ?? '', $imgFallbacks, $defaultImg);
    }

    if (!empty($dataSource['highlights'])) {
        $activeHighlights = tpdParseLines($dataSource['highlights']);
    }

    $activeSpecialNote    = trim((string)($dataSource['special_note'] ?? ''));
    $activeImportant      = trim((string)($dataSource['important'] ?? ''));
    $activeDocuments      = trim((string)($dataSource['documents_requierment'] ?? ''));
    $activeWhatToPack     = trim((string)($dataSource['what_to_pack'] ?? ''));
    $activePermitInfo     = trim((string)($dataSource['permit_info'] ?? ''));
    $activeCancellation   = trim((string)($dataSource['cancellation_policy'] ?? ''));
    $activeImportantNotes = trim((string)($dataSource['important_notes'] ?? ''));
}

/* =========================================================
   8. Children to display
   ========================================================= */
$childrenToShow = [];
if ($activeNode) {
    $childrenToShow = $childrenOf[$activeNode['slug']] ?? [];
} elseif ($activeMain) {
    $childrenToShow = $childrenOf[$activeMain['slug']] ?? [];
}

/* =========================================================
   9. Overview
   ========================================================= */
$activeOverview = '';
if ($activeNode) {
    $activeOverview = trim($activeNode['tour_overview'] ?? '');
    if ($activeOverview === '') $activeOverview = trim($activeNode['important_notes'] ?? '');
    if ($activeOverview === '') $activeOverview = trim($activeNode['accommodation'] ?? '');
} elseif ($activeMain) {
    $activeOverview = trim($activeMain['description'] ?? '');
}
?>

<style>
/* =========================================================
   LISTING / GRID
   ========================================================= */
.tp-section { background: var(--cream); }
.tp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
@media (max-width: 991px) { .tp-grid { grid-template-columns: repeat(2, 1fr); gap: 22px; } }
@media (max-width: 640px) { .tp-grid { grid-template-columns: 1fr; gap: 20px; } }

.tp-card {
  background: #fff; border-radius: 18px; overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 4px 18px rgba(15,61,46,.06);
  border: 1px solid #ece6d4; position: relative;
  transition: transform .35s cubic-bezier(.22,.61,.36,1),
              box-shadow .35s cubic-bezier(.22,.61,.36,1);
}
.tp-card:hover { transform: translateY(-6px); box-shadow: 0 18px 42px rgba(15,61,46,.12); }
.tp-card.is-highlighted {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(201,154,62,.20), 0 18px 42px rgba(15,61,46,.14);
}

.tp-media { aspect-ratio: 4 / 3; overflow: hidden; background: #eef2ee; position: relative; }
.tp-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .8s cubic-bezier(.22,.61,.36,1); }
.tp-card:hover .tp-media img { transform: scale(1.08); }

.tp-badge {
  position: absolute; top: 14px; left: 14px;
  padding: 5px 13px; background: rgba(255,255,255,.95); color: var(--forest);
  font-size: .68rem; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; border-radius: 999px; backdrop-filter: blur(6px);
}
.tp-body { padding: 22px 22px 20px; display: flex; flex-direction: column; flex: 1; }
.tp-title { font-family: var(--font-display); font-size: 1.22rem; color: var(--forest); margin: 0 0 10px; line-height: 1.25; }
.tp-desc { font-size: .88rem; line-height: 1.6; color: var(--muted); margin: 0 0 14px; flex: 1; }
.tp-meta {
  display: flex; flex-wrap: wrap; gap: 8px 14px;
  margin: 0 0 14px; padding: 12px 0 0;
  border-top: 1px dashed #eee; font-size: .78rem; color: var(--muted);
}
.tp-meta span { display: inline-flex; align-items: center; gap: 5px; }
.tp-meta i { color: var(--gold-dark); font-size: .72rem; }
.tp-actions { display: flex; gap: 8px; margin-top: auto; }
.tp-actions .btn { flex: 1; }
.tp-empty { text-align: center; padding: 60px 20px; color: var(--muted); }
.tp-empty i { font-size: 2.6rem; color: #c9d3ca; margin-bottom: 14px; display: block; }

.tp-highlights-mini {
  display: flex; flex-wrap: wrap; gap: 6px 8px;
  margin: 0 0 14px; padding: 10px 0 0;
  border-top: 1px dashed #eee;
}
.tp-highlights-mini span {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .72rem; font-weight: 500; color: #34573f;
  background: #f1f7f1; border: 1px solid #e0ecdf;
  padding: 3px 10px; border-radius: 999px; line-height: 1.5;
  white-space: nowrap; max-width: 100%;
  overflow: hidden; text-overflow: ellipsis;
}
.tp-highlights-mini i { color: #c99a3e; font-size: .62rem; flex-shrink: 0; }

/* =========================================================
   DETAIL VIEW
   ========================================================= */
.tpd-hero {
  position: relative; min-height: 460px; background-size: cover;
  background-position: center; display: flex; align-items: flex-end;
  border-radius: 0 0 24px 24px; overflow: hidden; margin-bottom: 0;
}
.tpd-hero::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(10,44,33,.88) 0%, rgba(10,44,33,.35) 55%, rgba(10,44,33,.10) 100%);
}
.tpd-hero-inner { position: relative; z-index: 2; width: 100%; padding: 70px 0 40px; }
.tpd-hero .eyebrow { color: #d4af37; font-size: .8rem; letter-spacing: .2em; text-transform: uppercase; font-weight: 700; margin-bottom: 12px; display: inline-block; }
.tpd-hero h1 { color: #fff; font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 3rem); line-height: 1.15; margin: 0 0 14px; font-weight: 700; max-width: 900px; text-shadow: 0 2px 20px rgba(0,0,0,.4); }
.tpd-hero p { color: rgba(255,255,255,.85); font-size: 1rem; max-width: 720px; margin: 0; }

.tpd-breadcrumb { padding: 16px 0; font-size: .82rem; color: #8a8a7a; background: #fbfaf5; }
.tpd-breadcrumb a { color: #1e4634; text-decoration: none; font-weight: 500; }
.tpd-breadcrumb a:hover { color: #d4af37; }
.tpd-breadcrumb span { margin: 0 8px; opacity: .5; }

.tpd-wrap { padding: 50px 0 60px; background: #fbfaf5; }
.tpd-grid { display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: start; }
@media (max-width: 960px) { .tpd-grid { grid-template-columns: 1fr; gap: 28px; } }

.tpd-card { background: #fff; border-radius: 18px; padding: 28px 30px; margin-bottom: 22px; border: 1px solid #ece6d4; box-shadow: 0 4px 24px rgba(15,61,46,.04); }
.tpd-card h2 { font-family: var(--font-display); color: var(--forest); font-size: 1.4rem; margin: 0 0 16px; display: flex; align-items: center; gap: 10px; }
.tpd-card h2 i { color: #d4af37; font-size: 1.05rem; }
.tpd-card p { color: #555; line-height: 1.8; margin: 0 0 12px; font-size: .94rem; }
.tpd-card p:last-child { margin-bottom: 0; }

.tpd-featured { background: #fff; border-radius: 18px; overflow: hidden; margin-bottom: 22px; border: 1px solid #ece6d4; box-shadow: 0 4px 24px rgba(15,61,46,.06); }
.tpd-featured img { width: 100%; height: auto; display: block; max-height: 520px; object-fit: cover; }

.tpd-highlights { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.tpd-hl-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 14px 16px; background: #f8f6ee;
  border-radius: 12px; border-left: 3px solid #d4af37;
  font-size: .88rem; color: #294536; line-height: 1.5; font-weight: 500;
}
.tpd-hl-item i { color: #d4af37; margin-top: 3px; flex-shrink: 0; }

.tpd-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.tpd-gallery img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 12px; cursor: zoom-in; transition: transform .3s, box-shadow .3s; box-shadow: 0 2px 8px rgba(15,61,46,.08); }
.tpd-gallery img:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 12px 30px rgba(15,61,46,.18); }

.tpd-day { display: flex; gap: 16px; padding: 14px 0; border-bottom: 1px dashed #e8e2d4; }
.tpd-day:last-child { border-bottom: none; }
.tpd-day-num { flex: 0 0 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e4634, #2c5f2d); color: #d4af37; font-family: var(--font-display); font-size: .78rem; font-weight: 700; border-radius: 12px; text-align: center; line-height: 1.1; }
.tpd-day-body { flex: 1; }
.tpd-day-body h4 { margin: 0 0 4px; font-size: .95rem; color: var(--forest); font-weight: 600; }
.tpd-day-body p { margin: 0; font-size: .88rem; line-height: 1.6; color: #555; }

.tpd-inc-exc { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
@media (max-width: 640px) { .tpd-inc-exc { grid-template-columns: 1fr; } }
.tpd-inc-exc h3 { margin: 0 0 12px; font-size: .98rem; font-family: var(--font-display); color: var(--forest); display: flex; align-items: center; gap: 8px; }
.tpd-inc-exc ul { list-style: none; padding: 0; margin: 0; }
.tpd-inc-exc li { display: flex; gap: 9px; font-size: .86rem; color: #555; padding: 5px 0; line-height: 1.55; }
.tpd-inc-exc li::before { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #27ae60; flex-shrink: 0; margin-top: 2px; }
.tpd-inc-exc .tpd-excl li::before { content: '\f00d'; color: #c0392b; }

.tpd-faq-item { border-bottom: 1px solid #ece6d4; padding: 14px 0; }
.tpd-faq-item:last-child { border-bottom: none; }
.tpd-faq-q { font-weight: 600; color: var(--forest); font-size: .93rem; margin: 0 0 6px; }
.tpd-faq-a { margin: 0; font-size: .88rem; color: #555; line-height: 1.65; }

.tpd-note-block {
  background: #fdfaf0;
  border: 1px solid #f0e3b3;
  border-left: 4px solid #d4af37;
  border-radius: 10px;
  padding: 18px 22px;
  margin: 0 0 14px;
}
.tpd-note-block:last-child { margin-bottom: 0; }
.tpd-note-block p { margin: 0 0 8px; font-size: .9rem; color: #4a4a3a; line-height: 1.7; }
.tpd-note-block p:last-child { margin-bottom: 0; }

.tpd-doc-list { list-style: none; padding: 0; margin: 0; }
.tpd-doc-list li {
  display: flex; gap: 10px; align-items: flex-start;
  padding: 10px 0; border-bottom: 1px dashed #f0ece0;
  font-size: .9rem; color: #555; line-height: 1.6;
}
.tpd-doc-list li:last-child { border-bottom: none; }
.tpd-doc-list li i { color: #d4af37; margin-top: 3px; flex-shrink: 0; width: 16px; }

.tpd-side { position: sticky; top: 90px; display: flex; flex-direction: column; gap: 18px; }
.tpd-side-card { background: #fff; border-radius: 18px; padding: 22px 24px; border: 1px solid #ece6d4; box-shadow: 0 4px 24px rgba(15,61,46,.05); }
.tpd-side-card h3 { font-family: var(--font-display); color: var(--forest); font-size: 1.05rem; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 2px solid #d4af37; display: inline-block; }
.tpd-info-list { list-style: none; padding: 0; margin: 0; }
.tpd-info-list li { display: flex; justify-content: space-between; align-items: flex-start; padding: 9px 0; border-bottom: 1px dashed #f0ece0; font-size: .86rem; gap: 12px; }
.tpd-info-list li:last-child { border-bottom: none; }
.tpd-info-list li span:first-child { color: #8a8a7a; display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.tpd-info-list li span:first-child i { color: #d4af37; width: 14px; }
.tpd-info-list li span:last-child { color: #294536; font-weight: 600; text-align: right; }

.tpd-btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px 18px; background: linear-gradient(135deg, #1e4634, #2c5f2d); color: #fff; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: .9rem; transition: all .25s; border: none; cursor: pointer; }
.tpd-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(30,70,52,.3); color: #fff; }

.tpd-sub-head { margin: 46px 0 22px; text-align: center; }
.tpd-sub-head h2 { font-family: var(--font-display); color: var(--forest); font-size: 1.6rem; margin: 0 0 6px; }
.tpd-sub-head p { color: #8a8a7a; margin: 0; font-size: .9rem; }

.tpd-coming { background: linear-gradient(135deg, #fdf8e7 0%, #faf4dc 100%); border: 1px solid #f0e3b3; border-radius: 18px; padding: 40px 30px; text-align: center; margin-bottom: 22px; }
.tpd-coming i.tpd-coming-icon { font-size: 2.4rem; color: #c99a3e; margin-bottom: 14px; display: block; }
.tpd-coming h3 { font-family: var(--font-display); color: #6b5a1e; font-size: 1.25rem; margin: 0 0 10px; }
.tpd-coming p { color: #8a7a3e; max-width: 520px; margin: 0 auto 20px; line-height: 1.6; font-size: .9rem; }
.tpd-coming a.tpd-btn-primary { display: inline-flex; width: auto; padding: 12px 26px; }

/* =========================================================
   IMPORTANT NOTES — styled variants
   ========================================================= */
.tpd-notes-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin: 0;
  padding: 0;
}
.tpd-note-item {
  background: #fdfaf0;
  border: 1px solid #f0e3b3;
  border-left: 4px solid #d4af37;
  border-radius: 10px;
  padding: 16px 20px;
}
.tpd-note-item p {
  margin: 0;
  font-size: .9rem;
  color: #4a4a3a;
  line-height: 1.7;
}
.tpd-note-item strong.note-title {
  color: #6b5a1e;
  display: block;
  margin-bottom: 4px;
  font-weight: 700;
}
</style>

<!-- ============================================================
     HERO
     ============================================================ -->
<?php
  $heroTitle = '';
  $heroSub   = '';
  $heroImg   = '';

  if ($activeNode) {
      $heroTitle = $activeNode['title'];
      $heroSub   = $activeOverview ? substr(strip_tags($activeOverview), 0, 180) : '';
      $heroImg   = pkgImage($activeNode, $imgFallbacks, $defaultImg);
  } elseif ($activeMain) {
      $heroTitle = $activeMain['title'];
      $heroSub   = $activeMain['description'] ?? '';
      $heroImg   = pkgImage($activeMain, $imgFallbacks, $defaultImg);
  }
?>
<?php if ($activeNode || $activeMain): ?>
<section class="tpd-hero" style="background-image:url('<?= htmlspecialchars($heroImg) ?>')">
  <div class="tpd-hero-inner">
    <div class="container">
      <span class="eyebrow">Tour Package</span>
      <h1><?= e($heroTitle) ?></h1>
      <?php if ($heroSub): ?><p><?= e($heroSub) ?></p><?php endif; ?>
    </div>
  </div>
</section>

<div class="tpd-breadcrumb">
  <div class="container">
    <a href="<?= BASE_URL ?>index.php">Home</a>
    <span>›</span>
    <a href="<?= PAGES_URL ?>tour-packages.php">Tour Packages</a>
    <?php if ($activeMain): ?>
      <span>›</span>
      <a href="<?= PAGES_URL ?>tour-packages.php?package=<?= urlencode($activeMain['slug']) ?>"><?= e($activeMain['title']) ?></a>
    <?php endif; ?>
    <?php
      $chainForBreadcrumb = $activeChain;
      if (count($chainForBreadcrumb) > 0) array_pop($chainForBreadcrumb);
      foreach ($chainForBreadcrumb as $c):
    ?>
      <span>›</span>
      <a href="<?= PAGES_URL ?>tour-packages.php?package=<?= urlencode($c['slug']) ?>"><?= e($c['title']) ?></a>
    <?php endforeach; ?>
    <?php if ($activeNode): ?>
      <span>›</span>
      <?= e($activeNode['title']) ?>
    <?php endif; ?>
  </div>
</div>

<section class="tpd-wrap">
  <div class="container">
    <div class="tpd-grid">

      <!-- MAIN CONTENT -->
      <div>
        <?php
          $hasOverview        = ($activeOverview !== '');
          $hasItinerary       = count($activeItinerary) > 0;
          $hasInclExc         = (count($activeIncl) > 0 || count($activeExcl) > 0);
          $hasGallery         = count($activeGallery) > 0;
          $hasFaq             = count($activeFaqs) > 0;
          $hasHighlights      = count($activeHighlights) > 0;
          $hasSpecialNote     = ($activeSpecialNote !== '');
          $hasImportant       = ($activeImportant !== '');
          $hasDocuments       = ($activeDocuments !== '');
          $hasWhatToPack      = ($activeWhatToPack !== '');
          $hasPermitInfo      = ($activePermitInfo !== '');
          $hasCancellation    = ($activeCancellation !== '');
          $hasImportantNotes  = ($activeImportantNotes !== '');
          $hasContent = ($hasOverview || $hasItinerary || $hasInclExc || $hasGallery ||
                         $hasFaq || $hasHighlights || $hasSpecialNote || $hasImportant ||
                         $hasDocuments || $hasWhatToPack || $hasPermitInfo || $hasCancellation ||
                         $hasImportantNotes);
        ?>

        <!-- Overview -->
        <?php if ($hasOverview): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-info-circle"></i> Overview</h2>
            <?php
              if (strpos($activeOverview, '<') === false) {
                  foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $activeOverview))) as $para) {
                      echo '<p>' . htmlspecialchars($para) . '</p>';
                  }
              } else {
                  echo $activeOverview;
              }
            ?>
          </div>
        <?php endif; ?>

        <!-- Featured Image -->
        <?php if ($activeFeatured): ?>
          <div class="tpd-featured">
            <img src="<?= htmlspecialchars($activeFeatured) ?>"
                 alt="<?= e($heroTitle) ?>"
                 loading="lazy"
                 onerror="this.parentNode.style.display='none';">
          </div>
        <?php endif; ?>

        <!-- Highlights -->
        <?php if ($hasHighlights): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-star"></i> Highlights</h2>
            <div class="tpd-highlights">
              <?php foreach ($activeHighlights as $h): ?>
                <div class="tpd-hl-item">
                  <i class="fas fa-check-circle"></i>
                  <span><?= htmlspecialchars($h) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Itinerary -->
        <?php if ($hasItinerary): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-route"></i> Itinerary</h2>
            <?php foreach ($activeItinerary as $day): ?>
              <div class="tpd-day">
                <div class="tpd-day-num">DAY<br><?= htmlspecialchars($day['day']) ?></div>
                <div class="tpd-day-body">
                  <?php if (!empty($day['title'])): ?>
                    <h4><?= htmlspecialchars($day['title']) ?></h4>
                  <?php endif; ?>
                  <p><?= nl2br(htmlspecialchars($day['content'])) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Inclusions / Exclusions -->
        <?php if ($hasInclExc): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-list-check"></i> What's Included</h2>
            <div class="tpd-inc-exc">
              <?php if (count($activeIncl)): ?>
                <div>
                  <h3><i class="fas fa-check-circle" style="color:#27ae60"></i> Inclusions</h3>
                  <ul>
                    <?php foreach ($activeIncl as $i): ?>
                      <li><?= htmlspecialchars($i) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              <?php if (count($activeExcl)): ?>
                <div class="tpd-excl">
                  <h3><i class="fas fa-times-circle" style="color:#c0392b"></i> Exclusions</h3>
                  <ul>
                    <?php foreach ($activeExcl as $x): ?>
                      <li><?= htmlspecialchars($x) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Documents & Permit Info -->
        <?php if ($hasDocuments || $hasPermitInfo): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-passport"></i> Documents & Permits</h2>

            <?php if ($hasDocuments): ?>
              <ul class="tpd-doc-list">
                <?php foreach (tpdPara($activeDocuments) as $line): ?>
                  <li><i class="fas fa-id-card"></i><span><?= htmlspecialchars($line) ?></span></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ($hasPermitInfo): ?>
              <div class="tpd-note-block" style="margin-top:14px;">
                <p><strong><i class="fas fa-stamp" style="color:#c99a3e;"></i> Permit Information</strong></p>
                <?php foreach (tpdPara($activePermitInfo) as $line): ?>
                  <p><?= htmlspecialchars($line) ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Special Note -->
        <?php if ($hasSpecialNote): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-exclamation-circle"></i> Special Note</h2>
            <div class="tpd-note-block">
              <?php foreach (tpdPara($activeSpecialNote) as $line): ?>
                <p><?= htmlspecialchars($line) ?></p>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================================
             NEW SECTION: IMPORTANT NOTES
             ============================================ -->
        <?php if ($hasImportantNotes): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-exclamation-triangle"></i> Important Notes</h2>
            <div class="tpd-notes-list">
              <?php
                $noteLines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $activeImportantNotes)));
                foreach ($noteLines as $line):
                  // Detect "Title: content" pattern → bold the title part
                  $hasColon = (strpos($line, ':') !== false) && (strpos($line, ':') < 80);
                  if ($hasColon) {
                      list($noteTitle, $noteBody) = explode(':', $line, 2);
                      $noteTitle = trim($noteTitle);
                      $noteBody  = trim($noteBody);
                  }
              ?>
                <div class="tpd-note-item">
                  <?php if ($hasColon && $noteTitle !== ''): ?>
                    <p>
                      <strong class="note-title"><?= htmlspecialchars($noteTitle) ?>:</strong>
                      <?= htmlspecialchars($noteBody) ?>
                    </p>
                  <?php else: ?>
                    <p><?= htmlspecialchars($line) ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Important Terms & Conditions -->
        <?php if ($hasImportant): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-info-circle"></i> Important Information</h2>
            <?php foreach (tpdPara($activeImportant) as $line): ?>
              <p><?= htmlspecialchars($line) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- What to Pack -->
        <?php if ($hasWhatToPack): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-suitcase-rolling"></i> What to Pack</h2>
            <div class="tpd-highlights">
              <?php foreach (tpdParseLines($activeWhatToPack) as $item): ?>
                <div class="tpd-hl-item">
                  <i class="fas fa-check-circle"></i>
                  <span><?= htmlspecialchars($item) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Gallery -->
        <?php if ($hasGallery): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-images"></i> Gallery</h2>
            <div class="tpd-gallery">
              <?php foreach ($activeGallery as $g):
                $gSrc = nnImageUrl($g, $dataSource['slug'] ?? '', $imgFallbacks, $defaultImg);
                if (!$gSrc) continue;
              ?>
                <img src="<?= htmlspecialchars($gSrc) ?>"
                     alt="<?= e($heroTitle) ?>"
                     loading="lazy"
                     onclick="window.open(this.src,'_blank')"
                     onerror="this.onerror=null;this.parentNode.removeChild(this);">
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- FAQ -->
        <?php if ($hasFaq): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            <?php foreach ($activeFaqs as $f): ?>
              <div class="tpd-faq-item">
                <p class="tpd-faq-q"><i class="fas fa-question" style="color:#d4af37"></i> <?= htmlspecialchars($f['question']) ?></p>
                <p class="tpd-faq-a"><?= nl2br(htmlspecialchars($f['answer'])) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Cancellation Policy -->
        <?php if ($hasCancellation): ?>
          <div class="tpd-card">
            <h2><i class="fas fa-ban"></i> Cancellation Policy</h2>
            <?php foreach (tpdPara($activeCancellation) as $line): ?>
              <p><?= htmlspecialchars($line) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Coming Soon -->
        <?php if (!$hasContent && count($childrenToShow) === 0): ?>
          <div class="tpd-coming">
            <i class="fas fa-compass tpd-coming-icon"></i>
            <h3>Detailed Information Coming Soon</h3>
            <p>
              We're currently preparing the complete itinerary, inclusions and pricing
              for <strong><?= e($heroTitle) ?></strong>.
              Contact us to receive details right away.
            </p>
            <a href="<?= PAGES_URL ?>contact.php?package=<?= urlencode($activePackage) ?>" class="tpd-btn-primary">
              <i class="fas fa-paper-plane"></i> Request Full Details
            </a>
          </div>
        <?php endif; ?>

        <!-- Children / Sub-destinations Grid -->
        <?php if (count($childrenToShow)): ?>
          <div class="tpd-sub-head">
            <h2>Destinations in <?= e($heroTitle) ?></h2>
            <p>Pick a destination below to explore more</p>
          </div>

          <div class="tp-grid">
            <?php foreach ($childrenToShow as $child):
              $cSlug  = $child['slug']  ?? '';
              $cTitle = $child['title'] ?? '';
              $cDesc  = trim((string)($child['tour_overview'] ?? ''));
              if ($cDesc === '') $cDesc = trim((string)($child['important_notes'] ?? ''));
              $cImg   = pkgImage($child, $imgFallbacks, $defaultImg);
              $cDays  = (int)($child['duration_days'] ?? 0);
              $cNights= (int)($child['duration_nights'] ?? 0);
              $cDiff  = $child['difficulty_level'] ?? '';
              $hasKids = !empty($childrenOf[$cSlug]);
            ?>
              <article class="tp-card reveal">
                <div class="tp-media">
                  <img src="<?= htmlspecialchars($cImg) ?>" alt="<?= e($cTitle) ?>" loading="lazy"
                       onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-<?= $defaultImg ?>?auto=format&fit=crop&w=900&q=80';">
                  <?php if ($hasKids): ?>
                    <span class="tp-badge">Category</span>
                  <?php endif; ?>
                </div>
                <div class="tp-body">
                  <h3 class="tp-title"><?= e($cTitle) ?></h3>
                  <?php if ($cDesc): ?>
                    <p class="tp-desc"><?= e(substr(strip_tags($cDesc), 0, 140)) ?><?= strlen(strip_tags($cDesc)) > 140 ? '…' : '' ?></p>
                  <?php else: ?>
                    <p class="tp-desc" style="font-style:italic;color:#b9b3a0;">Explore this destination</p>
                  <?php endif; ?>

                  <?php if ($cDays > 0 || $cDiff): ?>
                    <div class="tp-meta">
                      <?php if ($cDays > 0): ?>
                        <span><i class="fa-regular fa-clock"></i> <?= $cDays ?>D<?= $cNights > 0 ? ' / ' . $cNights . 'N' : '' ?></span>
                      <?php endif; ?>
                      <?php if ($cDiff): ?>
                        <span><i class="fa-solid fa-mountain"></i> <?= e(ucfirst($cDiff)) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="tp-actions">
                    <a href="<?= PAGES_URL ?>tour-packages.php?package=<?= urlencode($cSlug) ?>"
                       class="btn btn-primary btn-xs" style="flex:1;">
                      <?= $hasKids ? 'View Destinations' : 'View Details' ?>
                      <i class="fas fa-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

      <!-- SIDEBAR -->
      <aside class="tpd-side">
        <?php
          $quickInfo = $activeNode ?: $activeMain;
          $hasQuickInfo = ($quickInfo && (
              !empty($quickInfo['duration_days'])
           || !empty($quickInfo['difficulty_level'])
           || !empty($quickInfo['best_season'])
           || !empty($quickInfo['season'])
           || !empty($quickInfo['group_size'])
           || !empty($quickInfo['age_limit'])
           || !empty($quickInfo['starting_point'])
           || !empty($quickInfo['location'])
           || !empty($quickInfo['state'])
          ));
          if ($hasQuickInfo):
        ?>
          <div class="tpd-side-card">
            <h3>Quick Info</h3>
            <ul class="tpd-info-list">
              <?php if (!empty($quickInfo['duration_days'])): ?>
                <li><span><i class="fas fa-clock"></i> Duration</span>
                    <span><?= (int)$quickInfo['duration_days'] ?>D<?= !empty($quickInfo['duration_nights']) ? ' / ' . (int)$quickInfo['duration_nights'] . 'N' : '' ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['difficulty_level'])): ?>
                <li><span><i class="fas fa-mountain"></i> Difficulty</span>
                    <span><?= e(ucfirst($quickInfo['difficulty_level'])) ?></span></li>
              <?php endif; ?>
              <?php
                $seasonText = $quickInfo['best_season'] ?? ($quickInfo['season'] ?? '');
                if ($seasonText):
              ?>
                <li><span><i class="fas fa-calendar-alt"></i> Best Season</span>
                    <span><?= e($seasonText) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['group_size'])): ?>
                <li><span><i class="fas fa-users"></i> Group Size</span>
                    <span><?= e($quickInfo['group_size']) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['age_limit'])): ?>
                <li><span><i class="fas fa-child"></i> Age Limit</span>
                    <span><?= e($quickInfo['age_limit']) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['starting_point'])): ?>
                <li><span><i class="fas fa-map-marker-alt"></i> Starting Point</span>
                    <span><?= e($quickInfo['starting_point']) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['location'])): ?>
                <li><span><i class="fas fa-location-dot"></i> Location</span>
                    <span><?= e($quickInfo['location']) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($quickInfo['state'])): ?>
                <li><span><i class="fas fa-flag"></i> State</span>
                    <span><?= e($quickInfo['state']) ?></span></li>
              <?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Nearby Destinations -->
        <?php
          $parentOfActive = $activeNode['parent_slug'] ?? '';
          $siblings = $childrenOf[$parentOfActive] ?? [];
          $siblings = array_filter($siblings, function($s) use ($activeNode) {
              return $s['slug'] !== ($activeNode['slug'] ?? '');
          });
          if (count($siblings)):
        ?>
          <div class="tpd-side-card">
            <h3>Nearby Destinations</h3>
            <?php foreach ($siblings as $sib):
              $sibImg = pkgImage($sib, $imgFallbacks, $defaultImg);
            ?>
              <a href="<?= PAGES_URL ?>tour-packages.php?package=<?= urlencode($sib['slug']) ?>"
                 style="display:flex;gap:10px;align-items:center;padding:8px 0;text-decoration:none;color:inherit;border-bottom:1px dashed #f0ece0;">
                <img src="<?= htmlspecialchars($sibImg) ?>" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                  <div style="font-size:.86rem;font-weight:600;color:#1e4634;"><?= e($sib['title']) ?></div>
                  <?php if (!empty($sib['duration_days'])): ?>
                    <div style="font-size:.72rem;color:#8a8a7a;margin-top:2px;">
                      <i class="fas fa-clock"></i> <?= (int)$sib['duration_days'] ?> days
                    </div>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="tpd-side-card" style="background:linear-gradient(135deg,#1e4634,#2c5f2d);color:#fff;text-align:center;">
          <i class="fas fa-headset" style="font-size:1.6rem;color:#d4af37;margin-bottom:10px;display:block;"></i>
          <h3 style="color:#fff;border-color:#d4af37;">Need Help?</h3>
          <p style="color:rgba(255,255,255,.85);font-size:.88rem;margin:0 0 14px;">Our experts will help you pick the perfect journey.</p>
          <a href="<?= PAGES_URL ?>contact.php" style="display:inline-flex;align-items:center;gap:8px;background:#d4af37;color:#1e4634;padding:10px 22px;border-radius:999px;text-decoration:none;font-weight:600;font-size:.86rem;">
            Contact Us <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </aside>

    </div>
  </div>
</section>

<?php else: ?>

<!-- DEFAULT VIEW -->
<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1920&q=80')">
  <div class="container page-hero-inner">
    <span class="eyebrow eyebrow--light">Explore Our Journeys</span>
    <h1>Tour Packages</h1>
    <p>Curated experiences designed around the way you love to travel.</p>
    <nav class="breadcrumb">
      <a href="<?= BASE_URL ?>index.php">Home</a>
      <span class="sep">/</span>
      <span>Tour Packages</span>
    </nav>
  </div>
</section>

<section class="section tp-section">
  <div class="container">
    <?php if (empty($mainPackages)): ?>
      <div class="tp-empty">
        <i class="fas fa-suitcase-rolling"></i>
        <h3>No Tour Packages Found</h3>
        <p>Packages will appear here once they're added in the admin panel.</p>
      </div>
    <?php else: ?>
      <div class="tp-grid">
        <?php foreach ($mainPackages as $p):
          $slug   = $p['slug']  ?? '';
          $title  = $p['title'] ?? '';
          $desc   = $p['description'] ?? '';
          $img    = pkgImage($p, $imgFallbacks, $defaultImg);
          $subs   = $childrenOf[$slug] ?? [];
          $days   = (int)($p['duration_days']   ?? 0);
          $nights = (int)($p['duration_nights'] ?? 0);
          $diff   = $p['difficulty_level']      ?? '';
          $dest   = $p['destination']           ?? '';
          $badgeLabel = $dest !== '' ? $dest : $title;

          $cardHighlights = [];
          if (!empty($p['highlights'])) {
              $cardHighlights = array_slice(tpdParseLines($p['highlights']), 0, 4);
          }
        ?>
          <article class="tp-card reveal <?= !empty($subs) ? 'is-highlighted' : '' ?>">
            <div class="tp-media">
              <img src="<?= htmlspecialchars($img) ?>" alt="<?= e($title) ?>" loading="lazy"
                   onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-<?= $defaultImg ?>?auto=format&fit=crop&w=900&q=80';">
              <?php if ($badgeLabel !== ''): ?>
                <span class="tp-badge"><?= e($badgeLabel) ?></span>
              <?php endif; ?>
            </div>
            <div class="tp-body">
              <h3 class="tp-title"><?= e($title) ?></h3>
              <?php if ($desc): ?><p class="tp-desc"><?= e($desc) ?></p><?php endif; ?>

              <?php if (!empty($cardHighlights)): ?>
                <div class="tp-highlights-mini">
                  <?php foreach ($cardHighlights as $h): ?>
                    <span><i class="fas fa-check"></i> <?= e($h) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if ($days > 0 || $diff): ?>
                <div class="tp-meta">
                  <?php if ($days > 0): ?>
                    <span><i class="fa-regular fa-clock"></i> <?= $days ?>D<?= $nights > 0 ? ' / ' . $nights . 'N' : '' ?></span>
                  <?php endif; ?>
                  <?php if ($diff): ?>
                    <span><i class="fa-solid fa-mountain"></i> <?= e(ucfirst($diff)) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="tp-actions">
                <a href="<?= PAGES_URL ?>tour-packages.php?package=<?= urlencode($slug) ?>"
                   class="btn btn-outline btn-xs">View Details</a>
                <a href="<?= PAGES_URL ?>booking.php?package=<?= urlencode($slug) ?>"
                   class="btn btn-primary btn-xs">Book Now</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php endif; ?>

<section class="cta">
  <div class="container">
    <div class="cta-inner reveal">
      <span class="eyebrow eyebrow--light">Custom Trips</span>
      <h2>Want Something Different?</h2>
      <p>Let us design a fully custom itinerary just for you.</p>
      <div class="cta-actions">
        <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Enquire Now</a>
        <a href="<?= PAGES_URL ?>trek.php" class="btn btn-ghost btn-lg">Browse Treks</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
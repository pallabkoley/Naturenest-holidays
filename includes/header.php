<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/config.php';
$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? ($SITE['site_name'] . ' — premium Indian travel & tourism.');

/* ============================================================
   NAV DROPDOWN — Tour Packages (Normal + Offbeat Destinations)
   ============================================================ */
$destinationNormal  = [];
$destinationOffbeat = [];

if (isset($pdo) && $pdo instanceof PDO) {
    // Normal Destinations
    try {
        $stmt = $pdo->query("SELECT title, slug FROM tour_packages WHERE status = 'active' ORDER BY position ASC, id ASC");
        if ($stmt) $destinationNormal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { error_log('nav normal: ' . $e->getMessage()); }

    // Normal Destination Children
    if (!empty($destinationNormal)) {
        try {
            $normalChildStmt = $pdo->prepare("SELECT title, slug FROM tour_package_lists WHERE parent_slug = ? AND status = 'active' AND show_in_menu = 1 AND slug NOT LIKE 'offbeat-%' ORDER BY position ASC, id ASC");
            foreach ($destinationNormal as &$destination) {
                $normalChildStmt->execute([$destination['slug']]);
                $destination['children'] = $normalChildStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($destination['slug'] === 'sikkim' && empty($destination['children'])) {
                    $destination['children'] = [
                        ['title' => 'North Sikkim Himalayan Escape', 'slug' => 'north-sikkim-himalayan-escape'],
                        ['title' => 'South & West Sikkim Highland Escape', 'slug' => 'south-west-sikkim-highland-escape'],
                    ];
                }
            }
            unset($destination);
        } catch (Throwable $e) { error_log('nav normal children: ' . $e->getMessage()); }
    }

    // Offbeat Parents
    try {
        $stmt = $pdo->query("SELECT title, slug FROM tour_package_lists WHERE slug IN ('offbeat-north-bengal', 'offbeat-sikkim') AND status = 'active' AND show_in_menu = 1 ORDER BY position ASC, id ASC");
        if ($stmt) $destinationOffbeat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { error_log('nav offbeat: ' . $e->getMessage()); }

    // Offbeat Children
    if (!empty($destinationOffbeat)) {
        $childStmt = $pdo->prepare("SELECT title, slug FROM tour_package_lists WHERE parent_slug = ? AND status = 'active' AND show_in_menu = 1 ORDER BY position ASC, id ASC");
        foreach ($destinationOffbeat as &$parent) {
            $childStmt->execute([$parent['slug']]);
            $parent['children'] = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($parent);
    }
}

/* Fallback if DB unavailable */
if (empty($destinationNormal)) {
    $destinationNormal = [
        ['title' => 'Sikkim', 'slug' => 'sikkim', 'children' => [
            ['title' => 'North Sikkim Himalayan Escape', 'slug' => 'north-sikkim-himalayan-escape'],
            ['title' => 'South & West Sikkim Highland Escape', 'slug' => 'south-west-sikkim-highland-escape'],
        ]],
        ['title' => 'Darjeeling', 'slug' => 'darjeeling', 'children' => [
            ['title' => 'Darjeeling & Kalimpong Escape', 'slug' => 'darjeeling-kalimpong-escape'],
            ['title' => 'Darjeeling Himalayan Escape', 'slug' => 'darjeeling-himalayan-escape'],
        ]],
        ['title' => 'Himachal Pradesh', 'slug' => 'himachal-pradesh', 'children' => []],
        ['title' => 'Spiti Valley Trip', 'slug' => 'spiti-valley-trip', 'children' => []],
        ['title' => 'Kashmir', 'slug' => 'kashmir', 'children' => []],
        ['title' => 'Bhutan', 'slug' => 'bhutan', 'children' => []],
        ['title' => 'Puri', 'slug' => 'puri', 'children' => []],
        ['title' => 'Nagaland', 'slug' => 'nagaland', 'children' => []],
    ];
}

if (empty($destinationOffbeat)) {
    $destinationOffbeat = [
        ['title' => 'Offbeat North Bengal', 'slug' => 'offbeat-north-bengal', 'children' => [
            ['title' => 'Kolakham', 'slug' => 'kolakham'], ['title' => 'Pedong', 'slug' => 'pedong'],
            ['title' => 'Ramdhura', 'slug' => 'ramdhura'], ['title' => 'Mungpoo', 'slug' => 'mungpoo'],
            ['title' => 'Katergaon', 'slug' => 'katergaon'], ['title' => 'Lamahata', 'slug' => 'lamahata'],
            ['title' => 'Dawaipani', 'slug' => 'dawaipani'],
        ]],
        ['title' => 'Offbeat Sikkim', 'slug' => 'offbeat-sikkim', 'children' => [
            ['title' => 'Dzongu', 'slug' => 'dzongu'], ['title' => 'Borong', 'slug' => 'borong'],
            ['title' => 'Kaluk', 'slug' => 'kaluk'], ['title' => 'Rinchenpong', 'slug' => 'rinchenpong'],
            ['title' => 'Chayatal', 'slug' => 'chayatal'],
        ]],
    ];
}

// Format URLs for JS
foreach ($destinationNormal as &$d) {
    $d['url'] = PAGES_URL . 'tour-packages.php?package=' . urlencode($d['slug']);
    if (!empty($d['children'])) {
        foreach ($d['children'] as &$c) { $c['url'] = PAGES_URL . 'tour-packages.php?package=' . urlencode($c['slug']); }
        unset($c);
    } else { $d['children'] = []; }
}
unset($d);

foreach ($destinationOffbeat as &$o) {
    $o['url'] = PAGES_URL . 'tour-packages.php?package=' . urlencode($o['slug']);
    if (!empty($o['children'])) {
        foreach ($o['children'] as &$c) { $c['url'] = PAGES_URL . 'tour-packages.php?package=' . urlencode($c['slug']); }
        unset($c);
    } else { $o['children'] = []; }
}
unset($o);

$megaMenuJson = json_encode(['normal' => $destinationNormal, 'offbeat' => $destinationOffbeat], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e($pageDescription) ?>">
<meta name="theme-color" content="#F7F3E5">
<title><?= $pageTitle ? e($pageTitle) . ' | ' : '' ?><?= e($SITE['site_name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= ASSET_URL ?>css/style.css">
<link rel="stylesheet" href="<?= ASSET_URL ?>css/responsive.css">

<style>
/* ============================================================
   PALETTE & HEADER BASE (Desktop)
   ============================================================ */
:root{
  --hh-green-deep:  #244B38; --hh-green-text:  #294536; --hh-olive-dark:  #173A2D;
  --hh-ivory:       #F7F3E5; --hh-gold-line:   #C9A04A; --hh-gold-button: #D4A13A;
  --hh-gold-button-hover: #C08E30;
}
.site-header{
  position: fixed !important; top: 0; left: 0; right: 0; width: 100%;
  z-index: 1000; background: #F7F3E5 !important;
  border-bottom: 1px solid #C9A04A !important;
  box-shadow: 0 2px 12px rgba(36,75,56,.06) !important;
}
.site-header.is-scrolled{ box-shadow: 0 4px 20px rgba(36,75,56,.10) !important; }
.site-header::before, .site-header::after{ content: none !important; display: none !important; }

/* Botanical Edge Frame */
.site-header .header-leaf-left, .site-header .header-leaf-right{
  position: absolute; top: 0; bottom: 0; width: 110px; pointer-events: none;
  z-index: 1; background-repeat: no-repeat; background-position: center;
  background-size: contain; overflow: hidden; opacity: .20;
}
.site-header .header-leaf-left{
  left: 0; background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 140 100' preserveAspectRatio='xMinYMid meet'><g fill='%23A8AA8C' stroke='%23A8AA8C' stroke-width='0.5' stroke-linejoin='round'><path d='M8 78 C -2 56 12 34 34 36 C 42 58 32 80 8 78 Z' fill-opacity='0.9'/><path d='M30 66 C 26 48 40 30 58 34 C 62 52 52 70 30 66 Z' fill-opacity='0.85'/><path d='M54 54 C 52 38 66 22 82 26 C 86 42 76 58 54 54 Z' fill-opacity='0.8'/><path d='M78 42 C 76 28 88 16 102 20 C 104 34 96 46 78 42 Z' fill-opacity='0.75'/><path d='M14 88 C 18 76 32 70 46 74 C 46 86 34 94 14 88 Z' fill-opacity='0.7'/><path d='M40 78 C 44 66 58 60 72 64 C 72 76 60 84 40 78 Z' fill-opacity='0.65'/><path d='M66 66 C 70 54 84 48 96 54 C 96 64 86 72 66 66 Z' fill-opacity='0.6'/><path d='M2 66 C 4 50 18 42 32 46' fill='none' stroke-opacity='0.7'/><path d='M28 56 C 30 42 44 34 58 38' fill='none' stroke-opacity='0.7'/><path d='M52 44 C 54 32 68 24 82 28' fill='none' stroke-opacity='0.65'/></g></svg>");
}
.site-header .header-leaf-right{
  right: 0; background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 140 100' preserveAspectRatio='xMaxYMid meet'><g fill='%23A8AA8C' stroke='%23A8AA8C' stroke-width='0.5' stroke-linejoin='round'><path d='M132 78 C 142 56 128 34 106 36 C 98 58 108 80 132 78 Z' fill-opacity='0.9'/><path d='M110 66 C 114 48 100 30 82 34 C 78 52 88 70 110 66 Z' fill-opacity='0.85'/><path d='M86 54 C 88 38 74 22 58 26 C 54 42 64 58 86 54 Z' fill-opacity='0.8'/><path d='M62 42 C 64 28 52 16 38 20 C 36 34 44 46 62 42 Z' fill-opacity='0.75'/><path d='M126 88 C 122 76 108 70 94 74 C 94 86 106 94 126 88 Z' fill-opacity='0.7'/><path d='M100 78 C 96 66 82 60 68 64 C 68 76 80 84 100 78 Z' fill-opacity='0.65'/><path d='M74 66 C 70 54 56 48 44 54 C 44 64 54 72 74 66 Z' fill-opacity='0.6'/><path d='M138 66 C 136 50 122 42 108 46' fill='none' stroke-opacity='0.7'/><path d='M112 56 C 110 42 96 34 82 38' fill='none' stroke-opacity='0.7'/><path d='M88 44 C 86 32 72 24 58 28' fill='none' stroke-opacity='0.65'/></g></svg>");
}

/* Header Inner & Logo */
.site-header .header-inner{
  position: relative; z-index: 2; min-height: 78px; display: flex;
  align-items: center; padding-block: 4px; gap: 20px;
}
.site-header .main-nav{ margin-left: 40px; }
.site-header .nav-list{ gap: 34px; display: flex; align-items: center; }
.site-header .brand{
  background: transparent !important; border-radius: 0 !important; box-shadow: none !important;
  padding: 0 !important; gap: 11px; position: relative; z-index: 3; display: inline-flex; align-items: center;
}
.site-header .brand-logo{ height: 52px; width: auto; display: block; object-fit: contain; }

/* Navigation Links */
.site-header .nav-link{
  color: #294536 !important; font-weight: 500; font-size: .94rem;
  letter-spacing: .002em; padding: 6px 0; transition: color .22s;
  white-space: nowrap; position: relative; display: inline-flex; align-items: center; gap: 5px;
}
.site-header .nav-link:hover{ color: #B98B32 !important; }
.site-header .nav-link::after{
  content: ''; position: absolute; left: 0; bottom: 0; width: 0; height: 2px;
  background: #C9A04A !important; border-radius: 2px; transition: width .3s;
}
.site-header .nav-link:hover::after, .site-header .nav-link.is-active::after { width: 100%; }
.site-header .nav-link.is-active{ color: #244B38 !important; font-weight: 600; }
.site-header .nav-link .caret{ color: currentColor; margin-left: 2px; width: 12px; height: 12px; flex-shrink: 0; }
.site-header .nav-toggle span{ background: #294536; transition: background .2s; }
.site-header .nav-toggle:hover span{ background: #C9A04A; }

/* Mega Menu Desktop */
.site-header .has-mega-menu { position: relative; }
.site-header .mega-menu-wrapper {
  position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
  width: 900px; max-width: calc(100vw - 30px); z-index: 1050; display: none; padding-top: 15px;
}
.site-header .has-mega-menu:hover .mega-menu-wrapper,
.site-header .has-mega-menu.is-open .mega-menu-wrapper { display: block; }
.site-header .mega-menu {
  background: #FAF7ED; border: 1px solid #E3D5A8; border-radius: 14px;
  box-shadow: 0 18px 40px rgba(36,75,56,.14); display: flex; max-height: 70vh; overflow-y: auto; overflow-x: hidden;
}
.site-header .mega-menu-left {
  width: 35%; background: #F7F3E5; border-right: 1px solid #E3D5A8;
  padding: 20px 15px; border-radius: 14px 0 0 14px; flex-shrink: 0;
}
.site-header .menu-section-heading {
  color: #8A8068; font-size: .68rem; font-weight: 700; letter-spacing: .10em;
  text-transform: uppercase; padding: 10px 15px 5px; pointer-events: none;
}
.site-header .offbeat-heading { margin-top: 15px; border-top: 1px solid #E3D5A8; padding-top: 15px; }
.site-header .mega-left-item { list-style: none; margin-bottom: 2px; }
.site-header .mega-left-item a {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 15px; color: #294536; font-size: .9rem; font-weight: 500;
  border-radius: 8px; text-decoration: none; transition: all 0.2s ease;
}
.site-header .mega-left-item a:hover, .site-header .mega-left-item a.active {
  background: #F0EAD4; color: #244B38; padding-left: 20px;
}
.site-header .mega-menu-right {
  width: 65%; padding: 25px; display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; align-content: start;
}
.site-header .mega-right-link {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 18px; color: #294536; font-size: .9rem; font-weight: 500;
  text-decoration: none; border-radius: 8px; border: 1px solid transparent;
  background: #F7F3E5; transition: all 0.2s ease;
}
.site-header .mega-right-link:hover {
  background: #FAF7ED; border-color: #E3D5A8; color: #244B38; box-shadow: 0 4px 10px rgba(36,75,56,.05);
}
.site-header .mega-empty { grid-column: 1 / -1; color: #8A8068; font-size: .9rem; font-style: italic; padding: 20px; text-align: center; }

/* Buttons */
.site-header .btn-primary{
  background: #D4A13A !important; color: #173A2D !important; font-weight: 600 !important;
  border: none !important; border-radius: 999px !important; padding: 14px 28px !important;
  font-size: .94rem !important; line-height: 1; min-height: 52px; align-items: center;
  gap: 8px; position: relative; z-index: 3; box-shadow: 0 4px 12px rgba(80,60,20,.10) !important;
  transition: background .25s, transform .25s, box-shadow .25s;
}
.site-header .btn-primary:hover{ background: #C08E30 !important; transform: translateY(-1px); }
.site-header .btn-primary::after{ content: '\2192'; font-size: .95em; margin-left: 6px; opacity: .9; }

/* Book Now Visibility */
@media (min-width: 992px){ .site-header .nav-cta{ display: none !important; } .site-header .header-cta{ display: inline-flex !important; } }
@media (max-width: 991px){ .site-header .header-cta{ display: none !important; } .site-header .nav-cta{ display: inline-flex !important; } }

body{ padding-top: 78px; }
@media (max-width: 991px){ body{ padding-top: 70px; } }
@media (max-width: 767px){ body{ padding-top: 62px; } }

/* ============================================================
   MOBILE MENU CSS
   ============================================================ */
@media (max-width: 991px){
  .site-header .nav-toggle {
    display: flex !important;
    position: relative !important;
    z-index: 999999 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
  }

  .site-header .main-nav {
    position: fixed !important;
    top: 0 !important;
    right: -100% !important;
    width: 100% !important;
    max-width: 320px !important;
    height: 100vh !important;
    background: #FAF7ED !important;
    z-index: 99999 !important;
    transition: right 0.4s ease !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 90px 24px 40px !important;
    box-shadow: -14px 0 44px rgba(36,75,56,.14) !important;
    border-left: 1px solid #E3D5A8 !important;
    overflow-y: auto !important;
    margin-left: 0 !important;
    transform: none !important;
  }

  .site-header .main-nav.is-open {
    right: 0 !important;
  }

  .site-header .nav-list { flex-direction: column !important; align-items: stretch !important; gap: 0 !important; width: 100% !important; }
  .site-header .nav-list li { border-bottom: 1px solid #EFE5C7 !important; }
  .site-header .nav-link {
    display: flex !important; justify-content: space-between !important;
    width: 100% !important; padding: 17px 2px !important; font-size: 1rem !important;
    color: #294536 !important;
  }
  .site-header .nav-link::after { display: none !important; }

  /* মেগা মেনু মোবাইল ভিউ */
  .site-header .mega-menu-wrapper {
    position: static !important; transform: none !important;
    width: 100% !important; max-width: 100% !important; padding-top: 0 !important;
    display: none !important; box-shadow: none !important;
    border: none !important; border-radius: 0 !important; background: transparent !important;
  }
  .site-header .has-mega-menu.is-open .mega-menu-wrapper { display: block !important; }
  .site-header .mega-menu { flex-direction: column !important; max-height: none !important; border: none !important; background: transparent !important; box-shadow: none !important; }
  .site-header .mega-menu-left { width: 100% !important; border-right: none !important; padding: 0 !important; background: transparent !important; }
  .site-header .mega-left-item a { padding: 12px 15px !important; border-bottom: 1px solid #EFE5C7 !important; border-radius: 0 !important; color: #294536 !important; }
  .site-header .mega-menu-right { display: none !important; padding: 0 0 0 15px !important; background: #F5EFDE !important; }
  .site-header .mega-left-item.is-open .mega-menu-right { display: grid !important; grid-template-columns: 1fr !important; }
  .site-header .mega-right-link { background: transparent !important; border: none !important; border-bottom: 1px solid #EFE5C7 !important; border-radius: 0 !important; padding: 12px 15px !important; color: #294536 !important; }

  /* Backdrop sits BELOW the site-header (1000) so it never covers the drawer */
  .nav-backdrop { z-index: 900 !important; }
  body.no-scroll { overflow: hidden !important; }
}
</style>
</head>
<body>

<header class="site-header" id="siteHeader">
  <span class="header-leaf-left" aria-hidden="true"></span>
  <span class="header-leaf-right" aria-hidden="true"></span>

  <div class="container header-inner">
    <a href="<?= BASE_URL ?>index.php" class="brand" aria-label="Nature Nest Holidays — Home">
      <img src="<?= ASSET_URL ?>images/logo/nature-logo.png" alt="Nature Nest Holidays" class="brand-logo">
    </a>

    <nav class="main-nav" id="mainNav" aria-label="Primary">
      <ul class="nav-list">
        <li><a class="nav-link<?= nav_active('about', $currentPage) ?>" href="<?= PAGES_URL ?>about.php">About Us</a></li>

        <li class="has-dropdown has-mega-menu">
          <a class="nav-link<?= nav_active('tour-packages', $currentPage) ?>" href="<?= PAGES_URL ?>tour-packages.php">
            Tour Packages
            <svg class="caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
          </a>
          <div class="mega-menu-wrapper">
            <div class="mega-menu">
              <div class="mega-menu-left" id="megaMenuLeft"></div>
              <div class="mega-menu-right" id="megaMenuRight">
                <div class="mega-empty">Select a destination to view packages</div>
              </div>
            </div>
          </div>
        </li>

        <li><a class="nav-link<?= nav_active('corporate-tours', $currentPage) ?>" href="<?= PAGES_URL ?>corporate-tours.php">Corporate Tours</a></li>
        <li><a class="nav-link<?= nav_active('destination-wedding', $currentPage) ?>" href="<?= PAGES_URL ?>destination-wedding.php">Destination Wedding</a></li>
        <li><a class="nav-link<?= nav_active('trek', $currentPage) ?>" href="<?= PAGES_URL ?>trek.php">Trekking</a></li>
        <li><a class="nav-link<?= nav_active('contact', $currentPage) ?>" href="<?= PAGES_URL ?>contact.php">Contact Us</a></li>
      </ul>
      <a href="<?= PAGES_URL ?>booking.php" class="btn btn-primary nav-cta">Book Now</a>
    </nav>

    <a href="<?= PAGES_URL ?>booking.php" class="btn btn-primary btn-sm header-cta">Book Now</a>

    <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<div class="nav-backdrop" id="navBackdrop" hidden></div>

<script>
/* ============================================================
   MEGA MENU CONTENT LOADER
   NOTE: Mobile drawer open/close, hamburger toggle, backdrop click,
   Escape key, and resize reset all live in assets/js/main.js.
   This inline script ONLY builds the mega-menu content.
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

  const megaData = <?php echo $megaMenuJson; ?>;

  const leftCol  = document.getElementById('megaMenuLeft');
  const rightCol = document.getElementById('megaMenuRight');

  if (!leftCol || !rightCol || !megaData) return;

  leftCol.innerHTML = '';

  function createLeftItem(item) {
    const div = document.createElement('div');
    div.className = 'mega-left-item';

    const hasChildren = item.children && item.children.length > 0;
    const arrow = hasChildren ? ' <i class="fas fa-chevron-right"></i>' : '';

    div.innerHTML = `<a href="${item.url}" class="mega-link" data-slug="${item.slug}">
                       <span>${item.title}</span>${arrow}
                     </a>`;

    if (window.innerWidth > 991) {
      /* Desktop: hover swaps the right column */
      div.addEventListener('mouseenter', () => {
        document.querySelectorAll('.mega-left-item a').forEach(a => a.classList.remove('active'));
        div.querySelector('a').classList.add('active');
        updateRightColumn(item.children || []);
      });
    } else {
      /* Mobile: tap expands this item's children inline */
      div.querySelector('a').addEventListener('click', (e) => {
        if (!hasChildren) return;   /* leaf → let it navigate */
        e.preventDefault();
        e.stopPropagation();         /* do NOT bubble to any drawer handler */
        div.classList.toggle('is-open');
      });
    }
    return div;
  }

  function updateRightColumn(children) {
    rightCol.innerHTML = '';
    if (!children || children.length === 0) {
      rightCol.innerHTML = '<div class="mega-empty">Select a destination to view packages</div>';
      return;
    }
    children.forEach(child => {
      const a = document.createElement('a');
      a.href = child.url;
      a.className = 'mega-right-link';
      a.innerHTML = `<span>${child.title}</span>`;
      rightCol.appendChild(a);
    });
  }

  if (megaData.normal) {
    const h = document.createElement('div');
    h.className = 'menu-section-heading';
    h.textContent = 'Normal Destinations';
    leftCol.appendChild(h);
    megaData.normal.forEach(item => leftCol.appendChild(createLeftItem(item)));
  }

  if (megaData.offbeat) {
    const h = document.createElement('div');
    h.className = 'menu-section-heading offbeat-heading';
    h.textContent = 'Offbeat Destinations';
    leftCol.appendChild(h);
    megaData.offbeat.forEach(item => leftCol.appendChild(createLeftItem(item)));
  }
});
</script>

<main>
<?php
/* ============================================================
   DEBUG (production e comment out korun)
   ============================================================ */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ============================================================
   INCLUDES
   ============================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

/* ============================================================
   DATABASE
   ============================================================ */
$db = new Database();
$conn = $db->getConnection();

/* ============================================================
   HELPERS
   ============================================================ */
if (!function_exists('e')) {
    function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('plain_text')) {
    function plain_text($str, $len = 160) {
        $str = trim(strip_tags($str ?? ''));
        return mb_strlen($str) <= $len ? $str : mb_substr($str, 0, $len) . '…';
    }
}
if (!defined('PAGES_URL')) define('PAGES_URL', '');

/* ============================================================
   FETCH FESTIVAL BY SLUG
   ============================================================ */
$slug = trim($_GET['slug'] ?? '');
$festival = null;

if ($slug !== '') {
    $stmt = $conn->prepare("SELECT * FROM festivals WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $festival = $res->fetch_assoc();
    }
    $stmt->close();
}

/* ============================================================
   PAGE META — header er AGE
   ============================================================ */
if ($festival) {
    $pageTitle = $festival['meta_title'] ?: ($festival['name'] . ' - Nature Nest Holidays');
    $pageDescription = $festival['meta_description'] ?: plain_text($festival['description'] ?? '', 160);
} else {
    $pageTitle = 'Festival Not Found - Nature Nest Holidays';
    $pageDescription = 'The festival you are looking for does not exist.';
}

/* ============================================================
   HEADER
   ============================================================ */
require_once __DIR__ . '/../includes/header.php';

/* ============================================================
   404 — Not Found
   ============================================================ */
if (!$festival) {
    ?>
    <section class="section" style="padding:120px 0;text-align:center;">
        <div class="container">
            <div style="font-size:4rem;color:#D4A13A;margin-bottom:20px;">🪶</div>
            <h1 style="font-family:'Playfair Display',serif;color:#244B38;font-size:2.6rem;margin-bottom:16px;">
                Festival Not Found
            </h1>
            <p style="color:#6b7a70;margin-bottom:32px;font-size:1.05rem;">
                The festival you are looking for doesn't exist or is no longer available.
            </p>
            <a href="<?= PAGES_URL ?>festival.php" class="btn btn-primary btn-lg">Back to All Festivals</a>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

/* ============================================================
   PREPARE DATA
   ============================================================ */
$heroImg = !empty($festival['featured_image']) ? '../' . ltrim($festival['featured_image'], '/') : '';

$gallery = [];
if (!empty($festival['gallery_images'])) {
    $decoded = json_decode($festival['gallery_images'], true);
    if (is_array($decoded)) $gallery = $decoded;
}

$highlights = !empty($festival['highlights'])
    ? array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $festival['highlights'])))
    : [];

$inclusions = !empty($festival['inclusions'])
    ? array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $festival['inclusions'])))
    : [];

$exclusions = !empty($festival['exclusions'])
    ? array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $festival['exclusions'])))
    : [];

$itinerary = [];
if (!empty($festival['itinerary'])) {
    $decoded = json_decode($festival['itinerary'], true);
    if (is_array($decoded)) {
        $itinerary = $decoded;
    }
}

/* Booking + Enquiry URLs */
$bookingUrl = PAGES_URL . 'booking.php?festival=' . urlencode($festival['slug']);
$enquiryUrl = PAGES_URL . 'contact.php?festival=' . urlencode($festival['slug']) . '&subject=' . urlencode($festival['name']);
?>

<style>
/* ============================================================
   FESTIVAL DETAIL PAGE
   ============================================================ */
.fd-hero {
    position: relative;
    min-height: 560px;
    display: flex;
    align-items: flex-end;
    color: #fff;
    padding: 90px 0 60px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: #244B38;
}
.fd-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,.15) 0%, rgba(0,0,0,.78) 100%);
}
.fd-hero .container { position: relative; z-index: 2; }
.fd-hero-inner { max-width: 820px; }
.fd-eyebrow {
    color: #D4A13A; font-weight: 600; letter-spacing: .18em;
    font-size: .78rem; text-transform: uppercase;
    display: inline-block; margin-bottom: 14px;
}
.fd-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3rem; line-height: 1.15;
    margin: 0 0 18px; color: #fff;
}
.fd-hero-meta {
    display: flex; flex-wrap: wrap; gap: 20px;
    font-size: 0.95rem; color: rgba(255,255,255,.95);
    margin-bottom: 26px;
}
.fd-hero-meta span { display: inline-flex; align-items: center; gap: 7px; }
.fd-hero-meta svg { color: #D4A13A; flex-shrink: 0; }

.fd-hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
.fd-hero-actions .btn-ghost {
    background: transparent;
    border: 1.5px solid rgba(255,255,255,.7);
    color: #fff;
}
.fd-hero-actions .btn-ghost:hover {
    background: #fff; color: #244B38; border-color: #fff;
}

/* Layout */
.fd-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 40px;
    padding: 60px 0;
}
@media (max-width: 991px) {
    .fd-layout { grid-template-columns: 1fr; gap: 30px; padding: 40px 0; }
}

/* Back link */
.fd-back {
    display: inline-flex; align-items: center; gap: 8px;
    color: #244B38; font-size: 0.92rem;
    text-decoration: none; margin: 30px 0 0;
    font-weight: 500;
}
.fd-back:hover { color: #D4A13A; }

/* Main cards */
.fd-card {
    background: #fff; border-radius: 16px; padding: 32px;
    box-shadow: 0 4px 20px rgba(36, 75, 56, 0.07);
    margin-bottom: 26px;
}
.fd-card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.7rem; color: #244B38;
    margin: 0 0 20px;
    display: flex; align-items: center; gap: 12px;
    padding-bottom: 14px; border-bottom: 1px solid #f0ece1;
}
.fd-card h2 svg { color: #D4A13A; flex-shrink: 0; }
.fd-card p, .fd-card li { color: #4a5a51; line-height: 1.85; font-size: 0.98rem; }

.fd-overview p { margin: 0 0 14px; }
.fd-overview p:last-child { margin-bottom: 0; }

/* Highlights */
.fd-highlights {
    list-style: none; padding: 0; margin: 0;
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
.fd-highlights li {
    padding: 12px 16px;
    background: #F7F3E5;
    border-left: 3px solid #D4A13A;
    border-radius: 6px;
    font-size: 0.93rem;
    display: flex; align-items: flex-start; gap: 10px;
    line-height: 1.5;
}
.fd-highlights li::before {
    content: "★"; color: #D4A13A; font-size: 0.9rem;
    flex-shrink: 0; margin-top: 2px;
}
@media (max-width: 600px) { .fd-highlights { grid-template-columns: 1fr; } }

/* Itinerary */
.fd-itinerary { position: relative; padding-left: 10px; }
.fd-itinerary-item {
    position: relative;
    padding: 0 0 30px 50px;
    border-left: 2px solid #EAE0C8;
}
.fd-itinerary-item:last-child { border-left-color: transparent; padding-bottom: 0; }
.fd-itinerary-item::before {
    content: attr(data-day);
    position: absolute;
    left: -20px; top: 0;
    width: 38px; height: 38px;
    background: #D4A13A; color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.72rem; font-weight: 700;
    box-shadow: 0 2px 8px rgba(212,161,58,.35);
    z-index: 1;
}
.fd-itinerary-item h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.18rem; color: #244B38;
    margin: 6px 0 10px;
    line-height: 1.4;
}
.fd-itinerary-item p {
    font-size: 0.95rem; line-height: 1.8;
    color: #4a5a51; margin: 0;
    white-space: pre-line;
}

/* Inclusions / Exclusions */
.fd-inc-exc { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 600px) { .fd-inc-exc { grid-template-columns: 1fr; gap: 16px; } }
.fd-inc-exc h3 {
    font-size: 1rem; margin: 0 0 12px; padding-bottom: 8px;
    border-bottom: 2px solid;
}
.fd-inc-exc .inc h3 { color: #28a745; border-color: #28a745; }
.fd-inc-exc .exc h3 { color: #dc3545; border-color: #dc3545; }
.fd-inc-exc ul { list-style: none; padding: 0; margin: 0; }
.fd-inc-exc li {
    padding: 8px 0 8px 26px;
    position: relative;
    font-size: 0.92rem;
    line-height: 1.6;
    border-bottom: 1px dashed #f0ece1;
}
.fd-inc-exc li:last-child { border-bottom: none; }
.fd-inc-exc li::before {
    position: absolute; left: 0; top: 8px;
    font-weight: 700; font-size: 0.9rem;
}
.fd-inc-exc .inc li::before { content: "✓"; color: #28a745; }
.fd-inc-exc .exc li::before { content: "✕"; color: #dc3545; }

/* Gallery */
.fd-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}
.fd-gallery img {
    width: 100%; height: 140px; object-fit: cover;
    border-radius: 10px; cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.fd-gallery img:hover {
    transform: scale(1.04);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Notes */
.fd-note {
    background: #F7F3E5;
    border-left: 4px solid #D4A13A;
    padding: 16px 20px;
    border-radius: 8px;
    margin: 14px 0;
    font-size: 0.94rem;
    line-height: 1.7;
    color: #4a5a51;
}
.fd-note:last-child { margin-bottom: 0; }
.fd-note h4 {
    color: #244B38; font-size: 1rem;
    margin: 0 0 8px; font-weight: 600;
}
.fd-note p { margin: 0; white-space: pre-line; }

/* Sidebar */
.fd-sidebar { position: sticky; top: 20px; align-self: start; }

.fd-book-card {
    background: linear-gradient(135deg, #244B38 0%, #173A2D 100%);
    color: #fff; border-radius: 16px;
    padding: 30px; margin-bottom: 20px;
    box-shadow: 0 8px 24px rgba(36,75,56,0.2);
}
.fd-book-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem; color: #fff; margin: 0 0 10px;
}
.fd-book-card p {
    color: rgba(255,255,255,.88); font-size: 0.93rem;
    margin: 0 0 22px; line-height: 1.7;
}
.fd-book-card .btn {
    width: 100%; text-align: center;
    padding: 13px 20px;
    display: block;
    margin-bottom: 10px;
}
.fd-book-card .btn:last-child { margin-bottom: 0; }

/* Ghost button for sidebar (secondary) */
.btn-ghost-light {
    background: transparent !important;
    color: #fff !important;
    border: 1.5px solid rgba(255,255,255,.7) !important;
}
.btn-ghost-light:hover {
    background: #fff !important;
    color: #244B38 !important;
    border-color: #fff !important;
}

/* Quick facts */
.fd-info-list { list-style: none; padding: 0; margin: 0; }
.fd-info-list li {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 13px 0;
    border-bottom: 1px dashed #f0ece1;
    font-size: 0.92rem;
    gap: 12px;
}
.fd-info-list li:last-child { border-bottom: none; }
.fd-info-list .label { color: #6b7a70; flex-shrink: 0; }
.fd-info-list .value { color: #244B38; font-weight: 600; text-align: right; }

/* Related CTA */
.fd-related-cta {
    background: #F7F3E5; border-radius: 16px;
    padding: 24px; text-align: center;
    border: 1px dashed #D4A13A;
}
.fd-related-cta h4 {
    font-family: 'Playfair Display', serif;
    color: #244B38; margin: 0 0 8px; font-size: 1.15rem;
}
.fd-related-cta p {
    color: #6b7a70; font-size: 0.88rem;
    margin: 0 0 16px; line-height: 1.6;
}

/* Lightbox */
.fd-lightbox {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.92);
    display: none; align-items: center; justify-content: center;
    z-index: 9999; cursor: pointer; padding: 20px;
}
.fd-lightbox.active { display: flex; }
.fd-lightbox img {
    max-width: 95vw; max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
}

@media (max-width: 767px) {
    .fd-hero { min-height: 440px; padding: 70px 0 44px; }
    .fd-hero h1 { font-size: 2rem; }
    .fd-hero-meta { gap: 12px; font-size: 0.85rem; }
    .fd-card { padding: 24px 20px; }
    .fd-card h2 { font-size: 1.4rem; }
    .fd-itinerary-item { padding-left: 44px; }
}
</style>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="fd-hero" style="<?= $heroImg ? "background-image: url('" . e($heroImg) . "');" : '' ?>">
    <div class="container">
        <div class="fd-hero-inner">
            <span class="fd-eyebrow">Festival Experience</span>
            <h1><?= e($festival['name']) ?></h1>

            <div class="fd-hero-meta">
                <?php if (!empty($festival['location'])): ?>
                    <span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        <?= e($festival['location']) ?><?= !empty($festival['state']) ? ', ' . e($festival['state']) : '' ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($festival['duration'])): ?>
                    <span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <?= e($festival['duration']) ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($festival['festival_date'])): ?>
                    <span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <?= e($festival['festival_date']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="fd-hero-actions">
                <a href="<?= $bookingUrl ?>" class="btn btn-primary btn-lg">
                    Book Now
                </a>
                <a href="<?= $enquiryUrl ?>" class="btn btn-ghost btn-lg">
                    Enquiry Now
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     BODY
     ============================================================ -->
<section>
    <div class="container">
        <a href="<?= PAGES_URL ?>festival.php" class="fd-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Back to All Festivals
        </a>

        <div class="fd-layout">

            <!-- ============================================
                 MAIN CONTENT
                 ============================================ -->
            <div>

                <!-- Overview -->
                <?php if (!empty($festival['description'])): ?>
                <div class="fd-card fd-overview">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        Overview
                    </h2>
                    <?= nl2br(e($festival['description'])) ?>
                </div>
                <?php endif; ?>

                <!-- Highlights -->
                <?php if (!empty($highlights)): ?>
                <div class="fd-card">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9 12 2"/></svg>
                        Highlights
                    </h2>
                    <ul class="fd-highlights">
                        <?php foreach ($highlights as $h): ?>
                            <li><?= e($h) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Itinerary -->
                <?php if (!empty($itinerary)): ?>
                <div class="fd-card">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7l6-3 5.447 2.724A1 1 0 0121 7.618v10.764a1 1 0 01-1.447.894L15 17l-6 3z"/><path d="M9 7v13M15 4v13"/></svg>
                        Itinerary
                    </h2>
                    <div class="fd-itinerary">
                        <?php foreach ($itinerary as $i => $item): ?>
                            <div class="fd-itinerary-item" data-day="Day <?= e($item['day'] ?? ($i + 1)) ?>">
                                <h3><?= e($item['title'] ?? '') ?></h3>
                                <?php if (!empty($item['content'])): ?>
                                    <p><?= e($item['content']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Inclusions / Exclusions -->
                <?php if (!empty($inclusions) || !empty($exclusions)): ?>
                <div class="fd-card">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                        What's Included
                    </h2>
                    <div class="fd-inc-exc">
                        <?php if (!empty($inclusions)): ?>
                        <div class="inc">
                            <h3>✓ Inclusions</h3>
                            <ul>
                                <?php foreach ($inclusions as $inc): ?>
                                    <li><?= e($inc) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($exclusions)): ?>
                        <div class="exc">
                            <h3>✕ Exclusions</h3>
                            <ul>
                                <?php foreach ($exclusions as $exc): ?>
                                    <li><?= e($exc) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Gallery -->
                <?php if (!empty($gallery)): ?>
                <div class="fd-card">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        Gallery
                    </h2>
                    <div class="fd-gallery">
                        <?php foreach ($gallery as $img): ?>
                            <img src="../<?= e(ltrim($img, '/')) ?>"
                                 alt="<?= e($festival['name']) ?> gallery"
                                 loading="lazy"
                                 onclick="openLightbox('../<?= e(ltrim($img, '/')) ?>')">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Important Info -->
                <?php if (!empty($festival['important_notes']) || !empty($festival['permit_info']) || !empty($festival['cancellation_policy'])): ?>
                <div class="fd-card">
                    <h2>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                        Important Information
                    </h2>

                    <?php if (!empty($festival['important_notes'])): ?>
                        <div class="fd-note">
                            <h4>📌 Important Notes</h4>
                            <p><?= e($festival['important_notes']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($festival['permit_info'])): ?>
                        <div class="fd-note">
                            <h4>🪪 Permit Information</h4>
                            <p><?= e($festival['permit_info']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($festival['cancellation_policy'])): ?>
                        <div class="fd-note">
                            <h4>📄 Cancellation Policy</h4>
                            <p><?= e($festival['cancellation_policy']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>

            <!-- ============================================
                 SIDEBAR
                 ============================================ -->
            <aside class="fd-sidebar">

                <!-- Book + Enquiry Card -->
                <div class="fd-book-card">
                    <h3>Ready to Experience?</h3>
                    <p>Book your slot or send an enquiry — we'll craft your festival journey with handpicked stays and front-row access.</p>

                    <a href="<?= $bookingUrl ?>" class="btn btn-primary">
                        Book Now
                    </a>
                    <a href="<?= $enquiryUrl ?>" class="btn btn-ghost-light">
                        Enquiry Now
                    </a>
                </div>

                <!-- Quick Facts -->
                <?php
                $hasFacts = !empty($festival['duration'])
                    || !empty($festival['festival_date'])
                    || !empty($festival['best_time'])
                    || !empty($festival['starting_point'])
                    || !empty($festival['location'])
                    || !empty($festival['state']);
                ?>
                <?php if ($hasFacts): ?>
                <div class="fd-card" style="padding:26px;">
                    <h2 style="font-size:1.25rem; margin-bottom: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        Quick Facts
                    </h2>
                    <ul class="fd-info-list">
                        <?php if (!empty($festival['duration'])): ?>
                            <li><span class="label">Duration</span><span class="value"><?= e($festival['duration']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($festival['festival_date'])): ?>
                            <li><span class="label">Festival Date</span><span class="value"><?= e($festival['festival_date']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($festival['best_time'])): ?>
                            <li><span class="label">Best Time</span><span class="value"><?= e($festival['best_time']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($festival['starting_point'])): ?>
                            <li><span class="label">Starting Point</span><span class="value"><?= e($festival['starting_point']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($festival['location'])): ?>
                            <li><span class="label">Location</span><span class="value"><?= e($festival['location']) ?></span></li>
                        <?php endif; ?>
                        <?php if (!empty($festival['state'])): ?>
                            <li><span class="label">State</span><span class="value"><?= e($festival['state']) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Related CTA -->
                <div class="fd-related-cta">
                    <h4>Explore More Festivals</h4>
                    <p>Discover India's most vibrant celebrations curated by our experts.</p>
                    <a href="<?= PAGES_URL ?>festival.php" class="btn btn-outline btn-sm">View All Festivals</a>
                </div>

            </aside>
        </div>
    </div>
</section>

<!-- ============================================================
     LIGHTBOX
     ============================================================ -->
<div class="fd-lightbox" id="fdLightbox" onclick="closeLightbox()">
    <img src="" alt="Gallery" id="fdLightboxImg">
</div>

<script>
function openLightbox(src) {
    document.getElementById('fdLightboxImg').src = src;
    document.getElementById('fdLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('fdLightbox').classList.remove('active');
    document.getElementById('fdLightboxImg').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
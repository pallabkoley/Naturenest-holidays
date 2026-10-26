<?php
/* ============================================================
   DEBUG — Production e comment out korun
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
   DATABASE CONNECTION — Explicit instantiate
   ============================================================ */
$db = new Database();
$conn = $db->getConnection();

/* ============================================================
   HELPERS
   ============================================================ */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('plain_text')) {
    function plain_text($str, $len = 160) {
        $str = strip_tags($str ?? '');
        $str = trim($str);
        if (mb_strlen($str) <= $len) return $str;
        return mb_substr($str, 0, $len) . '…';
    }
}
if (!defined('PAGES_URL')) {
    define('PAGES_URL', '');
}

/* ============================================================
   FETCH FESTIVALS
   ============================================================ */
$festivals = [];
$fetch_error = '';

try {
    $result = $conn->query("
        SELECT id, name, slug, description, featured_image, location, state,
               duration, festival_date, best_time, price, featured
        FROM festivals
        WHERE status = 'active'
        ORDER BY display_order ASC, created_at DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $festivals[] = $row;
        }
    } else {
        $fetch_error = 'Query failed: ' . $conn->error;
    }
} catch (Exception $e) {
    $fetch_error = $e->getMessage();
}

/* ============================================================
   PAGE META — Header er AGE
   ============================================================ */
$pageTitle = 'Festivals of India';
$pageDescription = 'Experience the vibrant colours, ancient traditions and joyous celebrations of India\'s most spectacular festivals with Nature Nest Holidays.';

/* ============================================================
   HEADER (output shuru)
   ============================================================ */
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ============================================================
   FESTIVAL PAGE STYLES
   ============================================================ */
.festival-hero {
    position: relative;
    min-height: 520px;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, rgba(36,75,56,.75) 0%, rgba(23,58,45,.55) 100%),
                url('https://images.unsplash.com/photo-1532372320572-cda25653a26d?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
    color: #fff;
    padding: 100px 0 80px;
}
.festival-hero-inner { max-width: 720px; }
.festival-hero .eyebrow {
    color: #D4A13A; font-weight: 600; letter-spacing: .18em;
    font-size: .78rem; text-transform: uppercase;
    display: inline-block; margin-bottom: 16px;
}
.festival-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3.4rem; line-height: 1.15;
    margin: 0 0 20px; color: #fff;
}
.festival-hero p {
    font-size: 1.12rem; line-height: 1.7;
    color: rgba(255,255,255,.92);
    max-width: 620px; margin-bottom: 30px;
}
.festival-hero .hero-actions { display: flex; flex-wrap: wrap; gap: 14px; }
.festival-hero .btn-ghost {
    background: transparent;
    border: 1.5px solid rgba(255,255,255,.65);
    color: #fff;
}
.festival-hero .btn-ghost:hover { background: #fff; color: #244B38; border-color: #fff; }

.festival-intro { max-width: 780px; margin: 0 auto; text-align: center; }
.festival-intro p { font-size: 1.05rem; line-height: 1.8; color: #4a5a51; }

/* Empty State */
.festival-empty { text-align: center; padding: 60px 20px; max-width: 620px; margin: 0 auto; }
.festival-empty-icon {
    width: 80px; height: 80px; margin: 0 auto 24px;
    border-radius: 50%; background: #F7F3E5; border: 2px dashed #D4A13A;
    display: flex; align-items: center; justify-content: center; color: #B98B32;
}
.festival-empty h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem; color: #244B38; margin: 0 0 12px;
}
.festival-empty p { font-size: 1rem; line-height: 1.7; color: #6b7a70; margin: 0 0 26px; }

/* Grid */
.festival-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 28px;
    margin-top: 40px;
}
.festival-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(36, 75, 56, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex; flex-direction: column;
}
.festival-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(36, 75, 56, 0.15);
}
.festival-media {
    position: relative; width: 100%; height: 220px;
    overflow: hidden; background: #F7F3E5;
}
.festival-media img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.5s ease;
}
.festival-card:hover .festival-media img { transform: scale(1.06); }
.festival-media .no-image {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: #B98B32; font-size: 2.5rem;
    background: linear-gradient(135deg, #F7F3E5, #EAE0C8);
}
.festival-tag {
    position: absolute; top: 14px; left: 14px;
    background: #D4A13A; color: #fff;
    padding: 6px 14px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.festival-featured-badge {
    position: absolute; top: 14px; right: 14px;
    background: #fff; color: #B98B32;
    padding: 6px 10px; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.festival-body { padding: 22px 22px 24px; display: flex; flex-direction: column; flex: 1; }
.festival-body h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.35rem; color: #244B38;
    margin: 0 0 12px; line-height: 1.3;
}
.festival-meta {
    display: flex; flex-wrap: wrap; gap: 14px;
    margin-bottom: 14px; font-size: 0.85rem; color: #6b7a70;
}
.festival-meta span { display: inline-flex; align-items: center; gap: 5px; }
.festival-meta svg { color: #D4A13A; }
.festival-desc {
    font-size: 0.92rem; line-height: 1.65; color: #4a5a51;
    margin: 0 0 18px; flex: 1;
}
.festival-actions { display: flex; gap: 10px; margin-top: auto; }
.festival-actions .btn { flex: 1; text-align: center; }
.btn-xs { padding: 9px 14px; font-size: 0.82rem; border-radius: 8px; }

.festival-cta {
    background: linear-gradient(135deg, #244B38 0%, #173A2D 100%);
    color: #fff; border-radius: 20px; padding: 50px 40px;
    text-align: center; margin: 60px 0 0;
}
.festival-cta h3 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem; color: #fff; margin: 0 0 12px;
}
.festival-cta p {
    color: rgba(255,255,255,.85); max-width: 600px;
    margin: 0 auto 26px; font-size: 1rem; line-height: 1.7;
}

.festival-debug {
    background: #fff3cd; border: 1px solid #ffc107;
    color: #856404; padding: 12px 18px; border-radius: 8px;
    margin: 20px auto; max-width: 900px; font-size: 0.9rem;
}

@media (max-width: 991px) {
    .festival-hero { min-height: 420px; padding: 80px 0 60px; }
    .festival-hero h1 { font-size: 2.5rem; }
    .festival-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px; }
}
@media (max-width: 767px) {
    .festival-hero { min-height: 360px; padding: 70px 0 50px; }
    .festival-hero h1 { font-size: 2rem; }
    .festival-cta { padding: 36px 24px; }
    .festival-cta h3 { font-size: 1.5rem; }
    .festival-grid { grid-template-columns: 1fr; gap: 20px; }
    .festival-media { height: 200px; }
}
</style>

<!-- ============================================================
     DEBUG (chaile delete korun)
     ============================================================ -->
<?php if ($fetch_error): ?>
    <div class="festival-debug">
        <strong>⚠ Debug:</strong> <?= e($fetch_error) ?>
    </div>
<?php endif; ?>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="festival-hero">
    <div class="container">
        <div class="festival-hero-inner">
            <span class="eyebrow">Celebrations of India</span>
            <h1>Festivals That Awaken The Soul</h1>
            <p>India's festivals are living stories of devotion, colour and community. Join us to experience them the way locals do — with front-row access and heartfelt hospitality.</p>
            <div class="hero-actions">
                <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Plan With An Expert</a>
                <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-ghost btn-lg">Explore Tours</a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     INTRO
     ============================================================ -->
<section class="section">
    <div class="container">
        <div class="festival-intro reveal">
            <span class="eyebrow">Why Travel For Festivals?</span>
            <h2 style="font-family:'Playfair Display',serif; font-size:2.1rem; color:#244B38; margin:14px 0 20px;">
                Travel Deeper. Celebrate Louder.
            </h2>
            <p>Festivals are the heartbeat of India — the moment when ancient traditions, regional flavours, music, dance and devotion collide in a riot of colour. Travelling during a festival means you don't just see a destination, you feel it.</p>
        </div>
    </div>
</section>

<!-- ============================================================
     FESTIVAL LIST
     ============================================================ -->
<section class="section section--tint" id="festival-list">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Festival Calendar</span>
            <h2>Choose Your Celebration</h2>
            <p>From Himalayan monasteries to coastal chariot processions — every festival is a new journey.</p>
        </div>

        <?php if (empty($festivals)): ?>
            <div class="festival-empty reveal">
                <div class="festival-empty-icon">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>
                        <circle cx="12" cy="12" r="4"/>
                    </svg>
                </div>
                <h3>Festivals Coming Soon</h3>
                <p>We are curating a beautiful collection of India's most vibrant festivals for you. In the meantime, feel free to reach out and we'll craft a custom festival journey just for you.</p>
                <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary">Talk To An Expert</a>
            </div>
        <?php else: ?>
            <div class="festival-grid" id="festivalGrid">
                <?php foreach ($festivals as $f): ?>
                    <?php
                        $img_src = !empty($f['featured_image'])
                            ? '../' . ltrim($f['featured_image'], '/')
                            : '';
                        $tag_text = $f['festival_date'] ?: ($f['best_time'] ?: 'Festival');
                        $location_text = $f['location'] ?: ($f['state'] ?: 'India');
                    ?>
                    <article class="festival-card reveal">
                        <div class="festival-media">
                            <?php if ($img_src): ?>
                                <img src="<?= e($img_src) ?>" alt="<?= e($f['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image"><i class="fas fa-dharmachakra"></i></div>
                            <?php endif; ?>
                            <span class="festival-tag"><?= e(plain_text($tag_text, 30)) ?></span>
                            <?php if (!empty($f['featured'])): ?>
                                <span class="festival-featured-badge">★ Featured</span>
                            <?php endif; ?>
                        </div>
                        <div class="festival-body">
                            <h3><?= e($f['name']) ?></h3>
                            <div class="festival-meta">
                                <span>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    <?= e(plain_text($location_text, 30)) ?>
                                </span>
                                <span>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <?= e($f['duration']) ?>
                                </span>
                            </div>
                            <p class="festival-desc"><?= e(plain_text($f['description'], 160)) ?></p>
                            <div class="festival-actions">
                                <a href="<?= PAGES_URL ?>festival-detail.php?slug=<?= urlencode($f['slug']) ?>" class="btn btn-outline btn-xs">Read More</a>
                                <a href="<?= PAGES_URL ?>booking.php?festival=<?= urlencode($f['slug']) ?>" class="btn btn-primary btn-xs">Enquire Now</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="festival-cta reveal">
            <h3>Can't Find Your Festival?</h3>
            <p>Tell us which celebration you've been dreaming of and we'll craft a bespoke journey around it — with private guides, handpicked stays and front-row access.</p>
            <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Talk To A Festival Expert</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
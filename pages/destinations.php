<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Destinations';
$pageDescription = 'Explore all destinations covered by Nature Nest Holidays.';
require_once __DIR__ . '/../includes/header.php';

$destinations = [];
try {
    $destinations = $pdo->query("SELECT * FROM destinations WHERE status='active' ORDER BY display_order ASC, id ASC")->fetchAll();
} catch (Exception $e) {}
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=1920&q=80')">
  <div class="container page-hero-inner">
    <span class="eyebrow eyebrow--light">All Destinations</span>
    <h1>Places We Travel</h1>
    <p>From Himalayan hill towns to tropical backwaters — pick where you want to go next.</p>
    <nav class="breadcrumb">
      <a href="<?= BASE_URL ?>index.php">Home</a>
      <span class="sep">/</span>
      <span>Destinations</span>
    </nav>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (!empty($destinations)): ?>
      <div class="dest-grid">
        <?php foreach ($destinations as $d): ?>
          <a class="dest-card reveal"
             href="<?= PAGES_URL ?>destination-detail.php?slug=<?= urlencode($d['slug']) ?>">
            <div class="dest-media">
              <img src="<?= e(img_url($d['featured_image'], 'destinations')) ?>"
                   alt="<?= e($d['name']) ?>" loading="lazy">
              <?php if (!empty($d['best_time_to_visit'])): ?>
                <span class="dest-tag"><?= e($d['best_time_to_visit']) ?></span>
              <?php endif; ?>
            </div>
            <div class="dest-body">
              <h3><?= e($d['name']) ?></h3>
              <p><?= e(plain_text($d['description'], 140)) ?></p>
              <span class="card-btn">
                Explore
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="section-head">
        <h2>No destinations yet</h2>
        <p>Our team is adding destinations — please check back soon.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="cta">
  <div class="container">
    <div class="cta-inner reveal">
      <span class="eyebrow eyebrow--light">Not Sure Where To Go?</span>
      <h2>Let Us Help You Choose</h2>
      <p>Tell us how you like to travel — we'll suggest the perfect destination.</p>
      <div class="cta-actions">
        <a href="<?= PAGES_URL ?>contact.php" class="btn btn-primary btn-lg">Talk To An Expert</a>
        <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-ghost btn-lg">Browse Packages</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
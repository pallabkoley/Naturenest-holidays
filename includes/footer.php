<?php
/* =========================================================
   FOOTER — Nature Nest Holidays
   Works with or without a PDO connection.
   ========================================================= */

/* ---------- Try to get $pdo if not already set ---------- */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = null;
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        foreach (['pdo','db','conn','connection'] as $k) {
            if (isset($GLOBALS[$k]) && $GLOBALS[$k] instanceof PDO) { $pdo = $GLOBALS[$k]; break; }
        }
    }
}

/* ---------- Safe query helper ---------- */
if (!function_exists('safe_fetch_all')) {
    function safe_fetch_all($pdo, $sql, $fallback = []) {
        if (!($pdo instanceof PDO)) return $fallback;
        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return $rows ?: $fallback;
        } catch (Throwable $e) {
            error_log('Footer query failed: ' . $e->getMessage());
            return $fallback;
        }
    }
}

/* ---------- Fetch destinations with fallback ---------- */
$footerDestinations = safe_fetch_all(
    $pdo,
    "SELECT name, slug FROM destinations WHERE status = 'active' ORDER BY display_order ASC, name ASC LIMIT 6",
    // Fallback list — shown when DB unavailable
    [
        ['name' => 'Darjeeling', 'slug' => 'destination-detail?slug=darjeeling'],
        ['name' => 'Sikkim',     'slug' => 'destination-detail?slug=sikkim'],
        ['name' => 'Kashmir',    'slug' => 'destination-detail?slug=kashmir'],
        ['name' => 'Goa',        'slug' => 'destination-detail?slug=goa'],
        ['name' => 'Rajasthan',  'slug' => 'destination-detail?slug=rajasthan'],
        ['name' => 'Kerala',     'slug' => 'destination-detail?slug=kerala'],
    ]
);

/* ---------- Footer contact info ---------- */
$footerPhones  = ['+91 98324 68096', '+91 84209 58514'];
$footerEmail   = 'info@naturenestholidays.in';
$footerOffices = [
    '1/F Ma Apartment, Dakhinayan, Sodepur, Kolkata – 700110',
    '8, Raja Ram Mohan Road, 2nd Floor, Nabapally, Barasat – 700126',
];
?>

<footer class="site-footer">
  <div class="container footer-grid">

    <!-- ---------- Brand Column ---------- -->
    <div class="footer-col footer-brand">
      <a href="<?= BASE_URL ?>index.php" class="brand brand--light">
        <img src="<?= ASSET_URL ?>images/logo/nature-logo.png"
             alt="Nature Nest Holidays"
             style="height:58px;width:auto;display:block;">
      </a>
      <p>Premium Indian travel and tourism — curating domestic and international holidays, honeymoons, family tours, treks and destination weddings.</p>

      <div class="socials">
        <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">
          <i class="fa-brands fa-facebook-f"></i>
        </a>
        <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">
          <i class="fa-brands fa-instagram"></i>
        </a>
        <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube">
          <i class="fa-brands fa-youtube"></i>
        </a>
        <a href="https://wa.me/919832468096" target="_blank" rel="noopener" aria-label="WhatsApp">
          <i class="fa-brands fa-whatsapp"></i>
        </a>
      </div>
    </div>

    <!-- ---------- Quick Links ---------- -->
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul class="footer-links">
        <li><a href="<?= PAGES_URL ?>about.php">About Us</a></li>
        <li><a href="<?= PAGES_URL ?>tour-packages.php">Tour Packages</a></li>
        <li><a href="<?= PAGES_URL ?>destinations.php">Destinations</a></li>
        <li><a href="<?= PAGES_URL ?>trek.php">Trekking</a></li>
        <li><a href="<?= PAGES_URL ?>offbeat-stays.php">Offbeat Stays</a></li>
        <li><a href="<?= PAGES_URL ?>services.php">Services</a></li>
        <li><a href="<?= PAGES_URL ?>destination-wedding.php">Destination Wedding</a></li>
        <li><a href="<?= PAGES_URL ?>contact.php">Contact Us</a></li>
      </ul>
    </div>

    <!-- ---------- Destinations ---------- -->
    <div class="footer-col">
      <h4>Destinations</h4>
      <ul class="footer-links">
        <?php foreach ($footerDestinations as $d): ?>
          <li>
            <a href="<?= PAGES_URL ?><?= htmlspecialchars($d['slug'] ?? '') ?>">
              <?= htmlspecialchars($d['name'] ?? '') ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- ---------- Contact ---------- -->
    <div class="footer-col">
      <h4>Get in Touch</h4>
      <ul class="footer-contact">
        <?php foreach ($footerPhones as $ph): ?>
          <li>
            <i class="fa-solid fa-phone"></i>
            <a href="tel:<?= preg_replace('/\s+/', '', $ph) ?>"><?= htmlspecialchars($ph) ?></a>
          </li>
        <?php endforeach; ?>

        <li>
          <i class="fa-solid fa-envelope"></i>
          <a href="mailto:<?= $footerEmail ?>"><?= $footerEmail ?></a>
        </li>

        <?php foreach ($footerOffices as $i => $addr): ?>
          <li style="<?= $i === 0 ? 'align-items:flex-start;' : '' ?>">
            <i class="fa-solid fa-location-dot" style="margin-top:4px;"></i>
            <span><?= htmlspecialchars($addr) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

  </div>

  <!-- ---------- Bottom Bar ---------- -->
  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> Nature Nest Holidays. All Rights Reserved.</p>
    <ul class="footer-legal">
      <li><a href="<?= PAGES_URL ?>contact.php">Privacy Policy</a></li>
      <li><a href="<?= PAGES_URL ?>contact.php">Terms &amp; Conditions</a></li>
    </ul>
  </div>
</footer>

<button class="to-top" id="toTop" type="button" aria-label="Back to top">
  <i class="fa-solid fa-arrow-up"></i>
</button>

<script>
/* =========================================================
   GLOBAL JS — header, nav, reveal, back-to-top
   ========================================================= */
(function () {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  /* Sticky header */
  const header = $('#siteHeader');
  if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Mobile nav */
  const navToggle = $('#navToggle');
  const mainNav   = $('#mainNav');
  const backdrop  = $('#navBackdrop');

  function openNav() {
    if (!mainNav) return;
    mainNav.classList.add('is-open');
    navToggle && navToggle.classList.add('is-open');
    navToggle && navToggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('no-scroll');
    if (backdrop) {
      backdrop.hidden = false;
      requestAnimationFrame(() => backdrop.classList.add('is-visible'));
    }
  }
  function closeNav() {
    if (!mainNav) return;
    mainNav.classList.remove('is-open');
    navToggle && navToggle.classList.remove('is-open');
    navToggle && navToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('no-scroll');
    if (backdrop) {
      backdrop.classList.remove('is-visible');
      setTimeout(() => { backdrop.hidden = true; }, 320);
    }
  }
  if (navToggle) {
    navToggle.addEventListener('click', () => {
      mainNav.classList.contains('is-open') ? closeNav() : openNav();
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeNav);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNav(); });

  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { if (window.innerWidth > 991) closeNav(); }, 160);
  });

  /* Mobile dropdown accordion */
  $$('.has-dropdown').forEach(item => {
    const trigger = item.querySelector('.nav-link');
    if (!trigger) return;
    trigger.addEventListener('click', e => {
      if (window.innerWidth > 991) return;
      e.preventDefault();
      item.classList.toggle('is-open');
    });
  });

  /* Hero slider (only if present on this page) */
  const slides = $$('.hero-slide');
  if (slides.length) {
    const dotsBox = $('#heroDots');
    const prevBtn = $('.hero-nav.prev');
    const nextBtn = $('.hero-nav.next');
    let current = 0;
    let timer   = null;
    const DELAY = 6500;

    function buildDots() {
      if (!dotsBox) return;
      dotsBox.innerHTML = '';
      slides.forEach((_, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        b.addEventListener('click', () => go(i));
        dotsBox.appendChild(b);
      });
    }
    function updateDots() {
      if (!dotsBox) return;
      $$('button', dotsBox).forEach((d, i) => d.classList.toggle('is-active', i === current));
    }
    function go(i) {
      slides[current].classList.remove('is-active');
      current = (i + slides.length) % slides.length;
      slides[current].classList.add('is-active');
      updateDots();
      restart();
    }
    function next() { go(current + 1); }
    function prev() { go(current - 1); }
    function start() { timer = setInterval(next, DELAY); }
    function stop()  { timer && clearInterval(timer); timer = null; }
    function restart() { stop(); start(); }

    buildDots(); updateDots(); start();
    prevBtn && prevBtn.addEventListener('click', prev);
    nextBtn && nextBtn.addEventListener('click', next);
    const heroEl = $('.hero');
    if (heroEl) {
      heroEl.addEventListener('mouseenter', stop);
      heroEl.addEventListener('mouseleave', start);
    }
    document.addEventListener('visibilitychange', () => {
      document.hidden ? stop() : start();
    });
  }

  /* Back to top */
  const toTop = $('#toTop');
  if (toTop) {
    window.addEventListener('scroll', () => {
      toTop.classList.toggle('is-visible', window.scrollY > 500);
    }, { passive: true });
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  /* Reveal on scroll (safe — falls back to visible) */
  const revealItems = $$('.reveal');
  if ('IntersectionObserver' in window && revealItems.length) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    revealItems.forEach((el, i) => {
      el.style.transitionDelay = (Math.min(i % 4, 3) * 80) + 'ms';
      obs.observe(el);
    });
    setTimeout(() => {
      revealItems.forEach(el => el.classList.add('is-visible'));
    }, 1500);
  } else {
    revealItems.forEach(el => el.classList.add('is-visible'));
  }

})();
</script>
</body>
</html>
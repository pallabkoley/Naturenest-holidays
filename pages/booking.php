<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

if (!defined('SITE_URL')) {
    $__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $__scheme . '://' . $__host);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ---- Fetch tour if ?package= or ?tour_id= provided ---- */
$tour_details = null;
$pkg_slug     = isset($_GET['package']) ? trim($_GET['package']) : '';
$tour_id      = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;

try {
    $db   = new Database();
    $conn = $db->getConnection();

    if ($pkg_slug !== '') {
        $stmt = $conn->prepare("SELECT id, title, slug, starting_price AS price, duration_nights, duration_days
                                FROM tour_packages
                                WHERE slug = ? AND status='active' LIMIT 1");
        $stmt->bind_param("s", $pkg_slug);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r && $r->num_rows > 0) {
            $tour_details = $r->fetch_assoc();
        } else {
            $stmt = $conn->prepare("SELECT id, title, slug, price, duration_nights, duration_days
                                    FROM tour_package_lists
                                    WHERE slug = ? AND status='active' LIMIT 1");
            $stmt->bind_param("s", $pkg_slug);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $r->num_rows > 0) $tour_details = $r->fetch_assoc();
        }
    } elseif ($tour_id > 0) {
        $stmt = $conn->prepare("SELECT id, title, slug, starting_price AS price, duration_nights, duration_days
                                FROM tour_packages WHERE id = ? AND status='active' LIMIT 1");
        $stmt->bind_param("i", $tour_id);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r && $r->num_rows > 0) $tour_details = $r->fetch_assoc();
    }
} catch (Throwable $e) {
    error_log('booking.php lookup: ' . $e->getMessage());
}

$pageTitle       = $tour_details ? ('Book ' . $tour_details['title']) : 'Book Your Tour';
$pageDescription = 'Book your dream vacation with Nature Nest Holidays. Tell us your travel dates and preferences — we\'ll handle the rest.';
$currentPage     = 'booking';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.bk-section{ padding: 40px 0 90px; background: #F7F3E5; }

.bk-crumb{
  padding: 14px 0; background: #FAF7ED;
  border-bottom: 1px solid #efe5c7;
  font-size: .86rem; color: #6b7a72;
}
.bk-crumb ol{ display:flex; flex-wrap:wrap; gap:6px; list-style:none; margin:0; padding:0; }
.bk-crumb li{display:inline-flex; align-items:center; gap:6px;}
.bk-crumb li:not(:last-child)::after{content:'/'; color:#c9c2a8;}
.bk-crumb a{color:#294536; text-decoration:none; transition:color .18s;}
.bk-crumb a:hover{color:#C9A04A;}
.bk-crumb li.active{color:#8a8a7a;}

.bk-head{ text-align:center; margin-bottom: 36px; }
.bk-head .eyebrow{
  display:inline-block;
  font-size:.72rem; font-weight:700; letter-spacing:.18em;
  text-transform:uppercase; color:#C9A04A; margin-bottom:12px;
}
.bk-head h1{
  font-family: var(--font-display);
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  color: var(--forest); margin: 0 0 10px; line-height:1.15;
}
.bk-head p{ color:#6b7a72; font-size:.98rem; max-width: 620px; margin: 0 auto; }

.bk-notice{
  display:inline-flex; align-items:center; gap:10px;
  margin-top:18px; padding:10px 20px;
  background:#fff; border:1.5px solid #C9A04A;
  border-radius:999px; font-size:.88rem;
  color:#294536; font-weight:500;
}
.bk-notice i{color:#C9A04A;}
.bk-notice strong{color:var(--forest);}

.bk-card{
  max-width: 1000px; margin: 0 auto;
  background:#fff; border:1px solid #ece6d4;
  border-radius: 20px;
  box-shadow: 0 8px 30px rgba(15,61,46,.06);
  overflow:hidden;
}

.bk-block{ padding: 32px 40px; border-bottom: 1px dashed #efe5c7; }
.bk-block:last-of-type{border-bottom:0; padding-bottom:20px;}

.bk-block-title{
  display:flex; align-items:center; gap:12px;
  font-family: var(--font-display);
  font-size: 1.18rem; color: var(--forest); margin: 0 0 22px;
}
.bk-block-title .num{
  width:34px; height:34px;
  display:grid; place-items:center;
  border-radius:50%;
  background: var(--forest); color:#C9A04A;
  font-size:.9rem; font-weight:700;
  flex-shrink:0;
}

.bk-row{ display:grid; grid-template-columns: 1fr 1fr; gap: 18px 22px; }
.bk-row.full{grid-template-columns:1fr;}
@media (max-width:700px){
  .bk-row{grid-template-columns:1fr;}
  .bk-block{padding: 24px 22px;}
}

.bk-field{ display:flex; flex-direction:column; }
.bk-field label{
  font-size:.82rem; font-weight:600; color:#3b4a41;
  margin-bottom:7px; letter-spacing:.01em;
}
.bk-field label .req{color:#c0392b;}
.bk-field input,
.bk-field select,
.bk-field textarea{
  width:100%; padding: 12px 14px;
  background:#fdfcf8; border: 1.5px solid #e6dfc9;
  border-radius: 10px; font-size: .94rem;
  color: #22262a; font-family: inherit;
  transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
  box-sizing: border-box;
}
.bk-field textarea{resize:vertical; min-height:96px; line-height:1.55;}
.bk-field input:focus,
.bk-field select:focus,
.bk-field textarea:focus{
  outline:none; border-color: var(--forest); background:#fff;
  box-shadow: 0 0 0 3px rgba(15,61,46,.10);
}
.bk-field small{ display:block; margin-top:6px; font-size:.75rem; color:#8a8a7a; }

.bk-check{
  display:flex; align-items:flex-start; gap:10px;
  margin-bottom:14px; font-size:.88rem;
  color:#3b4a41; line-height:1.55;
}
.bk-check input[type="checkbox"]{
  width:18px; height:18px; margin-top:3px;
  accent-color: var(--forest); flex-shrink:0; cursor:pointer;
}
.bk-check a{color:var(--forest); text-decoration:underline;}

.bk-submit{
  padding: 30px 40px 40px;
  background: linear-gradient(180deg,#fbf8ee 0%, #ffffff 100%);
  border-top: 1px solid #efe5c7; text-align:center;
}
.bk-submit h4{
  font-family: var(--font-display); color: var(--forest);
  margin: 0 0 8px; font-size: 1.25rem;
}
.bk-submit p{ color:#6b7a72; font-size:.9rem; margin: 0 0 24px; }
.bk-actions{ display:flex; flex-wrap:wrap; gap:12px; justify-content:center; }
.bk-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  padding: 14px 30px; border-radius: 999px;
  font-size: .94rem; font-weight: 600; cursor:pointer;
  border: 1.5px solid transparent; text-decoration:none;
  transition: all .22s cubic-bezier(.22,.61,.36,1);
  font-family:inherit;
}
.bk-btn-primary{
  background:#D4A13A; color:#173A2D;
  box-shadow: 0 6px 18px rgba(80,60,20,.15);
}
.bk-btn-primary:hover{
  background:#C08E30; transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(80,60,20,.22);
}
.bk-btn-ghost{
  background:#fff; border-color:#d8d0b4; color:#294536;
}
.bk-btn-ghost:hover{
  border-color: var(--forest); color: var(--forest);
  transform: translateY(-1px);
}
.bk-security{
  margin-top:20px; font-size:.78rem; color:#8a8a7a;
  display:inline-flex; align-items:center; gap:6px;
}
.bk-security i{color:#C9A04A;}

.bk-support{
  max-width: 1000px; margin: 46px auto 0;
  display:grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
}
@media (max-width:700px){ .bk-support{grid-template-columns:1fr;} }
.bk-support-card{
  background:#fff; border:1px solid #ece6d4;
  border-radius: 16px; padding: 26px 22px;
  text-align:center;
  transition: transform .3s, box-shadow .3s;
}
.bk-support-card:hover{
  transform: translateY(-4px);
  box-shadow: 0 14px 30px rgba(15,61,46,.08);
}
.bk-support-card i{
  font-size:1.9rem; color:#C9A04A;
  margin-bottom:12px; display:block;
}
.bk-support-card h5{
  font-family: var(--font-display); color: var(--forest);
  font-size:1.05rem; margin: 0 0 8px;
}
.bk-support-card p{
  font-size:.85rem; color:#6b7a72; margin:0; line-height:1.55;
}
</style>

<!-- Breadcrumb -->
<section class="bk-crumb">
  <div class="container">
    <ol>
      <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
      <li><a href="<?= PAGES_URL ?>tour-packages.php">Tour Packages</a></li>
      <li class="active">Booking</li>
    </ol>
  </div>
</section>

<!-- Booking Section -->
<section class="bk-section">
  <div class="container">

    <div class="bk-head">
      <span class="eyebrow">Reserve Your Journey</span>
      <h1><?= $tour_details ? 'Book ' . e($tour_details['title']) : 'Book Your Tour' ?></h1>
      <p>Fill in the details below and our travel experts will contact you within 24 hours to confirm your booking.</p>

      <?php if ($tour_details): ?>
        <div class="bk-notice">
          <i class="fas fa-compass"></i>
          Selected: <strong><?= e($tour_details['title']) ?></strong>
          <?php if (!empty($tour_details['duration_days']) && $tour_details['duration_days'] > 0): ?>
            <span style="color:#8a8a7a;">·</span>
            <?= (int)$tour_details['duration_days'] ?>D<?= (int)$tour_details['duration_nights'] > 0 ? ' / ' . (int)$tour_details['duration_nights'] . 'N' : '' ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ⭐ FORM — action pointed at booking-submit.php (relative) -->
    <form id="bookingForm" method="POST" action="booking-submit.php" class="bk-card" novalidate>

      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
      <?php if ($tour_details): ?>
        <input type="hidden" name="tour_package_id" value="<?= (int)$tour_details['id'] ?>">
        <input type="hidden" name="package_title" value="<?= e($tour_details['title']) ?>">
      <?php elseif ($pkg_slug !== ''): ?>
        <input type="hidden" name="package_title" value="<?= e($pkg_slug) ?>">
      <?php endif; ?>

      <!-- 01 -->
      <div class="bk-block">
        <h3 class="bk-block-title"><span class="num">01</span> Personal Details</h3>
        <div class="bk-row">
          <div class="bk-field">
            <label for="name">Full Name <span class="req">*</span></label>
            <input type="text" id="name" name="name" required placeholder="e.g. Rohan Sharma">
          </div>
          <div class="bk-field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com">
          </div>
          <div class="bk-field">
            <label for="phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="phone" name="phone" required placeholder="10-digit mobile number" inputmode="numeric">
            <small>We'll contact you on this number</small>
          </div>
          <div class="bk-field">
            <label for="journey_type">Journey Type</label>
            <select id="journey_type" name="journey_type">
              <option value="">Select journey type</option>
              <option value="Solo">Solo Travel</option>
              <option value="Couple">Couple</option>
              <option value="Family">Family</option>
              <option value="Friends">Friends</option>
              <option value="Group">Group Tour</option>
              <option value="Corporate">Corporate</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 02 -->
      <div class="bk-block">
        <h3 class="bk-block-title"><span class="num">02</span> Travel Details</h3>
        <div class="bk-row">
          <div class="bk-field">
            <label for="travel_date">Travel Date <span class="req">*</span></label>
            <input type="date" id="travel_date" name="travel_date" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="bk-field">
            <label for="return_date">Return Date</label>
            <input type="date" id="return_date" name="return_date">
          </div>
          <div class="bk-field">
            <label for="destination">Destination</label>
            <select id="destination" name="destination">
              <option value="">Select destination</option>
              <?php
                $destOptions = ['Darjeeling','Sikkim','Kalimpong','Kurseong','Mirik','Dooars','Sandakphu','Himachal Pradesh','Kashmir','Bhutan','Puri','Multiple','Custom'];
                $preselect = $tour_details['title'] ?? '';
                foreach ($destOptions as $d):
              ?>
                <option value="<?= e($d) ?>" <?= ($preselect === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="bk-field" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; align-content:flex-start;">
            <div>
              <label for="adults">Adults <span class="req">*</span></label>
              <input type="number" id="adults" name="adults" min="1" max="20" value="1" required>
            </div>
            <div>
              <label for="children">Children</label>
              <input type="number" id="children" name="children" min="0" max="20" value="0">
              <small>Age 5–12</small>
            </div>
          </div>
        </div>
      </div>

      <!-- 03 -->
      <div class="bk-block">
        <h3 class="bk-block-title"><span class="num">03</span> Additional Information</h3>
        <div class="bk-row full">
          <div class="bk-field">
            <label for="custom_tour_details">Custom Tour Details</label>
            <textarea id="custom_tour_details" name="custom_tour_details" rows="3"
                      placeholder="Describe your custom tour requirements..."></textarea>
            <small>Leave blank if booking a package tour</small>
          </div>
          <div class="bk-field">
            <label for="special_requests">Special Requests</label>
            <textarea id="special_requests" name="special_requests" rows="3"
                      placeholder="Any dietary restrictions, accessibility needs, or special requests..."></textarea>
          </div>
          <div>
            <label class="bk-check">
              <input type="checkbox" id="newsletter" name="newsletter" value="1" checked>
              <span>Send me travel tips, deals, and updates from Nature Nest Holidays</span>
            </label>
            <label class="bk-check">
              <input type="checkbox" id="terms" name="terms" required>
              <span>I agree to the <a href="<?= PAGES_URL ?>terms.php" target="_blank">Terms &amp; Conditions</a>
              and <a href="<?= PAGES_URL ?>privacy-policy.php" target="_blank">Privacy Policy</a></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="bk-submit">
        <h4>Ready to Book?</h4>
        <p>Submit your booking request and our team will contact you shortly.</p>
        <div class="bk-actions">
          <button type="submit" class="bk-btn bk-btn-primary">
            <i class="fas fa-paper-plane"></i> Submit Booking Request
          </button>
          <button type="reset" class="bk-btn bk-btn-ghost">
            <i class="fas fa-redo"></i> Reset Form
          </button>
        </div>
        <div class="bk-security">
          <i class="fas fa-shield-alt"></i>
          Your information is secure and will not be shared with third parties.
        </div>
      </div>
    </form>

    <!-- Support cards -->
    <div class="bk-support">
      <div class="bk-support-card">
        <i class="fas fa-headset"></i>
        <h5>24/7 Support</h5>
        <p>Our travel experts are available round the clock to assist you.</p>
      </div>
      <div class="bk-support-card">
        <i class="fas fa-lock"></i>
        <h5>Secure Booking</h5>
        <p>Your personal and payment information is fully protected.</p>
      </div>
      <div class="bk-support-card">
        <i class="fas fa-calendar-check"></i>
        <h5>Instant Confirmation</h5>
        <p>Get booking confirmation via email and WhatsApp.</p>
      </div>
    </div>

  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ============================================================
   BOOKING FORM — validation + AJAX submit
   ⭐ POST URL is now relative: booking-submit.php
   ============================================================ */
(function () {
  'use strict';

  const form = document.getElementById('bookingForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const name       = document.getElementById('name').value.trim();
    const phone      = document.getElementById('phone').value.trim();
    const travelDate = document.getElementById('travel_date').value;
    const adults     = parseInt(document.getElementById('adults').value) || 0;
    const children   = parseInt(document.getElementById('children').value) || 0;
    const terms      = document.getElementById('terms').checked;

    const errors = [];
    if (!name) errors.push('Name is required.');
    if (!phone) {
      errors.push('Phone number is required.');
    } else if (!/^\d{10,15}$/.test(phone)) {
      errors.push('Please enter a valid phone number (10–15 digits).');
    }
    if (!travelDate) {
      errors.push('Travel date is required.');
    } else {
      const sel = new Date(travelDate); sel.setHours(0,0,0,0);
      const today = new Date(); today.setHours(0,0,0,0);
      if (sel < today) errors.push('Travel date must be today or later.');
    }
    if (adults < 1) errors.push('At least 1 adult is required.');
    if (adults > 20) errors.push('Maximum 20 adults allowed.');
    if (children > 20) errors.push('Maximum 20 children allowed.');
    if (!terms) errors.push('You must agree to the Terms & Conditions.');

    if (errors.length > 0) {
      Swal.fire({
        icon: 'error',
        title: 'Please check the form',
        html: errors.map(x => '• ' + x).join('<br>'),
        confirmButtonColor: '#D4A13A'
      });
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
    submitBtn.disabled = true;

    const formData = new FormData(form);

    /* ⭐ Relative URL — resolves to /pages/booking-submit.php */
    fetch('booking-submit.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(res => res.text())               /* read text first so we can debug if HTML comes back */
    .then(text => {
      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        console.error('Non-JSON response from server:', text);
        throw new Error('Server returned invalid response. Please try again.');
      }

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Booking Submitted!',
          html: `<p>${data.message || 'Thank you! Our team will contact you shortly.'}</p>
                 ${data.booking_ref ? `<p class="h5 mt-3">Reference: <strong>${data.booking_ref}</strong></p>` : ''}`,
          confirmButtonColor: '#D4A13A'
        }).then(() => {
          if (data.booking_ref) {
            window.location.href = 'booking-confirmation.php?ref=' + encodeURIComponent(data.booking_ref);
          } else {
            form.reset();
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Booking Failed',
          text: data.message || 'Please try again.',
          confirmButtonColor: '#D4A13A'
        });
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: err.message || 'Please check your connection and try again.',
        confirmButtonColor: '#D4A13A'
      });
    })
    .finally(() => {
      submitBtn.innerHTML = originalHTML;
      submitBtn.disabled = false;
    });
  });

  /* Date setup */
  const today = new Date().toISOString().split('T')[0];
  const travelInput = document.getElementById('travel_date');
  const returnInput = document.getElementById('return_date');
  travelInput.min = today;
  travelInput.addEventListener('change', () => {
    returnInput.min = travelInput.value;
    if (returnInput.value && returnInput.value < travelInput.value) returnInput.value = '';
  });

  /* Phone digits only */
  document.getElementById('phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 15);
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
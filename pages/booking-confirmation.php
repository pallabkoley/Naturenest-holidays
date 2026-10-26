<?php
/* =========================================================
   BOOKING CONFIRMATION — Nature Nest Holidays
   ========================================================= */

require_once __DIR__ . '/../includes/config.php';

if (!defined('SITE_URL')) {
    $__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $__scheme . '://' . $__host);
}

/* Get booking reference (URL first, session as fallback) */
$booking_ref = '';
if (isset($_GET['ref']) && $_GET['ref'] !== '') {
    $booking_ref = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['ref']);
} elseif (!empty($_SESSION['last_booking_ref'])) {
    $booking_ref = $_SESSION['last_booking_ref'];
}

/* ---- Fetch booking details ---- */
$booking_details = null;
if ($booking_ref !== '' && isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, t.title AS tour_name, t.slug AS tour_slug
            FROM bookings b
            LEFT JOIN tour_packages t ON b.tour_package_id = t.id
            WHERE b.booking_reference = ?
            LIMIT 1
        ");
        $stmt->execute([$booking_ref]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $booking_details = $row;
    } catch (Throwable $e) {
        error_log('booking-confirmation: ' . $e->getMessage());
    }
}

/* Status helpers */
function bc_status_class($s) {
    switch ($s) {
        case 'confirmed': return 'is-success';
        case 'pending':   return 'is-warning';
        case 'cancelled': return 'is-danger';
        case 'completed': return 'is-info';
        default:          return 'is-muted';
    }
}
function bc_pay_class($s) {
    switch ($s) {
        case 'paid':    return 'is-success';
        case 'partial': return 'is-info';
        case 'pending': return 'is-warning';
        default:        return 'is-muted';
    }
}

$pageTitle       = 'Booking Confirmation';
$pageDescription = 'Your booking with Nature Nest Holidays.';
$currentPage     = 'booking-confirmation';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ============================================================
   BOOKING CONFIRMATION — matches Nature Nest ivory/green/gold
   ============================================================ */
.bc-section{
  padding: 40px 0 90px;
  background: #F7F3E5;
}

/* Breadcrumb */
.bc-crumb{
  padding: 14px 0;
  background: #FAF7ED;
  border-bottom: 1px solid #efe5c7;
  font-size: .86rem;
  color: #6b7a72;
}
.bc-crumb ol{
  display:flex; flex-wrap:wrap; gap:6px;
  list-style:none; margin:0; padding:0;
}
.bc-crumb li{display:inline-flex; align-items:center; gap:6px;}
.bc-crumb li:not(:last-child)::after{content:'/'; color:#c9c2a8;}
.bc-crumb a{color:#294536; text-decoration:none; transition:color .18s;}
.bc-crumb a:hover{color:#C9A04A;}
.bc-crumb li.active{color:#8a8a7a;}

.bc-wrap{
  max-width: 900px;
  margin: 0 auto;
}

/* Card base */
.bc-card{
  background:#fff;
  border:1px solid #ece6d4;
  border-radius: 20px;
  box-shadow: 0 8px 30px rgba(15,61,46,.06);
  overflow:hidden;
  margin-bottom: 24px;
}

/* Success hero block */
.bc-hero{
  text-align:center;
  padding: 46px 30px 40px;
  background: linear-gradient(180deg,#fbf8ee 0%,#ffffff 100%);
  border-bottom: 1px dashed #efe5c7;
}
.bc-icon{
  width: 84px; height: 84px;
  margin: 0 auto 20px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: #e6f7ec;
  color: #1a7a3f;
  font-size: 2.4rem;
  animation: bcPop .6s cubic-bezier(.22,.61,.36,1);
}
.bc-icon i{ line-height: 1; display: block; }
@keyframes bcPop{
  0%{transform:scale(.4); opacity:0;}
  60%{transform:scale(1.1);}
  100%{transform:scale(1); opacity:1;}
}
.bc-hero h1{
  font-family: var(--font-display);
  font-size: clamp(1.6rem, 3.4vw, 2.2rem);
  color: var(--forest);
  margin: 0 0 8px;
}
.bc-hero p{
  color:#6b7a72;
  font-size:.96rem;
  margin: 0 0 20px;
}
.bc-ref-pill{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding: 10px 22px;
  background: #fff;
  border: 1.5px solid #C9A04A;
  border-radius: 999px;
  font-size: .9rem;
  color:#294536;
  font-weight: 500;
}
.bc-ref-pill i{ color:#C9A04A; }
.bc-ref-pill strong{
  color: var(--forest);
  font-family: 'Courier New', monospace;
  letter-spacing: .04em;
}

/* Section blocks */
.bc-block{
  padding: 26px 34px 28px;
  border-bottom: 1px dashed #efe5c7;
}
.bc-block:last-of-type{ border-bottom:0; }
@media (max-width:600px){ .bc-block{ padding: 22px 20px; } }

.bc-block-title{
  display:flex;
  align-items:center;
  gap:11px;
  font-family: var(--font-display);
  font-size: 1.15rem;
  color: var(--forest);
  margin: 0 0 20px;
}
.bc-block-title i{
  width: 34px; height: 34px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: var(--forest);
  color: #C9A04A;
  font-size: .9rem;
  flex-shrink: 0;
}

.bc-grid{
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px 26px;
}
@media (max-width:600px){ .bc-grid{ grid-template-columns:1fr; gap:16px; } }

.bc-item .bc-label{
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: #8a8a7a;
  margin: 0 0 5px;
}
.bc-item .bc-value{
  font-size: .98rem;
  color: #22262a;
  font-weight: 500;
  margin: 0;
  line-height: 1.5;
  word-wrap: break-word;
}
.bc-item .bc-value.mono{
  font-family: 'Courier New', monospace;
  letter-spacing: .03em;
  color: var(--forest);
  font-weight: 600;
}

/* Status badge */
.bc-badge{
  display: inline-block;
  padding: 4px 14px;
  border-radius: 999px;
  font-size: .76rem;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
}
.bc-badge.is-success{ background:#e6f7ec; color:#1a7a3f; }
.bc-badge.is-warning{ background:#fff4d6; color:#8a6100; }
.bc-badge.is-danger { background:#fdeaea; color:#a5261b; }
.bc-badge.is-info   { background:#e7f0fb; color:#1c5fa8; }
.bc-badge.is-muted  { background:#f0f0f0; color:#666; }

.bc-note{
  margin-top: 20px;
  padding: 14px 18px;
  background: #faf7ed;
  border-left: 3px solid #C9A04A;
  border-radius: 8px;
  font-size: .92rem;
  color: #294536;
  line-height: 1.6;
}

/* Next steps list */
.bc-steps{
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.bc-step{
  display: flex;
  gap: 14px;
  align-items: flex-start;
}
.bc-step-num{
  flex-shrink: 0;
  width: 32px; height: 32px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: var(--forest);
  color: #C9A04A;
  font-weight: 700;
  font-size: .85rem;
}
.bc-step-body h6{
  font-family: var(--font-body);
  font-size: .95rem;
  font-weight: 600;
  color: var(--forest);
  margin: 0 0 3px;
}
.bc-step-body p{
  font-size: .85rem;
  color: #6b7a72;
  margin: 0;
  line-height: 1.5;
}

/* Actions */
.bc-actions{
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  padding: 28px 34px 32px;
  background: linear-gradient(180deg,#fbf8ee 0%,#ffffff 100%);
  border-top: 1px solid #efe5c7;
}
.bc-btn{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 26px;
  border-radius: 999px;
  font-size: .9rem;
  font-weight: 600;
  text-decoration: none;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: all .22s cubic-bezier(.22,.61,.36,1);
  font-family: inherit;
}
.bc-btn-primary{
  background: #D4A13A;
  color: #173A2D;
  box-shadow: 0 6px 18px rgba(80,60,20,.15);
}
.bc-btn-primary:hover{
  background: #C08E30;
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(80,60,20,.22);
}
.bc-btn-ghost{
  background: #fff;
  border-color: #d8d0b4;
  color: #294536;
}
.bc-btn-ghost:hover{
  border-color: var(--forest);
  color: var(--forest);
  transform: translateY(-1px);
}

/* Not-found state */
.bc-notfound{
  text-align: center;
  padding: 60px 30px 50px;
}
.bc-notfound i{
  font-size: 3.4rem;
  color: #c9c2a8;
  margin-bottom: 20px;
  display: block;
}
.bc-notfound h1{
  font-family: var(--font-display);
  font-size: 1.9rem;
  color: var(--forest);
  margin: 0 0 10px;
}
.bc-notfound p{
  color: #6b7a72;
  font-size: .96rem;
  margin: 0 0 8px;
}
.bc-notfound .bc-warn{
  display:inline-block;
  margin: 20px auto;
  padding: 10px 20px;
  background: #fff4d6;
  color: #8a6100;
  border-radius: 10px;
  font-size: .88rem;
}

/* Support strip */
.bc-support{
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-top: 26px;
}
@media (max-width:700px){ .bc-support{ grid-template-columns:1fr; } }
.bc-support-card{
  background:#fff;
  border:1px solid #ece6d4;
  border-radius: 16px;
  padding: 22px 18px;
  text-align:center;
}
.bc-support-card i{
  font-size: 1.6rem;
  color: #C9A04A;
  margin-bottom: 10px;
  display: block;
}
.bc-support-card h5{
  font-family: var(--font-display);
  color: var(--forest);
  font-size: 1rem;
  margin: 0 0 6px;
}
.bc-support-card p{
  font-size: .84rem;
  color: #6b7a72;
  margin: 0;
  line-height: 1.5;
}
.bc-support-card a{
  color: var(--forest);
  text-decoration: none;
  font-weight: 600;
}
.bc-support-card a:hover{ text-decoration: underline; }
</style>

<!-- Breadcrumb -->
<section class="bc-crumb">
  <div class="container">
    <ol>
      <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
      <li><a href="<?= PAGES_URL ?>tour-packages.php">Tour Packages</a></li>
      <li class="active">Booking Confirmation</li>
    </ol>
  </div>
</section>

<section class="bc-section">
  <div class="container">
    <div class="bc-wrap">

      <?php if ($booking_details): ?>

        <!-- =============== SUCCESS HERO =============== -->
        <div class="bc-card">
          <div class="bc-hero">
            <div class="bc-icon">
              <i class="fas fa-check"></i>
            </div>
            <h1>Booking Confirmed!</h1>
            <p>Thank you for choosing Nature Nest Holidays.</p>

            <div class="bc-ref-pill">
              <i class="fas fa-tag"></i>
              Reference: <strong><?= e($booking_details['booking_reference']) ?></strong>
            </div>
          </div>

          <!-- =============== BOOKING INFO =============== -->
          <div class="bc-block">
            <h3 class="bc-block-title"><i class="fas fa-receipt"></i> Booking Details</h3>

            <div class="bc-grid">
              <div class="bc-item">
                <p class="bc-label">Reference</p>
                <p class="bc-value mono"><?= e($booking_details['booking_reference']) ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Booked On</p>
                <p class="bc-value"><?= date('M j, Y', strtotime($booking_details['created_at'])) ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Tour Package</p>
                <p class="bc-value">
                  <?= !empty($booking_details['tour_name'])
                        ? e($booking_details['tour_name'])
                        : 'Custom Tour Package' ?>
                </p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Status</p>
                <p class="bc-value">
                  <span class="bc-badge <?= bc_status_class($booking_details['status']) ?>">
                    <?= e(ucfirst($booking_details['status'])) ?>
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- =============== TRAVEL INFO =============== -->
          <div class="bc-block">
            <h3 class="bc-block-title"><i class="fas fa-plane"></i> Travel Details</h3>

            <div class="bc-grid">
              <div class="bc-item">
                <p class="bc-label">Traveler Name</p>
                <p class="bc-value"><?= e($booking_details['name']) ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Phone</p>
                <p class="bc-value"><?= e($booking_details['phone']) ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Email</p>
                <p class="bc-value"><?= $booking_details['email'] !== '' ? e($booking_details['email']) : 'Not provided' ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Journey Type</p>
                <p class="bc-value"><?= !empty($booking_details['journey_type']) ? e($booking_details['journey_type']) : 'Not specified' ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Travel Date</p>
                <p class="bc-value"><?= !empty($booking_details['travel_date']) ? date('M j, Y', strtotime($booking_details['travel_date'])) : '—' ?></p>
              </div>
              <?php if (!empty($booking_details['return_date'])): ?>
                <div class="bc-item">
                  <p class="bc-label">Return Date</p>
                  <p class="bc-value"><?= date('M j, Y', strtotime($booking_details['return_date'])) ?></p>
                </div>
              <?php endif; ?>
              <div class="bc-item">
                <p class="bc-label">Destination</p>
                <p class="bc-value"><?= !empty($booking_details['destination']) ? e($booking_details['destination']) : 'Multiple Destinations' ?></p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Travelers</p>
                <p class="bc-value">
                  <?= (int)$booking_details['adults'] ?> Adult<?= (int)$booking_details['adults'] > 1 ? 's' : '' ?>
                  <?php if ((int)$booking_details['children'] > 0): ?>
                    · <?= (int)$booking_details['children'] ?> Child<?= (int)$booking_details['children'] > 1 ? 'ren' : '' ?>
                  <?php endif; ?>
                </p>
              </div>
            </div>

            <?php if (!empty($booking_details['special_requests'])): ?>
              <div class="bc-note">
                <strong>Special Requests:</strong><br>
                <?= nl2br(e($booking_details['special_requests'])) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- =============== PAYMENT =============== -->
          <div class="bc-block">
            <h3 class="bc-block-title"><i class="fas fa-credit-card"></i> Payment</h3>

            <div class="bc-grid">
              <div class="bc-item">
                <p class="bc-label">Total Amount</p>
                <p class="bc-value">
                  <?php if (!empty($booking_details['total_amount']) && $booking_details['total_amount'] > 0): ?>
                    ₹<?= number_format((float)$booking_details['total_amount'], 2) ?>
                  <?php else: ?>
                    <em style="color:#8a8a7a;font-weight:400;">To be confirmed</em>
                  <?php endif; ?>
                </p>
              </div>
              <div class="bc-item">
                <p class="bc-label">Payment Status</p>
                <p class="bc-value">
                  <span class="bc-badge <?= bc_pay_class($booking_details['payment_status']) ?>">
                    <?= e(ucfirst($booking_details['payment_status'])) ?>
                  </span>
                </p>
              </div>
            </div>

            <div class="bc-note">
              <i class="fas fa-info-circle" style="color:#C9A04A;"></i>
              Our travel consultant will contact you shortly to confirm details and share payment options.
            </div>
          </div>

          <!-- =============== NEXT STEPS =============== -->
          <div class="bc-block">
            <h3 class="bc-block-title"><i class="fas fa-list-check"></i> What Happens Next</h3>

            <ul class="bc-steps">
              <li class="bc-step">
                <span class="bc-step-num">1</span>
                <div class="bc-step-body">
                  <h6>Confirmation Call</h6>
                  <p>Our travel consultant will call you within 24 hours.</p>
                </div>
              </li>
              <li class="bc-step">
                <span class="bc-step-num">2</span>
                <div class="bc-step-body">
                  <h6>Final Itinerary &amp; Payment</h6>
                  <p>We'll share the itinerary and agree on a payment schedule.</p>
                </div>
              </li>
              <li class="bc-step">
                <span class="bc-step-num">3</span>
                <div class="bc-step-body">
                  <h6>Travel Documents</h6>
                  <p>Receive vouchers, hotel confirmations and travel documents.</p>
                </div>
              </li>
              <li class="bc-step">
                <span class="bc-step-num">4</span>
                <div class="bc-step-body">
                  <h6>Enjoy Your Trip</h6>
                  <p>Travel with peace of mind — we handle the rest.</p>
                </div>
              </li>
            </ul>
          </div>

          <!-- =============== ACTIONS =============== -->
          <div class="bc-actions">
            <a href="<?= PAGES_URL ?>tour-packages.php" class="bc-btn bc-btn-primary">
              <i class="fas fa-calendar-alt"></i> Browse More Tours
            </a>
            <a href="<?= BASE_URL ?>index.php" class="bc-btn bc-btn-ghost">
              <i class="fas fa-home"></i> Back to Home
            </a>
            <button type="button" onclick="window.print();" class="bc-btn bc-btn-ghost">
              <i class="fas fa-print"></i> Print
            </button>
          </div>
        </div>

        <!-- =============== SUPPORT =============== -->
        <div class="bc-support">
          <div class="bc-support-card">
            <i class="fas fa-phone-alt"></i>
            <h5>Call Us</h5>
            <p>
              <a href="tel:<?= preg_replace('/\s+/', '', $SITE['contact_phone1'] ?? '') ?>">
                <?= e($SITE['contact_phone1'] ?? '+91 98324 68096') ?>
              </a>
            </p>
          </div>
          <div class="bc-support-card">
            <i class="fas fa-envelope"></i>
            <h5>Email Us</h5>
            <p>
              <a href="mailto:<?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>">
                <?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>
              </a>
            </p>
          </div>
          <div class="bc-support-card">
            <i class="fas fa-clock"></i>
            <h5>Support Hours</h5>
            <p>7:00 AM – 10:00 PM (Daily)</p>
          </div>
        </div>

      <?php else: ?>

        <!-- =============== NOT FOUND =============== -->
        <div class="bc-card">
          <div class="bc-notfound">
            <i class="fas fa-search"></i>
            <h1>Booking Not Found</h1>
            <p>We couldn't find a booking with the given reference.</p>

            <div class="bc-warn">
              <i class="fas fa-exclamation-triangle"></i>
              If you have a booking reference, please check it and try again.
            </div>

            <div style="margin-top:24px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
              <a href="<?= PAGES_URL ?>booking.php" class="bc-btn bc-btn-primary">
                <i class="fas fa-calendar-plus"></i> Make a New Booking
              </a>
              <a href="<?= BASE_URL ?>index.php" class="bc-btn bc-btn-ghost">
                <i class="fas fa-home"></i> Back to Home
              </a>
            </div>
          </div>
        </div>

        <!-- =============== SUPPORT =============== -->
        <div class="bc-support">
          <div class="bc-support-card">
            <i class="fas fa-phone-alt"></i>
            <h5>Call Us</h5>
            <p>
              <a href="tel:<?= preg_replace('/\s+/', '', $SITE['contact_phone1'] ?? '') ?>">
                <?= e($SITE['contact_phone1'] ?? '+91 98324 68096') ?>
              </a>
            </p>
          </div>
          <div class="bc-support-card">
            <i class="fas fa-envelope"></i>
            <h5>Email Us</h5>
            <p>
              <a href="mailto:<?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>">
                <?= e($SITE['site_email'] ?? 'info@naturenestholidays.in') ?>
              </a>
            </p>
          </div>
          <div class="bc-support-card">
            <i class="fas fa-comments"></i>
            <h5>Need Help?</h5>
            <p>Our team is happy to assist you find your booking.</p>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
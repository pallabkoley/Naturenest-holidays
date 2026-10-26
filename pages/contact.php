<?php
/* =========================================================
   CONTACT US — Nature Nest Holidays
   ========================================================= */

/* ---------- Load config ---------- */
if (!defined('BASE_URL')) {
    $__configCandidates = [
        __DIR__ . '/../config.php',
        __DIR__ . '/../includes/config.php',
        __DIR__ . '/../inc/config.php',
        __DIR__ . '/../../config.php',
    ];
    foreach ($__configCandidates as $__cfg) {
        if (file_exists($__cfg)) { require_once $__cfg; break; }
    }
    if (!defined('BASE_URL')) die('config.php not found.');
}

/* ---------- NOTE: db.php intentionally NOT loaded (avoids DB_HOST error) ---------- */
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle       = 'Contact Us';
$pageDescription = 'Get in touch with Nature Nest Holidays — offices in Sodepur and Barasat, Kolkata.';
$currentPage     = 'contact';

/* ---------- Form handling ---------- */
$formErrors  = [];
$formSuccess = false;
$old = [
    'name'         => '',
    'email'        => '',
    'phone'        => '',
    'subject'      => '',
    'tour_interest'=> '',
    'travel_date'  => '',
    'how_found'    => '',
    'message'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['website'])) {

    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if ($old['name'] === '')                                $formErrors['name']    = 'Please enter your full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))  $formErrors['email']   = 'Please enter a valid email address.';
    if ($old['subject'] === '')                             $formErrors['subject'] = 'Please enter a subject.';
    if ($old['message'] === '')                             $formErrors['message'] = 'Please describe your enquiry.';
    if (mb_strlen($old['message']) > 2000)                  $formErrors['message'] = 'Message must be 2000 characters or fewer.';

    if (empty($formErrors)) {
        // Email (no DB needed)
        $to      = 'info@naturenestholidays.in';
        $subject = 'Contact Enquiry — ' . $old['subject'];
        $body    = "Full Name: {$old['name']}\n"
                 . "Email: {$old['email']}\n"
                 . "Phone: {$old['phone']}\n"
                 . "Subject: {$old['subject']}\n"
                 . "Tour Interest: {$old['tour_interest']}\n"
                 . "Travel Date: {$old['travel_date']}\n"
                 . "How did they find us: {$old['how_found']}\n\n"
                 . "Message:\n{$old['message']}\n";
        $headers = "From: no-reply@naturenestholidays.in\r\n"
                 . "Reply-To: {$old['email']}\r\n";

        // Backup log file
        @file_put_contents(
            __DIR__ . '/../contact_submissions.log',
            "[" . date('Y-m-d H:i:s') . "] {$old['name']} <{$old['email']}> | {$old['phone']} | {$old['subject']}\n{$old['message']}\n---\n",
            FILE_APPEND
        );

        @mail($to, $subject, $body, $headers);

        $_SESSION['contact_success'] = true;
        header('Location: ' . PAGES_URL . 'contact.php?sent=1');
        exit;
    }
}

if (isset($_GET['sent']) && !empty($_SESSION['contact_success'])) {
    $formSuccess = true;
    unset($_SESSION['contact_success']);
}

/* ---------- Header ---------- */
$__headerCandidates = [
    __DIR__ . '/../header.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../inc/header.php',
];
$__headerFile = null;
foreach ($__headerCandidates as $__h) if (file_exists($__h)) { $__headerFile = $__h; break; }
if (!$__headerFile) die('header.php not found.');
require_once $__headerFile;
?>

<!-- ============================================================
     PAGE-SPECIFIC STYLES (only for contact page)
     ============================================================ -->
<style>
/* --- Contact layout --- */
.contact-wrap{
  display:grid;
  grid-template-columns:380px 1fr;
  gap:38px;
  align-items:start;
}
@media (max-width:991px){ .contact-wrap{grid-template-columns:1fr;gap:28px} }

/* --- Left: info cards --- */
.contact-aside h3{
  font-family:var(--font-display);
  font-size:1.6rem;
  color:var(--forest);
  margin-bottom:12px;
}
.contact-aside .aside-lead{
  font-size:.92rem;
  color:var(--muted);
  line-height:1.7;
  margin-bottom:26px;
}
.info-card{
  display:flex;
  gap:14px;
  padding:18px 20px;
  background:#fff;
  border:1px solid var(--line);
  border-radius:14px;
  margin-bottom:14px;
  transition:all .25s var(--ease);
}
.info-card:hover{
  border-color:var(--gold);
  box-shadow:0 8px 22px rgba(15,61,46,.06);
  transform:translateY(-2px);
}
.info-card .ico{
  flex-shrink:0;
  width:42px;height:42px;
  display:grid;place-items:center;
  border-radius:10px;
  background:var(--forest-light);
  color:var(--gold-dark);
  font-size:17px;
}
.info-card h4{
  font-family:var(--font-body);
  font-size:.95rem;
  font-weight:700;
  color:var(--forest);
  margin-bottom:5px;
}
.info-card p,
.info-card a{
  font-size:.85rem;
  color:var(--muted);
  line-height:1.55;
  display:block;
  transition:color .2s var(--ease);
}
.info-card a:hover{ color:var(--gold-dark); }
.info-card .map-link{
  display:inline-flex;
  align-items:center;
  gap:6px;
  margin-top:8px;
  font-size:.8rem;
  font-weight:600;
  color:var(--forest);
}
.info-card .map-link:hover{ color:var(--gold-dark); }

/* --- Follow us --- */
.follow-block{
  padding:22px 20px;
  background:var(--cream);
  border-radius:14px;
  border:1px solid var(--line);
}
.follow-block h4{
  font-family:var(--font-body);
  font-size:.82rem;
  font-weight:700;
  letter-spacing:.12em;
  text-transform:uppercase;
  color:var(--forest);
  margin-bottom:14px;
}
.follow-block .socials{ display:flex; gap:10px; }
.follow-block .socials a{
  width:40px;height:40px;
  border-radius:50%;
  display:grid;place-items:center;
  background:var(--forest);
  color:#fff;
  transition:all .25s var(--ease);
}
.follow-block .socials a:hover{
  background:var(--gold);
  color:var(--forest-deep);
  transform:translateY(-3px);
}

/* --- Right: form --- */
.form-panel{
  background:#fff;
  border:1px solid var(--line);
  border-radius:18px;
  padding:34px 34px 30px;
  box-shadow:0 10px 30px rgba(15,61,46,.05);
}
.form-panel > h3{
  font-family:var(--font-display);
  font-size:1.4rem;
  color:var(--forest);
  margin-bottom:6px;
}
.form-panel > .form-intro{
  font-size:.88rem;
  color:var(--muted);
  margin-bottom:24px;
}

.form-row{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
}
@media (max-width:600px){ .form-row{grid-template-columns:1fr;gap:0} }

.field .char-counter{
  display:block;
  text-align:right;
  font-size:.72rem;
  color:var(--muted);
  margin-top:5px;
}

.send-btn{
  width:100%;
  margin-top:8px;
  padding:15px 28px;
  border-radius:var(--radius-pill);
  border:0;
  background:linear-gradient(90deg,#4a3aff 0%,#5b3aff 55%,#c141d6 100%);
  color:#fff;
  font-weight:600;
  font-size:.96rem;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:9px;
  cursor:pointer;
  transition:all .28s var(--ease);
  box-shadow:0 8px 22px rgba(74,58,255,.28);
}
.send-btn:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(74,58,255,.38);
}

.secure-note{
  display:flex;
  align-items:center;
  gap:9px;
  margin-top:16px;
  font-size:.8rem;
  color:var(--muted);
}
.secure-note i{ color:var(--gold-dark); }

/* --- Our Locations --- */
.locations-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:22px;
  margin-top:44px;
}
@media (max-width:767px){ .locations-grid{grid-template-columns:1fr} }
.loc-card{
  background:#fff;
  border:1px solid var(--line);
  border-radius:16px;
  padding:26px 26px 22px;
  transition:all .28s var(--ease);
}
.loc-card:hover{
  transform:translateY(-4px);
  box-shadow:0 14px 34px rgba(15,61,46,.08);
  border-color:var(--green-soft);
}
.loc-card .pin{
  width:44px;height:44px;
  border-radius:50%;
  display:grid;place-items:center;
  background:var(--forest-light);
  color:var(--gold-dark);
  font-size:18px;
  margin-bottom:14px;
}
.loc-card h3{
  font-family:var(--font-display);
  font-size:1.18rem;
  color:var(--forest);
  margin-bottom:8px;
}
.loc-card p{
  font-size:.88rem;
  color:var(--muted);
  line-height:1.6;
  margin-bottom:14px;
}
.loc-card .map-link{
  display:inline-flex;align-items:center;gap:7px;
  font-size:.85rem;font-weight:600;color:var(--forest);
}
.loc-card .map-link:hover{ color:var(--gold-dark); }
</style>

<main>

  <!-- ============================================================
       PAGE HERO
       ============================================================ -->
  <section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1920&q=80');">
    <div class="container page-hero-inner">
      <span class="eyebrow eyebrow--light">Get In Touch</span>
      <h1>Contact Us</h1>
      <p>Tell us where you want to go — our travel experts will craft the perfect journey for you.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep">/</span>
        <span>Contact Us</span>
      </nav>
    </div>
  </section>

  <!-- ============================================================
       CONTACT WRAP — Info (left) + Form (right)
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="contact-wrap">

        <!-- ---------- LEFT: Info Cards ---------- -->
        <aside class="contact-aside">
          <h3>Get in Touch</h3>
          <p class="aside-lead">We're here to help you plan your perfect getaway. Reach out to us through any of the channels below.</p>

          <!-- Sodepur Office -->
          <div class="info-card">
            <div class="ico"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <h4>Sodepur Office</h4>
              <p>1/F Ma Apartment, Dakshinayan,<br>Sodepur, Kolkata – 700110</p>
              <a class="map-link" href="https://maps.google.com/?q=Sodepur+Kolkata+700110" target="_blank" rel="noopener">
                <i class="fa-solid fa-diamond-turn-right"></i> Get Directions
              </a>
            </div>
          </div>

          <!-- Barasat Office -->
          <div class="info-card">
            <div class="ico"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <h4>Barasat Office</h4>
              <p>6, Raja Ram Mohan Road, 2nd Floor,<br>Nabapally, Barasat – 700126</p>
              <a class="map-link" href="https://maps.google.com/?q=Raja+Ram+Mohan+Road+Barasat+700126" target="_blank" rel="noopener">
                <i class="fa-solid fa-diamond-turn-right"></i> Get Directions
              </a>
            </div>
          </div>

          <!-- Call Us -->
          <div class="info-card">
            <div class="ico"><i class="fa-solid fa-phone-volume"></i></div>
            <div>
              <h4>Call Us</h4>
              <a href="tel:+919832468096">+91 98324 68096</a>
              <a href="tel:+918420958514">+91 84209 58514</a>
            </div>
          </div>

          <!-- Email Us -->
          <div class="info-card">
            <div class="ico"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <h4>Email Us</h4>
              <a href="mailto:info@naturenestholidays.in">info@naturenestholidays.in</a>
            </div>
          </div>

          <!-- Working Hours -->
          <div class="info-card">
            <div class="ico"><i class="fa-solid fa-clock"></i></div>
            <div>
              <h4>Working Hours</h4>
              <p>Monday – Sunday: 7:00 AM – 10:00 PM<br>24/7 Emergency Support Available</p>
            </div>
          </div>

          <!-- Follow Us -->
          <div class="follow-block">
            <h4>Follow Us</h4>
            <div class="socials">
              <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
              <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
              <a href="https://wa.me/919832468096" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
              <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            </div>
          </div>
        </aside>

        <!-- ---------- RIGHT: Form ---------- -->
        <div class="form-panel">
          <h3>Send Us a Message</h3>
          <p class="form-intro">Fill out the form below and we'll get back to you as soon as possible.</p>

          <?php if ($formSuccess): ?>
            <div class="form-success">
              <i class="fa-solid fa-circle-check"></i>
              <span><strong>Thank you!</strong> Your enquiry has been received. Our team will contact you shortly.</span>
            </div>
          <?php else: ?>

          <form method="post" action="<?= PAGES_URL ?>contact.php" novalidate>
            <!-- Honeypot -->
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off"
                   style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">

            <!-- Row 1: Name + Email -->
            <div class="form-row">
              <div class="field <?= isset($formErrors['name']) ? 'has-error' : '' ?>">
                <label for="c-name">Full Name <span style="color:#D14343;">*</span></label>
                <input type="text" id="c-name" name="name" required
                       value="<?= htmlspecialchars($old['name']) ?>"
                       placeholder="Enter your full name">
                <span class="error-msg"><?= $formErrors['name'] ?? '' ?></span>
              </div>

              <div class="field <?= isset($formErrors['email']) ? 'has-error' : '' ?>">
                <label for="c-email">Email Address <span style="color:#D14343;">*</span></label>
                <input type="email" id="c-email" name="email" required
                       value="<?= htmlspecialchars($old['email']) ?>"
                       placeholder="Enter your email address">
                <span class="error-msg"><?= $formErrors['email'] ?? '' ?></span>
              </div>
            </div>

            <!-- Row 2: Phone + Subject -->
            <div class="form-row">
              <div class="field">
                <label for="c-phone">Phone Number</label>
                <input type="tel" id="c-phone" name="phone"
                       value="<?= htmlspecialchars($old['phone']) ?>"
                       placeholder="Enter your phone number">
                <span style="font-size:.72rem;color:var(--muted);margin-top:5px;display:block;">We'll call you if needed</span>
              </div>

              <div class="field <?= isset($formErrors['subject']) ? 'has-error' : '' ?>">
                <label for="c-subject">Subject <span style="color:#D14343;">*</span></label>
                <input type="text" id="c-subject" name="subject" required
                       value="<?= htmlspecialchars($old['subject']) ?>"
                       placeholder="What is this regarding?">
                <span class="error-msg"><?= $formErrors['subject'] ?? '' ?></span>
              </div>
            </div>

            <!-- Row 3: Tour Interest + Travel Date -->
            <div class="form-row">
              <div class="field">
                <label for="c-tour">Tour Interest</label>
                <select id="c-tour" name="tour_interest">
                  <option value="">Select a tour (optional)</option>
                  <option value="Darjeeling Escape"      <?= $old['tour_interest']==='Darjeeling Escape'      ? 'selected':'' ?>>Darjeeling Escape</option>
                  <option value="Sikkim Adventure"       <?= $old['tour_interest']==='Sikkim Adventure'       ? 'selected':'' ?>>Sikkim Adventure</option>
                  <option value="Kashmir Paradise"       <?= $old['tour_interest']==='Kashmir Paradise'       ? 'selected':'' ?>>Kashmir Paradise</option>
                  <option value="Kerala Backwaters"      <?= $old['tour_interest']==='Kerala Backwaters'      ? 'selected':'' ?>>Kerala Backwaters</option>
                  <option value="Goa Beach Holiday"      <?= $old['tour_interest']==='Goa Beach Holiday'      ? 'selected':'' ?>>Goa Beach Holiday</option>
                  <option value="Rajasthan Heritage"     <?= $old['tour_interest']==='Rajasthan Heritage'     ? 'selected':'' ?>>Rajasthan Heritage</option>
                  <option value="Destination Wedding"    <?= $old['tour_interest']==='Destination Wedding'    ? 'selected':'' ?>>Destination Wedding</option>
                  <option value="Custom Itinerary"       <?= $old['tour_interest']==='Custom Itinerary'       ? 'selected':'' ?>>Custom Itinerary</option>
                </select>
              </div>

              <div class="field">
                <label for="c-date">Travel Date (Optional)</label>
                <input type="date" id="c-date" name="travel_date"
                       value="<?= htmlspecialchars($old['travel_date']) ?>">
              </div>
            </div>

            <!-- Row 4: How did you find us -->
            <div class="field">
              <label for="c-found">How did you find us?</label>
              <select id="c-found" name="how_found">
                <option value="">Select one (optional)</option>
                <option value="Google Search"    <?= $old['how_found']==='Google Search'    ? 'selected':'' ?>>Google Search</option>
                <option value="Social Media"     <?= $old['how_found']==='Social Media'     ? 'selected':'' ?>>Social Media</option>
                <option value="Friend / Family"  <?= $old['how_found']==='Friend / Family'  ? 'selected':'' ?>>Friend / Family</option>
                <option value="Travel Blog"      <?= $old['how_found']==='Travel Blog'      ? 'selected':'' ?>>Travel Blog</option>
                <option value="Previous Customer"<?= $old['how_found']==='Previous Customer'? 'selected':'' ?>>Previous Customer</option>
                <option value="Other"            <?= $old['how_found']==='Other'            ? 'selected':'' ?>>Other</option>
              </select>
            </div>

            <!-- Row 5: Message -->
            <div class="field <?= isset($formErrors['message']) ? 'has-error' : '' ?>">
              <label for="c-message">Your Message <span style="color:#D14343;">*</span></label>
              <textarea id="c-message" name="message" required maxlength="2000"
                        placeholder="Please describe your inquiry or request in detail..."><?= htmlspecialchars($old['message']) ?></textarea>
              <span class="char-counter"><span id="charCount">0</span> / 2000 characters</span>
              <span class="error-msg"><?= $formErrors['message'] ?? '' ?></span>
            </div>

            <!-- Send Button -->
            <button type="submit" class="send-btn">
              <i class="fa-solid fa-paper-plane"></i> Send Message
            </button>

            <p class="secure-note">
              <i class="fa-solid fa-shield-halved"></i>
              Your information is secure and will not be shared with third parties.
            </p>
          </form>

          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================================================
       OUR LOCATIONS
       ============================================================ -->
  <section class="section section--tint">
    <div class="container">
      <div class="section-head reveal">
        <span class="eyebrow">Visit Us</span>
        <h2>Our Locations</h2>
        <p>Two offices across Kolkata — drop by for a chat about your next journey.</p>
      </div>

      <div class="locations-grid reveal">

        <div class="loc-card">
          <div class="pin"><i class="fa-solid fa-location-dot"></i></div>
          <h3>Sodepur Office</h3>
          <p>1/F Ma Apartment, Dakshinayan,<br>Sodepur, Kolkata – 700110</p>
          <a class="map-link" href="https://maps.google.com/?q=Sodepur+Kolkata+700110" target="_blank" rel="noopener">
            <i class="fa-solid fa-diamond-turn-right"></i> Get Directions
          </a>
        </div>

        <div class="loc-card">
          <div class="pin"><i class="fa-solid fa-location-dot"></i></div>
          <h3>Barasat Office</h3>
          <p>6, Raja Ram Mohan Road, 2nd Floor,<br>Nabapally, Barasat – 700126</p>
          <a class="map-link" href="https://maps.google.com/?q=Raja+Ram+Mohan+Road+Barasat+700126" target="_blank" rel="noopener">
            <i class="fa-solid fa-diamond-turn-right"></i> Get Directions
          </a>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================================================
       CTA
       ============================================================ -->
  <section class="cta">
    <div class="container">
      <div class="cta-inner reveal">
        <span class="eyebrow eyebrow--light">Prefer To Browse?</span>
        <h2>Explore Our Tour Packages</h2>
        <p>Curated itineraries across India's most beautiful destinations.</p>
        <div class="cta-actions">
          <a href="<?= PAGES_URL ?>tour-packages.php" class="btn btn-primary btn-lg">View Packages</a>
          <a href="<?= PAGES_URL ?>destination-wedding.php" class="btn btn-ghost btn-lg">Destination Weddings</a>
        </div>
      </div>
    </div>
  </section>

</main>

<!-- ============================================================
     Char counter script
     ============================================================ -->
<script>
(function(){
  var ta = document.getElementById('c-message');
  var cc = document.getElementById('charCount');
  if (ta && cc) {
    var update = function(){ cc.textContent = ta.value.length; };
    ta.addEventListener('input', update);
    update();
  }
})();
</script>

<?php
$__footerCandidates = [
    __DIR__ . '/../footer.php',
    __DIR__ . '/../includes/footer.php',
    __DIR__ . '/../inc/footer.php',
];
$__footerFile = null;
foreach ($__footerCandidates as $__f) if (file_exists($__f)) { $__footerFile = $__f; break; }
if ($__footerFile) require_once $__footerFile;
?>
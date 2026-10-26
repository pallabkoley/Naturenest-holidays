<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize Database
$db = new Database();
$conn = $db->getConnection();

// Force fresh connection
while ($conn->more_results()) {
    $conn->next_result();
    if ($res = $conn->store_result()) {
        $res->free();
    }
}

// Check connection
if (!$conn) {
    die("Database connection failed");
}

// Initialize variables
$tour_list = null;
$included_tours = [];

// Get tour list by slug
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    
    if (empty($slug)) {
        header('Location: tour-lists.php');
        exit();
    }
    
    try {
        // Get tour list details
        $stmt = $conn->prepare("SELECT * FROM tour_package_lists WHERE slug = ? LIMIT 1");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $tour_list = $result->fetch_assoc();
        $stmt->close();

        if (!$tour_list) {
            header('Location: tour-lists.php');
            exit();
        }

        // Get included tours
        $selected_tours = json_decode($tour_list['selected_tours'] ?? '[]', true);
        if (!empty($selected_tours) && is_array($selected_tours)) {
            $placeholders = implode(',', array_fill(0, count($selected_tours), '?'));
            $types = str_repeat('i', count($selected_tours));
            
            $tour_stmt = $conn->prepare("
                SELECT id, title, slug, duration_days, duration_nights, 
                       difficulty_level, group_size, price, price_per_person, 
                       featured_image, tour_overview, best_season
                FROM tour_package_lists 
                WHERE id IN ($placeholders) AND status = 'active'
            ");
            $tour_stmt->bind_param($types, ...$selected_tours);
            $tour_stmt->execute();
            $tour_result = $tour_stmt->get_result();
            while ($tour_row = $tour_result->fetch_assoc()) {
                $included_tours[] = $tour_row;
            }
            $tour_stmt->close();
        }

        // Process gallery images for hero slider
        $gallery_images = json_decode($tour_list['gallery_images'] ?? '[]', true);
        if (!is_array($gallery_images)) {
            $gallery_images = [];
        }
        
        // Add featured image to gallery if not already included
        if (!empty($tour_list['featured_image']) && !in_array($tour_list['featured_image'], $gallery_images)) {
            array_unshift($gallery_images, $tour_list['featured_image']);
        }
        
        // Process inclusions and exclusions
        $inclusions = !empty($tour_list['inclusions']) ? explode("\n", $tour_list['inclusions']) : [];
        $exclusions = !empty($tour_list['exclusions']) ? explode("\n", $tour_list['exclusions']) : [];
        $faqs = !empty($tour_list['faqs']) ? json_decode($tour_list['faqs'], true) : [];
        
        // Process important notes
        $important_notes = !empty($tour_list['important_notes']) ? explode("\n", $tour_list['important_notes']) : [];
        
        // Process itinerary data - NEW: Check if itinerary is JSON or plain text
        $itinerary_items = [];
        $itinerary_json = $tour_list['tour_itinerary'];
        if (!empty($itinerary_json)) {
            $decoded_itinerary = json_decode($itinerary_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_itinerary)) {
                $itinerary_items = $decoded_itinerary;
            } else {
                // If not JSON, treat as plain text
                $itinerary_items = [['day' => '1', 'title' => 'Itinerary', 'content' => $itinerary_json]];
            }
        }
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        header('Location: tour-lists.php');
        exit();
    }
} else {
    header('Location: tour-lists.php');
    exit();
}

// Set page metadata
$page_title = $tour_list['title'] . ' - Package Collection - ' . SITE_NAME;
$meta_description = $tour_list['meta_description'] ?? 'Explore this comprehensive tour package collection including multiple destinations. Curated travel experiences by ' . SITE_NAME;
$meta_keywords = $tour_list['meta_keywords'] ?? 'tour package, travel collection, multi-destination tour, holiday package';

// Add JavaScript for interactivity


// Include header
include '../includes/header.php';
?>
<!-- Hero Section -->
<section class="tour-hero">
    <?php if (!empty($tour_list['featured_image'])): ?>
        <?php 
        $featuredImage = ltrim($tour_list['featured_image'], '/');
        $featuredImageUrl = SITE_URL . '/' . $featuredImage;
        $localPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $featuredImage;
        if (!file_exists($localPath) || !is_file($localPath)) {
            $featuredImageUrl = SITE_URL . '/assets/images/default-tour.jpg';
        }
        ?>
        <img src="<?php echo $featuredImageUrl; ?>" 
             alt="<?php echo htmlspecialchars($tour_list['title']); ?>" 
             class="tour-hero-image"
             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-tour.jpg'">
    <?php else: ?>
        <img src="<?php echo SITE_URL; ?>/assets/images/default-tour.jpg" 
             alt="<?php echo htmlspecialchars($tour_list['title']); ?>" 
             class="tour-hero-image">
    <?php endif; ?>
    <div class="tour-hero-overlay">
        <div class="tour-hero-content">
            <span class="tour-hero-badge">Package Collection</span>
            <h2 class="section-title-modern">
                <span class="title-gradient"><?php echo htmlspecialchars($tour_list['title']); ?></span>
            </h2>
            <p class="section-subtitle">This curated package includes multiple destinations for comprehensive travel experiences.</p>
        </div>
    </div>
</section>

<div class="tour-detail-container">
    <div class="container">
        <!-- Main Layout Row -->
        <div class="row">
            <!-- Left Content (70% width) -->
            <div class="col-lg-10 col-xl-9">
                <div class="main-content-section left-content">
                    
                    <!-- Hero Section with Slider -->
                    <?php if (!empty($gallery_images)): ?>
<section class="gallery-section" data-aos="fade-up">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Gallery</h2>
            <p class="section-description">Explore moments from this amazing package</p>
        </div>
        
        <!-- Bootstrap Carousel -->
        <div id="packageHeroCarousel" class="carousel slide gallery-slider" data-bs-ride="carousel">
            <!-- Indicators/Dots -->
            <div class="carousel-indicators">
                <?php foreach ($gallery_images as $index => $image): ?>
                    <button type="button" 
                            data-bs-target="#packageHeroCarousel" 
                            data-bs-slide-to="<?php echo $index; ?>" 
                            class="<?php echo $index === 0 ? 'active' : ''; ?>"
                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
            
            <!-- Slides -->
            <div class="carousel-inner">
                <?php foreach ($gallery_images as $index => $image): ?>
                    <?php 
                    $galleryImage = ltrim($image, '/');
                    $galleryImageUrl = SITE_URL . '/' . $galleryImage;
                    $localPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $galleryImage;
                    if (!file_exists($localPath) || !is_file($localPath)) {
                        $galleryImageUrl = SITE_URL . '/assets/images/default-tour.jpg';
                    }
                    ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo $galleryImageUrl; ?>" 
                             class="d-block w-100"
                             alt="Gallery Image <?php echo $index + 1; ?>"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-tour.jpg'">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Navigation Buttons -->
            <button class="carousel-control-prev" type="button" data-bs-target="#packageHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#packageHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

                    
                    <!-- Title and Description -->
                    <section class="content-section" data-aos="fade-up">
                        <h1 class="section-title">
                            <i class="fas fa-layer-group"></i>
                            <?php echo htmlspecialchars($tour_list['title']); ?>
                        </h1>
                        <p class="section-description">A comprehensive tour package offering multiple destinations for unforgettable travel experiences</p>
                    </section>
                    
                    <!-- Quick Stats Bar -->
                    <div class="quick-stats-bar" data-aos="fade-up">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <div class="quick-stat">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-number"><?php echo $tour_list['duration_days']; ?>D</div>
                                        <div class="quick-stat-label">Duration</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="quick-stat">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-number"><?php echo $tour_list['group_size'] ?? 'Custom'; ?></div>
                                        <div class="quick-stat-label">Group Size</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="quick-stat">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-mountain"></i>
                                    </div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-number"><?php echo ucfirst($tour_list['difficulty_level'] ?? 'Moderate'); ?></div>
                                        <div class="quick-stat-label">Difficulty</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="quick-stat">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div class="quick-stat-content">
                                        <div class="quick-stat-number">₹<?php echo number_format($tour_list['price'], 0); ?></div>
                                        <div class="quick-stat-label">Starting Price</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Overview -->
                    <section class="content-section" id="overview" data-aos="fade-up">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Package Overview
                            </h2>
                        </div>
                       <div class="overview-content">
    <?php echo $tour_list['tour_overview'] ?? 'This comprehensive tour package offers an unforgettable journey through multiple destinations.'; ?>
</div>
                    </section>
                    
                    <!-- Itinerary Section with Accordion -->
                    <?php if (!empty($itinerary_items)): ?>
                    <section class="content-section" id="itinerary" data-aos="fade-up">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-route"></i>
                                Detailed Itinerary
                            </h2>
                        </div>
                        
                        <div class="itinerary-accordion">
                            <?php foreach ($itinerary_items as $index => $item): ?>
                            <div class="itinerary-accordion-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="itinerary-accordion-header">
                                    <div class="itinerary-accordion-day">
                                        <div class="day-number"><?php echo $item['day'] ?? ($index + 1); ?></div>
                                        <h3 class="itinerary-accordion-title"><?php echo htmlspecialchars($item['title'] ?? 'Day ' . ($index + 1)); ?></h3>
                                    </div>
                                    <div class="itinerary-accordion-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="itinerary-accordion-content">
                                    <p><?php echo nl2br(htmlspecialchars($item['content'] ?? '')); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Inclusions & Exclusions -->
                    <?php if (!empty($inclusions) || !empty($exclusions)): ?>
                    <section class="content-section" id="inclusions" data-aos="fade-up">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-list-check"></i>
                                What's Included & Excluded
                            </h2>
                        </div>
                        
                        <div class="row g-4">
                            <?php if (!empty($inclusions)): ?>
                            <div class="col-md-6">
                                <div class="inclusion-card included">
                                    <div class="inclusion-card-header">
                                        <h3 class="inclusion-card-title">
                                            <i class="fas fa-check-circle me-2"></i> What's Included
                                        </h3>
                                    </div>
                                    <div class="inclusion-card-body">
                                        <ul class="inclusion-list">
                                            <?php foreach ($inclusions as $inclusion): ?>
                                                <?php if (trim($inclusion)): ?>
                                                <li>
                                                    <i class="fas fa-check"></i>
                                                    <span><?php echo htmlspecialchars(trim($inclusion)); ?></span>
                                                </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($exclusions)): ?>
                            <div class="col-md-6">
                                <div class="inclusion-card excluded">
                                    <div class="inclusion-card-header">
                                        <h3 class="inclusion-card-title">
                                            <i class="fas fa-times-circle me-2"></i> What's Excluded
                                        </h3>
                                    </div>
                                    <div class="inclusion-card-body">
                                        <ul class="inclusion-list">
                                            <?php foreach ($exclusions as $exclusion): ?>
                                                <?php if (trim($exclusion)): ?>
                                                <li>
                                                    <i class="fas fa-times"></i>
                                                    <span><?php echo htmlspecialchars(trim($exclusion)); ?></span>
                                                </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Important Information -->
                    <section class="content-section" id="information">
                        <div class="section-header" data-aos="fade-up">
                            <h2 class="section-title">
                                <i class="fas fa-exclamation-triangle"></i>
                                Important Information
                            </h2>
                        </div>
                        
                        <div class="row g-4">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <?php if (!empty($tour_list['permit_info'])): ?>
                                <div class="modern-card gradient-1" data-aos="fade-right">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Permit Information</h3>
                                        <div class="modern-card-text">
                                            <?php echo nl2br(htmlspecialchars($tour_list['permit_info'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($important_notes)): ?>
                                <div class="modern-card gradient-3" data-aos="fade-right" data-aos-delay="100">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Important Notes</h3>
                                        <div class="modern-card-text">
                                            <ul class="modern-list">
                                                <?php foreach ($important_notes as $note): ?>
                                                    <?php if (trim($note)): ?>
                                                    <li>
                                                        <i class="fas fa-circle"></i>
                                                        <span><?php echo htmlspecialchars(trim($note)); ?></span>
                                                    </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <?php if (!empty($tour_list['cancellation_policy'])): ?>
                                <div class="modern-card gradient-2" data-aos="fade-left">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-ban"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Cancellation Policy</h3>
                                        <div class="modern-card-text">
                                            <?php echo nl2br(htmlspecialchars($tour_list['cancellation_policy'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tour_list['guide_service'])): ?>
                                <div class="modern-card gradient-6" data-aos="fade-left" data-aos-delay="100">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Guide Service</h3>
                                        <div class="modern-card-text">
                                            <?php echo nl2br(htmlspecialchars($tour_list['guide_service'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Services & Facilities -->
                    <section class="content-section" id="services">
                        <div class="section-header" data-aos="fade-up">
                            <h2 class="section-title">
                                <i class="fas fa-hotel"></i>
                                Services & Facilities
                            </h2>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <?php if (!empty($tour_list['accommodation'])): ?>
                                <div class="modern-card gradient-5" data-aos="fade-right">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Accommodation</h3>
                                        <div class="modern-card-text">
                                            <?php echo nl2br(htmlspecialchars($tour_list['accommodation'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <?php if (!empty($tour_list['transport'])): ?>
                                <div class="modern-card gradient-7" data-aos="fade-left">
                                    <div class="modern-card-icon">
                                        <i class="fas fa-bus"></i>
                                    </div>
                                    <div class="modern-card-content">
                                        <h3 class="modern-card-title">Transport</h3>
                                        <div class="modern-card-text">
                                            <?php echo nl2br(htmlspecialchars($tour_list['transport'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- FAQ Section -->
                    <?php if (!empty($faqs)): ?>
                    <section class="content-section" id="faq" data-aos="fade-up">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-question-circle"></i>
                                Frequently Asked Questions
                            </h2>
                        </div>
                        
                        <div class="faq-container">
                            <?php foreach ($faqs as $index => $faq): ?>
                            <div class="faq-item <?php echo $index === 0 ? 'active' : ''; ?>" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                                <div class="faq-question">
                                    <?php echo htmlspecialchars($faq['question'] ?? ''); ?>
                                    <div class="faq-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="faq-answer">
                                    <?php echo nl2br(htmlspecialchars($faq['answer'] ?? '')); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Right Sidebar (30% width) -->
            <div class="col-lg-4 col-xl-3">
                <div class="right-sidebar" id="enquirySidebar">
                    
                    <!-- Quick Enquiry Form -->
                    <div class="enquiry-form-container" data-aos="fade-left">
                        <h3><i class="fas fa-envelope"></i> Quick Enquiry</h3>
                        <form id="enquiryForm" class="enquiry-form">
                            <div class="form-group">
                                <label for="enquiryName">
                                    <i class="fas fa-user"></i> Full Name *
                                </label>
                                <input type="text" id="enquiryName" name="name" class="form-control" placeholder="Your Name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="enquiryEmail">
                                    <i class="fas fa-envelope"></i> Email Address *
                                </label>
                                <input type="email" id="enquiryEmail" name="email" class="form-control" placeholder="your@email.com" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="enquiryPhone">
                                    <i class="fas fa-phone"></i> Phone Number *
                                </label>
                                <input type="tel" id="enquiryPhone" name="phone" class="form-control" placeholder="10-digit number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="enquiryMessage">
                                    <i class="fas fa-comment"></i> Your Message
                                </label>
                                <textarea id="enquiryMessage" name="message" class="form-control" placeholder="I'm interested in this package..."></textarea>
                            </div>
                            
                            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($tour_list['title']); ?>">
                            <input type="hidden" name="package_url" value="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
                            
                            <button type="submit" class="btn-enquire">
                                <i class="fas fa-paper-plane"></i> Send Enquiry
                            </button>
                            
                            <div class="text-center mt-3" style="font-size: 12px; color: #718096;">
                                <i class="fas fa-shield-alt me-1"></i> Your information is secure with us
                            </div>
                        </form>
                    </div>
                    
                    <!-- Got a Question Section -->
                    <div class="contact-info-box" data-aos="fade-left" data-aos-delay="100">
                        <h3><i class="fas fa-question-circle"></i> Got a Question?</h3>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Call Us</h4>
                                <p>
                                    <a href="tel:+91 8159901115">+91 8159901115</a><br>
                                    <a href="tel:+91 8670951999">+91 8670951999</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Email Us</h4>
                                <p>
                                    <a href="mailto:info@newayatra.com">info@newayatra.com</a><br>
                                    <a href="mailto:support@newayatra.com">support@newayatra.com</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Visit Us</h4>
                                <p>
                                    Newa Yatra Travels<br>
                                    Darjeeling, West Bengal<br>
                                    India - 734101
                                </p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Working Hours</h4>
                                <p>
                                    Monday - Friday: 9:00 AM - 7:00 PM<br>
                                    Saturday: 9:00 AM - 5:00 PM<br>
                                    Sunday: 10:00 AM - 4:00 PM
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Summary -->
                    <div class="contact-info-box" data-aos="fade-left" data-aos-delay="200">
                        <h3><i class="fas fa-info-circle"></i> Package Summary</h3>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Duration</h4>
                                <p><?php echo $tour_list['duration_days']; ?> Days / <?php echo $tour_list['duration_nights']; ?> Nights</p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Group Size</h4>
                                <p><?php echo $tour_list['group_size'] ?? 'Custom Group'; ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Starting Price</h4>
                                <p>₹<?php echo number_format($tour_list['price'], 0); ?> <?php echo $tour_list['price_per_person'] ? 'per person' : 'per package'; ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="contact-info-icon">
                                <i class="fas fa-sun"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Best Season</h4>
                                <p><?php echo htmlspecialchars($tour_list['best_season'] ?? 'All Year Round'); ?></p>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="#booking" class="btn btn-primary w-100">
                                <i class="fas fa-calendar-check me-2"></i> Book Now
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Booking CTA (Full width below both columns) -->
        <section class="cta-section mt-5" id="booking" data-aos="fade-up">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <h2 class="cta-title">Ready to Book Your Adventure?</h2>
                        <p class="cta-description">
                            Secure your spot on this amazing package today. Limited availability for the best travel experience.
                        </p>
                        <div class="cta-buttons">
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-primary btn-lg px-5 py-3">
                                <i class="fas fa-envelope me-2"></i> Enquire Now
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/booking.php?package=<?php echo urlencode($tour_list['slug']); ?>" 
                               class="btn btn-outline-light btn-lg px-5 py-3 ms-3">
                                <i class="fas fa-calendar-check me-2"></i> Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
   
    // FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });
            
            // Open clicked item if it was closed
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
    
    // Itinerary Accordion
    const itineraryItems = document.querySelectorAll('.itinerary-accordion-item');
    itineraryItems.forEach(item => {
        const header = item.querySelector('.itinerary-accordion-header');
        header.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Close all items
            itineraryItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });
            
            // Open clicked item if it was closed
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
   
});
</script>
<style>
.container{
    max-width: 1600px !important;
}

.gallery-section{
    padding: 0 !important;
}
.section-title::after{
    left:8% !important;
}
/* New Layout Styles */
.tour-detail-container {
    background: #f8f9fa;
    padding: 30px 0;
}

.main-content-section {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Left Side (Larger) */
.left-content {
    position: relative;
}

/* Right Sidebar (Fixed) */
.right-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
    margin-bottom: 30px;
}

.right-sidebar.sticky {
    position: fixed;
    top: 100px;
    width: 350px;
    z-index: 100;
}

/* Responsive breakpoints */
@media (max-width: 1199px) {
    .right-sidebar {
        position: relative;
        top: 0;
    }
    
    .right-sidebar.sticky {
        position: relative;
        width: 100%;
        top: 0;
    }
}

/* Enquiry Form Styling */
.enquiry-form-container {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e0e6ed;
    margin-bottom: 30px;
}

.enquiry-form-container h3 {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f4f8;
    display: flex;
    align-items: center;
    gap: 10px;
}

.enquiry-form-container h3 i {
    color: #c66b05;
}

.enquiry-form .form-group {
    margin-bottom: 20px;
}

.enquiry-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.enquiry-form label i {
    color: #c66b05;
    margin-right: 8px;
    width: 16px;
}

.enquiry-form .form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e6ed;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
    color: #2c3e50;
}

.enquiry-form .form-control:focus {
    outline: none;
    border-color: #c66b05;
    box-shadow: 0 0 0 3px rgba(198, 107, 5, 0.1);
    background: #fffaf0;
}

.enquiry-form textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.enquiry-form .btn-enquire {
    background: linear-gradient(135deg, #c66b05, #e67e22);
    color: white;
    border: none;
    padding: 14px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    font-size: 16px;
    margin-top: 10px;
}

.enquiry-form .btn-enquire:hover {
    background: linear-gradient(135deg, #b85c00, #d26900);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(198, 107, 5, 0.2);
}

.enquiry-form .btn-enquire:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Contact Info Box */
.contact-info-box {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e0e6ed;
}

.contact-info-box h3 {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f4f8;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-info-box h3 i {
    color: #c66b05;
}

.contact-info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f4f8;
}

.contact-info-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.contact-info-icon {
    width: 40px;
    height: 40px;
    background: rgba(198, 107, 5, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #c66b05;
    font-size: 1rem;
    margin-right: 15px;
    flex-shrink: 0;
}

.contact-info-content h4 {
    color: #2c3e50;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.contact-info-content p {
    color: #4a5568;
    font-size: 14px;
    line-height: 1.5;
    margin: 0;
}

.contact-info-content a {
    color: #c66b05;
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-info-content a:hover {
    color: #b85c00;
    text-decoration: underline;
}

/* Itinerary Accordion Styles */
.itinerary-accordion {
    margin: 30px 0;
}

.itinerary-accordion-item {
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.itinerary-accordion-item.active {
    background: white;
    border-color: #c66b05;
    box-shadow: 0 2px 10px rgba(198, 107, 5, 0.1);
}

.itinerary-accordion-header {
    padding: 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.itinerary-accordion-item.active .itinerary-accordion-header {
    background: rgba(198, 107, 5, 0.05);
}

.itinerary-accordion-day {
    display: flex;
    align-items: center;
    gap: 15px;
}

.day-number {
    width: 36px;
    height: 36px;
    background: #c66b05;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}

.itinerary-accordion-title {
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.itinerary-accordion-toggle {
    color: #c66b05;
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.itinerary-accordion-item.active .itinerary-accordion-toggle {
    transform: rotate(180deg);
}

.itinerary-accordion-content {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.itinerary-accordion-item.active .itinerary-accordion-content {
    padding: 20px;
    max-height: 1000px;
}

.itinerary-accordion-content p {
    color: #4a5568;
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

.itinerary-accordion-content p + p {
    margin-top: 10px;
}

/* Quick Stats Bar - Modified for new layout */
.quick-stats-bar {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin: 30px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Gallery Section */
.gallery-section {
    margin: 30px 0;
}

/* Content Sections Styling */
.content-section {
    margin: 30px 0;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e6ed;
}

.content-section:last-child {
    border-bottom: none;
}

.section-header {
    margin-bottom: 25px;
}

.section-title {
    color: #2c3e50;
    font-size: 1.5rem;
    font-weight: 700;
    /*margin-bottom: 10px;*/
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f4f8;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #c66b05;
    background: rgba(198, 107, 5, 0.1);
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-description {
    color: #718096;
    font-size: 1rem;
    margin: 0;
}

/* Package Overview */
.overview-content {
    color: #4a5568;
    font-size: 16px;
    line-height: 1.8;
    text-align: justify;
}

/* Inclusions & Exclusions */
.inclusion-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 2px solid #e0e6ed;
    height: 100%;
}

.inclusion-card.included {
    border-color: #48bb78;
}

.inclusion-card.excluded {
    border-color: #e53e3e;
}

.inclusion-card-header {
    margin-bottom: 20px;
}

.inclusion-card-title {
    color: #2c3e50;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
}

.inclusion-card.included .inclusion-card-title i {
    color: #48bb78;
}

.inclusion-card.excluded .inclusion-card-title i {
    color: #e53e3e;
}

.inclusion-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.inclusion-list li {
    display: flex;
    align-items: flex-start;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f4f8;
}

.inclusion-list li:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.inclusion-list li i {
    margin-right: 10px;
    margin-top: 2px;
    flex-shrink: 0;
}

.inclusion-card.included .inclusion-list li i {
    color: #48bb78;
}

.inclusion-card.excluded .inclusion-list li i {
    color: #e53e3e;
}

.inclusion-list li span {
    color: #4a5568;
    font-size: 14px;
    line-height: 1.5;
}

/* Responsive Adjustments */
@media (max-width: 1199px) {
    .left-content {
        margin-bottom: 30px;
    }
    
    .right-sidebar {
        position: relative;
        top: 0;
    }
}

@media (max-width: 768px) {
    .main-content-section {
        padding: 20px 15px;
    }
    
    .enquiry-form-container,
    .contact-info-box {
        padding: 20px 15px;
    }
    
    .itinerary-accordion-header {
        padding: 15px;
    }
    
    .itinerary-accordion-content {
        padding: 0 15px;
    }
    
    .itinerary-accordion-item.active .itinerary-accordion-content {
        padding: 15px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
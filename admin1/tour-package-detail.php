<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Get package details
$package = null;
$stmt = $conn->prepare("SELECT * FROM tour_package_lists WHERE slug = ? AND status = 'active'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

$package = $result->fetch_assoc();
$stmt->close();

// Decode JSON fields
$selected_tours = $package['selected_tours'] ? json_decode($package['selected_tours'], true) : [];
$gallery_images = $package['gallery_images'] ? json_decode($package['gallery_images'], true) : [];
$faqs = $package['faqs'] ? json_decode($package['faqs'], true) : [];

// Get individual tour details
$tour_details = [];
if (!empty($selected_tours)) {
    $tour_ids = implode(',', array_map('intval', $selected_tours));
    $result = $conn->query("SELECT * FROM tour_packages WHERE id IN ($tour_ids) AND status = 'active'");
    while ($row = $result->fetch_assoc()) {
        $tour_details[] = $row;
    }
}

// Set page metadata
$page_title = $package['meta_title'] ?: $package['title'];
$meta_description = $package['meta_description'] ?: substr(strip_tags($package['tour_overview']), 0, 160);
$meta_keywords = $package['meta_keywords'] ?: 'tour package, travel, holiday, ' . $package['title'];

// Include header
include 'header.php';
?>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../<?php echo $package['featured_image']; ?>') center/cover no-repeat;">
    <div class="container">
        <div class="hero-content text-white text-center">
            <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($package['title']); ?></h1>
            <div class="hero-meta d-flex justify-content-center flex-wrap gap-3 mb-4">
                <span class="badge bg-warning fs-6">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo $package['duration_days']; ?> Days / <?php echo $package['duration_nights']; ?> Nights
                </span>
                <span class="badge bg-success fs-6">
                    <i class="fas fa-rupee-sign me-1"></i>
                    ₹<?php echo number_format($package['price']); ?> 
                    <?php if ($package['price_per_person']): ?>per person<?php endif; ?>
                </span>
                <span class="badge bg-info fs-6">
                    <i class="fas fa-users me-1"></i>
                    <?php echo $package['group_size'] ?: 'Flexible'; ?>
                </span>
                <span class="badge bg-secondary fs-6">
                    <i class="fas fa-mountain me-1"></i>
                    <?php echo ucfirst($package['difficulty_level']); ?>
                </span>
            </div>
            <a href="booking.php?package=<?php echo $package['id']; ?>" class="btn btn-warning btn-lg px-5 py-3">
                <i class="fas fa-calendar-alt me-2"></i>Book This Package
            </a>
        </div>
    </div>
</section>

<!-- Package Details -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Gallery -->
                <?php if (!empty($gallery_images)): ?>
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-images me-2"></i>Photo Gallery</h3>
                    <div class="row g-3">
                        <?php foreach ($gallery_images as $index => $image): ?>
                        <div class="col-md-4 col-6">
                            <a href="../<?php echo $image; ?>" data-lightbox="gallery" class="gallery-item">
                                <img src="../<?php echo $image; ?>" alt="Gallery Image <?php echo $index + 1; ?>" 
                                     class="img-fluid rounded shadow-sm" style="height: 200px; object-fit: cover; width: 100%;">
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tour Overview -->
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-file-alt me-2"></i>Tour Overview</h3>
                    <div class="content-box">
                        <?php echo $package['tour_overview']; ?>
                    </div>
                </div>
                
                <!-- Included Tours -->
                <?php if (!empty($tour_details)): ?>
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-map-marked-alt me-2"></i>Included Tours</h3>
                    <div class="row">
                        <?php foreach ($tour_details as $tour): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if ($tour['featured_image']): ?>
                                <img src="../<?php echo $tour['featured_image']; ?>" class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($tour['title']); ?>" style="height: 180px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($tour['title']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <?php echo $tour['duration_days']; ?>D/<?php echo $tour['duration_nights']; ?>N
                                        • ₹<?php echo number_format($tour['starting_price']); ?>
                                    </p>
                                    <a href="tour-detail.php?slug=<?php echo $tour['slug']; ?>" class="btn btn-outline-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Detailed Itinerary -->
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-route me-2"></i>Detailed Itinerary</h3>
                    <div class="content-box">
                        <?php echo $package['tour_itinerary']; ?>
                    </div>
                </div>
                
                <!-- Inclusions & Exclusions -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card border-success h-100">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Inclusions</h4>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($package['inclusions'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger h-100">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0"><i class="fas fa-times-circle me-2"></i>Exclusions</h4>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($package['exclusions'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- FAQs -->
                <?php if (!empty($faqs)): ?>
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h3>
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                        type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $index; ?>">
                                    <?php echo htmlspecialchars($faq['question']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" 
                                 class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Other Details -->
                <div class="mb-5">
                    <h3 class="mb-4 border-bottom pb-2"><i class="fas fa-info-circle me-2"></i>Other Details</h3>
                    <div class="row">
                        <?php if ($package['accommodation']): ?>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-bed text-primary me-2"></i>Accommodation</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($package['accommodation'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($package['transport']): ?>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-car text-primary me-2"></i>Transport</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($package['transport'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($package['meals']): ?>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-utensils text-primary me-2"></i>Meals</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($package['meals'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($package['guide_service']): ?>
                        <div class="col-md-6 mb-3">
                            <h5><i class="fas fa-user-tie text-primary me-2"></i>Guide Service</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($package['guide_service'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Important Notes -->
                <?php if ($package['important_notes']): ?>
                <div class="alert alert-warning">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h4>
                    <?php echo nl2br(htmlspecialchars($package['important_notes'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Cancellation Policy -->
                <?php if ($package['cancellation_policy']): ?>
                <div class="alert alert-info">
                    <h4><i class="fas fa-ban me-2"></i>Cancellation Policy</h4>
                    <?php echo nl2br(htmlspecialchars($package['cancellation_policy'])); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Booking Widget -->
                <div class="card shadow-lg sticky-top" style="top: 100px;">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Book This Package</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h2 class="text-warning fw-bold">₹<?php echo number_format($package['price']); ?></h2>
                            <p class="text-muted">
                                <?php if ($package['price_per_person']): ?>
                                    per person
                                <?php else: ?>
                                    total package
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h5><i class="fas fa-clock text-primary me-2"></i>Duration</h5>
                            <p class="mb-2"><?php echo $package['duration_days']; ?> Days</p>
                            <p class="mb-0"><?php echo $package['duration_nights']; ?> Nights</p>
                        </div>
                        
                        <?php if ($package['best_season']): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-calendar-alt text-primary me-2"></i>Best Season</h5>
                            <p class="mb-0"><?php echo htmlspecialchars($package['best_season']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($package['difficulty_level']): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-mountain text-primary me-2"></i>Difficulty Level</h5>
                            <p class="mb-0">
                                <span class="badge bg-<?php 
                                    echo $package['difficulty_level'] === 'easy' ? 'success' : 
                                         ($package['difficulty_level'] === 'moderate' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($package['difficulty_level']); ?>
                                </span>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <a href="booking.php?package=<?php echo $package['id']; ?>" 
                           class="btn btn-warning btn-lg w-100 py-3">
                            <i class="fas fa-calendar-alt me-2"></i>Book Now
                        </a>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-phone me-1"></i>Need help? Call: +91 9876543210
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>Quick Info</h5>
                        <ul class="list-unstyled">
                            <?php if ($package['group_size']): ?>
                            <li class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>Group Size:</strong> <?php echo htmlspecialchars($package['group_size']); ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($package['age_limit']): ?>
                            <li class="mb-2">
                                <i class="fas fa-user-friends text-primary me-2"></i>
                                <strong>Age Limit:</strong> <?php echo htmlspecialchars($package['age_limit']); ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($package['permit_info']): ?>
                            <li class="mb-2">
                                <i class="fas fa-passport text-primary me-2"></i>
                                <strong>Permits:</strong> <?php echo htmlspecialchars($package['permit_info']); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Share This Package -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-share-alt me-2"></i>Share This Package</h5>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(current_url()); ?>" 
                               target="_blank" class="btn btn-sm btn-facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(current_url()); ?>&text=<?php echo urlencode($package['title']); ?>" 
                               target="_blank" class="btn btn-sm btn-twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode($package['title'] . ' - ' . current_url()); ?>" 
                               target="_blank" class="btn btn-sm btn-whatsapp" style="background: #25D366; color: white;">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Packages -->
<?php
// Get related packages
$related_packages = [];
$result = $conn->query("
    SELECT * FROM tour_package_lists 
    WHERE status = 'active' AND id != {$package['id']}
    ORDER BY RAND() 
    LIMIT 3
");

if ($result && $result->num_rows > 0):
?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">You Might Also Like</h2>
        <div class="row">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <?php if ($row['featured_image']): ?>
                    <img src="../<?php echo $row['featured_image']; ?>" class="card-img-top" 
                         alt="<?php echo htmlspecialchars($row['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <p class="card-text text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $row['duration_days']; ?>D/<?php echo $row['duration_nights']; ?>N
                            • <i class="fas fa-rupee-sign me-1"></i>₹<?php echo number_format($row['price']); ?>
                        </p>
                        <a href="tour-package-detail.php?slug=<?php echo $row['slug']; ?>" class="btn btn-outline-primary">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Include footer
include 'footer.php';
?>
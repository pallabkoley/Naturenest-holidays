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

// Force fresh connection and clear any cached results
while ($conn->more_results()) {
    $conn->next_result();
    if ($res = $conn->store_result()) {
        $res->free();
    }
}

// Check if connection is successful
if (!$conn) {
    die("Database connection failed");
}

// Initialize variables
$tour = null;
$package_lists = [];

// Initialize page variables
$page_title = 'Tour Packages - ' . SITE_NAME;
$meta_description = 'Tour package collections - ' . SITE_NAME;
$meta_keywords = 'tour packages, travel packages';
$page_css = '';

// Function to get tour by slug
function getTourBySlug($conn, $slug) {
    $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE BINARY slug = ? AND status = 'active' LIMIT 1");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $tour = $result->fetch_assoc();
    
    $stmt->close();
    
    return $tour;
}

// Get tour by slug
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    
    if (empty($slug)) {
        header('Location: tours.php');
        exit();
    }
    
    try {
        $tour = getTourBySlug($conn, $slug);
        
        if ($tour) {
            // Set page metadata
            $page_title = htmlspecialchars($tour['title'] ?? '') . ' - Package Collections - ' . SITE_NAME;
            $meta_description = 'Tour package collections including ' . htmlspecialchars($tour['title'] ?? '') . ' - ' . SITE_NAME;
            $meta_keywords = htmlspecialchars($tour['title'] ?? '') . ', tour package collections, travel packages';
            
            // Get tour package lists that include this tour
            try {
                // Get ALL active package lists
                $all_packages_stmt = $conn->prepare("SELECT * FROM tour_package_lists WHERE status = 'active' ORDER BY position ASC, created_at DESC");
                
                if ($all_packages_stmt->execute()) {
                    $all_packages_result = $all_packages_stmt->get_result();
                    if ($all_packages_result && $all_packages_result->num_rows > 0) {
                        while ($row = $all_packages_result->fetch_assoc()) {
                            // Decode selected tours to check if it includes current tour
                            $selected_tours = json_decode($row['selected_tours'] ?? '[]', true);
                            
                            // Check if this tour is included
                            if (is_array($selected_tours) && in_array($tour['id'], $selected_tours)) {
                                $row['tour_count'] = count($selected_tours);
                                $row['includes_this_tour'] = true;
                                $package_lists[] = $row;
                            }
                        }
                    }
                }
                $all_packages_stmt->close();
            } catch (Exception $e) {
                error_log("Error fetching package lists: " . $e->getMessage());
            }
            
        } else {
            // No tour found
            header('Location: tours.php');
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Error in tour-detail.php: " . $e->getMessage());
        header('Location: tours.php');
        exit();
    }
} else {
    // No slug provided
    header('Location: tours.php');
    exit();
}

// Function to get included tour titles
function getIncludedTourTitles($conn, $tour_ids) {
    $titles = [];
    
    if (empty($tour_ids) || !is_array($tour_ids)) {
        return $titles;
    }
    
    try {
        // Filter out non-numeric IDs
        $numeric_ids = array_filter($tour_ids, 'is_numeric');
        
        if (empty($numeric_ids)) {
            return $titles;
        }
        
        $placeholders = implode(',', array_fill(0, count($numeric_ids), '?'));
        $types = str_repeat('i', count($numeric_ids));
        
        $tour_stmt = $conn->prepare("SELECT id, title, slug FROM tour_packages WHERE id IN ($placeholders)");
        $tour_stmt->bind_param($types, ...$numeric_ids);
        
        if ($tour_stmt->execute()) {
            $tour_result = $tour_stmt->get_result();
            while ($tour_row = $tour_result->fetch_assoc()) {
                $titles[$tour_row['id']] = [
                    'title' => $tour_row['title'],
                    'slug' => $tour_row['slug']
                ];
            }
        }
        $tour_stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching included tour titles: " . $e->getMessage());
    }
    
    return $titles;
}

// Get WhatsApp number
$whatsapp_number = '+91 8670951999';
try {
    $whatsapp_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_number' LIMIT 1");
    if ($whatsapp_result && $whatsapp_row = $whatsapp_result->fetch_assoc()) {
        $whatsapp_number = $whatsapp_row['setting_value'] ?? '+91 8670951999';
    }
} catch (Exception $e) {
    error_log("Error fetching WhatsApp number: " . $e->getMessage());
}

// Define CSS
$page_css = <<<CSS
/* Package Lists Page CSS */
.package-lists-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding-top: 120px;
    position: relative;
    overflow: hidden;
}

.package-lists-page::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,218.7C672,235,768,245,864,234.7C960,224,1056,192,1152,181.3C1248,171,1344,181,1392,186.7L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
    background-size: cover;
}

.page-header {
    text-align: center;
    color: white;
    margin-bottom: 60px;
    position: relative;
    z-index: 1;
}

.tour-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 12px 30px;
    border-radius: 50px;
    display: inline-block;
    margin-bottom: 25px;
    font-weight: 600;
    letter-spacing: 1px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.page-title {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    background: linear-gradient(to right, #ffffff, #f0f0f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 40px;
}

/* Package Cards */
.package-lists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.package-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    transition: all 0.4s ease;
    position: relative;
    border: none;
}

.package-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 30px 60px rgba(102, 126, 234, 0.3);
}

.package-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 8px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    z-index: 1;
}

.package-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 2;
    background: linear-gradient(135deg, #f6d365, #fda085);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 5px 15px rgba(246, 211, 101, 0.3);
}

.package-badge i {
    font-size: 1rem;
}

.package-image {
    height: 220px;
    width: 100%;
    overflow: hidden;
    position: relative;
}

.package-image::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60%;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
}

.package-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.8s ease;
}

.package-card:hover .package-image img {
    transform: scale(1.1);
}

.package-content {
    padding: 30px;
}

.package-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.3;
    color: #2d3748;
}

.package-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s;
}

.package-title a:hover {
    color: #667eea;
    text-decoration: none;
}

.package-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
}

.package-meta-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    font-size: 1rem;
}

.package-meta-item i {
    color: #667eea;
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.difficulty-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.difficulty-easy {
    background: linear-gradient(135deg, #48bb78, #38a169);
    color: white;
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
}

.difficulty-moderate {
    background: linear-gradient(135deg, #ed8936, #dd6b20);
    color: white;
    box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
}

.difficulty-difficult {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    color: white;
    box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
}

.package-description {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 25px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Included Tours */
.included-tours {
    background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    border: 1px solid #e2e8f0;
}

.included-tours h6 {
    font-size: 1rem;
    color: #2d3748;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.included-tours h6 i {
    color: #667eea;
    font-size: 1.2rem;
}

.included-tours-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tour-tag {
    background: white;
    color: #4a5568;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tour-tag:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    text-decoration: none;
}

.tour-tag.current-tour {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.tour-tag.current-tour i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.package-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 25px;
    border-top: 2px solid #f1f5f9;
    margin-top: 20px;
}

.package-price {
    font-size: 2.2rem;
    font-weight: 800;
    color: #667eea;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

.package-price span {
    font-size: 1rem;
    color: #94a3b8;
    font-weight: 500;
    display: block;
    margin-top: 5px;
}

.package-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: 1rem;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.package-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

/* Empty State */
.empty-packages {
    text-align: center;
    padding: 80px 20px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.empty-packages-icon {
    font-size: 5rem;
    margin-bottom: 30px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
    100% { transform: translateY(0px); }
}

.empty-packages h4 {
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 1.8rem;
    font-weight: 700;
}

.empty-packages p {
    color: #64748b;
    max-width: 500px;
    margin: 0 auto 30px;
    font-size: 1.1rem;
    line-height: 1.6;
}

.view-all-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 15px 35px;
    border-radius: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.view-all-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

/* Back Button */
.back-btn-container {
    position: fixed;
    top: 100px;
    left: 30px;
    z-index: 1000;
}

.back-btn {
    background: rgba(255, 255, 255, 0.9);
    color: #667eea;
    border: none;
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.back-btn:hover {
    background: white;
    color: #764ba2;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    text-decoration: none;
}

/* WhatsApp Float */
.whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #25D366;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    z-index: 1000;
    transition: all 0.3s ease;
    text-decoration: none;
}

.whatsapp-float:hover {
    transform: scale(1.1) rotate(5deg);
    color: white;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 1200px) {
    .package-lists-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .package-lists-page {
        padding-top: 100px;
        padding-bottom: 60px;
    }
    
    .page-title {
        font-size: 2.5rem;
    }
    
    .package-lists-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .package-card {
        margin: 0 10px;
    }
    
    .back-btn-container {
        top: 80px;
        left: 20px;
    }
    
    .whatsapp-float {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 2rem;
    }
    
    .page-subtitle {
        font-size: 1rem;
        padding: 0 15px;
    }
    
    .package-footer {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
    
    .package-btn {
        text-align: center;
        justify-content: center;
    }
    
    .package-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .back-btn-container {
        position: relative;
        top: 0;
        left: 0;
        margin: 20px 0;
        text-align: center;
    }
    
    .back-btn {
        display: inline-flex;
    }
}
CSS;

// Include header
include '../includes/header.php';
?>

<!-- Package Lists Page -->
<section class="package-lists-page">
    <div class="container">
        <!-- Back Button -->
        <div class="back-btn-container">
            <a href="<?php echo SITE_URL; ?>/pages/tour-packages.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Tours
            </a>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div class="tour-badge">
                <i class="fas fa-layer-group me-2"></i>Package Collections
            </div>
            <h1 class="page-title">Package Collections Including <?php echo htmlspecialchars($tour['title']); ?></h1>
            <p class="page-subtitle">
                This tour is part of these amazing package collections. Explore comprehensive travel experiences that combine multiple destinations for the perfect adventure.
            </p>
        </div>

        <!-- Package Lists Grid -->
        <div class="package-lists-grid">
            <?php if (empty($package_lists)): ?>
                <div class="empty-packages">
                    <div class="empty-packages-icon">
                        <i class="fas fa-compass"></i>
                    </div>
                    <h4>No Package Collections Found</h4>
                    <p>This tour is not currently included in any package collections. Check back later or explore our other tour packages.</p>
                    <div class="mt-4">
                        <a href="<?php echo SITE_URL; ?>/pages/tour-lists.php" class="view-all-btn">
                            <i class="fas fa-compass me-2"></i> View All Package Collections
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/tour-packages.php" class="view-all-btn ms-3">
                            <i class="fas fa-map-marked-alt me-2"></i> View All Tours
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($package_lists as $package): 
                    // Get included tours IDs
                    $selected_tours = json_decode($package['selected_tours'] ?? '[]', true);
                    $included_tour_ids = is_array($selected_tours) ? $selected_tours : [];
                    
                    // Get tour titles
                    $included_tours_titles = getIncludedTourTitles($conn, $included_tour_ids);
                ?>
                    <div class="package-card">
                        <?php if ($package['tour_count'] > 1): ?>
                            <span class="package-badge">
                                <i class="fas fa-layer-group"></i> <?php echo $package['tour_count']; ?> Tours Included
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($package['featured_image'])): ?>
                            <div class="package-image">
                                <img src="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars(ltrim($package['featured_image'], '/')); ?>" 
                                     alt="<?php echo htmlspecialchars($package['title']); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-tour.jpg'">
                            </div>
                        <?php else: ?>
                            <div class="package-image" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-map-marked-alt" style="font-size: 4rem;"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-content">
                            <h3 class="package-title">
                                <a href="<?php echo SITE_URL; ?>/pages/tour-list-detail.php?slug=<?php echo htmlspecialchars($package['slug']); ?>">
                                    <?php echo htmlspecialchars($package['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="package-meta">
                                <div class="package-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $package['duration_days']; ?> Days / <?php echo $package['duration_nights']; ?> Nights</span>
                                </div>
                                <?php if (!empty($package['difficulty_level'])): ?>
                                <div class="package-meta-item">
                                    <span class="difficulty-badge difficulty-<?php echo htmlspecialchars($package['difficulty_level']); ?>">
                                        <?php echo ucfirst($package['difficulty_level']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($package['best_season'])): ?>
                                <div class="package-meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo htmlspecialchars($package['best_season']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($package['tour_overview'])): ?>
                                <div class="package-description">
                                    <?php echo htmlspecialchars(substr(strip_tags($package['tour_overview']), 0, 150)); ?>...
                                </div>
                            <?php endif; ?>
                            
                            <!-- Included Tours -->
                            <?php if (!empty($included_tours_titles)): ?>
                                <div class="included-tours">
                                    <h6><i class="fas fa-map-marked-alt"></i> Included Tours:</h6>
                                    <div class="included-tours-list">
                                        <?php foreach ($included_tours_titles as $tour_id => $tour_info): ?>
                                            <a href="<?php echo SITE_URL; ?>/pages/tour-detail.php?slug=<?php echo htmlspecialchars($tour_info['slug']); ?>" 
                                               class="tour-tag <?php echo $tour_id == $tour['id'] ? 'current-tour' : ''; ?>">
                                                <?php 
                                                if ($tour_id == $tour['id']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                }
                                                echo htmlspecialchars($tour_info['title']); 
                                                ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="package-footer">
                                <div class="package-price">
                                    ₹<?php echo number_format($package['price'], 0); ?>
                                    <?php if ($package['price_per_person']): ?>
                                        <span>per person</span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/pages/tour-list-detail.php?slug=<?php echo htmlspecialchars($package['slug']); ?>" 
                                   class="package-btn">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($package_lists)): ?>
            <!-- View More Button -->
            <div class="text-center mt-5 pt-4">
                <p class="text-white mb-4" style="opacity: 0.8; font-size: 1.1rem;">
                    Explore more amazing travel experiences
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?php echo SITE_URL; ?>/pages/tour-lists.php" class="view-all-btn">
                        <i class="fas fa-layer-group me-2"></i> All Package Collections
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/tour-packages.php" class="view-all-btn" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                        <i class="fas fa-map-marked-alt me-2"></i> All Individual Tours
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- WhatsApp Float -->
<?php if (!empty($whatsapp_number) && !empty($tour['title'])): ?>
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>?text=Hi,%20I'm%20interested%20in%20package%20collections%20including%20<?php echo urlencode($tour['title']); ?>" 
   target="_blank" class="whatsapp-float" title="Chat with us on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>

<!-- JavaScript for animations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Package collections page loaded for: <?php echo htmlspecialchars($tour['title'] ?? ''); ?>');
    
    // Add animation to package cards
    const packageCards = document.querySelectorAll('.package-card');
    packageCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate__animated', 'animate__fadeInUp');
    });
    
    // Add hover effect to tour tags
    const tourTags = document.querySelectorAll('.tour-tag');
    tourTags.forEach(tag => {
        tag.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        tag.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
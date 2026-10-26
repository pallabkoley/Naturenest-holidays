<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Clear any previous destination session data
unset($_SESSION['destination']);
unset($_SESSION['selected_destination']);

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

// Debug mode - add ?debug=1 to URL to see debug info
$debug_mode = isset($_GET['debug']);

if ($debug_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Check if connection is successful
if (!$conn) {
    die("Database connection failed");
}

// Initialize all variables
$destination = null;
$related_destinations = [];
$other_destinations = [];
$destination_tours = [];

// Initialize page variables
$page_title = 'Destination Details - ' . SITE_NAME;
$meta_description = 'Explore destinations with Newa Yatra';
$meta_keywords = 'destination, travel, explore';
$page_css = '';

// Function to get destination by slug with binary comparison
function getDestinationBySlug($conn, $slug) {
    // Clear any previous results
    $conn->store_result();
    
    $stmt = $conn->prepare("SELECT * FROM destinations WHERE BINARY slug = ? AND status = 'active' LIMIT 1");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $destination = $result->fetch_assoc();
    
    $stmt->close();
    
    return $destination;
}

// Get destination by slug
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    
    // Debug: Show raw slug
    echo "<!-- DEBUG: Raw slug from URL: '" . htmlspecialchars($_GET['slug']) . "' -->\n";
    echo "<!-- DEBUG: Trimmed slug: '" . htmlspecialchars($slug) . "' -->\n";
    
    // Don't use FILTER_SANITIZE_STRING as it might change the slug
    // Instead, just trim and validate
    $slug = trim($slug);
    
    if (empty($slug)) {
        header('Location: destinations.php');
        exit();
    }
    
    try {
        // Debug: Show database contents
        $check_stmt = $conn->query("SELECT id, slug, name, LENGTH(slug) as len FROM destinations");
        if ($check_stmt) {
            echo "<!-- DEBUG: Database contents -->\n";
            while ($row = $check_stmt->fetch_assoc()) {
                echo "<!-- DEBUG: ID: " . $row['id'] . " | Slug: '" . htmlspecialchars($row['slug']) . "' (Length: " . $row['len'] . ") | Name: " . htmlspecialchars($row['name']) . " -->\n";
            }
            $check_stmt->close();
        }
        
        // Use the function to get destination
        $destination = getDestinationBySlug($conn, $slug);
        
        echo "<!-- DEBUG: Query executed with BINARY comparison -->\n";
        echo "<!-- DEBUG: Destination found: " . ($destination ? 'Yes' : 'No') . " -->\n";
        
        if ($destination) {
            // Debug: Show what we got
            echo "<!-- DEBUG: Destination fetched from database -->\n";
            echo "<!-- DEBUG: Destination ID: " . $destination['id'] . " -->\n";
            echo "<!-- DEBUG: Destination Slug from DB: '" . htmlspecialchars($destination['slug']) . "' -->\n";
            echo "<!-- DEBUG: Destination Name: '" . htmlspecialchars($destination['name']) . "' -->\n";
            
            // Verify slug match
            if ($destination['slug'] !== $slug) {
                echo "<!-- DEBUG: WARNING: Slug mismatch! -->\n";
                echo "<!-- DEBUG: URL Slug: '" . htmlspecialchars($slug) . "' -->\n";
                echo "<!-- DEBUG: DB Slug: '" . htmlspecialchars($destination['slug']) . "' -->\n";
                echo "<!-- DEBUG: Are they equal? " . ($destination['slug'] === $slug ? 'YES' : 'NO') . " -->\n";
                
                // Show character codes for debugging
                echo "<!-- DEBUG: URL Slug char codes: ";
                for($i = 0; $i < strlen($slug); $i++) {
                    echo ord($slug[$i]) . " ";
                }
                echo " -->\n";
                
                echo "<!-- DEBUG: DB Slug char codes: ";
                for($i = 0; $i < strlen($destination['slug']); $i++) {
                    echo ord($destination['slug'][$i]) . " ";
                }
                echo " -->\n";
                
                header('Location: destinations.php');
                exit();
            } else {
                echo "<!-- DEBUG: SUCCESS: Slugs match perfectly! -->\n";
            }
            
            // Set session variable to track current destination
            $_SESSION['current_destination_id'] = $destination['id'];
            
            // Set page metadata
            $page_title = htmlspecialchars($destination['name']) . ' - ' . SITE_NAME;
            $meta_description = htmlspecialchars(substr(strip_tags($destination['description']), 0, 160)) . ' | ' . htmlspecialchars($destination['overview'] ?? '');
            $meta_keywords = htmlspecialchars($destination['name']) . ', travel destination, tourism, ' . htmlspecialchars($destination['attractions'] ?? '');

        } else {
            // Try alternative: remove any URL encoding
            $decoded_slug = urldecode($slug);
            if ($decoded_slug !== $slug) {
                echo "<!-- DEBUG: Trying with URL decoded slug: '" . htmlspecialchars($decoded_slug) . "' -->\n";
                
                $destination = getDestinationBySlug($conn, $decoded_slug);
                if ($destination) {
                    echo "<!-- DEBUG: Found destination with decoded slug -->\n";
                }
            }
            
            if (!$destination) {
                echo "<!-- DEBUG: No destination found with any slug variation -->\n";
                header('Location: destinations.php');
                exit();
            }
        }
        
    } catch (Exception $e) {
        echo "<!-- DEBUG: Exception: " . htmlspecialchars($e->getMessage()) . " -->\n";
        error_log("Error in destination-detail.php: " . $e->getMessage());
        header('Location: destinations.php');
        exit();
    }
} else {
    // No slug provided
    header('Location: destinations.php');
    exit();
}

// Verify that destination data is correct
if (!isset($destination['id']) || !isset($destination['slug'])) {
    echo "<!-- DEBUG: ERROR: Destination data incomplete -->\n";
    header('Location: destinations.php');
    exit();
}

// Double-check slug matches
if ($destination['slug'] !== $slug) {
    echo "<!-- DEBUG: FATAL: Destination slug mismatch after fetching -->\n";
    echo "<!-- DEBUG: Expected: '" . htmlspecialchars($slug) . "' -->\n";
    echo "<!-- DEBUG: Got: '" . htmlspecialchars($destination['slug']) . "' -->\n";
    header('Location: destinations.php');
    exit();
}

// Final debug output to verify destination data
echo "<!-- DEBUG: CONFIRMED DESTINATION DATA -->\n";
echo "<!-- DEBUG: Destination ID: " . $destination['id'] . " -->\n";
echo "<!-- DEBUG: Destination Name: " . htmlspecialchars($destination['name']) . " -->\n";
echo "<!-- DEBUG: Destination Slug: " . htmlspecialchars($destination['slug']) . " -->\n";

// Get tours for this destination
try {
    $tours_sql = "SELECT id, title, slug, duration_days, starting_price, featured_image, destination
                  FROM tour_packages 
                  WHERE status = 'active' AND (destination = ? OR destination LIKE ? OR destination LIKE ? OR destination LIKE ?)
                  ORDER BY created_at DESC LIMIT 6";
    $tours_stmt = $conn->prepare($tours_sql);
    if ($tours_stmt) {
        $destination_name = $destination['name'];
        $like_start = $destination_name . '%';
        $like_middle = '%' . $destination_name . '%';
        $like_end = '%' . $destination_name;
        
        $tours_stmt->bind_param("ssss", $destination_name, $like_start, $like_middle, $like_end);
        
        if ($tours_stmt->execute()) {
            $tours_result = $tours_stmt->get_result();
            if ($tours_result && $tours_result->num_rows > 0) {
                while ($row = $tours_result->fetch_assoc()) {
                    if ($row && is_array($row)) {
                        $destination_tours[] = $row;
                    }
                }
            }
        }
        $tours_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching destination tours: " . $e->getMessage());
    $destination_tours = [];
}

// Get other active destinations for sidebar
try {
    $other_sql = "SELECT id, name, slug, description, featured_image 
                  FROM destinations 
                  WHERE status = 'active' AND id != ? 
                  ORDER BY display_order, created_at DESC LIMIT 5";
    $other_stmt = $conn->prepare($other_sql);
    if ($other_stmt) {
        $other_stmt->bind_param("i", $destination['id']);
        
        if ($other_stmt->execute()) {
            $other_result = $other_stmt->get_result();
            if ($other_result && $other_result->num_rows > 0) {
                while ($row = $other_result->fetch_assoc()) {
                    if ($row && is_array($row)) {
                        $other_destinations[] = $row;
                    }
                }
            }
        }
        $other_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching other destinations: " . $e->getMessage());
    $other_destinations = [];
}

// Verify destination hasn't been overwritten after getting other destinations
if (isset($destination['id']) && isset($_SESSION['current_destination_id']) && $destination['id'] != $_SESSION['current_destination_id']) {
    echo "<!-- DEBUG: WARNING: Destination variable was overwritten! -->\n";
    // Try to refetch the correct destination
    $refetch_stmt = $conn->prepare("SELECT * FROM destinations WHERE id = ?");
    $refetch_stmt->bind_param("i", $_SESSION['current_destination_id']);
    if ($refetch_stmt->execute()) {
        $refetch_result = $refetch_stmt->get_result();
        if ($refetch_result->num_rows > 0) {
            $destination = $refetch_result->fetch_assoc();
            echo "<!-- DEBUG: Destination data restored -->\n";
        }
    }
    $refetch_stmt->close();
}

// Get related destinations (based on display order or random)
try {
    $related_sql = "SELECT id, name, slug, description, featured_image 
                    FROM destinations 
                    WHERE status = 'active' AND id != ? 
                    ORDER BY display_order DESC, RAND() LIMIT 4";
    $related_stmt = $conn->prepare($related_sql);
    if ($related_stmt) {
        $related_stmt->bind_param("i", $destination['id']);
        
        if ($related_stmt->execute()) {
            $related_result = $related_stmt->get_result();
            if ($related_result && $related_result->num_rows > 0) {
                while ($row = $related_result->fetch_assoc()) {
                    if ($row && is_array($row)) {
                        $related_destinations[] = $row;
                    }
                }
            }
        }
        $related_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching related destinations: " . $e->getMessage());
    $related_destinations = [];
}

// Get contact info
$site_settings = [];
try {
    $contact_result = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('contact_phone1', 'site_email')");
    if ($contact_result) {
        while ($row = $contact_result->fetch_assoc()) {
            if ($row && is_array($row)) {
                $site_settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching site settings: " . $e->getMessage());
    $site_settings = [
        'contact_phone1' => '+91 8670951999',
        'site_email' => 'info@newayatra.com'
    ];
}

// Get WhatsApp number from settings
$whatsapp_number = '+91 8670951999';
try {
    $whatsapp_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_number' LIMIT 1");
    if ($whatsapp_result && $whatsapp_row = $whatsapp_result->fetch_assoc()) {
        $whatsapp_number = $whatsapp_row['setting_value'] ?? '+91 8670951999';
    }
} catch (Exception $e) {
    error_log("Error fetching WhatsApp number: " . $e->getMessage());
}

// Define CSS for destination detail page
$page_css = <<<CSS
/* Destination Detail Specific Styles */
.destination-hero {
    position: relative;
    height: 70vh;
    min-height: 500px;
    overflow: hidden;
    border-radius: var(--border-radius-lg);
    margin: 30px 0 40px 0;
    background: var(--light-color);
}

.destination-hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.destination-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
    display: flex;
    align-items: flex-end;
    padding: 40px;
}

.destination-hero-content {
    color: white;
    max-width: 800px;
}

.destination-hero-badge {
    background: var(--primary-color);
    color: white;
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 20px;
}

.destination-title-detail {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 15px;
    line-height: 1.2;
    color: white !important;
}

.destination-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 20px;
    align-items: center;
}

.destination-meta-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255, 255, 255, 0.9);
}

.destination-meta-item i {
    font-size: 1.2rem;
    color: var(--warning-color);
}

.destination-section {
    margin-bottom: 50px;
}

.section-title-destination {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 25px;
    color: var(--secondary-color);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
    position: relative;
}

.section-title-destination:after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100px;
    height: 2px;
    background: var(--primary-color);
}

.destination-description {
    font-size: 1.1rem;
    line-height: 1.8;
    color: var(--dark-color);
    margin-bottom: 30px;
}

.destination-overview {
    background: var(--light-color);
    padding: 30px;
    border-radius: var(--border-radius-lg);
    margin-bottom: 30px;
    border-left: 4px solid var(--primary-color);
}

.destination-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.info-card {
    background: white;
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    border-top: 4px solid;
    transition: var(--transition);
    height: 100%;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.info-card-primary {
    border-top-color: var(--primary-color);
}

.info-card-secondary {
    border-top-color: var(--secondary-color);
}

.info-card-accent {
    border-top-color: var(--accent-color);
}

.info-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: inherit;
}

.info-card-primary .info-icon {
    color: var(--primary-color);
}

.info-card-secondary .info-icon {
    color: var(--secondary-color);
}

.info-card-accent .info-icon {
    color: var(--accent-color);
}

.info-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.info-text {
    color: var(--gray-color);
    line-height: 1.6;
}

.attractions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.attraction-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    height: 100%;
}

.attraction-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.attraction-image {
    height: 200px;
    overflow: hidden;
}

.attraction-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.attraction-card:hover .attraction-image img {
    transform: scale(1.05);
}

.attraction-content {
    padding: 20px;
}

.attraction-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--secondary-color);
}

.attraction-desc {
    color: var(--gray-color);
    line-height: 1.6;
}

.destination-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.gallery-item-destination {
    border-radius: var(--border-radius);
    overflow: hidden;
    position: relative;
    height: 200px;
    cursor: pointer;
    transition: transform 0.3s;
}

.gallery-item-destination:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.gallery-item-destination img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.gallery-item-destination:hover .gallery-overlay {
    opacity: 1;
}

.gallery-overlay i {
    color: white;
    font-size: 2rem;
}

.tours-section {
    background: linear-gradient(135deg, rgba(198, 107, 5, 0.05), rgba(142, 20, 2, 0.03));
    padding: 50px 0;
    border-radius: var(--border-radius-lg);
}

.tour-grid-destination {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.tour-card-destination {
    background: white;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    height: 100%;
}

.tour-card-destination:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}

.tour-image-destination {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.tour-image-destination img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.tour-card-destination:hover .tour-image-destination img {
    transform: scale(1.05);
}

.tour-badge-destination {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--primary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.tour-info-destination {
    padding: 20px;
}

.tour-title-destination {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.tour-meta-destination {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.9rem;
    color: var(--gray-color);
}

.tour-price-destination {
    color: var(--primary-color);
    font-size: 1.3rem;
    font-weight: 700;
    margin: 10px 0;
}

.tour-desc-destination {
    color: var(--gray-color);
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 15px;
}

.related-destinations {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.related-destination-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    display: block;
    text-decoration: none;
    color: inherit;
}

.related-destination-card:hover {
    transform: translateY(-5px);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow);
}

.related-destination-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.related-destination-card:hover .related-destination-image {
    transform: scale(1.05);
}

.related-destination-content {
    padding: 20px;
}

.related-destination-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--dark-color);
}

.related-destination-desc {
    color: var(--gray-color);
    font-size: 0.9rem;
    line-height: 1.5;
}

.quick-contact-destination {
    background: white;
    padding: 25px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    border-top: 3px solid var(--primary-color);
    margin-top: 30px;
}

.contact-info-destination p {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-info-destination i {
    color: var(--primary-color);
    width: 20px;
    text-align: center;
}

.other-destination-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: var(--border-radius);
    margin-bottom: 10px;
    text-decoration: none;
    color: inherit;
    transition: var(--transition);
    border: 1px solid var(--light-gray);
    box-shadow: var(--shadow-sm);
}

.other-destination-item:hover {
    transform: translateX(5px);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow);
    border-color: var(--primary-color);
}

.other-destination-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    margin-right: 15px;
    flex-shrink: 0;
}

.other-destination-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.other-destination-content h6 {
    margin: 0 0 5px 0;
    font-size: 0.95rem;
    color: var(--dark-color);
}

.other-destination-content p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--gray-color);
    line-height: 1.4;
}

.cta-section-destination {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 60px 0;
    border-radius: var(--border-radius-lg);
    margin: 50px 0;
    text-align: center;
}

.cta-title-destination {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: white !important;
}

.cta-text-destination {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 30px;
}

.season-info {
    background: var(--light-color);
    padding: 25px;
    border-radius: var(--border-radius);
    margin: 20px 0;
    border-left: 4px solid var(--info-color);
}

.season-info h5 {
    color: var(--info-color);
    margin-bottom: 10px;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .destination-title-detail {
        font-size: 2.5rem;
    }
    
    .destination-hero {
        height: 60vh;
        min-height: 400px;
    }
    
    .attractions-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .tour-grid-destination {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .destination-hero {
        height: 50vh;
        min-height: 300px;
        margin: 20px 0 30px 0;
    }
    
    .destination-title-detail {
        font-size: 2rem;
    }
    
    .destination-hero-overlay {
        padding: 20px;
    }
    
    .destination-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .section-title-destination {
        font-size: 1.8rem;
    }
    
    .destination-info-grid {
        grid-template-columns: 1fr;
    }
    
    .related-destinations {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .cta-title-destination {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .destination-title-detail {
        font-size: 1.8rem;
    }
    
    .destination-hero {
        height: 40vh;
        min-height: 250px;
    }
    
    .section-title-destination {
        font-size: 1.5rem;
    }
    
    .attractions-grid,
    .tour-grid-destination,
    .related-destinations {
        grid-template-columns: 1fr;
    }
    
    .destination-gallery {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .cta-title-destination {
        font-size: 1.8rem;
    }
    
    .cta-text-destination {
        font-size: 1rem;
    }
}
CSS;

// Include header AFTER all variables are defined
include '../includes/header.php';
?>

<!-- Debug info -->
<?php if ($debug_mode): ?>
<div style="background: #f8f9fa; border: 2px solid #dc3545; padding: 15px; margin: 15px; border-radius: 5px;">
    <h4>Debug Information</h4>
    <p><strong>Slug from URL:</strong> <?php echo htmlspecialchars($_GET['slug'] ?? ''); ?></p>
    <p><strong>Sanitized Slug:</strong> <?php echo htmlspecialchars($slug ?? ''); ?></p>
    <p><strong>Destination Found:</strong> <?php echo isset($destination) ? 'Yes' : 'No'; ?></p>
    <?php if (isset($destination)): ?>
    <p><strong>Destination ID:</strong> <?php echo $destination['id']; ?></p>
    <p><strong>Destination Name:</strong> <?php echo htmlspecialchars($destination['name']); ?></p>
    <p><strong>Destination Slug:</strong> <?php echo htmlspecialchars($destination['slug']); ?></p>
    <p><strong>Session Destination ID:</strong> <?php echo $_SESSION['current_destination_id'] ?? 'Not set'; ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Destination Hero Section -->
<section class="destination-hero">
    <?php if (!empty($destination['featured_image'])): ?>
        <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars(ltrim($destination['featured_image'], '/')); ?>" 
             alt="<?php echo htmlspecialchars($destination['name']); ?>" 
             class="destination-hero-image"
             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-destination.jpg'">
    <?php else: ?>
        <img src="<?php echo SITE_URL; ?>/assets/images/default-destination.jpg" 
             alt="<?php echo htmlspecialchars($destination['name']); ?>" 
             class="destination-hero-image">
    <?php endif; ?>
    <div class="destination-hero-overlay">
        <div class="destination-hero-content">
            <span class="destination-hero-badge">Top Destination</span>
            <h1 class="destination-title-detail"><?php echo htmlspecialchars($destination['name']); ?></h1>
            
            <div class="destination-meta">
                <?php if (!empty($destination['best_time_to_visit'])): ?>
                <div class="destination-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Best Time: <?php echo htmlspecialchars($destination['best_time_to_visit']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container">
    <div class="row">
        <!-- Left Column - Destination Details -->
        <div class="col-lg-8">
            <!-- Destination Description -->
            <section class="destination-section">
                <h2 class="section-title-destination">About <?php echo htmlspecialchars($destination['name']); ?></h2>
                <div class="destination-description">
                    <?php echo nl2br(htmlspecialchars($destination['description'])); ?>
                </div>
                
                <?php if (!empty($destination['overview'])): ?>
                <div class="destination-overview">
                    <h3 class="h4 mb-3"><i class="fas fa-info-circle me-2"></i> Destination Overview</h3>
                    <?php echo nl2br(htmlspecialchars($destination['overview'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Destination Info Grid -->
                <div class="destination-info-grid">
                    <?php if (!empty($destination['best_time_to_visit'])): ?>
                    <div class="info-card info-card-primary">
                        <div class="info-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <div class="info-title">Best Time to Visit</div>
                        <div class="info-text">
                            <?php echo htmlspecialchars($destination['best_time_to_visit']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-card info-card-secondary">
                        <div class="info-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="info-title">Explore</div>
                        <div class="info-text">
                            Discover the beauty and culture of <?php echo htmlspecialchars($destination['name']); ?>
                        </div>
                    </div>
                    
                    <div class="info-card info-card-accent">
                        <div class="info-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="info-title">Photography</div>
                        <div class="info-text">
                            Perfect destination for photography enthusiasts
                        </div>
                    </div>
                </div>
            </section>

            <!-- Top Attractions -->
            <?php if (!empty($destination['attractions'])): ?>
            <section class="destination-section">
                <h2 class="section-title-destination">Top Attractions</h2>
                <div class="attractions-grid">
                    <?php 
                    $attractions = explode(',', $destination['attractions']);
                    $attraction_counter = 0;
                    $attraction_images = ['mountain', 'temple', 'palace', 'lake', 'forest', 'monument'];
                    
                    foreach ($attractions as $attraction):
                        $attraction = trim($attraction);
                        if (!empty($attraction)):
                            $attraction_counter++;
                            $image_type = $attraction_images[$attraction_counter % count($attraction_images)];
                    ?>
                    <div class="attraction-card">
                       
                        <div class="attraction-content">
                            <h3 class="attraction-title"><?php echo htmlspecialchars($attraction); ?></h3>
                            <p class="attraction-desc">
                                Experience the beauty and significance of <?php echo htmlspecialchars($attraction); ?> in <?php echo htmlspecialchars($destination['name']); ?>.
                            </p>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Gallery -->
            <?php
            $gallery_images = [];
            
            if (!empty($destination['gallery_images'])) {
                if (is_string($destination['gallery_images'])) {
                    // Try to decode JSON first
                    $decoded = json_decode($destination['gallery_images'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $gallery_images = $decoded;
                    } else {
                        // Try comma-separated
                        $gallery_images = array_filter(array_map('trim', explode(',', $destination['gallery_images'])));
                    }
                } elseif (is_array($destination['gallery_images'])) {
                    $gallery_images = $destination['gallery_images'];
                }
            }

            if (!empty($gallery_images)):
            ?>
            <section class="destination-section">
                <h2 class="section-title-destination">Gallery</h2>
                <div class="destination-gallery">
                    <?php foreach ($gallery_images as $index => $image): 
                        $image_url = SITE_URL . '/' . ltrim($image, '/');
                    ?>
                    <a href="<?php echo htmlspecialchars($image_url); ?>" 
                       class="gallery-item-destination" 
                       data-lightbox="destination-gallery"
                       data-title="<?php echo htmlspecialchars($destination['name']); ?> - Image <?php echo $index + 1; ?>">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="Gallery Image <?php echo $index + 1; ?> of <?php echo htmlspecialchars($destination['name']); ?>"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-gallery.jpg'">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Tours for this Destination -->
            <?php 
            $valid_destination_tours = is_array($destination_tours) ? $destination_tours : [];
            $destination_tours_count = count($valid_destination_tours);
            
            if ($destination_tours_count > 0): 
            ?>
            <section class="tours-section">
                <div class="container">
                    <h2 class="section-title-destination text-center">Tours in <?php echo htmlspecialchars($destination['name']); ?></h2>
                    <p class="text-center mb-4">Explore our curated tours to experience the best of <?php echo htmlspecialchars($destination['name']); ?></p>
                    
                    <div class="tour-grid-destination">
                        <?php foreach ($valid_destination_tours as $tour): 
                            if (!is_array($tour)) continue;
                        ?>
                        <a href="tour-detail.php?slug=<?php echo htmlspecialchars($tour['slug'] ?? ''); ?>" class="tour-card-destination">
                            <div class="tour-image-destination">
                                <?php if (!empty($tour['featured_image'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars(ltrim($tour['featured_image'], '/')); ?>" 
                                         alt="<?php echo htmlspecialchars($tour['title'] ?? 'Tour'); ?>"
                                         onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-tour.jpg'">
                                <?php else: ?>
                                    <img src="<?php echo SITE_URL; ?>/assets/images/default-tour.jpg" 
                                         alt="<?php echo htmlspecialchars($tour['title'] ?? 'Tour'); ?>">
                                <?php endif; ?>
                                <span class="tour-badge-destination"><?php echo htmlspecialchars($tour['destination'] ?? 'Tour'); ?></span>
                            </div>
                            <div class="tour-info-destination">
                                <h3 class="tour-title-destination"><?php echo htmlspecialchars($tour['title'] ?? 'Tour'); ?></h3>
                                <div class="tour-meta-destination">
                                    <span><i class="fas fa-clock me-1"></i> <?php echo $tour['duration_days'] ?? 0; ?> Days</span>
                                </div>
                                <div class="tour-price-destination">
                                    ₹<?php echo number_format($tour['starting_price'] ?? 0, 2); ?>
                                    <span class="text-muted small">/person</span>
                                </div>
                                <p class="tour-desc-destination">
                                    Experience the beauty of <?php echo htmlspecialchars($destination['name']); ?> with our expertly crafted tour.
                                </p>
                                <button class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-eye me-2"></i> View Tour Details
                                </button>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Related Destinations -->
            <?php 
            $valid_related_destinations = is_array($related_destinations) ? $related_destinations : [];
            $related_destinations_count = count($valid_related_destinations);
            
            if ($related_destinations_count > 0): 
            ?>
            <section class="destination-section">
                <h2 class="section-title-destination">Related Destinations</h2>
                <div class="related-destinations">
                    <?php foreach ($valid_related_destinations as $related_destination): 
                        if (!is_array($related_destination)) continue;
                    ?>
                    <a href="destination-detail.php?slug=<?php echo htmlspecialchars($related_destination['slug'] ?? ''); ?>" class="related-destination-card">
                        <?php if (!empty($related_destination['featured_image'])): ?>
                            <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars(ltrim($related_destination['featured_image'], '/')); ?>" 
                                 alt="<?php echo htmlspecialchars($related_destination['name'] ?? 'Destination'); ?>" 
                                 class="related-destination-image"
                                 onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-destination.jpg'">
                        <?php else: ?>
                            <img src="<?php echo SITE_URL; ?>/assets/images/default-destination.jpg" 
                                 alt="<?php echo htmlspecialchars($related_destination['name'] ?? 'Destination'); ?>" 
                                 class="related-destination-image">
                        <?php endif; ?>
                        <div class="related-destination-content">
                            <h3 class="related-destination-title"><?php echo htmlspecialchars($related_destination['name'] ?? 'Destination'); ?></h3>
                            <p class="related-destination-desc">
                                <?php 
                                $desc = $related_destination['description'] ?? '';
                                echo htmlspecialchars(substr($desc, 0, 100)) . (strlen($desc) > 100 ? '...' : '');
                                ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Contact -->
            <div class="quick-contact-destination">
                <h4 class="section-title-destination mb-3">Need Help Planning?</h4>
                <div class="contact-info-destination">
                    <p><i class="fas fa-phone"></i> <strong>Call Us:</strong> <?php echo htmlspecialchars($site_settings['contact_phone1'] ?? '+91 8670951999'); ?></p>
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($site_settings['site_email'] ?? 'info@newayatra.com'); ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Hours:</strong> Mon-Sun: 8:00 AM - 8:00 PM</p>
                    <p><i class="fas fa-headset"></i> <strong>Support:</strong> 24/7 WhatsApp Available</p>
                </div>
                <div class="mt-4">
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>?text=Hi,%20I'm%20interested%20in%20visiting%20<?php echo urlencode($destination['name']); ?>" 
                       target="_blank" class="btn btn-success w-100">
                        <i class="fab fa-whatsapp me-2"></i> Chat on WhatsApp
                    </a>
                </div>
            </div>

            <!-- Other Destinations -->
            <?php 
            $valid_other_destinations = is_array($other_destinations) ? $other_destinations : [];
            $other_destinations_count = count($valid_other_destinations);
            
            if ($other_destinations_count > 0): 
            ?>
            <div class="quick-contact-destination mt-4">
                <h4 class="section-title-destination mb-3">Explore Other Destinations</h4>
                <div class="destination-list">
                    <?php foreach ($valid_other_destinations as $other_destination): 
                        if (!is_array($other_destination)) continue;
                    ?>
                    <a href="destination-detail.php?slug=<?php echo htmlspecialchars($other_destination['slug'] ?? ''); ?>" class="other-destination-item">
                        <div class="other-destination-image">
                            <?php if (!empty($other_destination['featured_image'])): ?>
                                <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars(ltrim($other_destination['featured_image'], '/')); ?>" 
                                     alt="<?php echo htmlspecialchars($other_destination['name'] ?? 'Destination'); ?>"
                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>/assets/images/default-destination.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/default-destination.jpg" 
                                     alt="<?php echo htmlspecialchars($other_destination['name'] ?? 'Destination'); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="other-destination-content">
                            <h6><?php echo htmlspecialchars($other_destination['name'] ?? 'Destination'); ?></h6>
                            <p>
                                <?php 
                                $desc = $other_destination['description'] ?? '';
                                echo htmlspecialchars(substr($desc, 0, 60)) . (strlen($desc) > 60 ? '...' : '');
                                ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Best Time to Visit Details -->
            <?php if (!empty($destination['best_time_to_visit'])): ?>
            <div class="quick-contact-destination mt-4">
                <h4 class="section-title-destination mb-3">When to Visit</h4>
                <div class="season-info">
                    <h5><i class="fas fa-calendar-check me-2"></i> Recommended Season</h5>
                    <p class="mb-0"><?php echo htmlspecialchars($destination['best_time_to_visit']); ?></p>
                </div>
                <div class="mt-3">
                    <h6 class="h6 mb-2"><i class="fas fa-lightbulb me-2"></i> Travel Tips</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Book accommodations in advance</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Check weather forecasts</li>
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Pack appropriate clothing</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> Travel insurance recommended</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Inquiry Form -->
            <div class="quick-contact-destination mt-4">
                <h4 class="section-title-destination mb-3">Quick Inquiry</h4>
                <form id="destinationInquiryForm" method="POST" action="<?php echo SITE_URL; ?>/includes/process-inquiry.php">
                    <input type="hidden" name="destination_name" value="<?php echo htmlspecialchars($destination['name']); ?>">
                    <input type="hidden" name="destination_id" value="<?php echo $destination['id']; ?>">
                    
                    <div class="mb-3">
                        <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                    </div>
                    
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                    </div>
                    
                    <div class="mb-3">
                        <input type="tel" class="form-control" name="phone" placeholder="Phone Number">
                    </div>
                    
                    <div class="mb-3">
                        <textarea class="form-control" name="message" rows="3" placeholder="Your inquiry about <?php echo htmlspecialchars($destination['name']); ?>"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i> Send Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<section class="cta-section-destination">
    <div class="container">
        <h2 class="cta-title-destination">Ready to Explore <?php echo htmlspecialchars($destination['name']); ?>?</h2>
        <p class="cta-text-destination">Let us help you plan the perfect trip. Contact our travel experts today!</p>
        <div class="cta-buttons">
            <a href="contact.php" class="btn btn-light btn-lg">
                <i class="fas fa-calendar-check me-2"></i> Plan Your Trip
            </a>
            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $site_settings['contact_phone1'] ?? '+918670951999'); ?>" class="btn btn-outline-light btn-lg">
                <i class="fas fa-phone me-2"></i> Call Now
            </a>
        </div>
    </div>
</section>

<!-- WhatsApp Float -->
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>?text=Hi,%20I'm%20interested%20in%20visiting%20<?php echo urlencode($destination['name']); ?>" 
   target="_blank" class="whatsapp-float" title="Chat with us on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Page Specific JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Destination detail page loaded for: <?php echo htmlspecialchars($destination['name'] ?? ''); ?>');
    
    // Lightbox initialization for gallery
    if (typeof lightbox !== 'undefined') {
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Image %1 of %2'
        });
    }
    
    // Inquiry form handling
    const inquiryForm = document.getElementById('destinationInquiryForm');
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const nameInput = this.querySelector('input[name="name"]');
            const emailInput = this.querySelector('input[name="email"]');
            
            if (!nameInput.value.trim()) {
                alert('Please enter your name.');
                nameInput.focus();
                return;
            }
            
            if (!emailInput.value.trim()) {
                alert('Please enter your email address.');
                emailInput.focus();
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value)) {
                alert('Please enter a valid email address.');
                emailInput.focus();
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
            submitBtn.disabled = true;
            
            // Submit form (you can replace this with AJAX)
            setTimeout(() => {
                alert('Thank you for your inquiry! We will contact you soon.');
                inquiryForm.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1500);
            
            // For production, you would use AJAX to submit the form
            // this.submit();
        });
    }
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '#!') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                
                // Calculate offset for navbar
                const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 80;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add animation to info cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe info cards
    document.querySelectorAll('.info-card').forEach(card => {
        observer.observe(card);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
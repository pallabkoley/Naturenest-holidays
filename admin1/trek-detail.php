<?php
/* =========================================================
   TREK DETAILS — Admin Preview
   Path: /admin1/trek-detail.php
   ========================================================= */

// 1. Force correct path constants BEFORE loading config.php
// This prevents the admin folder from breaking frontend links
if (!defined('BASE_URL')) define('BASE_URL', '../');
if (!defined('PAGES_URL')) define('PAGES_URL', '../pages/');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 2. Get trek ID and slug from URL (Safe Sanitization)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? htmlspecialchars(strip_tags(trim($_GET['slug']))) : '';

// 3. Handle empty parameters
if ($id <= 0 && empty($slug)) {
    header('Location: manage-treks.php'); // Redirect to admin trek list
    exit;
}

// 4. Fetch trek details using MySQLi
$conn = $db->getConnection();

if (!empty($slug)) {
    $stmt = $conn->prepare("SELECT * FROM treks WHERE slug = ? AND status = 1");
    $stmt->bind_param("s", $slug);
} else {
    $stmt = $conn->prepare("SELECT * FROM treks WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$result = $stmt->get_result();
$trek = $result->fetch_assoc();

// 5. Handle Trek Not Found
if (!$trek) {
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;">
        <h2 style="color:#c66b05;">Trek Not Found</h2>
        <p>The trek you are looking for does not exist or is currently inactive.</p>
        <a href="manage-treks.php" style="color:#8f1402; font-weight:bold;">Return to Manage Treks</a>
    </div>');
}

// 6. Decode JSON fields
$gallery_images = !empty($trek['gallery_images']) ? json_decode($trek['gallery_images'], true) : [];
$highlights = !empty($trek['highlights']) ? json_decode($trek['highlights'], true) : [];
if (empty($highlights) && !empty($trek['highlights']) && is_string($trek['highlights'])) {
    $highlights = explode("\n", trim($trek['highlights']));
}

$itinerary = !empty($trek['itinerary']) ? json_decode($trek['itinerary'], true) : [];
$faq = !empty($trek['faq']) ? json_decode($trek['faq'], true) : [];

// 7. Get similar treks
$similar_treks = [];
$similar_query = "SELECT id, name, slug, difficulty_level, duration, price, featured_image, description 
                  FROM treks 
                  WHERE status = 1 AND id != ? AND difficulty_level = ? 
                  ORDER BY display_order ASC, name ASC LIMIT 3";
$stmt = $conn->prepare($similar_query);
$stmt->bind_param("is", $trek['id'], $trek['difficulty_level']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $similar_treks[] = $row;
}

if (empty($similar_treks)) {
    $similar_query = "SELECT id, name, slug, difficulty_level, duration, price, featured_image, description 
                      FROM treks WHERE status = 1 AND id != ? 
                      ORDER BY display_order ASC, name ASC LIMIT 3";
    $stmt = $conn->prepare($similar_query);
    $stmt->bind_param("i", $trek['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $similar_treks[] = $row;
    }
}

// 8. Get featured treks for sidebar
$featured_treks = [];
$featured_query = "SELECT id, name, slug, difficulty_level, duration, featured_image 
                   FROM treks WHERE featured = 1 AND status = 1 AND id != ? 
                   ORDER BY display_order ASC LIMIT 3";
$stmt = $conn->prepare($featured_query);
$stmt->bind_param("i", $trek['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $featured_treks[] = $row;
}

// 9. Page title and meta
$page_title = $trek['name'] . ' - Trekking Package';
$meta_description = !empty($trek['meta_description']) ? $trek['meta_description'] : substr(strip_tags($trek['description']), 0, 160);
$meta_keywords = !empty($trek['meta_keywords']) ? $trek['meta_keywords'] : 'trekking, himalayas, adventure, ' . $trek['name'];
$difficulty_class = strtolower(str_replace(' ', '-', $trek['difficulty_level']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #ff6b35;
            --primary-dark: #e85a2a;
            --secondary-color: #2c3e50;
            --accent-color: #27ae60;
            --dark-color: #1a2526;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
            --border-color: #e9ecef;
            --shadow: 0 5px 20px rgba(0,0,0,0.05);
            --shadow-hover: 0 10px 30px rgba(255, 107, 53, 0.15);
        }

        body { font-family: 'Montserrat', sans-serif; color: #333; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; font-weight: 600; }

        .section-title { font-size: 2.5rem; font-weight: 600; margin-bottom: 1rem; position: relative; color: var(--dark-color); }
        .section-title::after { content: ''; position: absolute; bottom: -10px; left: 0; width: 60px; height: 3px; background: var(--primary-color); }
        .section-title.text-center::after { left: 50%; transform: translateX(-50%); }
        .section-subtitle { font-size: 1.1rem; color: var(--gray-color); max-width: 700px; }
        .section-tag { display: inline-block; color: var(--primary-color); font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }

        /* Page Header */
        .page-header {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.5)), url('<?php echo !empty($trek['featured_image']) ? '../' . $trek['featured_image'] : '../assets/images/trek-header.jpg'; ?>');
            background-size: cover; background-position: center; padding: 100px 0 60px; color: white; text-align: center; margin-bottom: 50px;
        }
        .page-header h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .page-header .breadcrumb { background: transparent; justify-content: center; }
        .page-header .breadcrumb-item a { color: white; text-decoration: none; opacity: 0.8; transition: opacity 0.3s ease; }
        .page-header .breadcrumb-item a:hover { opacity: 1; }
        .page-header .breadcrumb-item.active { color: var(--primary-color); }
        .page-header .breadcrumb-item + .breadcrumb-item::before { color: white; }

        /* Trek Detail */
        .trek-detail-wrapper { background: white; border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; margin-bottom: 50px; }
        .trek-gallery { position: relative; }
        .main-image { position: relative; overflow: hidden; aspect-ratio: 16/9; }
        .main-image img { width: 100%; height: 100%; object-fit: cover; }
        .trek-badge {
            position: absolute; top: 20px; right: 20px; background: var(--primary-color); color: white;
            padding: 8px 25px; border-radius: 50px; font-size: 1rem; font-weight: 600;
            z-index: 2; box-shadow: var(--shadow); text-transform: uppercase; letter-spacing: 1px;
        }
        .trek-featured {
            position: absolute; top: 20px; left: 20px; background: rgba(255, 193, 7, 0.9); color: #212529;
            padding: 8px 20px; border-radius: 50px; font-size: 0.9rem; font-weight: 600;
            z-index: 2; backdrop-filter: blur(5px);
        }
        .trek-featured i { margin-right: 8px; color: #ff6b35; }
        .gallery-thumbnails { display: flex; gap: 10px; padding: 15px; background: var(--light-color); overflow-x: auto; }
        .gallery-thumbnails img {
            width: 100px; height: 70px; object-fit: cover; border-radius: 8px; cursor: pointer;
            transition: all 0.3s ease; opacity: 0.7; border: 2px solid transparent;
        }
        .gallery-thumbnails img:hover, .gallery-thumbnails img.active { opacity: 1; border-color: var(--primary-color); transform: scale(1.05); }
        .trek-content { padding: 40px; }
        .trek-header { margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .trek-category { display: inline-block; color: var(--primary-color); font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; font-weight: 600; }
        .trek-header h1 { font-size: 2.5rem; margin-bottom: 15px; }
        .trek-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 20px; }
        .meta-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--light-color); border-radius: 10px; transition: all 0.3s ease; }
        .meta-item:hover { background: white; box-shadow: var(--shadow); transform: translateY(-3px); }
        .meta-icon { width: 45px; height: 45px; background: rgba(255, 107, 53, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.2rem; }
        .meta-content h4 { font-size: 0.9rem; margin-bottom: 5px; color: var(--gray-color); font-family: 'Montserrat', sans-serif; }
        .meta-content p { margin: 0; font-weight: 600; color: var(--dark-color); }
        .trek-description { margin-bottom: 40px; }
        .trek-description h2 { font-size: 1.8rem; margin-bottom: 20px; }
        .trek-description p { color: var(--gray-color); line-height: 1.8; margin-bottom: 20px; font-size: 1rem; }

        /* Highlights */
        .highlights-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0; }
        .highlight-item { display: flex; align-items: center; gap: 10px; padding: 15px; background: var(--light-color); border-radius: 10px; border-left: 3px solid var(--primary-color); }
        .highlight-item i { color: var(--primary-color); font-size: 1.2rem; }
        .highlight-item span { color: var(--dark-color); font-weight: 500; }

        /* Itinerary */
        .itinerary-timeline { position: relative; padding: 20px 0; }
        .itinerary-timeline::before { content: ''; position: absolute; left: 30px; top: 0; bottom: 0; width: 2px; background: var(--border-color); }
        .itinerary-item { position: relative; padding-left: 80px; margin-bottom: 30px; }
        .itinerary-day {
            position: absolute; left: 0; width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;
            font-size: 1rem; box-shadow: var(--shadow); z-index: 2;
        }
        .itinerary-content { background: var(--light-color); padding: 20px; border-radius: 10px; border-left: 3px solid var(--primary-color); }
        .itinerary-content h4 { font-size: 1.3rem; margin-bottom: 10px; color: var(--dark-color); }
        .itinerary-content p { color: var(--gray-color); margin: 0; line-height: 1.6; }

        /* Info Cards */
        .info-card { background: var(--light-color); border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid var(--border-color); transition: all 0.3s ease; }
        .info-card:hover { background: white; box-shadow: var(--shadow-hover); }
        .info-card h3 { font-size: 1.3rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .info-card h3 i { color: var(--primary-color); }
        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .info-list li:last-child { border-bottom: none; }
        .info-list li i { width: 20px; color: var(--primary-color); margin-top: 3px; }
        .info-list li span { color: var(--gray-color); flex: 1; line-height: 1.6; }

        /* FAQ Accordion */
        .faq-accordion { margin-top: 20px; }
        .faq-item { margin-bottom: 15px; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
        .faq-question { padding: 15px 20px; background: white; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 600; transition: all 0.3s ease; }
        .faq-question:hover { background: var(--light-color); }
        .faq-question i { transition: transform 0.3s ease; }
        .faq-question.active i { transform: rotate(180deg); }
        .faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s ease; background: var(--light-color); }
        .faq-answer.show { padding: 20px; max-height: 500px; }
        .faq-answer p { margin: 0; color: var(--gray-color); line-height: 1.6; }

        /* Price Card */
        .price-card { background: linear-gradient(135deg, var(--primary-color), #ff8c00); color: white; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 30px; }
        .price-card h3 { color: white; font-size: 1.5rem; margin-bottom: 15px; }
        .price-amount { font-size: 3rem; font-weight: 700; margin-bottom: 10px; }
        .price-amount small { font-size: 1rem; opacity: 0.9; }
        .btn-light { background: white; color: var(--primary-color); padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; display: inline-block; transition: all 0.3s ease; }
        .btn-light:hover { background: var(--primary-color); color: white; transform: translateY(-3px); }

        /* Action Buttons */
        .action-buttons { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn-primary {
            background: var(--primary-color); border: none; padding: 14px 35px; border-radius: 50px; color: white;
            font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
            transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; font-size: 0.95rem;
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-3px); box-shadow: var(--shadow-hover); color: white; }
        .btn-outline {
            background: transparent; border: 2px solid var(--primary-color); padding: 12px 30px; border-radius: 50px;
            color: var(--primary-color); font-weight: 600; text-decoration: none; display: inline-flex;
            align-items: center; gap: 10px; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; font-size: 0.95rem;
        }
        .btn-outline:hover { background: var(--primary-color); color: white; transform: translateY(-3px); }
        .btn-share {
            background: var(--light-color); border: 1px solid var(--border-color); padding: 12px 25px; border-radius: 50px;
            color: var(--dark-color); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;
        }
        .btn-share:hover { background: var(--primary-color); color: white; border-color: var(--primary-color); }

        /* Sidebar Cards */
        .filter-section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
        .filter-title { font-size: 1.3rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary-color); }
        .similar-trek-card { display: flex; gap: 15px; margin-bottom: 20px; padding: 12px; background: var(--light-color); border-radius: 10px; transition: all 0.3s ease; }
        .similar-trek-card:hover { background: white; box-shadow: var(--shadow); transform: translateX(5px); }
        .similar-trek-image { width: 80px; height: 60px; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .similar-trek-image img { width: 100%; height: 100%; object-fit: cover; }
        .similar-trek-content { flex: 1; min-width: 0; }
        .similar-trek-content h4 { font-size: 1rem; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .similar-trek-content .difficulty { font-size: 0.8rem; color: var(--primary-color); font-weight: 600; text-transform: uppercase; }
        .similar-trek-content .duration { font-size: 0.8rem; color: var(--gray-color); }
        .similar-trek-link { color: var(--primary-color); text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .similar-trek-link:hover i { transform: translateX(5px); }
        .help-card { background: linear-gradient(135deg, var(--secondary-color), #1a2526); color: white; border-radius: 12px; padding: 30px; text-align: center; }
        .help-card h4 { color: white; font-size: 1.3rem; margin-bottom: 15px; }
        .help-card p { color: rgba(255,255,255,0.8); margin-bottom: 20px; }

        /* Difficulty Badge */
        .difficulty-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .difficulty-easy { background: #28a745; color: white; }
        .difficulty-moderate { background: #ffc107; color: #212529; }
        .difficulty-moderate-to-difficult { background: #fd7e14; color: white; }
        .difficulty-difficult { background: #dc3545; color: white; }
        .difficulty-very-difficult { background: #6f42c1; color: white; }
        .difficulty-strenuous { background: #6f42c1; color: white; }

        /* Share Modal */
        .share-buttons { display: flex; gap: 15px; justify-content: center; margin-top: 20px; }
        .share-btn { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; transition: all 0.3s ease; text-decoration: none; }
        .share-btn.facebook { background: #3b5998; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }
        .share-btn:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); color: white; }

        /* Similar Treks Card */
        .trek-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); height: 100%; transition: all 0.3s ease; }
        .trek-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        .trek-image { position: relative; height: 200px; overflow: hidden; }
        .trek-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .trek-card:hover .trek-image img { transform: scale(1.05); }
        .trek-image .difficulty-badge { position: absolute; top: 15px; right: 15px; z-index: 2; }
        .trek-info { padding: 20px; }
        .trek-info h3 { font-size: 1.3rem; margin-bottom: 10px; }
        .trek-info .trek-description { color: var(--gray-color); font-size: 0.9rem; margin-bottom: 15px; line-height: 1.6; }
        .trek-info .trek-meta { display: flex; gap: 15px; margin-bottom: 15px; font-size: 0.9rem; color: var(--gray-color); }
        .trek-info .trek-meta i { color: var(--primary-color); margin-right: 5px; }
        .trek-info .price { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 15px; }
        .btn-link { color: var(--primary-color); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .btn-link:hover { color: var(--primary-dark); }
        .btn-link:hover i { transform: translateX(5px); }

        /* Responsive */
        @media (max-width: 991px) {
            .page-header h1 { font-size: 2.8rem; }
            .trek-header h1 { font-size: 2rem; }
            .trek-content { padding: 30px; }
        }
        @media (max-width: 767px) {
            .page-header { padding: 80px 0 40px; }
            .page-header h1 { font-size: 2.2rem; }
            .trek-content { padding: 20px; }
            .trek-header h1 { font-size: 1.8rem; }
            .trek-meta { grid-template-columns: 1fr; }
            .itinerary-timeline::before { left: 20px; }
            .itinerary-item { padding-left: 60px; }
            .itinerary-day { width: 40px; height: 40px; font-size: 0.8rem; }
            .action-buttons { flex-direction: column; }
            .action-buttons a { width: 100%; justify-content: center; }
        }
        @media (max-width: 575px) {
            .page-header h1 { font-size: 1.8rem; }
            .section-title { font-size: 1.8rem; }
            .gallery-thumbnails img { width: 70px; height: 50px; }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 data-aos="fade-up"><?php echo htmlspecialchars($trek['name']); ?></h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="../pages/trekking.php">Treks</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($trek['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Trek Detail Section -->
<section class="trek-detail-section py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="trek-detail-wrapper" data-aos="fade-up">
                    <!-- Gallery -->
                    <div class="trek-gallery">
                        <div class="main-image">
                            <?php 
                            $featured_image = !empty($trek['featured_image']) 
                                ? '../' . $trek['featured_image']
                                : '../assets/images/trek-placeholder.jpg';
                            ?>
                            <img src="<?php echo $featured_image; ?>" 
                                 alt="<?php echo htmlspecialchars($trek['name']); ?>"
                                 id="mainImage"
                                 onerror="this.src='../assets/images/trek-placeholder.jpg'">
                            
                            <?php if($trek['featured'] == 1): ?>
                            <div class="trek-featured">
                                <i class="fas fa-crown"></i> Featured Trek
                            </div>
                            <?php endif; ?>
                            
                            <span class="trek-badge">
                                <i class="fas fa-flag-checkered me-2"></i>
                                <?php echo htmlspecialchars($trek['difficulty_level']); ?>
                            </span>
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <?php if(!empty($gallery_images) && is_array($gallery_images)): ?>
                        <div class="gallery-thumbnails">
                            <img src="<?php echo '../' . $trek['featured_image']; ?>" 
                                 alt="Main" 
                                 class="active"
                                 onclick="changeImage(this.src)">
                            <?php foreach($gallery_images as $index => $image): ?>
                                <?php if(!empty($image)): ?>
                                <img src="../<?php echo htmlspecialchars($image); ?>" 
                                     alt="Gallery <?php echo $index + 1; ?>"
                                     onclick="changeImage(this.src)">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="trek-content">
                        <!-- Header -->
                        <div class="trek-header">
                            <div class="trek-category">
                                <i class="fas fa-route me-2"></i> Trekking Adventure
                            </div>
                            <h1><?php echo htmlspecialchars($trek['name']); ?></h1>
                            
                            <!-- Quick Info Meta -->
                            <div class="trek-meta">
                                <div class="meta-item">
                                    <div class="meta-icon"><i class="fas fa-clock"></i></div>
                                    <div class="meta-content">
                                        <h4>Duration</h4>
                                        <p><?php echo htmlspecialchars($trek['duration'] ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"><i class="fas fa-mountain"></i></div>
                                    <div class="meta-content">
                                        <h4>Max Altitude</h4>
                                        <p><?php echo htmlspecialchars($trek['max_altitude'] ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"><i class="fas fa-map-marker-alt"></i></div>
                                    <div class="meta-content">
                                        <h4>Starting Point</h4>
                                        <p><?php echo htmlspecialchars($trek['starting_point'] ?: 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"><i class="fas fa-calendar-alt"></i></div>
                                    <div class="meta-content">
                                        <h4>Best Season</h4>
                                        <p><?php echo htmlspecialchars($trek['best_season'] ?: 'Year Round'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="trek-description">
                            <h2>About <?php echo htmlspecialchars($trek['name']); ?></h2>
                            <?php echo $trek['description']; ?>
                        </div>
                        
                        <!-- Highlights -->
                        <?php if(!empty($highlights)): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-star"></i> Trek Highlights</h3>
                            <div class="highlights-grid">
                                <?php foreach($highlights as $highlight): ?>
                                    <?php if(!empty(trim($highlight))): ?>
                                    <div class="highlight-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?php echo htmlspecialchars(trim($highlight)); ?></span>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Itinerary (FIXED: $loop->iteration replaced with $day_counter) -->
                        <?php if(!empty($itinerary) && is_array($itinerary)): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-route"></i> Detailed Itinerary</h3>
                            <div class="itinerary-timeline">
                                <?php $day_counter = 1; ?>
                                <?php foreach($itinerary as $item): ?>
                                    <?php if(!empty($item['title']) || !empty($item['content'])): ?>
                                    <div class="itinerary-item">
                                        <div class="itinerary-day">
                                            Day <?php echo isset($item['day']) && !empty($item['day']) ? htmlspecialchars($item['day']) : $day_counter; ?>
                                        </div>
                                        <div class="itinerary-content">
                                            <h4><?php echo htmlspecialchars($item['title'] ?? 'Day ' . $day_counter); ?></h4>
                                            <p><?php echo nl2br(htmlspecialchars($item['content'] ?? '')); ?></p>
                                        </div>
                                    </div>
                                    <?php $day_counter++; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Inclusions & Exclusions -->
                        <div class="row g-4">
                            <?php if(!empty($trek['inclusions'])): ?>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> Inclusions</h3>
                                    <?php echo $trek['inclusions']; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($trek['exclusions'])): ?>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h3><i class="fas fa-times-circle" style="color: #dc3545;"></i> Exclusions</h3>
                                    <?php echo $trek['exclusions']; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- FAQs -->
                        <?php if(!empty($faq) && is_array($faq)): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                            <div class="faq-accordion">
                                <?php foreach($faq as $index => $item): ?>
                                    <?php if(!empty($item['question'])): ?>
                                    <div class="faq-item">
                                        <div class="faq-question" onclick="toggleFAQ(<?php echo $index; ?>)">
                                            <?php echo htmlspecialchars($item['question']); ?>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="faq-answer" id="faq-<?php echo $index; ?>">
                                            <p><?php echo nl2br(htmlspecialchars($item['answer'] ?? '')); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons (FIXED: Paths now point to ../pages/) -->
                        <div class="action-buttons">
                            <a href="../pages/booking.php?type=trek&id=<?php echo $trek['id']; ?>&name=<?php echo urlencode($trek['name']); ?>" class="btn-primary">
                                <i class="fas fa-calendar-check"></i> Book This Trek
                            </a>
                            <a href="../pages/contact.php?subject=<?php echo urlencode('Inquiry about ' . $trek['name'] . ' Trek'); ?>" class="btn-outline">
                                <i class="fas fa-headset"></i> Ask a Question
                            </a>
                            <a href="#" class="btn-share" data-bs-toggle="modal" data-bs-target="#shareModal">
                                <i class="fas fa-share-alt"></i> Share
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar" data-aos="fade-left">
                    <!-- Price Card -->
                    <div class="price-card">
                        <h3>Trek Price</h3>
                        <div class="price-amount">
                            ₹<?php echo number_format($trek['price'], 2); ?>
                            <small>per person</small>
                        </div>
                        <a href="../pages/booking.php?type=trek&id=<?php echo $trek['id']; ?>&name=<?php echo urlencode($trek['name']); ?>" class="btn-light w-100">
                            Book Now <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                    
                    <!-- Quick Info Card -->
                    <div class="filter-section">
                        <h4 class="filter-title"><i class="fas fa-info-circle me-2"></i>Quick Info</h4>
                        <ul class="info-list">
                            <li><i class="fas fa-clock"></i> <span><strong>Duration:</strong> <?php echo $trek['duration'] ?: 'N/A'; ?></span></li>
                            <li><i class="fas fa-mountain"></i> <span><strong>Difficulty:</strong> <span class="difficulty-badge difficulty-<?php echo $difficulty_class; ?>"><?php echo $trek['difficulty_level']; ?></span></span></li>
                            <li><i class="fas fa-arrow-up"></i> <span><strong>Max Altitude:</strong> <?php echo $trek['max_altitude'] ?: 'N/A'; ?></span></li>
                            <li><i class="fas fa-map-marker-alt"></i> <span><strong>Start Point:</strong> <?php echo $trek['starting_point'] ?: 'N/A'; ?></span></li>
                            <li><i class="fas fa-calendar-alt"></i> <span><strong>Best Season:</strong> <?php echo $trek['best_season'] ?: 'Year Round'; ?></span></li>
                        </ul>
                    </div>
                    
                    <!-- Similar Treks (FIXED: Paths now point to ../pages/) -->
                    <?php if(!empty($similar_treks)): ?>
                    <div class="filter-section">
                        <h4 class="filter-title"><i class="fas fa-hiking me-2"></i>Similar Treks</h4>
                        <?php foreach($similar_treks as $similar): ?>
                        <div class="similar-trek-card">
                            <div class="similar-trek-image">
                                <?php 
                                $similar_image = !empty($similar['featured_image']) 
                                    ? '../' . $similar['featured_image']
                                    : '../assets/images/trek-placeholder.jpg';
                                ?>
                                <img src="<?php echo $similar_image; ?>" alt="<?php echo htmlspecialchars($similar['name']); ?>">
                            </div>
                            <div class="similar-trek-content">
                                <h4><?php echo htmlspecialchars($similar['name']); ?></h4>
                                <div class="difficulty"><?php echo $similar['difficulty_level']; ?></div>
                                <div class="duration mb-2"><?php echo $similar['duration']; ?></div>
                                <a href="../pages/trek-detail.php?id=<?php echo $similar['id']; ?>&slug=<?php echo $similar['slug']; ?>" class="similar-trek-link">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Featured Treks (FIXED: Paths now point to ../pages/) -->
                    <?php if(!empty($featured_treks)): ?>
                    <div class="filter-section">
                        <h4 class="filter-title"><i class="fas fa-star me-2"></i>Featured Treks</h4>
                        <?php foreach($featured_treks as $featured): ?>
                        <div class="similar-trek-card">
                            <div class="similar-trek-image">
                                <?php 
                                $featured_image = !empty($featured['featured_image']) 
                                    ? '../' . $featured['featured_image']
                                    : '../assets/images/trek-placeholder.jpg';
                                ?>
                                <img src="<?php echo $featured_image; ?>" alt="<?php echo htmlspecialchars($featured['name']); ?>">
                            </div>
                            <div class="similar-trek-content">
                                <h4><?php echo htmlspecialchars($featured['name']); ?></h4>
                                <div class="difficulty"><?php echo $featured['difficulty_level']; ?></div>
                                <div class="duration mb-2"><?php echo $featured['duration']; ?></div>
                                <a href="../pages/trek-detail.php?id=<?php echo $featured['id']; ?>&slug=<?php echo $featured['slug']; ?>" class="similar-trek-link">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Need Help Card -->
                    <div class="help-card">
                        <i class="fas fa-headset fa-2x mb-3"></i>
                        <h4>Need Help Planning?</h4>
                        <p>Our trek experts can help you choose the perfect trek and answer any questions.</p>
                        <a href="../pages/contact.php" class="btn-light">Contact Us <i class="fas fa-arrow-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Similar Treks Section (FIXED: Paths now point to ../pages/) -->
<?php if(!empty($similar_treks)): ?>
<section class="similar-treks-section py-5 bg-light">
    <div class="container">
        <div class="row text-center mb-5" data-aos="fade-up">
            <div class="col-12">
                <span class="section-tag">Explore More</span>
                <h2 class="section-title">Similar Treks You Might Like</h2>
                <p class="section-subtitle">Discover other amazing treks with similar difficulty level</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach($similar_treks as $index => $similar): ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="trek-card">
                    <div class="trek-image">
                        <?php 
                        $similar_image = !empty($similar['featured_image']) 
                            ? '../' . $similar['featured_image']
                            : '../assets/images/trek-placeholder.jpg';
                        ?>
                        <img src="<?php echo $similar_image; ?>" 
                             alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        <span class="difficulty-badge difficulty-<?php echo strtolower(str_replace(' ', '-', $similar['difficulty_level'])); ?>">
                            <?php echo $similar['difficulty_level']; ?>
                        </span>
                    </div>
                    <div class="trek-info">
                        <h3><?php echo htmlspecialchars($similar['name']); ?></h3>
                        <p class="trek-description">
                            <?php 
                            $desc = strip_tags($similar['description'] ?? '');
                            echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc;
                            ?>
                        </p>
                        <div class="trek-meta">
                            <span><i class="fas fa-clock"></i> <?php echo $similar['duration']; ?></span>
                        </div>
                        <div class="price">
                            ₹<?php echo number_format($similar['price'], 2); ?>
                        </div>
                        <a href="../pages/trek-detail.php?id=<?php echo $similar['id']; ?>&slug=<?php echo $similar['slug']; ?>" class="btn-link">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="../pages/trekking.php" class="btn btn-outline-primary btn-lg">
                View All Treks <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Share <?php echo htmlspecialchars($trek['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Share this trek with your friends and family</p>
                <div class="share-buttons">
                    <?php 
                    $current_url = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    $share_text = urlencode('Check out ' . $trek['name'] . ' trek with Newa Yatra!');
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" class="share-btn facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo $share_text; ?>&url=<?php echo $current_url; ?>" target="_blank" class="share-btn twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://wa.me/?text=<?php echo $share_text . ' ' . $current_url; ?>" target="_blank" class="share-btn whatsapp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    AOS.init({ duration: 1000, once: true });

    function changeImage(src) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.gallery-thumbnails img').forEach(img => { img.classList.remove('active'); });
        event.target.classList.add('active');
    }

    function toggleFAQ(index) {
        const answer = document.getElementById('faq-' + index);
        const question = answer.previousElementSibling;
        answer.classList.toggle('show');
        question.classList.toggle('active');
    }

    document.querySelectorAll('img').forEach(img => {
        img.onerror = function() {
            if(!this.src.includes('trek-placeholder.jpg') && !this.src.includes('trek-header.jpg')) {
                this.src = '../assets/images/trek-placeholder.jpg';
            }
        };
    });
</script>

<?php include '../includes/footer.php'; ?>
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin('login.php');
$user = $auth->getUserInfo();

if (!$auth->isAdmin()) {
    die('<div style="text-align:center; padding:50px;">
        <h1 style="color:#c66b05;">Access Denied</h1>
        <p>You do not have permission to access the admin panel.</p>
        <a href="../index.php" style="color:#8f1402;">Return to Home</a>
    </div>');
}

$conn = $db->getConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_trek':
                $name = trim($_POST['name']);
                $slug = createSlug($name);
                $description = trim($_POST['description']);
                $duration = trim($_POST['duration']);
                $difficulty_level = $_POST['difficulty_level'];
                $max_altitude = trim($_POST['max_altitude']);
                $best_season = trim($_POST['best_season']);
                $starting_point = trim($_POST['starting_point']);
                $price = floatval($_POST['price']);
                $highlights = trim($_POST['highlights']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $faq = trim($_POST['faq']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $meta_keywords = trim($_POST['meta_keywords']);
                $status = isset($_POST['status']) ? 1 : 0;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = intval($_POST['display_order']);
                
                // Handle featured image upload
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = __DIR__ . '/assets/upload/treks/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = 'admin1/assets/upload/treks/' . $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = __DIR__ . '/assets/upload/treks/gallery/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === 0) {
                            $file_extension = pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION);
                            $unique_name = uniqid() . '_' . time() . '_' . $key . '.' . $file_extension;
                            $target_file = $upload_dir . $unique_name;
                            
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $max_size = 5 * 1024 * 1024; // 5MB
                            
                            if (in_array(strtolower($file_extension), $allowed_types)) {
                                if ($_FILES['gallery_images']['size'][$key] <= $max_size) {
                                    if (move_uploaded_file($tmp_name, $target_file)) {
                                        $gallery_images[] = 'admin1/assets/upload/treks/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO treks (
                    name, slug, description, featured_image, gallery_images, duration, difficulty_level,
                    max_altitude, best_season, starting_point, price, highlights, itinerary,
                    inclusions, exclusions, faq, meta_title, meta_description, meta_keywords,
                    status, featured, display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                
                $stmt->bind_param(
                    "ssssssssssdssssssssiii",
                    $name, $slug, $description, $featured_image, $gallery_json, $duration, $difficulty_level,
                    $max_altitude, $best_season, $starting_point, $price, $highlights, $itinerary,
                    $inclusions, $exclusions, $faq, $meta_title, $meta_description, $meta_keywords,
                    $status, $featured, $display_order
                );
                
                if ($stmt->execute()) {
                    $message = 'Trek added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding trek: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_trek':
                $trek_id = intval($_POST['trek_id']);
                $name = trim($_POST['name']);
                $slug = createSlug($name);
                $description = trim($_POST['description']);
                $duration = trim($_POST['duration']);
                $difficulty_level = $_POST['difficulty_level'];
                $max_altitude = trim($_POST['max_altitude']);
                $best_season = trim($_POST['best_season']);
                $starting_point = trim($_POST['starting_point']);
                $price = floatval($_POST['price']);
                $highlights = trim($_POST['highlights']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $faq = trim($_POST['faq']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $meta_keywords = trim($_POST['meta_keywords']);
                $status = isset($_POST['status']) ? 1 : 0;
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = intval($_POST['display_order']);
                
                // Get current trek data
                $current_trek = [];
                $result = $conn->query("SELECT featured_image, gallery_images FROM treks WHERE id = $trek_id");
                if ($result) {
                    $current_trek = $result->fetch_assoc();
                }
                
                // Handle featured image update
                $featured_image = $current_trek['featured_image'];
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = __DIR__ . '/assets/upload/treks/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($featured_image && file_exists(__DIR__ . '/../' . $featured_image)) {
                        unlink(__DIR__ . '/../' . $featured_image);
                    }
                    
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024;
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = 'admin1/assets/upload/treks/' . $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = $current_trek['gallery_images'] ? json_decode($current_trek['gallery_images'], true) : [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = __DIR__ . '/assets/upload/treks/gallery/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === 0) {
                            $file_extension = pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION);
                            $unique_name = uniqid() . '_' . time() . '_' . $key . '.' . $file_extension;
                            $target_file = $upload_dir . $unique_name;
                            
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $max_size = 5 * 1024 * 1024;
                            
                            if (in_array(strtolower($file_extension), $allowed_types)) {
                                if ($_FILES['gallery_images']['size'][$key] <= $max_size) {
                                    if (move_uploaded_file($tmp_name, $target_file)) {
                                        $gallery_images[] = 'admin1/assets/upload/treks/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Handle deletion of gallery images
                if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                    foreach ($_POST['delete_gallery_images'] as $image_to_delete) {
                        if (!empty($image_to_delete)) {
                            $key = array_search($image_to_delete, $gallery_images);
                            if ($key !== false) {
                                if (file_exists(__DIR__ . '/../' . $image_to_delete)) {
                                    unlink(__DIR__ . '/../' . $image_to_delete);
                                }
                                unset($gallery_images[$key]);
                            }
                        }
                    }
                    $gallery_images = array_values($gallery_images);
                }
                
                $stmt = $conn->prepare("
                    UPDATE treks 
                    SET 
                        name = ?, 
                        slug = ?,
                        description = ?, 
                        featured_image = ?, 
                        gallery_images = ?, 
                        duration = ?,
                        difficulty_level = ?, 
                        max_altitude = ?, 
                        best_season = ?, 
                        starting_point = ?, 
                        price = ?,
                        highlights = ?, 
                        itinerary = ?, 
                        inclusions = ?, 
                        exclusions = ?, 
                        faq = ?,
                        meta_title = ?, 
                        meta_description = ?, 
                        meta_keywords = ?,
                        status = ?, 
                        featured = ?,
                        display_order = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                
                $stmt->bind_param(
                    "ssssssssssdssssssssiiii",
                    $name, $slug, $description, $featured_image, $gallery_json, $duration,
                    $difficulty_level, $max_altitude, $best_season, $starting_point, $price,
                    $highlights, $itinerary, $inclusions, $exclusions, $faq,
                    $meta_title, $meta_description, $meta_keywords,
                    $status, $featured, $display_order, $trek_id
                );
                
                if ($stmt->execute()) {
                    $message = 'Trek updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating trek: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_trek':
                $trek_id = intval($_POST['trek_id']);
                
                $result = $conn->query("SELECT featured_image, gallery_images FROM treks WHERE id = $trek_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete featured image
                    if ($row['featured_image'] && file_exists(__DIR__ . '/../' . $row['featured_image'])) {
                        unlink(__DIR__ . '/../' . $row['featured_image']);
                    }
                    
                    // Delete gallery images
                    if ($row['gallery_images']) {
                        $gallery_images = json_decode($row['gallery_images'], true);
                        if (is_array($gallery_images)) {
                            foreach ($gallery_images as $image) {
                                if (file_exists(__DIR__ . '/../' . $image)) {
                                    unlink(__DIR__ . '/../' . $image);
                                }
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM treks WHERE id = ?");
                $stmt->bind_param("i", $trek_id);
                
                if ($stmt->execute()) {
                    $message = 'Trek deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting trek: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all treks for listing
$treks = [];
$result = $conn->query("SELECT * FROM treks ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $treks[] = $row;
    }
}

// Get trek count for stats
$total_treks = 0;
$active_treks = 0;
$featured_treks = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM treks");
if ($result) { $row = $result->fetch_assoc(); $total_treks = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM treks WHERE status = 1");
if ($result) { $row = $result->fetch_assoc(); $active_treks = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM treks WHERE featured = 1 AND status = 1");
if ($result) { $row = $result->fetch_assoc(); $featured_treks = $row['count']; }

// Get other stats for sidebar
$total_destinations = 0; $total_services = 0; $total_cars = 0; $total_testimonials = 0; $total_activities = 0; $total_bookings = 0; $total_messages = 0; $unread_messages = 0;
if ($result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'")) { $row = $result->fetch_assoc(); $total_destinations = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")) { $row = $result->fetch_assoc(); $total_services = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'")) { $row = $result->fetch_assoc(); $total_cars = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'approved' AND featured = 1")) { $row = $result->fetch_assoc(); $total_testimonials = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM popular_activities WHERE status = 'active'")) { $row = $result->fetch_assoc(); $total_activities = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM bookings")) { $row = $result->fetch_assoc(); $total_bookings = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM contact_messages")) { $row = $result->fetch_assoc(); $total_messages = $row['count']; }
if ($result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'")) { $row = $result->fetch_assoc(); $unread_messages = $row['count']; }

// Check if we're in add/edit mode
$edit_mode = false;
$current_trek = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $trek_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM treks WHERE id = $trek_id");
    if ($result && $result->num_rows > 0) {
        $current_trek = $result->fetch_assoc();
        $edit_mode = true;
    }
}

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'trek-' . time() : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Treks - Nature Nest Admin</title>
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .trek-image-preview { max-width: 200px; max-height: 150px; border-radius: 5px; border: 2px solid #ddd; margin: 10px 0; object-fit: cover; }
        .gallery-container { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
        .gallery-image { width: 100px; height: 80px; object-fit: cover; border-radius: 5px; border: 2px solid #ddd; }
        .gallery-item { position: relative; }
        .delete-gallery-image { position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-1 { background: #28a745; color: white; }
        .status-0 { background: #dc3545; color: white; }
        .featured-badge { background: #ffc107; color: #212529; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .difficulty-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .difficulty-Easy { background: #28a745; color: white; }
        .difficulty-Moderate { background: #ffc107; color: #000; }
        .difficulty-Moderate-to-Difficult { background: #fd7e14; color: white; }
        .difficulty-Difficult { background: #dc3545; color: white; }
        .difficulty-Strenuous { background: #6f42c1; color: white; }
        .price-badge { background: #6f42c1; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 600; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-icon { padding: 5px 10px; font-size: 12px; }
        .trek-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #e0e0e0; transition: transform 0.2s; }
        .trek-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .empty-state { text-align: center; padding: 50px 20px; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
        .form-container { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-control { width: 90%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #e67e22; box-shadow: 0 0 0 2px rgba(198,107,5,.2); }
        .form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .table-actions { display: flex; align-items: center; gap: 10px; }
        .text-muted { color: #6c757d; }
        .table-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px; overflow: hidden; }
        .table-header { background: linear-gradient(135deg, #c66b05, #e67e22); color: white; padding: 15px 25px; font-size: 18px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; }
        .table-body { padding: 0; overflow-x: auto; }
        .table { width: 100%; min-width: 1200px; border-collapse: collapse; }
        .table thead { background: #f8f9fa; }
        .table th { padding: 15px; text-align: left; font-weight: 600; color: #495057; border-bottom: 2px solid #e9ecef; }
        .table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #e9ecef; }
        .table tbody tr:hover { background-color: #f8f9fa; }
        .trek-table-image { width: 120px !important; height: 80px !important; object-fit: cover !important; border-radius: 5px; border: 2px solid #e9ecef; }
        .trek-table-image-placeholder { width: 120px; height: 80px; background: #e9ecef; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #6c757d; }
        .table img { max-width: 120px !important; max-height: 80px !important; width: 120px !important; height: 80px !important; object-fit: cover !important; }
        .table-cell-image { width: 120px; min-width: 120px; max-width: 120px; }
        @media (max-width: 768px) {
            .table-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .table-actions { width: 100%; justify-content: flex-start; }
            .trek-table-image, .trek-table-image-placeholder { width: 100px; height: 70px; }
        }
        .form-columns { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        @media (max-width: 1024px) { .form-columns { grid-template-columns: 1fr; } }
        .duration-badge { background: #17a2b8; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .altitude-badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .highlights-preview { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 8px; }
        .highlight-tag { background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
        .display-order-badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .form-check-group { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .form-check { margin-bottom: 10px; }
        .form-check-input { margin-right: 8px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        <div class="main-content">
            <div class="navbar-top">
                <div class="welcome-text"><i class="fas fa-hiking me-2"></i>Treks Management</div>
                <div>
                    <span class="text-muted me-3"><i class="fas fa-box me-1"></i><?php echo $total_treks; ?> Total Treks</span>
                    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(40,167,69,.1); color:#28a745;"><i class="fas fa-hiking"></i></div><div class="stat-number"><?php echo $total_treks; ?></div><div class="stat-title">Total Treks</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(32,201,151,.1); color:#20c997;"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?php echo $active_treks; ?></div><div class="stat-title">Active Treks</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(255,193,7,.1); color:#ffc107;"><i class="fas fa-star"></i></div><div class="stat-number"><?php echo $featured_treks; ?></div><div class="stat-title">Featured Treks</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(111,66,193,.1); color:#6f42c1;"><i class="fas fa-plus"></i></div><div class="stat-number">New</div><div class="stat-title"><a href="manage-treks.php?action=add" style="color: inherit; text-decoration: none;">Add Trek <i class="fas fa-arrow-right"></i></a></div></div>
                </div>
                
                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_trek' : 'add_trek'; ?>">
                            <?php if ($edit_mode): ?><input type="hidden" name="trek_id" value="<?php echo $current_trek['id']; ?>"><?php endif; ?>
                            
                            <div class="form-columns">
                                <div>
                                    <div class="form-group"><label for="name"><i class="fas fa-heading me-1"></i> Trek Name *</label><input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($current_trek['name']) : ''; ?>" required class="form-control"></div>
                                    <div class="form-group"><label for="description"><i class="fas fa-align-left me-1"></i> Description *</label><textarea id="description" name="description" rows="4" required class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['description']) : ''; ?></textarea></div>
                                    <div class="form-group"><label for="itinerary"><i class="fas fa-route me-1"></i> Detailed Itinerary</label><textarea id="itinerary" name="itinerary" rows="6" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['itinerary']) : ''; ?></textarea></div>
                                    <div class="form-group"><label for="highlights"><i class="fas fa-star me-1"></i> Highlights</label><textarea id="highlights" name="highlights" rows="3" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['highlights']) : ''; ?></textarea></div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group"><label for="inclusions"><i class="fas fa-check-circle me-1"></i> Inclusions</label><textarea id="inclusions" name="inclusions" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['inclusions']) : ''; ?></textarea></div>
                                        <div class="form-group"><label for="exclusions"><i class="fas fa-times-circle me-1"></i> Exclusions</label><textarea id="exclusions" name="exclusions" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['exclusions']) : ''; ?></textarea></div>
                                    </div>
                                    <div class="form-group"><label for="faq"><i class="fas fa-question-circle me-1"></i> FAQs</label><textarea id="faq" name="faq" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['faq']) : ''; ?></textarea></div>
                                </div>
                                
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($edit_mode && $current_trek['featured_image']): ?>
                                            <img id="featured_image_preview" src="../<?php echo htmlspecialchars($current_trek['featured_image']); ?>" alt="Featured Image" style="max-width: 200px; margin-top: 10px;"><br><small>Current image</small>
                                        <?php else: ?>
                                            <img id="featured_image_preview" src="" alt="Preview" class="trek-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gallery_images"><i class="fas fa-images me-1"></i> Gallery Images</label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Select multiple images for gallery</small>
                                        <?php if ($edit_mode && $current_trek['gallery_images']): ?>
                                            <?php $gallery_images = json_decode($current_trek['gallery_images'], true); ?>
                                            <?php if (!empty($gallery_images)): ?>
                                                <div class="gallery-container" id="existing_gallery">
                                                    <?php foreach ($gallery_images as $index => $image): ?>
                                                        <div class="gallery-item" id="gallery-item-<?php echo $index; ?>">
                                                            <img src="../<?php echo htmlspecialchars($image); ?>" alt="Gallery" style="width: 100px; height: 80px; object-fit: cover;">
                                                            <div class="delete-gallery-image" onclick="markImageForDeletion(<?php echo $index; ?>, '<?php echo htmlspecialchars($image); ?>')"><i class="fas fa-times"></i></div>
                                                            <input type="hidden" name="delete_gallery_images[]" id="delete_<?php echo $index; ?>" value="">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="gallery-container" id="new_gallery_preview"></div>
                                    </div>
                                    
                                    <div class="form-group"><label for="price"><i class="fas fa-rupee-sign me-1"></i> Price (₹)</label><input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_mode ? $current_trek['price'] : ''; ?>" class="form-control"></div>
                                    <div class="form-group"><label for="duration"><i class="fas fa-clock me-1"></i> Duration *</label><input type="text" id="duration" name="duration" required value="<?php echo $edit_mode ? htmlspecialchars($current_trek['duration']) : ''; ?>" class="form-control"></div>
                                    
                                    <div class="form-group">
                                        <label for="difficulty_level"><i class="fas fa-mountain me-1"></i> Difficulty Level *</label>
                                        <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                                            <option value="">Select Difficulty</option>
                                            <?php foreach(['Easy','Moderate','Moderate to Difficult','Difficult','Strenuous'] as $d): ?>
                                                <option value="<?php echo $d; ?>" <?php echo ($edit_mode && $current_trek['difficulty_level'] == $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group"><label for="max_altitude"><i class="fas fa-arrow-up me-1"></i> Max Altitude</label><input type="text" id="max_altitude" name="max_altitude" value="<?php echo $edit_mode ? htmlspecialchars($current_trek['max_altitude']) : ''; ?>" class="form-control"></div>
                                    <div class="form-group"><label for="starting_point"><i class="fas fa-map-marker-alt me-1"></i> Starting Point</label><input type="text" id="starting_point" name="starting_point" value="<?php echo $edit_mode ? htmlspecialchars($current_trek['starting_point']) : ''; ?>" class="form-control"></div>
                                    <div class="form-group"><label for="best_season"><i class="fas fa-calendar-alt me-1"></i> Best Season</label><input type="text" id="best_season" name="best_season" value="<?php echo $edit_mode ? htmlspecialchars($current_trek['best_season']) : ''; ?>" class="form-control"></div>
                                    <div class="form-group"><label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label><input type="number" id="display_order" name="display_order" value="<?php echo $edit_mode ? $current_trek['display_order'] : '0'; ?>" class="form-control"></div>
                                    
                                    <div class="form-check-group">
                                        <div class="form-check"><input type="checkbox" id="status" name="status" value="1" class="form-check-input" <?php echo ($edit_mode && $current_trek['status'] == 1) ? 'checked' : ''; ?>><label for="status"><i class="fas fa-toggle-on me-1"></i> Active</label><small class="text-muted d-block">Only active treks are visible on the website</small></div>
                                        <div class="form-check"><input type="checkbox" id="featured" name="featured" value="1" class="form-check-input" <?php echo ($edit_mode && $current_trek['featured'] == 1) ? 'checked' : ''; ?>><label for="featured"><i class="fas fa-star me-1"></i> Featured Trek</label></div>
                                    </div>
                                    
                                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                        <h4 style="margin-bottom: 15px;"><i class="fas fa-search"></i> SEO Settings</h4>
                                        <div class="form-group"><label>Meta Title</label><input type="text" name="meta_title" value="<?php echo $edit_mode ? htmlspecialchars($current_trek['meta_title']) : ''; ?>" class="form-control"></div>
                                        <div class="form-group"><label>Meta Description</label><textarea name="meta_description" rows="2" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['meta_description']) : ''; ?></textarea></div>
                                        <div class="form-group"><label>Meta Keywords</label><textarea name="meta_keywords" rows="2" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_trek['meta_keywords']) : ''; ?></textarea></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i><?php echo $edit_mode ? 'Update Trek' : 'Add Trek'; ?></button>
                                <a href="manage-treks.php" class="btn btn-outline"><i class="fas fa-times me-1"></i>Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <?php if (empty($treks)): ?>
                        <div class="empty-state"><i class="fas fa-hiking"></i><h3>No Treks Found</h3><p>Get started by adding your first trek.</p><a href="manage-treks.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add First Trek</a></div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header"><i class="fas fa-list"></i> All Treks<div class="table-actions"><a href="manage-treks.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add New</a></div></div>
                            <div class="table-body">
                                <table class="table">
                                    <thead><tr><th style="width:150px;">Image</th><th>Trek Details</th><th style="width:120px;">Duration</th><th style="width:120px;">Difficulty</th><th style="width:120px;">Price</th><th style="width:100px;">Status</th><th style="width:80px;">Featured</th><th style="width:150px;">Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($treks as $trek): $hl = !empty($trek['highlights']) ? array_slice(explode("\n", $trek['highlights']), 0, 2) : []; ?>
                                        <tr>
                                            <td class="table-cell-image">
                                                <?php if (!empty($trek['featured_image'])): ?><img src="../<?php echo htmlspecialchars($trek['featured_image']); ?>" alt="" class="trek-table-image"><?php else: ?><div class="trek-table-image-placeholder"><i class="fas fa-image"></i></div><?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($trek['name']); ?></strong><br>
                                                <small class="text-muted">/<?php echo $trek['slug']; ?></small>
                                                <?php if (!empty($trek['starting_point'])): ?><div><small><i class="fas fa-map-marker-alt"></i> Start: <?php echo htmlspecialchars($trek['starting_point']); ?></small></div><?php endif; ?>
                                                <?php if (!empty($trek['max_altitude'])): ?><span class="altitude-badge"><i class="fas fa-arrow-up"></i> <?php echo htmlspecialchars($trek['max_altitude']); ?></span><?php endif; ?>
                                                <?php if (!empty($hl)): ?><div class="highlights-preview"><?php foreach ($hl as $h): if(trim($h)): ?><span class="highlight-tag"><?php echo htmlspecialchars(trim($h)); ?></span><?php endif; endforeach; ?></div><?php endif; ?>
                                            </td>
                                            <td><span class="duration-badge"><?php echo htmlspecialchars($trek['duration']); ?></span></td>
                                            <td><span class="difficulty-badge difficulty-<?php echo str_replace(' ', '-', $trek['difficulty_level']); ?>"><?php echo htmlspecialchars($trek['difficulty_level']); ?></span></td>
                                            <td><span class="price-badge">₹<?php echo number_format($trek['price'], 2); ?></span></td>
                                            <td><span class="status-badge status-<?php echo $trek['status']; ?>"><?php echo $trek['status'] ? 'Active' : 'Inactive'; ?></span></td>
                                            <td><?php if ($trek['featured']): ?><span class="featured-badge"><i class="fas fa-star"></i></span><?php endif; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="manage-treks.php?action=edit&id=<?php echo $trek['id']; ?>" class="btn btn-icon btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="../pages/trek.php?slug=<?php echo $trek['slug']; ?>" target="_blank" class="btn btn-icon btn-info" title="View"><i class="fas fa-eye"></i></a>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this trek?');">
                                                        <input type="hidden" name="action" value="delete_trek"><input type="hidden" name="trek_id" value="<?php echo $trek['id']; ?>">
                                                        <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                                <div class="mt-2"><span class="display-order-badge">Order: <?php echo $trek['display_order']; ?></span></div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/admin-script.js"></script>
    <script>
    document.getElementById('featured_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0]; const preview = document.getElementById('featured_image_preview');
        if (file && preview) { const reader = new FileReader(); reader.onload = ev => { preview.src = ev.target.result; preview.style.display = 'block'; }; reader.readAsDataURL(file); }
    });
    document.getElementById('gallery_images')?.addEventListener('change', function(e) {
        const files = e.target.files; const previewContainer = document.getElementById('new_gallery_preview'); previewContainer.innerHTML = '';
        for (let i = 0; i < files.length; i++) {
            const reader = new FileReader(); reader.onload = ev => { const div = document.createElement('div'); div.className = 'gallery-item'; div.innerHTML = `<img src="${ev.target.result}" class="gallery-image">`; previewContainer.appendChild(div); }; reader.readAsDataURL(files[i]);
        }
    });
    function markImageForDeletion(index, imagePath) {
        const input = document.getElementById('delete_' + index); const container = document.getElementById('gallery-item-' + index);
        if (input && container) { input.value = imagePath; container.style.opacity = '0.5'; container.style.pointerEvents = 'none'; }
    }
    document.querySelector('form')?.addEventListener('submit', function(e) {
        if (!document.getElementById('name')?.value.trim() || !document.getElementById('description')?.value.trim() || !document.getElementById('duration')?.value.trim() || !document.getElementById('difficulty_level')?.value) {
            e.preventDefault(); alert('Please fill in all required fields.'); return false;
        }
    });
    </script>
</body>
</html>
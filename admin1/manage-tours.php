<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize auth and check login
$auth = new Auth();
$auth->requireLogin('login.php');

// Get user info
$user = $auth->getUserInfo();

// If not admin, show restricted access
if (!$auth->isAdmin()) {
    die('<div style="text-align:center; padding:50px;">
        <h1 style="color:#c66b05;">Access Denied</h1>
        <p>You do not have permission to access the admin panel.</p>
        <a href="../index.php" style="color:#8f1402;">Return to Home</a>
    </div>');
}

// Database connection
$conn = $db->getConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_tour':
                // Add new tour
                $title = trim($_POST['title']);
                $slug = createSlug($title);
                $duration_nights = intval($_POST['duration_nights']);
                $duration_days = intval($_POST['duration_days']);
                $starting_price = floatval($_POST['starting_price']);
                $description = trim($_POST['description']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $destination = trim($_POST['destination']);
                $highlights = trim($_POST['highlights']);
                $season = trim($_POST['season']);
                $group_size = trim($_POST['group_size']);
                $special_note = trim($_POST['special_note']);
                $important = trim($_POST['important']);
                $documents_requierment = trim($_POST['documents_requierment']);
                $what_to_pack = trim($_POST['what_to_pack']);
                $difficulty_level = $_POST['difficulty_level'];
                $status = $_POST['status'];
                $position = $_POST['position'];
                
                // Handle featured image upload
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../uploads/tours/';
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
                                $featured_image = 'uploads/tours/' . $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = '../uploads/tours/gallery/';
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
                                        $gallery_images[] = 'uploads/tours/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO tour_packages (title, slug, duration_nights, duration_days, starting_price, description, itinerary, inclusions, exclusions, featured_image, gallery_images, destination, highlights, season, group_size, difficulty_level, position, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                $stmt->bind_param("ssiidsssssssssssss", $title, $slug, $duration_nights, $duration_days, $starting_price, $description, $itinerary, $inclusions, $exclusions, $featured_image, $gallery_json, $destination, $highlights, $season, $group_size, $difficulty_level, $position, $status);                
                if ($stmt->execute()) {
                    $message = 'Tour package added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding tour: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_tour':
                // Update existing tour
                $tour_id = intval($_POST['tour_id']);
                $title = trim($_POST['title']);
                $duration_nights = intval($_POST['duration_nights']);
                $duration_days = intval($_POST['duration_days']);
                $starting_price = floatval($_POST['starting_price']);
                $description = trim($_POST['description']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $destination = trim($_POST['destination']);
                $highlights = trim($_POST['highlights']);
                $season = trim($_POST['season']);
                $group_size = trim($_POST['group_size']);
                 $special_note = trim($_POST['special_note']);
                $important = trim($_POST['important']);
                $documents_requierment = trim($_POST['documents_requierment']);
                $what_to_pack = trim($_POST['what_to_pack']);
                $difficulty_level = $_POST['difficulty_level'];
                $status = $_POST['status'];
                $position = $_POST['position'];
                
                // Get current tour data
                $current_tour = [];
                $result = $conn->query("SELECT featured_image, gallery_images FROM tour_packages WHERE id = $tour_id");
                if ($result) {
                    $current_tour = $result->fetch_assoc();
                }
                
                // Handle featured image update
                $featured_image = $current_tour['featured_image'];
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../uploads/tours/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($featured_image && file_exists('../' . $featured_image)) {
                        unlink('../' . $featured_image);
                    }
                    
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024;
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = 'uploads/tours/' . $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = $current_tour['gallery_images'] ? json_decode($current_tour['gallery_images'], true) : [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = '../uploads/tours/gallery/';
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
                                        $gallery_images[] = 'uploads/tours/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Handle deletion of gallery images
                if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                    foreach ($_POST['delete_gallery_images'] as $image_to_delete) {
                        if (($key = array_search($image_to_delete, $gallery_images)) !== false) {
                            if (file_exists('../' . $image_to_delete)) {
                                unlink('../' . $image_to_delete);
                            }
                            unset($gallery_images[$key]);
                        }
                    }
                    $gallery_images = array_values($gallery_images);
                }
                
              $stmt = $conn->prepare("
                    UPDATE tour_packages 
                    SET 
                        title = ?, 
                        duration_nights = ?, 
                        duration_days = ?, 
                        starting_price = ?, 
                        description = ?, 
                        itinerary = ?, 
                        inclusions = ?, 
                        exclusions = ?, 
                        featured_image = ?, 
                        gallery_images = ?, 
                        destination = ?, 
                        highlights = ?, 
                        season = ?, 
                        group_size = ?, 
                        difficulty_level = ?,
                        special_note = ?,
                        important = ?,
                        documents_requierment = ?,
                        what_to_pack = ?,
                        position = ?,
                        status = ?
                    WHERE id = ?
                ");
                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                $stmt->bind_param(
                    "siidsssssssssssssssssi", 
                    $title,
                    $duration_nights,
                    $duration_days,
                    $starting_price,
                    $description,
                    $itinerary,
                    $inclusions,
                    $exclusions,
                    $featured_image,
                    $gallery_json,
                    $destination,
                    $highlights,
                    $season,
                    $group_size,
                    $difficulty_level,
                    $special_note,
                    $important,
                    $documents_requierment,
                    $what_to_pack,
                    $position,
                    $status,
                    $tour_id
                );
                
                if ($stmt->execute()) {
                    $message = 'Tour package updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating tour: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_tour':
                // Delete tour
                $tour_id = intval($_POST['tour_id']);
                
                // Get tour data to delete images
                $result = $conn->query("SELECT featured_image, gallery_images FROM tour_packages WHERE id = $tour_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete featured image
                    if ($row['featured_image'] && file_exists('../' . $row['featured_image'])) {
                        unlink('../' . $row['featured_image']);
                    }
                    
                    // Delete gallery images
                    if ($row['gallery_images']) {
                        $gallery_images = json_decode($row['gallery_images'], true);
                        foreach ($gallery_images as $image) {
                            if (file_exists('../' . $image)) {
                                unlink('../' . $image);
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM tour_packages WHERE id = ?");
                $stmt->bind_param("i", $tour_id);
                
                if ($stmt->execute()) {
                    $message = 'Tour package deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting tour: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all tours for listing
$tours = [];
$result = $conn->query("SELECT * FROM tour_packages ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tours[] = $row;
    }
}

// Get tour count for stats
$total_tours = 0;
$active_tours = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages");
if ($result) {
    $row = $result->fetch_assoc();
    $total_tours = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $active_tours = $row['count'];
}

// Get other stats for sidebar
$total_destinations = 0;
$total_services = 0;
$total_cars = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_destinations = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_services = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'");
if ($result) { $row = $result->fetch_assoc(); $total_cars = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'approved' AND featured = 1");
if ($result) { $row = $result->fetch_assoc(); $total_testimonials = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM popular_activities WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_activities = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
if ($result) { $row = $result->fetch_assoc(); $total_bookings = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
if ($result) { $row = $result->fetch_assoc(); $total_messages = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
if ($result) { $row = $result->fetch_assoc(); $unread_messages = $row['count']; }

// Check if we're in add/edit mode
$edit_mode = false;
$current_tour = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $tour_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM tour_packages WHERE id = $tour_id");
    if ($result && $result->num_rows > 0) {
        $current_tour = $result->fetch_assoc();
        $edit_mode = true;
    }
}

// Function to create slug
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'tour-' . time();
    }
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tour Packages - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage tours page */
        .tour-image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            border: 2px solid #ddd;
            margin: 10px 0;
            object-fit: cover;
        }
        
        .gallery-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .gallery-image {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #ddd;
        }
        
        .gallery-item {
            position: relative;
        }
        
        .delete-gallery-image {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active { background: #28a745; color: white; }
        .status-draft { background: #6c757d; color: white; }
        .status-inactive { background: #dc3545; color: white; }
        
        .difficulty-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .difficulty-easy { background: #28a745; color: white; }
        .difficulty-moderate { background: #ffc107; color: #000; }
        .difficulty-difficult { background: #dc3545; color: white; }
        
        .price-badge {
            background: #6f42c1;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .tour-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .tour-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .tour-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 90%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(198, 107, 5, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .text-end {
            text-align: right;
        }
        
        .table-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        /* Fixed table layout styles */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 20px;
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary-color), #e67e22);
            color: white;
            padding: 15px 25px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-body {
            padding: 0;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            min-width: 1000px; /* Ensure table has minimum width */
            border-collapse: collapse;
        }
        
        .table thead {
            background: #f8f9fa;
        }
        
        .table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Fixed image size in table */
        .tour-table-image {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #e9ecef;
        }
        
        .tour-table-image-container {
            width: 120px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 5px;
            border: 2px solid #e9ecef;
            overflow: hidden;
        }
        
        .tour-table-image-placeholder {
            width: 120px;
            height: 80px;
            background: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
         .tour-table-image {
            width: 120px !important;
            height: 80px !important;
            object-fit: cover !important;
            border-radius: 5px;
            border: 2px solid #e9ecef;
            background-size: cover;
            background-position: center;
        }
        
        .tour-table-image-container {
            width: 120px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 5px;
            border: 2px solid #e9ecef;
            overflow: hidden;
        }
        
        /* Use background-image for better loading */
        .tour-table-image-bg {
            width: 120px;
            height: 80px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 5px;
            border: 2px solid #e9ecef;
        }
        
        .tour-table-image-placeholder {
            width: 120px;
            height: 80px;
            background: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        /* Force all images in table to be fixed size */
        .table img {
            max-width: 120px !important;
            max-height: 80px !important;
            width: 120px !important;
            height: 80px !important;
            object-fit: cover !important;
        }
        
        /* Prevent large images from breaking layout */
        .table-cell-image {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        /* Lazy loading for images */
        img.lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        img.lazy.loaded {
            opacity: 1;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .table-body {
                padding: 0;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .table-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .tour-table-image {
                width: 100px;
                height: 70px;
            }
            
            .tour-table-image-container,
            .tour-table-image-placeholder {
                width: 100px;
                height: 70px;
            }
        }
        
        /* Form layout improvements */
        .form-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 1024px) {
            .form-columns {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Container -->
    <div class="admin-container">
        <?php include 'admin-sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="navbar-top">
                <div class="welcome-text">
                    <i class="fas fa-map-marked-alt me-2"></i>Tour Packages Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-box me-1"></i>
                        <?php echo $total_tours; ?> Total Tours
                    </span>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_tours; ?></div>
                        <div class="stat-title">Total Tours</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(32, 201, 151, 0.1); color: #20c997;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_tours; ?></div>
                        <div class="stat-title">Active Tours</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_tours - $active_tours; ?></div>
                        <div class="stat-title">Inactive/Draft</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="manage-tours.php?action=add" style="color: inherit; text-decoration: none;">
                                Add Tour <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>
                    <!-- Add/Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_tour' : 'add_tour'; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="tour_id" value="<?php echo $current_tour['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-columns">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="title"><i class="fas fa-heading me-1"></i> Tour Title</label>
                                        <input type="text" id="title" name="title" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_tour['title']) : ''; ?>" 
                                               class="form-control" placeholder="Enter tour package title">
                                    </div>
                                     <div class="form-group">
                                        <label for="position"><i class="fas fa-heading me-1"></i> Menu Position</label>
                                        <input type="number" id="position" name="position" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_tour['position']) : ''; ?>" 
                                               class="form-control" placeholder="Enter tour menu position">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Description</label>
                                        <textarea id="description" name="description" rows="4" class="form-control" 
                                                  placeholder="Enter tour description"><?php echo $edit_mode ? htmlspecialchars($current_tour['description']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="itinerary"><i class="fas fa-route me-1"></i> Detailed Itinerary</label>
                                        <textarea id="itinerary" name="itinerary" rows="6" class="form-control" 
                                                  placeholder="Enter day-by-day itinerary (use bullet points)"><?php echo $edit_mode ? htmlspecialchars($current_tour['itinerary']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="special_note"><i class="fas fa-sticky-note me-1"></i> Special Note</label>
                                        <textarea id="special_note" name="special_note" rows="3" class="form-control" 
                                                  placeholder="Enter special notes for this tour"><?php echo $edit_mode ? htmlspecialchars($current_tour['special_note']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="important"><i class="fas fa-exclamation-circle me-1"></i> Important Notes</label>
                                        <textarea id="important" name="important" rows="3" class="form-control" 
                                                  placeholder="Enter important notes"><?php echo $edit_mode ? htmlspecialchars($current_tour['important']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="documents_requierment"><i class="fas fa-file-alt me-1"></i> Document's Requirements</label>
                                        <textarea id="documents_requierment" name="documents_requierment" rows="3" class="form-control" 
                                                  placeholder="Enter document requirements"><?php echo $edit_mode ? htmlspecialchars($current_tour['documents_requierment']) : ''; ?></textarea>
                                    </div>  
                                    
                                    <div class="form-group">
                                        <label for="what_to_pack"><i class="fas fa-suitcase me-1"></i> What To Pack</label>
                                        <textarea id="what_to_pack" name="what_to_pack" rows="3" class="form-control" 
                                                  placeholder="Enter what to pack list"><?php echo $edit_mode ? htmlspecialchars($current_tour['what_to_pack']) : ''; ?></textarea>
                                    </div>  
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="inclusions"><i class="fas fa-check-circle me-1"></i> Inclusions</label>
                                            <textarea id="inclusions" name="inclusions" rows="4" class="form-control" 
                                                      placeholder="What's included?"><?php echo $edit_mode ? htmlspecialchars($current_tour['inclusions']) : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="exclusions"><i class="fas fa-times-circle me-1"></i> Exclusions</label>
                                            <textarea id="exclusions" name="exclusions" rows="4" class="form-control" 
                                                      placeholder="What's not included?"><?php echo $edit_mode ? htmlspecialchars($current_tour['exclusions']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Meta & Images -->
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($edit_mode && $current_tour['featured_image']): ?>
                                            <img id="featured_image_preview" src="../<?php echo htmlspecialchars($current_tour['featured_image']); ?>" 
                                                 alt="Featured Image" width=500;>
                                            <br>
                                            <small>Current image</small>
                                        <?php else: ?>
                                            <img id="featured_image_preview" src="" alt="Preview" class="tour-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gallery_images"><i class="fas fa-images me-1"></i> Gallery Images</label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Select multiple images for gallery</small>
                                        
                                        <?php if ($edit_mode && $current_tour['gallery_images']): ?>
                                            <?php $gallery_images = json_decode($current_tour['gallery_images'], true); ?>
                                            <?php if (!empty($gallery_images)): ?>
                                                <div class="gallery-container" id="existing_gallery">
                                                    <?php foreach ($gallery_images as $image): ?>
                                                        <div class="gallery-item">
                                                            <img src="../<?php echo htmlspecialchars($image); ?>" alt="Gallery" width=300>
                                                            <div class="delete-gallery-image" onclick="markImageForDeletion('<?php echo $image; ?>')">
                                                                <i class="fas fa-times"></i>
                                                            </div>
                                                            <input type="hidden" name="delete_gallery_images[]" id="delete_<?php echo md5($image); ?>" value="">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="gallery-container" id="new_gallery_preview"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="starting_price"><i class="fas fa-rupee-sign me-1"></i> Starting Price (₹)</label>
                                        <input type="number" id="starting_price" name="starting_price" step="0.01" min="0"
                                               value="<?php echo $edit_mode ? $current_tour['starting_price'] : ''; ?>" class="form-control">
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="duration_days"><i class="fas fa-sun me-1"></i> Days</label>
                                            <input type="number" id="duration_days" name="duration_days" min="1"
                                                   value="<?php echo $edit_mode ? $current_tour['duration_days'] : ''; ?>" class="form-control">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="duration_nights"><i class="fas fa-moon me-1"></i> Nights</label>
                                            <input type="number" id="duration_nights" name="duration_nights" min="0"
                                                   value="<?php echo $edit_mode ? $current_tour['duration_nights'] : ''; ?>" class="form-control">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="destination"><i class="fas fa-map-marker-alt me-1"></i> Destination</label>
                                        <input type="text" id="destination" name="destination" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_tour['destination']) : ''; ?>" 
                                               class="form-control" placeholder="e.g., Darjeeling, Sikkim">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="difficulty_level"><i class="fas fa-mountain me-1"></i> Difficulty Level</label>
                                        <select id="difficulty_level" name="difficulty_level" class="form-control">
                                            <option value="easy" <?php echo ($edit_mode && $current_tour['difficulty_level'] == 'easy') ? 'selected' : ''; ?>>Easy</option>
                                            <option value="moderate" <?php echo ($edit_mode && $current_tour['difficulty_level'] == 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                                            <option value="difficult" <?php echo ($edit_mode && $current_tour['difficulty_level'] == 'difficult') ? 'selected' : ''; ?>>Difficult</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="season"><i class="fas fa-calendar-alt me-1"></i> Best Season</label>
                                        <input type="text" id="season" name="season" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_tour['season']) : ''; ?>" 
                                               class="form-control" placeholder="e.g., March to June, September to November">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="group_size"><i class="fas fa-users me-1"></i> Group Size</label>
                                        <input type="text" id="group_size" name="group_size" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_tour['group_size']) : ''; ?>" 
                                               class="form-control" placeholder="e.g., 2-12 people">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="highlights"><i class="fas fa-star me-1"></i> Key Highlights</label>
                                        <textarea id="highlights" name="highlights" rows="3" class="form-control" 
                                                  placeholder="Key highlights separated by commas"><?php echo $edit_mode ? htmlspecialchars($current_tour['highlights']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="active" <?php echo ($edit_mode && $current_tour['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="draft" <?php echo ($edit_mode && $current_tour['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                            <option value="inactive" <?php echo ($edit_mode && $current_tour['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Tour' : 'Add Tour'; ?>
                                </button>
                                <a href="manage-tours.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Tour List View -->
                    <?php if (empty($tours)): ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marked-alt"></i>
                            <h3>No Tour Packages Found</h3>
                            <p>Get started by adding your first tour package.</p>
                            <a href="manage-tours.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Tour
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Tour Packages
                                <div class="table-actions">
                                    <a href="manage-tours.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                </div>
                            </div>
                            <div class="table-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 150px;">Image</th>
                                            <th>Tour Details</th>
                                            <th style="width: 100px;">Duration</th>
                                            <th style="width: 120px;">Price</th>
                                            <th style="width: 100px;">Difficulty</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 120px;">Created</th>
                                            <th style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tours as $tour): ?>
                                            <tr>
                                                <td>
                                                   
                                                            <img src="../<?php echo htmlspecialchars($tour['featured_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($tour['title']); ?>" 
                                                                 width=200>
                                                      
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($tour['title']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($tour['destination']); ?></small><br>
                                                        <small style="color: #6c757d; font-size: 12px;">
                                                            <?php echo substr(htmlspecialchars($tour['description']), 0, 100); ?>...
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo $tour['duration_days']; ?> Days<br>
                                                    <small><?php echo $tour['duration_nights']; ?> Nights</small>
                                                </td>
                                                <td>
                                                    <span class="price-badge">
                                                        ₹<?php echo number_format($tour['starting_price'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="difficulty-badge difficulty-<?php echo strtolower($tour['difficulty_level']); ?>">
                                                        <?php echo ucfirst($tour['difficulty_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $tour['status']; ?>">
                                                        <?php echo ucfirst($tour['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($tour['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="manage-tours.php?action=edit&id=<?php echo $tour['id']; ?>" 
                                                           class="btn btn-icon btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="../pages/tour-detail.php?slug=<?php echo $tour['slug']; ?>" target="_blank" 
                                                           class="btn btn-icon btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this tour?');">
                                                            <input type="hidden" name="action" value="delete_tour">
                                                            <input type="hidden" name="tour_id" value="<?php echo $tour['id']; ?>">
                                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
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

    <!-- Local JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <script>
    // Image preview for featured image
    document.getElementById('featured_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('featured_image_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Image preview for gallery images
    document.getElementById('gallery_images')?.addEventListener('change', function(e) {
        const files = e.target.files;
        const previewContainer = document.getElementById('new_gallery_preview');
        previewContainer.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'gallery-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Gallery Preview" class="gallery-image">
                `;
                previewContainer.appendChild(div);
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Mark gallery image for deletion
    function markImageForDeletion(imagePath) {
        const inputId = 'delete_' + md5(imagePath);
        const input = document.getElementById(inputId);
        
        if (input) {
            input.value = imagePath;
            const imgContainer = input.closest('.gallery-item');
            if (imgContainer) {
                imgContainer.style.opacity = '0.5';
                imgContainer.style.pointerEvents = 'none';
            }
        }
    }
    
    // Simple MD5 function for generating unique IDs
    function md5(inputString) {
        let hash = 0;
        for (let i = 0; i < inputString.length; i++) {
            const char = inputString.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString();
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Tour Management loaded successfully');
        
        // Make table rows clickable for better UX
        document.querySelectorAll('.table tbody tr').forEach(row => {
            const editLink = row.querySelector('a[title="Edit"]');
            if (editLink) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function(e) {
                    if (!e.target.closest('.action-buttons')) {
                        window.location.href = editLink.href;
                    }
                });
            }
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + N: Add new tour
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                window.location.href = 'manage-tours.php?action=add';
            }
            
            // Escape key: Go back to list
            if (e.key === 'Escape' && window.location.href.includes('action=')) {
                window.location.href = 'manage-tours.php';
            }
        });
    });
    </script>
</body>
</html>
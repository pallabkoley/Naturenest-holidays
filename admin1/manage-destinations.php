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
            case 'add_destination':
                // Add new destination
                $name = trim($_POST['name']);
                $slug = createSlug($_POST['slug'] ?? $name);
                $description = trim($_POST['description']);
                $overview = trim($_POST['overview']);
                $best_time_to_visit = trim($_POST['best_time_to_visit']);
                $attractions = trim($_POST['attractions']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Handle featured image upload
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/destinations/';
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
                                $featured_image = $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = '../assets/uploads/destinations/gallery/';
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
                                        $gallery_images[] = $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                
                $stmt = $conn->prepare("INSERT INTO destinations (name, slug, description, overview, best_time_to_visit, attractions, featured_image, gallery_images, meta_title, meta_description, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("ssssssssssis", $name, $slug, $description, $overview, $best_time_to_visit, $attractions, $featured_image, $gallery_json, $meta_title, $meta_description, $display_order, $status);
                
                if ($stmt->execute()) {
                    $message = 'Destination added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding destination: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_destination':
                // Update existing destination
                $destination_id = intval($_POST['destination_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $overview = trim($_POST['overview']);
                $best_time_to_visit = trim($_POST['best_time_to_visit']);
                $attractions = trim($_POST['attractions']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Get current destination data
                $current_destination = [];
                $result = $conn->query("SELECT featured_image, gallery_images FROM destinations WHERE id = $destination_id");
                if ($result) {
                    $current_destination = $result->fetch_assoc();
                }
                
                // Handle featured image update
                $featured_image = $current_destination['featured_image'];
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/destinations/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($featured_image && file_exists($upload_dir . $featured_image)) {
                        unlink($upload_dir . $featured_image);
                    }
                    
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024;
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = $unique_name;
                            }
                        }
                    }
                }
                
                // Handle gallery images
                $gallery_images = $current_destination['gallery_images'] ? json_decode($current_destination['gallery_images'], true) : [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = '../assets/uploads/destinations/gallery/';
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
                                        $gallery_images[] = $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Handle deletion of gallery images
                if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                    $upload_dir = '../assets/uploads/destinations/gallery/';
                    foreach ($_POST['delete_gallery_images'] as $image_to_delete) {
                        if (($key = array_search($image_to_delete, $gallery_images)) !== false) {
                            if (file_exists($upload_dir . $image_to_delete)) {
                                unlink($upload_dir . $image_to_delete);
                            }
                            unset($gallery_images[$key]);
                        }
                    }
                    $gallery_images = array_values($gallery_images);
                }
                
                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
                
                $stmt = $conn->prepare("UPDATE destinations SET name = ?, description = ?, overview = ?, best_time_to_visit = ?, attractions = ?, featured_image = ?, gallery_images = ?, meta_title = ?, meta_description = ?, display_order = ?, status = ? WHERE id = ?");
                
                $stmt->bind_param("sssssssssisi", $name, $description, $overview, $best_time_to_visit, $attractions, $featured_image, $gallery_json, $meta_title, $meta_description, $display_order, $status, $destination_id);
                
                if ($stmt->execute()) {
                    $message = 'Destination updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating destination: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_destination':
                // Delete destination
                $destination_id = intval($_POST['destination_id']);
                
                // Get destination data to delete images
                $result = $conn->query("SELECT featured_image, gallery_images FROM destinations WHERE id = $destination_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete featured image
                    if ($row['featured_image']) {
                        $upload_dir = '../assets/uploads/destinations/';
                        if (file_exists($upload_dir . $row['featured_image'])) {
                            unlink($upload_dir . $row['featured_image']);
                        }
                    }
                    
                    // Delete gallery images
                    if ($row['gallery_images']) {
                        $gallery_images = json_decode($row['gallery_images'], true);
                        $upload_dir = '../assets/uploads/destinations/gallery/';
                        foreach ($gallery_images as $image) {
                            if (file_exists($upload_dir . $image)) {
                                unlink($upload_dir . $image);
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
                $stmt->bind_param("i", $destination_id);
                
                if ($stmt->execute()) {
                    $message = 'Destination deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting destination: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all destinations for listing
$destinations = [];
$result = $conn->query("SELECT * FROM destinations ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $destinations[] = $row;
    }
}

// Get destination count for stats
$total_destinations = 0;
$active_destinations = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM destinations");
if ($result) {
    $row = $result->fetch_assoc();
    $total_destinations = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $active_destinations = $row['count'];
}

// Get other stats for sidebar
$total_tours = 0;
$total_services = 0;
$total_cars = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_tours = $row['count']; }

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
$current_destination = null;
if (isset($_GET['action']) && ($_GET['action'] === 'edit') && isset($_GET['id'])) {
    $destination_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM destinations WHERE id = $destination_id");
    if ($result && $result->num_rows > 0) {
        $current_destination = $result->fetch_assoc();
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
        return 'destination-' . time();
    }
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Destinations - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage destinations page */
        .destination-image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            border: 2px solid #ddd;
            margin: 10px 0;
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
        .status-inactive { background: #dc3545; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .destination-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .destination-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .destination-image {
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
            width: 100%;
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
        
        .order-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
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
                    <i class="fas fa-mountain me-2"></i>Destinations Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-mountain me-1"></i>
                        <?php echo $total_destinations; ?> Total Destinations
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
                        <div class="stat-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_destinations; ?></div>
                        <div class="stat-title">Total Destinations</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_destinations; ?></div>
                        <div class="stat-title">Active Destinations</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_destinations - $active_destinations; ?></div>
                        <div class="stat-title">Inactive</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="manage-destinations.php?action=add" style="color: inherit; text-decoration: none;">
                                Add Destination <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                    <!-- Add/Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_destination' : 'add_destination'; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="destination_id" value="<?php echo $current_destination['id']; ?>">
                            <?php endif; ?>
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="name"><i class="fas fa-heading me-1"></i> Destination Name *</label>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_destination['name']) : ''; ?>" 
                                               required class="form-control" placeholder="Enter destination name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug"><i class="fas fa-link me-1"></i> URL Slug *</label>
                                        <input type="text" id="slug" name="slug" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_destination['slug']) : ''; ?>" 
                                               required class="form-control" placeholder="destination-name">
                                        <small class="text-muted">Used in URLs: <?php echo SITE_URL; ?>/pages/destination-detail.php?slug=</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Brief Description *</label>
                                        <textarea id="description" name="description" rows="3" required class="form-control" 
                                                  placeholder="Enter brief description for listings"><?php echo $edit_mode ? htmlspecialchars($current_destination['description']) : ''; ?></textarea>
                                        <small class="text-muted">A short description for listings</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="overview"><i class="fas fa-file-alt me-1"></i> Detailed Overview</label>
                                        <textarea id="overview" name="overview" rows="6" class="form-control" 
                                                  placeholder="Enter detailed overview of the destination"><?php echo $edit_mode ? htmlspecialchars($current_destination['overview']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="best_time_to_visit"><i class="fas fa-calendar-alt me-1"></i> Best Time to Visit</label>
                                        <input type="text" id="best_time_to_visit" name="best_time_to_visit" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_destination['best_time_to_visit']) : ''; ?>" 
                                               class="form-control" placeholder="e.g., October to March">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="attractions"><i class="fas fa-camera me-1"></i> Main Attractions</label>
                                        <textarea id="attractions" name="attractions" rows="3" class="form-control" 
                                                  placeholder="List main attractions, one per line"><?php echo $edit_mode ? htmlspecialchars($current_destination['attractions']) : ''; ?></textarea>
                                        <small class="text-muted">List main attractions, one per line</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Meta & Images -->
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($edit_mode && $current_destination['featured_image']): ?>
                                            <img id="featured_image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars($current_destination['featured_image']); ?>" 
                                                 alt="Featured Image" class="destination-image-preview">
                                            <br>
                                            <small>Current image</small>
                                        <?php else: ?>
                                            <img id="featured_image_preview" src="" alt="Preview" class="destination-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gallery_images"><i class="fas fa-images me-1"></i> Gallery Images</label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Select multiple images for gallery</small>
                                        
                                        <?php if ($edit_mode && $current_destination['gallery_images']): ?>
                                            <?php $gallery_images = json_decode($current_destination['gallery_images'], true); ?>
                                            <?php if (!empty($gallery_images)): ?>
                                                <div class="gallery-container" id="existing_gallery">
                                                    <?php foreach ($gallery_images as $image): ?>
                                                        <div class="gallery-item">
                                                            <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/gallery/<?php echo htmlspecialchars($image); ?>" alt="Gallery" class="gallery-image">
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
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label>
                                        <input type="number" id="display_order" name="display_order" min="0" 
                                               value="<?php echo $edit_mode ? $current_destination['display_order'] : '0'; ?>" class="form-control">
                                        <small class="text-muted">Lower numbers appear first</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active" <?php echo ($edit_mode && $current_destination['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($edit_mode && $current_destination['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <!-- SEO Settings -->
                                    <div class="form-group">
                                        <label for="meta_title"><i class="fas fa-search me-1"></i> Meta Title</label>
                                        <input type="text" id="meta_title" name="meta_title" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_destination['meta_title']) : ''; ?>" 
                                               class="form-control" placeholder="Leave empty to use destination name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_description"><i class="fas fa-search-plus me-1"></i> Meta Description</label>
                                        <textarea id="meta_description" name="meta_description" rows="3" class="form-control" 
                                                  placeholder="Enter meta description for SEO"><?php echo $edit_mode ? htmlspecialchars($current_destination['meta_description']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Destination' : 'Add Destination'; ?>
                                </button>
                                <a href="manage-destinations.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && $edit_mode): ?>
                    <!-- Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_destination">
                            <input type="hidden" name="destination_id" value="<?php echo $current_destination['id']; ?>">
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="name"><i class="fas fa-heading me-1"></i> Destination Name *</label>
                                        <input type="text" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($current_destination['name']); ?>" 
                                               required class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug"><i class="fas fa-link me-1"></i> URL Slug *</label>
                                        <input type="text" id="slug" name="slug" 
                                               value="<?php echo htmlspecialchars($current_destination['slug']); ?>" 
                                               required class="form-control">
                                        <small class="text-muted">Used in URLs: <?php echo SITE_URL; ?>/pages/destination-detail.php?slug=</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Brief Description *</label>
                                        <textarea id="description" name="description" rows="3" required class="form-control"><?php echo htmlspecialchars($current_destination['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="overview"><i class="fas fa-file-alt me-1"></i> Detailed Overview</label>
                                        <textarea id="overview" name="overview" rows="6" class="form-control"><?php echo htmlspecialchars($current_destination['overview']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="best_time_to_visit"><i class="fas fa-calendar-alt me-1"></i> Best Time to Visit</label>
                                        <input type="text" id="best_time_to_visit" name="best_time_to_visit" 
                                               value="<?php echo htmlspecialchars($current_destination['best_time_to_visit']); ?>" 
                                               class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="attractions"><i class="fas fa-camera me-1"></i> Main Attractions</label>
                                        <textarea id="attractions" name="attractions" rows="3" class="form-control"><?php echo htmlspecialchars($current_destination['attractions']); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Meta & Images -->
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($current_destination['featured_image']): ?>
                                            <img id="featured_image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars($current_destination['featured_image']); ?>" 
                                                 alt="Featured Image" class="destination-image-preview">
                                            <br>
                                            <small>Current image</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gallery_images"><i class="fas fa-images me-1"></i> Gallery Images</label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Select multiple images for gallery</small>
                                        
                                        <?php if ($current_destination['gallery_images']): ?>
                                            <?php $gallery_images = json_decode($current_destination['gallery_images'], true); ?>
                                            <?php if (!empty($gallery_images)): ?>
                                                <div class="gallery-container" id="existing_gallery">
                                                    <?php foreach ($gallery_images as $image): ?>
                                                        <div class="gallery-item">
                                                            <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/gallery/<?php echo htmlspecialchars($image); ?>" alt="Gallery" class="gallery-image">
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
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label>
                                        <input type="number" id="display_order" name="display_order" min="0" 
                                               value="<?php echo $current_destination['display_order']; ?>" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active" <?php echo ($current_destination['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($current_destination['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_title"><i class="fas fa-search me-1"></i> Meta Title</label>
                                        <input type="text" id="meta_title" name="meta_title" 
                                               value="<?php echo htmlspecialchars($current_destination['meta_title']); ?>" 
                                               class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_description"><i class="fas fa-search-plus me-1"></i> Meta Description</label>
                                        <textarea id="meta_description" name="meta_description" rows="3" class="form-control"><?php echo htmlspecialchars($current_destination['meta_description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Update Destination
                                </button>
                                <a href="manage-destinations.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Destination List View -->
                    <?php if (empty($destinations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-mountain"></i>
                            <h3>No Destinations Found</h3>
                            <p>Get started by adding your first destination.</p>
                            <a href="manage-destinations.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Destination
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Destinations
                                <div class="table-actions">
                                    <a href="manage-destinations.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                </div>
                            </div>
                            <div class="table-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Destination</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($destinations as $destination): ?>
                                            <tr>
                                                <td>
                                                    <form method="POST" style="display: inline;" onchange="this.submit()">
                                                        <input type="hidden" name="action" value="update_order">
                                                        <input type="hidden" name="destination_id" value="<?php echo $destination['id']; ?>">
                                                        <input type="number" name="display_order" value="<?php echo $destination['display_order']; ?>" 
                                                               min="0" >
                                                    </form>
                                                </td>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 15px;">
                                                        <?php if ($destination['featured_image']): ?>
                                                            <img src="<?php echo SITE_URL; ?>/assets/uploads/destinations/<?php echo htmlspecialchars($destination['featured_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($destination['name']); ?>" 
                                                                  width=200>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($destination['name']); ?></strong><br>
                                                            <small class="text-muted">/<?php echo $destination['slug']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo substr(strip_tags($destination['description']), 0, 100); ?>...
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $destination['status']; ?>">
                                                        <?php echo ucfirst($destination['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="manage-destinations.php?action=edit&id=<?php echo $destination['id']; ?>" 
                                                           class="btn btn-icon btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo SITE_URL; ?>/pages/destination-detail.php?slug=<?php echo $destination['slug']; ?>" target="_blank" 
                                                           class="btn btn-icon btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this destination?');">
                                                            <input type="hidden" name="action" value="delete_destination">
                                                            <input type="hidden" name="destination_id" value="<?php echo $destination['id']; ?>">
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
    
    // Form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const name = document.getElementById('name')?.value.trim();
        const slug = document.getElementById('slug')?.value.trim();
        const description = document.getElementById('description')?.value.trim();
        
        if (name && slug && description) {
            if (!slug.match(/^[a-z0-9\-]+$/)) {
                e.preventDefault();
                alert('Slug can only contain lowercase letters, numbers, and hyphens.');
                return false;
            }
        }
        
        return true;
    });
    
    // Generate slug from name
    document.getElementById('name')?.addEventListener('blur', function() {
        const name = this.value.trim();
        const slugInput = document.getElementById('slug');
        
        if (name && slugInput && !slugInput.value) {
            const slug = name.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.value = slug;
        }
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Destination Management loaded successfully');
        
        // Make table rows clickable for better UX
        document.querySelectorAll('.table tbody tr').forEach(row => {
            const editLink = row.querySelector('a[title="Edit"]');
            if (editLink) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function(e) {
                    if (!e.target.closest('.action-buttons') && !e.target.closest('form')) {
                        window.location.href = editLink.href;
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
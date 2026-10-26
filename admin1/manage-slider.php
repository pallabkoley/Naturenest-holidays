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

// Create slider table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `hero_slider` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(500) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(500) NOT NULL,
    `button_text` VARCHAR(100) DEFAULT 'Explore Now',
    `button_link` VARCHAR(500) DEFAULT '#',
    `display_order` INT(11) DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_slider':
                // Add new slider
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $description = trim($_POST['description']);
                $button_text = trim($_POST['button_text']);
                $button_link = trim($_POST['button_link']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/slider/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                                $image = 'uploads/slider/' . $unique_name;
                            }
                        }
                    }
                }
                
                if (empty($image)) {
                    $message = 'Please upload a slider image.';
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("INSERT INTO hero_slider (title, subtitle, description, image, button_text, button_link, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->bind_param("ssssssis", $title, $subtitle, $description, $image, $button_text, $button_link, $display_order, $status);
                    
                    if ($stmt->execute()) {
                        $message = 'Slider added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error adding slider: ' . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
                break;
                
            case 'update_slider':
                // Update existing slider
                $slider_id = intval($_POST['slider_id']);
                $title = trim($_POST['title']);
                $subtitle = trim($_POST['subtitle']);
                $description = trim($_POST['description']);
                $button_text = trim($_POST['button_text']);
                $button_link = trim($_POST['button_link']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Get current slider data
                $current_slider = [];
                $result = $conn->query("SELECT image FROM hero_slider WHERE id = $slider_id");
                if ($result) {
                    $current_slider = $result->fetch_assoc();
                }
                
                // Handle image update
                $image = $current_slider['image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../uploads/slider/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($image && file_exists('../' . $image)) {
                        unlink('../' . $image);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024;
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                                $image = 'uploads/slider/' . $unique_name;
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("UPDATE hero_slider SET title = ?, subtitle = ?, description = ?, image = ?, button_text = ?, button_link = ?, display_order = ?, status = ? WHERE id = ?");
                
                $stmt->bind_param("ssssssisi", $title, $subtitle, $description, $image, $button_text, $button_link, $display_order, $status, $slider_id);
                
                if ($stmt->execute()) {
                    $message = 'Slider updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating slider: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_slider':
                // Delete slider
                $slider_id = intval($_POST['slider_id']);
                
                // Get slider data to delete image
                $result = $conn->query("SELECT image FROM hero_slider WHERE id = $slider_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete image
                    if ($row['image'] && file_exists('../' . $row['image'])) {
                        unlink('../' . $row['image']);
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM hero_slider WHERE id = ?");
                $stmt->bind_param("i", $slider_id);
                
                if ($stmt->execute()) {
                    $message = 'Slider deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting slider: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_status':
                // Update slider status
                $slider_id = intval($_POST['slider_id']);
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE hero_slider SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $slider_id);
                
                if ($stmt->execute()) {
                    $message = 'Status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating status: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all sliders for listing
$sliders = [];
$result = $conn->query("SELECT * FROM hero_slider ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sliders[] = $row;
    }
}

// Get slider count for stats
$total_sliders = 0;
$active_sliders = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM hero_slider");
if ($result) {
    $row = $result->fetch_assoc();
    $total_sliders = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM hero_slider WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $active_sliders = $row['count'];
}

// Get other stats for sidebar (same as manage-tours.php)
$total_tours = 0;
$total_destinations = 0;
$total_services = 0;
$total_cars = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages");
if ($result) { $row = $result->fetch_assoc(); $total_tours = $row['count']; }

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
$current_slider = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $slider_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM hero_slider WHERE id = $slider_id");
    if ($result && $result->num_rows > 0) {
        $current_slider = $result->fetch_assoc();
        $edit_mode = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Slider - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage slider page */
        .slider-image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
            border: 2px solid #ddd;
            margin: 10px 0;
        }
        
        .slider-thumb {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #ddd;
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
        
        .order-badge {
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
        
        .slider-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .slider-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        
        .image-upload-container {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .image-upload-container:hover {
            border-color: #007bff;
            background: #e8f5e9;
        }
        
        .upload-icon {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .recommended-size {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .preview-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .table-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .drag-handle {
            cursor: move;
            color: #6c757d;
            margin-right: 10px;
        }
        
        .sortable-ghost {
            opacity: 0.4;
        }
        
        .sortable-drag {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    <i class="fas fa-sliders-h me-2"></i>Hero Slider Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-image me-1"></i>
                        <?php echo $total_sliders; ?> Slides
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
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_sliders; ?></div>
                        <div class="stat-title">Total Slides</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_sliders; ?></div>
                        <div class="stat-title">Active Slides</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_sliders - $active_sliders; ?></div>
                        <div class="stat-title">Inactive Slides</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="manage-slider.php?action=add" style="color: inherit; text-decoration: none;">
                                Add Slide <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>
                    <!-- Add/Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_slider' : 'add_slider'; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="slider_id" value="<?php echo $current_slider['id']; ?>">
                            <?php endif; ?>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <!-- Left Column - Form Fields -->
                                <div>
                                    <div class="form-group">
                                        <label for="title"><i class="fas fa-heading me-1"></i> Slide Title *</label>
                                        <input type="text" id="title" name="title" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_slider['title']) : ''; ?>" 
                                               required class="form-control" placeholder="Enter slide title">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="subtitle"><i class="fas fa-subscript me-1"></i> Subtitle</label>
                                        <input type="text" id="subtitle" name="subtitle" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_slider['subtitle']) : ''; ?>" 
                                               class="form-control" placeholder="Enter slide subtitle">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Description</label>
                                        <textarea id="description" name="description" rows="4" class="form-control" 
                                                  placeholder="Enter slide description"><?php echo $edit_mode ? htmlspecialchars($current_slider['description']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="button_text"><i class="fas fa-button me-1"></i> Button Text</label>
                                            <input type="text" id="button_text" name="button_text" 
                                                   value="<?php echo $edit_mode ? htmlspecialchars($current_slider['button_text']) : 'Explore Now'; ?>" 
                                                   class="form-control" placeholder="Button text">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="button_link"><i class="fas fa-link me-1"></i> Button Link</label>
                                            <input type="text" id="button_link" name="button_link" 
                                                   value="<?php echo $edit_mode ? htmlspecialchars($current_slider['button_link']) : '#'; ?>" 
                                                   class="form-control" placeholder="Button link">
                                            <small class="text-muted">Use # for same page or enter URL</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order *</label>
                                        <input type="number" id="display_order" name="display_order" 
                                               value="<?php echo $edit_mode ? $current_slider['display_order'] : 0; ?>" 
                                               required class="form-control" min="0">
                                        <small class="text-muted">Lower number appears first</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status *</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active" <?php echo ($edit_mode && $current_slider['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($edit_mode && $current_slider['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Image Upload -->
                                <div>
                                    <div class="form-group">
                                        <label for="image"><i class="fas fa-image me-1"></i> Slide Image *</label>
                                        
                                        <?php if ($edit_mode && $current_slider['image']): ?>
                                            <div class="preview-container">
                                                <div class="preview-title">Current Image:</div>
                                                <img src="../<?php echo htmlspecialchars($current_slider['image']); ?>" 
                                                     alt="Current Image" class="slider-image-preview">
                                                <div class="text-muted" style="margin-top: 10px;">
                                                    Upload new image to replace current one
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="image-upload-container" id="imageUploadContainer">
                                            <div class="upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <div id="uploadText">
                                                Click to upload or drag & drop
                                            </div>
                                            <div class="recommended-size">
                                                Recommended: 1920x600px (JPG, PNG, WebP)
                                            </div>
                                            <input type="file" id="image" name="image" accept="image/*" 
                                                   style="display: none;" <?php echo !$edit_mode ? 'required' : ''; ?>>
                                        </div>
                                        
                                        <div class="preview-container" id="imagePreview" style="display: none;">
                                            <div class="preview-title">Image Preview:</div>
                                            <img id="previewImage" src="" alt="Preview" class="slider-image-preview">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Best Practices:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Use high-quality landscape images</li>
                                                <li>Keep text on slides concise</li>
                                                <li>Ensure images are optimized for web</li>
                                                <li>Use contrasting colors for readability</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Slide' : 'Add Slide'; ?>
                                </button>
                                <a href="manage-slider.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <?php if ($edit_mode): ?>
                                    <a href="../index.php#heroCarousel" target="_blank" class="btn btn-info">
                                        <i class="fas fa-eye me-1"></i> Preview on Homepage
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Slider List View -->
                    <?php if (empty($sliders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-sliders-h"></i>
                            <h3>No Slider Images Found</h3>
                            <p>Get started by adding your first slider image for the homepage.</p>
                            <a href="manage-slider.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Slide
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Slider Images
                                <div class="table-actions">
                                    <a href="manage-slider.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                    <button onclick="saveSortOrder()" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Save Order
                                    </button>
                                </div>
                            </div>
                            <div class="table-body">
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Drag and drop</strong> to reorder slides. Changes will be saved automatically when you click "Save Order".
                                </div>
                                
                                <div id="sortableContainer">
                                    <?php foreach ($sliders as $index => $slider): ?>
                                        <div class="slider-card" data-id="<?php echo $slider['id']; ?>">
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <div class="drag-handle">
                                                    <i class="fas fa-bars"></i>
                                                </div>
                                                
                                                <?php if ($slider['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($slider['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($slider['title']); ?>" 
                                                         class="slider-thumb">
                                                <?php else: ?>
                                                    <div class="slider-thumb" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div style="flex: 1;">
                                                    <h5 style="margin: 0 0 5px 0;">
                                                        <?php echo htmlspecialchars($slider['title']); ?>
                                                    </h5>
                                                    <?php if ($slider['subtitle']): ?>
                                                        <p style="margin: 0 0 5px 0; color: #6c757d;">
                                                            <?php echo htmlspecialchars($slider['subtitle']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <div style="display: flex; gap: 10px; font-size: 0.85rem;">
                                                        <span class="order-badge">
                                                            Order: <?php echo $slider['display_order']; ?>
                                                        </span>
                                                        <span class="status-badge status-<?php echo $slider['status']; ?>">
                                                            <?php echo ucfirst($slider['status']); ?>
                                                        </span>
                                                        <?php if ($slider['button_text']): ?>
                                                            <span class="text-muted">
                                                                <i class="fas fa-link"></i> <?php echo htmlspecialchars($slider['button_text']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="action-buttons">
                                                    <a href="manage-slider.php?action=edit&id=<?php echo $slider['id']; ?>" 
                                                       class="btn btn-icon btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../index.php#heroCarousel" target="_blank" 
                                                       class="btn btn-icon btn-info" title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this slider image?');">
                                                        <input type="hidden" name="action" value="delete_slider">
                                                        <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                                        <button type="submit" class="btn btn-icon btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm" 
                                                                onchange="this.form.submit()" style="width: auto; font-size: 12px;">
                                                            <option value="active" <?php echo $slider['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $slider['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview Section -->
                        <div class="table-container mt-4">
                            <div class="table-header">
                                <i class="fas fa-desktop"></i> Slider Preview
                            </div>
                            <div class="table-body">
                                <div id="sliderPreview" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php 
                                        $active_sliders = array_filter($sliders, function($s) { 
                                            return $s['status'] == 'active'; 
                                        });
                                        $active_sliders = array_slice($active_sliders, 0, 5); // Show max 5 for preview
                                        ?>
                                        <?php if (empty($active_sliders)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-sliders-h fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No active slides to preview</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($active_sliders as $index => $slider): ?>
                                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                    <div style="position: relative; height: 300px; background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../<?php echo htmlspecialchars($slider['image']); ?>'); background-size: cover; background-position: center; border-radius: 8px;">
                                                        <div style="position: absolute; bottom: 20px; left: 20px; right: 20px; color: white;">
                                                            <h4><?php echo htmlspecialchars($slider['title']); ?></h4>
                                                            <?php if ($slider['subtitle']): ?>
                                                                <p><?php echo htmlspecialchars($slider['subtitle']); ?></p>
                                                            <?php endif; ?>
                                                            <?php if ($slider['button_text']): ?>
                                                                <a href="#" class="btn btn-light btn-sm">
                                                                    <?php echo htmlspecialchars($slider['button_text']); ?>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <button class="carousel-control-prev" type="button" data-bs-target="#sliderPreview" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#sliderPreview" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Include SortableJS for drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    
    <!-- Local JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <script>
    // Image upload and preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        const uploadContainer = document.getElementById('imageUploadContainer');
        const previewContainer = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const uploadText = document.getElementById('uploadText');
        
        if (uploadContainer && imageInput) {
            // Click to upload
            uploadContainer.addEventListener('click', function() {
                imageInput.click();
            });
            
            // Drag and drop
            uploadContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#28a745';
                this.style.background = '#e8f5e9';
            });
            
            uploadContainer.addEventListener('dragleave', function() {
                this.style.borderColor = '#ccc';
                this.style.background = '#f8f9fa';
            });
            
            uploadContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ccc';
                this.style.background = '#f8f9fa';
                
                if (e.dataTransfer.files.length) {
                    imageInput.files = e.dataTransfer.files;
                    previewFile(e.dataTransfer.files[0]);
                }
            });
            
            imageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    previewFile(this.files[0]);
                }
            });
        }
        
        function previewFile(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                uploadContainer.style.display = 'none';
            }
            
            reader.readAsDataURL(file);
        }
        
        // Initialize drag & drop sorting
        initializeSortable();
    });
    
    // Sortable initialization
    function initializeSortable() {
        const container = document.getElementById('sortableContainer');
        if (container) {
            new Sortable(container, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.drag-handle',
                onEnd: function() {
                    updateOrderNumbers();
                }
            });
        }
    }
    
    // Update order numbers visually
    function updateOrderNumbers() {
        const items = document.querySelectorAll('#sortableContainer .slider-card');
        items.forEach((item, index) => {
            const orderBadge = item.querySelector('.order-badge');
            if (orderBadge) {
                orderBadge.textContent = 'Order: ' + (index + 1);
            }
        });
    }
    
    // Save sort order to database
    function saveSortOrder() {
        const items = document.querySelectorAll('#sortableContainer .slider-card');
        const orderData = [];
        
        items.forEach((item, index) => {
            const id = item.getAttribute('data-id');
            orderData.push({
                id: id,
                order: index + 1
            });
        });
        
        // Show loading
        const saveBtn = document.querySelector('button[onclick="saveSortOrder()"]');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        saveBtn.disabled = true;
        
        // Send AJAX request
        fetch('api/update-slider-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Order saved successfully!', 'success');
                // Reload page after 1 second
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage('Error saving order: ' + data.message, 'error');
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error saving order. Please try again.', 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }
    
    // Show message
    function showMessage(message, type) {
        // Create message element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
        `;
        
        // Insert after stats grid
        const content = document.querySelector('.dashboard-content');
        const statsGrid = document.querySelector('.stats-grid');
        content.insertBefore(alert, statsGrid.nextSibling);
        
        // Remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    // Form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const title = document.getElementById('title')?.value.trim();
        const image = document.getElementById('image')?.files.length;
        const hasCurrentImage = <?php echo ($edit_mode && $current_slider['image']) ? 'true' : 'false'; ?>;
        
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the slide.');
            return false;
        }
        
        if (!hasCurrentImage && (!image || image === 0)) {
            e.preventDefault();
            alert('Please upload an image for the slide.');
            return false;
        }
        
        return true;
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Slider Management loaded successfully');
        
        // Make slider cards clickable for edit
        document.querySelectorAll('.slider-card').forEach(card => {
            const editLink = card.querySelector('a[title="Edit"]');
            if (editLink) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function(e) {
                    // Only navigate if not clicking on buttons or drag handle
                    if (!e.target.closest('.action-buttons') && !e.target.closest('.drag-handle')) {
                        window.location.href = editLink.href;
                    }
                });
            }
        });
        
        // Initialize carousel preview if exists
        if (document.querySelector('#sliderPreview')) {
            const carousel = new bootstrap.Carousel('#sliderPreview', {
                interval: 3000
            });
        }
    });
    </script>
</body>
</html>
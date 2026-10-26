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
            case 'add_service':
                // Add new service
                $title = trim($_POST['title']);
                $slug = createSlug($_POST['slug'] ?? $title);
                $icon = trim($_POST['icon']);
                $description = trim($_POST['description']);
                $detailed_description = trim($_POST['detailed_description']);
                $features = trim($_POST['features']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Handle featured image upload
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/services/';
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
                
                $stmt = $conn->prepare("INSERT INTO services (title, slug, icon, description, detailed_description, features, featured_image, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sssssssis", $title, $slug, $icon, $description, $detailed_description, $features, $featured_image, $display_order, $status);
                
                if ($stmt->execute()) {
                    $message = 'Service added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding service: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_service':
                // Update existing service
                $service_id = intval($_POST['service_id']);
                $title = trim($_POST['title']);
                $icon = trim($_POST['icon']);
                $description = trim($_POST['description']);
                $detailed_description = trim($_POST['detailed_description']);
                $features = trim($_POST['features']);
                $display_order = intval($_POST['display_order']);
                $status = $_POST['status'];
                
                // Get current service data
                $current_service = [];
                $result = $conn->query("SELECT featured_image FROM services WHERE id = $service_id");
                if ($result) {
                    $current_service = $result->fetch_assoc();
                }
                
                // Handle featured image update
                $featured_image = $current_service['featured_image'] ?? '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/services/';
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
                
                $stmt = $conn->prepare("UPDATE services SET title = ?, icon = ?, description = ?, detailed_description = ?, features = ?, featured_image = ?, display_order = ?, status = ? WHERE id = ?");
                
                $stmt->bind_param("ssssssisi", $title, $icon, $description, $detailed_description, $features, $featured_image, $display_order, $status, $service_id);
                
                if ($stmt->execute()) {
                    $message = 'Service updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating service: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_service':
                // Delete service
                $service_id = intval($_POST['service_id']);
                
                // Get service data to delete images
                $result = $conn->query("SELECT featured_image FROM services WHERE id = $service_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete featured image
                    if ($row['featured_image']) {
                        $upload_dir = '../assets/uploads/services/';
                        if (file_exists($upload_dir . $row['featured_image'])) {
                            unlink($upload_dir . $row['featured_image']);
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
                $stmt->bind_param("i", $service_id);
                
                if ($stmt->execute()) {
                    $message = 'Service deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting service: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_order':
                // Update display order
                $service_id = intval($_POST['service_id']);
                $display_order = intval($_POST['display_order']);
                
                $stmt = $conn->prepare("UPDATE services SET display_order = ? WHERE id = ?");
                $stmt->bind_param("ii", $display_order, $service_id);
                
                if ($stmt->execute()) {
                    $message = 'Display order updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating display order: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all services for listing
$services = [];
$result = $conn->query("SELECT * FROM services ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Get service count for stats
$total_services = 0;
$active_services = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM services");
if ($result) {
    $row = $result->fetch_assoc();
    $total_services = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $active_services = $row['count'];
}

// Get other stats for sidebar
$total_destinations = 0;
$total_tours = 0;
$total_cars = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_destinations = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_tours = $row['count']; }

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
$current_service = null;
if (isset($_GET['action']) && ($_GET['action'] === 'edit') && isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM services WHERE id = $service_id");
    if ($result && $result->num_rows > 0) {
        $current_service = $result->fetch_assoc();
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
        return 'service-' . time();
    }
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage services page */
        .service-image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            border: 2px solid #ddd;
            margin: 10px 0;
        }
        
        .icon-preview {
            font-size: 2rem;
            margin: 10px 0;
            color: var(--primary-color);
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
        
        .service-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .service-image {
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
        
        .icon-picker {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .icon-option {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .icon-option:hover {
            border-color: var(--primary-color);
            background: #fffaf0;
        }
        
        .icon-option.selected {
            border-color: var(--primary-color);
            background: #fffaf0;
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
                    <i class="fas fa-concierge-bell me-2"></i>Services Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-concierge-bell me-1"></i>
                        <?php echo $total_services; ?> Total Services
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
                        <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_services; ?></div>
                        <div class="stat-title">Total Services</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_services; ?></div>
                        <div class="stat-title">Active Services</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_services - $active_services; ?></div>
                        <div class="stat-title">Inactive</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="manage-services.php?action=add" style="color: inherit; text-decoration: none;">
                                Add Service <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                    <!-- Add/Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_service' : 'add_service'; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="service_id" value="<?php echo $current_service['id']; ?>">
                            <?php endif; ?>
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="title"><i class="fas fa-heading me-1"></i> Service Title *</label>
                                        <input type="text" id="title" name="title" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_service['title']) : ''; ?>" 
                                               required class="form-control" placeholder="Enter service title">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug"><i class="fas fa-link me-1"></i> URL Slug *</label>
                                        <input type="text" id="slug" name="slug" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_service['slug']) : ''; ?>" 
                                               required class="form-control" placeholder="service-name">
                                        <small class="text-muted">Used in URLs: <?php echo SITE_URL; ?>/pages/service-detail.php?slug=</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="icon"><i class="fas fa-icons me-1"></i> Font Awesome Icon *</label>
                                        <input type="text" id="icon" name="icon" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_service['icon']) : ''; ?>" 
                                               required class="form-control" placeholder="fas fa-car">
                                        <small class="text-muted">Enter Font Awesome icon class (e.g., fas fa-car, fas fa-hotel)</small>
                                        <?php if ($edit_mode && $current_service['icon']): ?>
                                            <div class="icon-preview">
                                                <i class="<?php echo htmlspecialchars($current_service['icon']); ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Brief Description *</label>
                                        <textarea id="description" name="description" rows="3" required class="form-control" 
                                                  placeholder="Enter brief description for listings"><?php echo $edit_mode ? htmlspecialchars($current_service['description']) : ''; ?></textarea>
                                        <small class="text-muted">A short description for listings</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="detailed_description"><i class="fas fa-file-alt me-1"></i> Detailed Description</label>
                                        <textarea id="detailed_description" name="detailed_description" rows="6" class="form-control" 
                                                  placeholder="Enter detailed description of the service"><?php echo $edit_mode ? htmlspecialchars($current_service['detailed_description']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="features"><i class="fas fa-star me-1"></i> Key Features</label>
                                        <textarea id="features" name="features" rows="4" class="form-control" 
                                                  placeholder="List key features, one per line"><?php echo $edit_mode ? htmlspecialchars($current_service['features']) : ''; ?></textarea>
                                        <small class="text-muted">List key features, one per line</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Meta & Images -->
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($edit_mode && $current_service['featured_image']): ?>
                                            <img id="featured_image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/services/<?php echo htmlspecialchars($current_service['featured_image']); ?>" 
                                                 alt="Featured Image" width=600>
                                            <br>
                                            <small>Current image</small>
                                        <?php else: ?>
                                            <img id="featured_image_preview" src="" alt="Preview" class="service-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label>
                                        <input type="number" id="display_order" name="display_order" min="0" 
                                               value="<?php echo $edit_mode ? $current_service['display_order'] : '0'; ?>" class="form-control">
                                        <small class="text-muted">Lower numbers appear first</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active" <?php echo ($edit_mode && $current_service['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($edit_mode && $current_service['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Popular Icons -->
                                    <div class="form-group">
                                        <label><i class="fas fa-icons me-1"></i> Popular Icons</label>
                                        <div class="icon-picker">
                                            <?php 
                                            $popular_icons = [
                                                'fas fa-car', 'fas fa-taxi', 'fas fa-hotel', 'fas fa-utensils', 
                                                'fas fa-hiking', 'fas fa-mountain', 'fas fa-camera', 'fas fa-compass',
                                                'fas fa-map-marker-alt', 'fas fa-road', 'fas fa-bus', 'fas fa-train',
                                                'fas fa-ship', 'fas fa-plane', 'fas fa-suitcase', 'fas fa-passport',
                                                'fas fa-globe-asia', 'fas fa-tree', 'fas fa-water', 'fas fa-sun',
                                                'fas fa-umbrella-beach', 'fas fa-monument', 'fas fa-church',
                                                'fas fa-concierge-bell', 'fas fa-user-tie', 'fas fa-headset',
                                                'fas fa-wallet', 'fas fa-gift', 'fas fa-medal', 'fas fa-star',
                                                'fas fa-shield-alt', 'fas fa-clock', 'fas fa-calendar-alt',
                                                'fas fa-heart', 'fas fa-gem', 'fas fa-crown', 'fas fa-award'
                                            ];
                                            
                                            foreach ($popular_icons as $icon): 
                                                $is_selected = ($edit_mode && $current_service['icon'] == $icon);
                                            ?>
                                                <div class="icon-option <?php echo $is_selected ? 'selected' : ''; ?>" 
                                                     onclick="selectIcon('<?php echo $icon; ?>')">
                                                    <i class="<?php echo $icon; ?> fa-lg"></i><br>
                                                    <small><?php echo str_replace('fas fa-', '', $icon); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Service' : 'Add Service'; ?>
                                </button>
                                <a href="manage-services.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && $edit_mode): ?>
                    <!-- Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_service">
                            <input type="hidden" name="service_id" value="<?php echo $current_service['id']; ?>">
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="title"><i class="fas fa-heading me-1"></i> Service Title *</label>
                                        <input type="text" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($current_service['title']); ?>" 
                                               required class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug"><i class="fas fa-link me-1"></i> URL Slug *</label>
                                        <input type="text" id="slug" name="slug" 
                                               value="<?php echo htmlspecialchars($current_service['slug']); ?>" 
                                               required class="form-control">
                                        <small class="text-muted">Used in URLs: <?php echo SITE_URL; ?>/pages/service-detail.php?slug=</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="icon"><i class="fas fa-icons me-1"></i> Font Awesome Icon *</label>
                                        <input type="text" id="icon" name="icon" 
                                               value="<?php echo htmlspecialchars($current_service['icon']); ?>" 
                                               required class="form-control">
                                        <small class="text-muted">Enter Font Awesome icon class</small>
                                        <?php if ($current_service['icon']): ?>
                                            <div class="icon-preview">
                                                <i class="<?php echo htmlspecialchars($current_service['icon']); ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Brief Description *</label>
                                        <textarea id="description" name="description" rows="3" required class="form-control"><?php echo htmlspecialchars($current_service['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="detailed_description"><i class="fas fa-file-alt me-1"></i> Detailed Description</label>
                                        <textarea id="detailed_description" name="detailed_description" rows="6" class="form-control"><?php echo htmlspecialchars($current_service['detailed_description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="features"><i class="fas fa-star me-1"></i> Key Features</label>
                                        <textarea id="features" name="features" rows="4" class="form-control"><?php echo htmlspecialchars($current_service['features']); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Meta & Images -->
                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($current_service['featured_image']): ?>
                                            <img id="featured_image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/services/<?php echo htmlspecialchars($current_service['featured_image']); ?>" 
                                                 alt="Featured Image" width=600>
                                            <br>
                                            <small>Current image</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label>
                                        <input type="number" id="display_order" name="display_order" min="0" 
                                               value="<?php echo $current_service['display_order']; ?>" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="active" <?php echo ($current_service['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($current_service['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Popular Icons -->
                                    <div class="form-group">
                                        <label><i class="fas fa-icons me-1"></i> Popular Icons</label>
                                        <div class="icon-picker">
                                            <?php 
                                            $popular_icons = [
                                                'fas fa-car', 'fas fa-taxi', 'fas fa-hotel', 'fas fa-utensils', 
                                                'fas fa-hiking', 'fas fa-mountain', 'fas fa-camera', 'fas fa-compass',
                                                'fas fa-map-marker-alt', 'fas fa-road', 'fas fa-bus', 'fas fa-train',
                                                'fas fa-ship', 'fas fa-plane', 'fas fa-suitcase', 'fas fa-passport',
                                                'fas fa-globe-asia', 'fas fa-tree', 'fas fa-water', 'fas fa-sun',
                                                'fas fa-umbrella-beach', 'fas fa-monument', 'fas fa-church',
                                                'fas fa-concierge-bell', 'fas fa-user-tie', 'fas fa-headset',
                                                'fas fa-wallet', 'fas fa-gift', 'fas fa-medal', 'fas fa-star',
                                                'fas fa-shield-alt', 'fas fa-clock', 'fas fa-calendar-alt',
                                                'fas fa-heart', 'fas fa-gem', 'fas fa-crown', 'fas fa-award'
                                            ];
                                            
                                            foreach ($popular_icons as $icon): 
                                                $is_selected = ($current_service['icon'] == $icon);
                                            ?>
                                                <div class="icon-option <?php echo $is_selected ? 'selected' : ''; ?>" 
                                                     onclick="selectIcon('<?php echo $icon; ?>')">
                                                    <i class="<?php echo $icon; ?> fa-lg"></i><br>
                                                    <small><?php echo str_replace('fas fa-', '', $icon); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Update Service
                                </button>
                                <a href="manage-services.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Service List View -->
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-concierge-bell"></i>
                            <h3>No Services Found</h3>
                            <p>Get started by adding your first service.</p>
                            <a href="manage-services.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Service
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Services
                                <div class="table-actions">
                                    <a href="manage-services.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                </div>
                            </div>
                            <div class="table-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Service</th>
                                            <th>Icon</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td>
                                                    <form method="POST" style="display: inline;" onchange="this.submit()">
                                                        <input type="hidden" name="action" value="update_order">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                        <input type="number" name="display_order" value="<?php echo $service['display_order']; ?>" 
                                                               min="0" class="order-input">
                                                    </form>
                                                </td>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 15px;">
                                                        <?php if ($service['featured_image']): ?>
                                                            <img src="<?php echo SITE_URL; ?>/assets/uploads/services/<?php echo htmlspecialchars($service['featured_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($service['title']); ?>" 
                                                                 width=200>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($service['title']); ?></strong><br>
                                                            <small class="text-muted">/<?php echo $service['slug']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($service['icon']): ?>
                                                        <i class="<?php echo htmlspecialchars($service['icon']); ?> fa-lg"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo substr(strip_tags($service['description']), 0, 100); ?>...
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $service['status']; ?>">
                                                        <?php echo ucfirst($service['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="manage-services.php?action=edit&id=<?php echo $service['id']; ?>" 
                                                           class="btn btn-icon btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo SITE_URL; ?>/pages/service-detail.php?slug=<?php echo $service['slug']; ?>" target="_blank" 
                                                           class="btn btn-icon btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this service?');">
                                                            <input type="hidden" name="action" value="delete_service">
                                                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
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
    
    // Select icon from picker
    function selectIcon(iconClass) {
        document.getElementById('icon').value = iconClass;
        
        // Update icon preview
        const iconPreview = document.querySelector('.icon-preview');
        if (iconPreview) {
            iconPreview.innerHTML = `<i class="${iconClass}"></i>`;
        } else {
            const iconInput = document.getElementById('icon');
            const parent = iconInput.parentElement;
            if (!parent.querySelector('.icon-preview')) {
                const preview = document.createElement('div');
                preview.className = 'icon-preview';
                preview.innerHTML = `<i class="${iconClass}"></i>`;
                iconInput.insertAdjacentElement('afterend', preview);
            }
        }
        
        // Update selected state in picker
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
        });
        event.target.closest('.icon-option').classList.add('selected');
    }
    
    // Generate slug from title
    document.getElementById('title')?.addEventListener('blur', function() {
        const title = this.value.trim();
        const slugInput = document.getElementById('slug');
        
        if (title && slugInput && !slugInput.value) {
            const slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.value = slug;
        }
    });
    
    // Form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const title = document.getElementById('title')?.value.trim();
        const slug = document.getElementById('slug')?.value.trim();
        const icon = document.getElementById('icon')?.value.trim();
        const description = document.getElementById('description')?.value.trim();
        
        if (title && slug && icon && description) {
            if (!slug.match(/^[a-z0-9\-]+$/)) {
                e.preventDefault();
                alert('Slug can only contain lowercase letters, numbers, and hyphens.');
                return false;
            }
        }
        
        return true;
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Service Management loaded successfully');
        
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
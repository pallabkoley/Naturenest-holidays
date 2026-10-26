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
            case 'add_car':
                // Add new car
                $car_name = trim($_POST['car_name']);
                $car_type = trim($_POST['car_type']);
                $model = trim($_POST['model']);
                $capacity = intval($_POST['capacity']);
                $features = trim($_POST['features']);
                $daily_rate = floatval($_POST['daily_rate']);
                $weekly_rate = floatval($_POST['weekly_rate']);
                $status = $_POST['status'];
                
                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/cars/';
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
                                $image = $unique_name;
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO rental_cars (car_name, car_type, model, capacity, features, daily_rate, weekly_rate, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sssisddss", $car_name, $car_type, $model, $capacity, $features, $daily_rate, $weekly_rate, $image, $status);
                
                if ($stmt->execute()) {
                    $message = 'Car added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding car: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_car':
                // Update existing car
                $car_id = intval($_POST['car_id']);
                $car_name = trim($_POST['car_name']);
                $car_type = trim($_POST['car_type']);
                $model = trim($_POST['model']);
                $capacity = intval($_POST['capacity']);
                $features = trim($_POST['features']);
                $daily_rate = floatval($_POST['daily_rate']);
                $weekly_rate = floatval($_POST['weekly_rate']);
                $status = $_POST['status'];
                
                // Get current car data
                $current_car = [];
                $result = $conn->query("SELECT image FROM rental_cars WHERE id = $car_id");
                if ($result) {
                    $current_car = $result->fetch_assoc();
                }
                
                // Handle image update
                $image = $current_car['image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/uploads/cars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($image && file_exists($upload_dir . $image)) {
                        unlink($upload_dir . $image);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $max_size = 5 * 1024 * 1024;
                    
                    if (in_array(strtolower($file_extension), $allowed_types)) {
                        if ($_FILES['image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                                $image = $unique_name;
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("UPDATE rental_cars SET car_name = ?, car_type = ?, model = ?, capacity = ?, features = ?, daily_rate = ?, weekly_rate = ?, image = ?, status = ? WHERE id = ?");
                
                $stmt->bind_param("sssisddssi", $car_name, $car_type, $model, $capacity, $features, $daily_rate, $weekly_rate, $image, $status, $car_id);
                
                if ($stmt->execute()) {
                    $message = 'Car updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating car: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_car':
                // Delete car
                $car_id = intval($_POST['car_id']);
                
                // Get car data to delete images
                $result = $conn->query("SELECT image FROM rental_cars WHERE id = $car_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete image
                    if ($row['image']) {
                        $upload_dir = '../assets/uploads/cars/';
                        if (file_exists($upload_dir . $row['image'])) {
                            unlink($upload_dir . $row['image']);
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM rental_cars WHERE id = ?");
                $stmt->bind_param("i", $car_id);
                
                if ($stmt->execute()) {
                    $message = 'Car deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting car: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all cars for listing
$cars = [];
$result = $conn->query("SELECT * FROM rental_cars ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
}

// Get car count for stats
$total_cars = 0;
$available_cars = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars");
if ($result) {
    $row = $result->fetch_assoc();
    $total_cars = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'");
if ($result) {
    $row = $result->fetch_assoc();
    $available_cars = $row['count'];
}

// Get other stats for sidebar
$total_destinations = 0;
$total_tours = 0;
$total_services = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_destinations = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_tours = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_services = $row['count']; }

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
$current_car = null;
if (isset($_GET['action']) && ($_GET['action'] === 'edit') && isset($_GET['id'])) {
    $car_id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM rental_cars WHERE id = $car_id");
    if ($result && $result->num_rows > 0) {
        $current_car = $result->fetch_assoc();
        $edit_mode = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage cars page */
        .car-image-preview {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            margin: 10px 0;
        }
        
        .car-table-image {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #e9ecef;
        }
        
        .car-table-image-container {
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
        
        .car-table-image-placeholder {
            width: 120px;
            height: 80px;
            background: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-available { background: #28a745; color: white; }
        .status-unavailable { background: #dc3545; color: white; }
        .status-maintenance { background: #ffc107; color: #000; }
        
        .car-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .car-type-sedan { background: #6f42c1; color: white; }
        .car-type-suv { background: #17a2b8; color: white; }
        .car-type-van { background: #20c997; color: white; }
        .car-type-luxury { background: #fd7e14; color: white; }
        .car-type-4x4 { background: #e83e8c; color: white; }
        
        .rate-badge {
            background: #007bff;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .capacity-badge {
            background: #6c757d;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .car-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .car-card:hover {
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
        
        /* Fixed table layout */
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
            min-width: 1000px;
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
        
        /* Force image sizing */
        .table-cell-image {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        
        .table img {
            max-width: 120px !important;
            max-height: 80px !important;
            width: 120px !important;
            height: 80px !important;
            object-fit: cover !important;
        }
        
        /* Form layout */
        .form-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .form-columns {
                grid-template-columns: 1fr;
            }
            
            .table-body {
                padding: 0;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .car-image-preview {
                width: 200px;
                height: 150px;
            }
        }
        
        /* Features chips */
        .features-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 10px 0;
        }
        
        .feature-chip {
            background: #e9ecef;
            color: #495057;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
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
                    <i class="fas fa-car me-2"></i>Car Rental Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-car me-1"></i>
                        <?php echo $total_cars; ?> Total Cars
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
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_cars; ?></div>
                        <div class="stat-title">Total Cars</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $available_cars; ?></div>
                        <div class="stat-title">Available</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_cars - $available_cars; ?></div>
                        <div class="stat-title">Not Available</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(32, 201, 151, 0.1); color: #20c997;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="manage-cars.php?action=add" style="color: inherit; text-decoration: none;">
                                Add Car <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>
                    <!-- Add/Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_car' : 'add_car'; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="car_id" value="<?php echo $current_car['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-columns">
                                <!-- Left Column - Basic Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="car_name"><i class="fas fa-car me-1"></i> Car Name *</label>
                                        <input type="text" id="car_name" name="car_name" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_car['car_name']) : ''; ?>" 
                                               required class="form-control" placeholder="e.g., Toyota Corolla, Ford Explorer">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="car_type"><i class="fas fa-tags me-1"></i> Car Type *</label>
                                        <select id="car_type" name="car_type" required class="form-control">
                                            <option value="">Select Car Type</option>
                                            <option value="sedan" <?php echo ($edit_mode && $current_car['car_type'] == 'sedan') ? 'selected' : ''; ?>>Sedan</option>
                                            <option value="suv" <?php echo ($edit_mode && $current_car['car_type'] == 'suv') ? 'selected' : ''; ?>>SUV</option>
                                            <option value="van" <?php echo ($edit_mode && $current_car['car_type'] == 'van') ? 'selected' : ''; ?>>Van/Minivan</option>
                                            <option value="luxury" <?php echo ($edit_mode && $current_car['car_type'] == 'luxury') ? 'selected' : ''; ?>>Luxury Car</option>
                                            <option value="4x4" <?php echo ($edit_mode && $current_car['car_type'] == '4x4') ? 'selected' : ''; ?>>4x4/Jeep</option>
                                            <option value="hatchback" <?php echo ($edit_mode && $current_car['car_type'] == 'hatchback') ? 'selected' : ''; ?>>Hatchback</option>
                                            <option value="coupe" <?php echo ($edit_mode && $current_car['car_type'] == 'coupe') ? 'selected' : ''; ?>>Coupe</option>
                                            <option value="convertible" <?php echo ($edit_mode && $current_car['car_type'] == 'convertible') ? 'selected' : ''; ?>>Convertible</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="model"><i class="fas fa-cogs me-1"></i> Model *</label>
                                        <input type="text" id="model" name="model" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_car['model']) : ''; ?>" 
                                               required class="form-control" placeholder="e.g., 2023, Hybrid, Diesel">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="capacity"><i class="fas fa-users me-1"></i> Capacity *</label>
                                        <input type="number" id="capacity" name="capacity" min="1" max="20" 
                                               value="<?php echo $edit_mode ? $current_car['capacity'] : ''; ?>" 
                                               required class="form-control" placeholder="Number of passengers">
                                        <small class="text-muted">Number of passengers including driver</small>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="daily_rate"><i class="fas fa-rupee-sign me-1"></i> Daily Rate (₹) *</label>
                                            <input type="number" id="daily_rate" name="daily_rate" step="0.01" min="0" required
                                                   value="<?php echo $edit_mode ? $current_car['daily_rate'] : ''; ?>" class="form-control">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="weekly_rate"><i class="fas fa-rupee-sign me-1"></i> Weekly Rate (₹)</label>
                                            <input type="number" id="weekly_rate" name="weekly_rate" step="0.01" min="0"
                                                   value="<?php echo $edit_mode ? $current_car['weekly_rate'] : ''; ?>" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Image & Features -->
                                <div>
                                    <div class="form-group">
                                        <label for="image"><i class="fas fa-image me-1"></i> Car Image *</label>
                                        <input type="file" id="image" name="image" accept="image/*" class="form-control">
                                        <?php if ($edit_mode && $current_car['image']): ?>
                                            <img id="image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/cars/<?php echo htmlspecialchars($current_car['image']); ?>" 
                                                 alt="Car Image" class="car-image-preview">
                                            <br>
                                            <small>Current image</small>
                                        <?php else: ?>
                                            <img id="image_preview" src="" alt="Preview" class="car-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="features"><i class="fas fa-star me-1"></i> Features</label>
                                        <textarea id="features" name="features" rows="8" class="form-control" 
                                                  placeholder="Enter features separated by commas or new lines"><?php echo $edit_mode ? htmlspecialchars($current_car['features']) : ''; ?></textarea>
                                        <small class="text-muted">Separate features with commas or new lines</small>
                                        
                                        <div class="features-suggestions mt-2">
                                            <small><strong>Common Features:</strong></small>
                                            <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
                                                <span class="badge badge-light" onclick="addFeature('AC')">AC</span>
                                                <span class="badge badge-light" onclick="addFeature('Music System')">Music System</span>
                                                <span class="badge badge-light" onclick="addFeature('GPS Navigation')">GPS</span>
                                                <span class="badge badge-light" onclick="addFeature('Bluetooth')">Bluetooth</span>
                                                <span class="badge badge-light" onclick="addFeature('Power Windows')">Power Windows</span>
                                                <span class="badge badge-light" onclick="addFeature('Airbags')">Airbags</span>
                                                <span class="badge badge-light" onclick="addFeature('ABS')">ABS</span>
                                                <span class="badge badge-light" onclick="addFeature('Reverse Camera')">Reverse Camera</span>
                                                <span class="badge badge-light" onclick="addFeature('Sunroof')">Sunroof</span>
                                                <span class="badge badge-light" onclick="addFeature('Leather Seats')">Leather Seats</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status *</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="available" <?php echo ($edit_mode && $current_car['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                            <option value="unavailable" <?php echo ($edit_mode && $current_car['status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                            <option value="maintenance" <?php echo ($edit_mode && $current_car['status'] == 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Car' : 'Add Car'; ?>
                                </button>
                                <a href="manage-cars.php" class="btn btn-outline">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Car List View -->
                    <?php if (empty($cars)): ?>
                        <div class="empty-state">
                            <i class="fas fa-car"></i>
                            <h3>No Cars Found</h3>
                            <p>Get started by adding your first car for rental.</p>
                            <a href="manage-cars.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Car
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Rental Cars
                                <div class="table-actions">
                                    <a href="manage-cars.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                </div>
                            </div>
                            <div class="table-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 120px;" class="table-cell-image">Image</th>
                                            <th>Car Details</th>
                                            <th style="width: 100px;">Type</th>
                                            <th style="width: 80px;">Capacity</th>
                                            <th style="width: 120px;">Rates</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cars as $car): 
                                            // Parse features for display
                                            $features = !empty($car['features']) ? explode(',', $car['features']) : [];
                                            $features = array_map('trim', $features);
                                            $features = array_slice($features, 0, 3); // Show only first 3
                                        ?>
                                            <tr>
                                                <td class="table-cell-image">
                                                    <?php if ($car['image']): ?>
                                                        <div class="car-table-image-container">
                                                            <img src="<?php echo SITE_URL; ?>/assets/uploads/cars/<?php echo htmlspecialchars($car['image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($car['car_name']); ?>" 
                                                                 class="car-table-image">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="car-table-image-placeholder">
                                                            <i class="fas fa-car fa-lg"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($car['car_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($car['model']); ?></small>
                                                        <?php if (!empty($features)): ?>
                                                            <div class="features-chips">
                                                                <?php foreach ($features as $feature): ?>
                                                                    <?php if (!empty(trim($feature))): ?>
                                                                        <span class="feature-chip"><?php echo htmlspecialchars($feature); ?></span>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                                <?php if (count($features) > 3): ?>
                                                                    <span class="feature-chip">+<?php echo (count(explode(',', $car['features'])) - 3); ?> more</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $car_type_classes = [
                                                        'sedan' => 'car-type-sedan',
                                                        'suv' => 'car-type-suv',
                                                        'van' => 'car-type-van',
                                                        'luxury' => 'car-type-luxury',
                                                        '4x4' => 'car-type-4x4',
                                                        'hatchback' => 'car-type-hatchback',
                                                        'coupe' => 'car-type-coupe',
                                                        'convertible' => 'car-type-convertible'
                                                    ];
                                                    $type_class = $car_type_classes[$car['car_type']] ?? 'car-type-sedan';
                                                    ?>
                                                    <span class="car-type-badge <?php echo $type_class; ?>">
                                                        <?php echo ucfirst($car['car_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="capacity-badge">
                                                        <i class="fas fa-users me-1"></i><?php echo $car['capacity']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="rate-badge">
                                                            ₹<?php echo number_format($car['daily_rate'], 2); ?>/day
                                                        </span>
                                                        <?php if ($car['weekly_rate'] > 0): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                ₹<?php echo number_format($car['weekly_rate'], 2); ?>/week
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $car['status']; ?>">
                                                        <?php echo ucfirst($car['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="manage-cars.php?action=edit&id=<?php echo $car['id']; ?>" 
                                                           class="btn btn-icon btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this car?');">
                                                            <input type="hidden" name="action" value="delete_car">
                                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
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
    // Image preview
    document.getElementById('image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('image_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Add feature to textarea
    function addFeature(feature) {
        const textarea = document.getElementById('features');
        const currentValue = textarea.value.trim();
        
        if (currentValue) {
            // Check if feature already exists
            const features = currentValue.split(/[,，\n]+/).map(f => f.trim());
            if (!features.includes(feature)) {
                textarea.value = currentValue + ', ' + feature;
            }
        } else {
            textarea.value = feature;
        }
    }
    
    // Form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const carName = document.getElementById('car_name')?.value.trim();
        const carType = document.getElementById('car_type')?.value;
        const model = document.getElementById('model')?.value.trim();
        const capacity = document.getElementById('capacity')?.value;
        const dailyRate = document.getElementById('daily_rate')?.value;
        const weeklyRate = document.getElementById('weekly_rate')?.value;
        
        if (carName && carType && model && capacity && dailyRate) {
            if (parseInt(capacity) < 1) {
                e.preventDefault();
                alert('Capacity must be at least 1.');
                return false;
            }
            
            if (parseFloat(dailyRate) <= 0) {
                e.preventDefault();
                alert('Daily rate must be greater than 0.');
                return false;
            }
            
            if (weeklyRate && parseFloat(weeklyRate) > 0 && parseFloat(weeklyRate) < parseFloat(dailyRate)) {
                e.preventDefault();
                alert('Weekly rate should be less than 7 times daily rate.');
                return false;
            }
        }
        
        return true;
    });
    
    // Auto-calculate weekly rate from daily rate
    document.getElementById('daily_rate')?.addEventListener('blur', function() {
        const dailyRate = parseFloat(this.value);
        const weeklyRateInput = document.getElementById('weekly_rate');
        
        if (dailyRate > 0 && weeklyRateInput && (!weeklyRateInput.value || weeklyRateInput.value == 0)) {
            // Suggest a discounted weekly rate (e.g., 5.5 days for weekly)
            const suggestedWeeklyRate = (dailyRate * 5.5).toFixed(2);
            if (!confirm(`Auto-calculate weekly rate as ₹${suggestedWeeklyRate} (5.5 days)?`)) {
                return;
            }
            weeklyRateInput.value = suggestedWeeklyRate;
        }
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Car Management loaded successfully');
        
        // Make table rows clickable for better UX
        document.querySelectorAll('.table tbody tr').forEach(row => {
            const editLink = row.querySelector('a[title="Edit"]');
            if (editLink) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function(e) {
                    if (!e.target.closest('.action-buttons') && 
                        !e.target.closest('form') && 
                        !e.target.closest('button')) {
                        window.location.href = editLink.href;
                    }
                });
            }
        });
        
        // Force image resizing in table
        function forceImageResize() {
            document.querySelectorAll('.table img').forEach(img => {
                img.style.maxWidth = '120px !important';
                img.style.maxHeight = '80px !important';
                img.style.width = '120px !important';
                img.style.height = '80px !important';
                img.style.objectFit = 'cover !important';
            });
        }
        
        forceImageResize();
        setTimeout(forceImageResize, 1000);
        
        // Monitor for dynamically loaded images
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    forceImageResize();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N: Add new car
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'manage-cars.php?action=add';
        }
        
        // Escape key: Go back to list
        if (e.key === 'Escape' && window.location.href.includes('action=')) {
            window.location.href = 'manage-cars.php';
        }
    });
    </script>
</body>
</html>
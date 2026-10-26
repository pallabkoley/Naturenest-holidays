<?php
// Content Security Policy Header - MUST BE FIRST LINE
header("Content-Security-Policy: script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net");

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

// Get trek ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-treks.php');
    exit();
}

$trek_id = intval($_GET['id']);

// Set current page for sidebar
$current_page = 'edit-trek.php';

// Database connection
$conn = $db->getConnection();
$message = '';
$message_type = '';

// Handle messages from redirect
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Get trek data
$trek = null;
$result = $conn->query("SELECT * FROM treks WHERE id = $trek_id");
if ($result && $result->num_rows > 0) {
    $trek = $result->fetch_assoc();
} else {
    header('Location: manage-treks.php');
    exit();
}

// Decode trek data for pre-filling
$gallery_images = $trek['gallery_images'] ? json_decode($trek['gallery_images'], true) : [];
$faq = $trek['faq'] ? json_decode($trek['faq'], true) : [];

// Decode itinerary data
$itinerary_items = [];
$itinerary_json = $trek['itinerary'];
if (!empty($itinerary_json)) {
    $decoded_itinerary = json_decode($itinerary_json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_itinerary)) {
        $itinerary_items = $decoded_itinerary;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_trek') {
    // Get form data
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
    
    // Handle itinerary data
    $itinerary_data = [];
    if (isset($_POST['itinerary_day']) && isset($_POST['itinerary_title']) && isset($_POST['itinerary_content'])) {
        for ($i = 0; $i < count($_POST['itinerary_day']); $i++) {
            if (!empty(trim($_POST['itinerary_title'][$i]))) {
                $itinerary_data[] = [
                    'day' => trim($_POST['itinerary_day'][$i]),
                    'title' => trim($_POST['itinerary_title'][$i]),
                    'content' => trim($_POST['itinerary_content'][$i])
                ];
            }
        }
    }
    $itinerary = !empty($itinerary_data) ? json_encode($itinerary_data) : '';
    
    // Inclusions & Exclusions
    $inclusions = trim($_POST['inclusions']);
    $exclusions = trim($_POST['exclusions']);
    
    // Handle FAQs
    $faq_data = [];
    if (isset($_POST['faq_question']) && isset($_POST['faq_answer'])) {
        for ($i = 0; $i < count($_POST['faq_question']); $i++) {
            if (!empty(trim($_POST['faq_question'][$i]))) {
                $faq_data[] = [
                    'question' => trim($_POST['faq_question'][$i]),
                    'answer' => trim($_POST['faq_answer'][$i])
                ];
            }
        }
    }
    $faq_json = !empty($faq_data) ? json_encode($faq_data) : null;
    
    // SEO fields
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $meta_keywords = trim($_POST['meta_keywords']);
    
    // Status and display order
    $status = isset($_POST['status']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);
    
    // Get current trek data
    $current_trek = $trek;
    $current_gallery_images = $current_trek['gallery_images'] ? json_decode($current_trek['gallery_images'], true) : [];
    
    // Handle featured image update
    $featured_image = $current_trek['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $upload_dir = '../uploads/treks/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old image if exists and not empty
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
                    $featured_image = 'uploads/treks/' . $unique_name;
                }
            }
        }
    }
    
    // Handle gallery images - start with existing ones
    $gallery_images = $current_gallery_images;
    
    // Add new gallery images
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $upload_dir = '../uploads/treks/gallery/';
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
                            $gallery_images[] = 'uploads/treks/gallery/' . $unique_name;
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
                    if (file_exists('../' . $image_to_delete)) {
                        unlink('../' . $image_to_delete);
                    }
                    unset($gallery_images[$key]);
                }
            }
        }
        $gallery_images = array_values($gallery_images); // Re-index array
    }
    
    // Prepare update query
    $stmt = $conn->prepare("UPDATE treks SET
        name = ?, slug = ?, description = ?, featured_image = ?, gallery_images = ?, duration = ?,
        difficulty_level = ?, max_altitude = ?, best_season = ?, starting_point = ?, price = ?,
        highlights = ?, itinerary = ?, inclusions = ?, exclusions = ?, faq = ?,
        meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?, featured = ?,
        display_order = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;
    
    $stmt->bind_param(
        "ssssssssssdssssssssiiii",
        $name, $slug, $description, $featured_image, $gallery_json, $duration,
        $difficulty_level, $max_altitude, $best_season, $starting_point, $price,
        $highlights, $itinerary, $inclusions, $exclusions, $faq_json,
        $meta_title, $meta_description, $meta_keywords, $status, $featured,
        $display_order, $trek_id
    );
    
    if ($stmt->execute()) {
        $message = 'Trek updated successfully!';
        $message_type = 'success';
        
        // Refresh trek data
        $result = $conn->query("SELECT * FROM treks WHERE id = $trek_id");
        if ($result && $result->num_rows > 0) {
            $trek = $result->fetch_assoc();
            // Re-decode data for display
            $gallery_images = $trek['gallery_images'] ? json_decode($trek['gallery_images'], true) : [];
            $faq = $trek['faq'] ? json_decode($trek['faq'], true) : [];
            $itinerary_items = [];
            $itinerary_json = $trek['itinerary'];
            if (!empty($itinerary_json)) {
                $decoded_itinerary = json_decode($itinerary_json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_itinerary)) {
                    $itinerary_items = $decoded_itinerary;
                }
            }
        }
    } else {
        $message = 'Error updating trek: ' . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'trek-' . time();
    }
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trek - Newa Yatra Admin</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
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
                    <i class="fas fa-edit me-2"></i>Edit Trek
                </div>
                <div>
                    <a href="manage-treks.php" class="btn btn-outline-header">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
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
                <!-- Form Container -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data" id="trekForm" novalidate>
                        <input type="hidden" name="action" value="update_trek">
                        <input type="hidden" name="trek_id" value="<?php echo $trek_id; ?>">
                        
                        <!-- Two Column Layout -->
                        <div class="form-columns">
                            <!-- Left Column - Main Details -->
                            <div>
                                <!-- Basic Information Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-info-circle"></i>
                                        Basic Information
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="name" class="form-label required">
                                            <i class="fas fa-heading"></i>Trek Name
                                        </label>
                                        <input type="text" id="name" name="name" required class="form-control" 
                                               value="<?php echo htmlspecialchars($trek['name']); ?>"
                                               placeholder="e.g., Everest Base Camp Trek">
                                    </div>
                                    
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label for="duration" class="form-label required">
                                                <i class="fas fa-clock"></i>Duration
                                            </label>
                                            <input type="text" id="duration" name="duration" required class="form-control" 
                                                   value="<?php echo htmlspecialchars($trek['duration']); ?>"
                                                   placeholder="e.g., 14 Days">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="difficulty_level" class="form-label required">
                                                <i class="fas fa-mountain"></i>Difficulty Level
                                            </label>
                                            <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                                                <option value="">Select Difficulty</option>
                                                <option value="Easy" <?php echo $trek['difficulty_level'] == 'Easy' ? 'selected' : ''; ?>>Easy</option>
                                                <option value="Moderate" <?php echo $trek['difficulty_level'] == 'Moderate' ? 'selected' : ''; ?>>Moderate</option>
                                                <option value="Moderate to Difficult" <?php echo $trek['difficulty_level'] == 'Moderate to Difficult' ? 'selected' : ''; ?>>Moderate to Difficult</option>
                                                <option value="Difficult" <?php echo $trek['difficulty_level'] == 'Difficult' ? 'selected' : ''; ?>>Difficult</option>
                                                <option value="Strenuous" <?php echo $trek['difficulty_level'] == 'Strenuous' ? 'selected' : ''; ?>>Strenuous</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label for="price" class="form-label required">
                                                <i class="fas fa-rupee-sign"></i>Price (₹)
                                            </label>
                                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                                   class="form-control" value="<?php echo $trek['price']; ?>"
                                                   placeholder="e.g., 45000.00">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="max_altitude" class="form-label">
                                                <i class="fas fa-arrow-up"></i>Max Altitude
                                            </label>
                                            <input type="text" id="max_altitude" name="max_altitude" class="form-control" 
                                                   value="<?php echo htmlspecialchars($trek['max_altitude']); ?>"
                                                   placeholder="e.g., 5,364m">
                                        </div>
                                    </div>
                                    
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label for="starting_point" class="form-label">
                                                <i class="fas fa-map-marker-alt"></i>Starting Point
                                            </label>
                                            <input type="text" id="starting_point" name="starting_point" class="form-control" 
                                                   value="<?php echo htmlspecialchars($trek['starting_point']); ?>"
                                                   placeholder="e.g., Lukla">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="best_season" class="form-label">
                                                <i class="fas fa-calendar-alt"></i>Best Season
                                            </label>
                                            <input type="text" id="best_season" name="best_season" class="form-control" 
                                                   value="<?php echo htmlspecialchars($trek['best_season']); ?>"
                                                   placeholder="e.g., March to May, September to November">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-file-alt"></i>
                                        Description & Highlights
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="description" class="form-label required">
                                            <i class="fas fa-align-left"></i>Description
                                        </label>
                                        <textarea id="description" name="description" class="form-control summernote" rows="6" 
                                                  placeholder="Write detailed trek description..."><?php echo htmlspecialchars($trek['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="highlights" class="form-label">
                                            <i class="fas fa-star"></i>Highlights
                                        </label>
                                        <textarea id="highlights" name="highlights" class="form-control summernote" rows="4" 
                                                  placeholder="Key highlights of the trek..."><?php echo htmlspecialchars($trek['highlights']); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Itinerary Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-route"></i>
                                        Detailed Itinerary
                                    </h3>
                                    
                                    <div id="itinerary_container">
                                        <div class="itinerary-section" id="itinerary_template" style="display: none;">
                                            <div class="itinerary-header">
                                                <h4>Day <span class="day-number">1</span></h4>
                                                <button type="button" class="btn-remove-itinerary">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-group">
                                                <label>Day Number</label>
                                                <input type="number" class="form-control itinerary-day" name="itinerary_day[]" min="1" value="1" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Day Title</label>
                                                <input type="text" class="form-control itinerary-title" name="itinerary_title[]" placeholder="e.g., Arrival in Kathmandu" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Day Content</label>
                                                <textarea class="form-control itinerary-content" name="itinerary_content[]" rows="4" placeholder="Detailed description of the day's activities..." required></textarea>
                                            </div>
                                        </div>
                                        
                                        <div id="added_itinerary">
                                            <!-- Existing itinerary items -->
                                            <?php if (!empty($itinerary_items)): ?>
                                                <?php foreach ($itinerary_items as $index => $item): ?>
                                                    <div class="itinerary-section" id="itinerary_<?php echo $index + 1; ?>">
                                                        <div class="itinerary-header">
                                                            <h4>Day <span class="day-number"><?php echo $index + 1; ?></span></h4>
                                                            <button type="button" class="btn-remove-itinerary">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Day Number</label>
                                                            <input type="number" class="form-control itinerary-day" name="itinerary_day[]" min="1" 
                                                                   value="<?php echo isset($item['day']) ? $item['day'] : ($index + 1); ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Day Title</label>
                                                            <input type="text" class="form-control itinerary-title" name="itinerary_title[]" 
                                                                   value="<?php echo htmlspecialchars($item['title']); ?>" placeholder="e.g., Arrival in Kathmandu" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Day Content</label>
                                                            <textarea class="form-control itinerary-content" name="itinerary_content[]" rows="4" 
                                                                      placeholder="Detailed description of the day's activities..." required><?php echo htmlspecialchars($item['content']); ?></textarea>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div id="empty_itinerary_message" class="empty-message" <?php echo !empty($itinerary_items) ? 'style="display:none;"' : ''; ?>>
                                            <p>No itinerary added yet. Click the button below to add the first day.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="button" id="add_itinerary_btn" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> Add Day
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Inclusions & Exclusions -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-list-check"></i>
                                        Inclusions & Exclusions
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="inclusions" class="form-label">
                                            <i class="fas fa-check-circle"></i>Inclusions
                                        </label>
                                        <textarea id="inclusions" name="inclusions" class="form-control summernote" rows="6" 
                                                  placeholder="What's included in this trek? (Use bullet points)"><?php echo htmlspecialchars($trek['inclusions']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="exclusions" class="form-label">
                                            <i class="fas fa-times-circle"></i>Exclusions
                                        </label>
                                        <textarea id="exclusions" name="exclusions" class="form-control summernote" rows="6" 
                                                  placeholder="What's not included? (Use bullet points)"><?php echo htmlspecialchars($trek['exclusions']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Meta & Images -->
                            <div>
                                <!-- Images Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-images"></i>
                                        Images & Media
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="featured_image" class="form-label">
                                            <i class="fas fa-image"></i>Featured Image
                                        </label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <?php if ($trek['featured_image']): ?>
                                            <div id="current_featured_image" class="current-image">
                                                <img src="../<?php echo htmlspecialchars($trek['featured_image']); ?>" 
                                                     alt="Current Featured Image" class="tour-image-preview">
                                                <small class="text-muted">Current featured image</small>
                                            </div>
                                        <?php endif; ?>
                                        <div id="featured_preview" style="display: none; margin-top: 10px;">
                                            <img id="featured_preview_img" src="" alt="Preview" class="tour-image-preview">
                                        </div>
                                        <small class="text-muted">Recommended: 1200x800px (Max: 5MB). Leave empty to keep current image.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gallery_images" class="form-label">
                                            <i class="fas fa-images"></i>Gallery Images
                                        </label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Select multiple images to add to gallery (Max: 5MB each)</small>
                                        
                                        <!-- Existing Gallery Images -->
                                        <?php if (!empty($gallery_images)): ?>
                                            <div class="mt-3">
                                                <label>Current Gallery Images (Click to remove):</label>
                                                <div class="gallery-preview" id="existing_gallery_preview">
                                                    <?php foreach ($gallery_images as $index => $image): ?>
                                                        <div class="gallery-item">
                                                            <img src="../<?php echo htmlspecialchars($image); ?>" 
                                                                 alt="Gallery Image" class="existing-gallery-image">
                                                            <span class="remove-existing-image" 
                                                                  onclick="markImageForDeletion('<?php echo $image; ?>', this)">
                                                                <i class="fas fa-times"></i>
                                                            </span>
                                                            <input type="hidden" name="delete_gallery_images[]" 
                                                                   id="delete_<?php echo md5($image); ?>" value="">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- New Gallery Images Preview -->
                                        <div class="gallery-preview" id="new_gallery_preview" style="min-height: 50px;"></div>
                                    </div>
                                </div>
                                
                                <!-- Settings Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-cog"></i>
                                        Settings
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="display_order" class="form-label">
                                            <i class="fas fa-sort-numeric-down"></i>Display Order
                                        </label>
                                        <input type="number" id="display_order" name="display_order" value="<?php echo $trek['display_order']; ?>" class="form-control">
                                        <small class="text-muted">Lower numbers appear first</small>
                                    </div>
                                    
                                    <div class="form-check-group">
                                        <div class="form-check">
                                            <input type="checkbox" id="status" name="status" value="1" class="form-check-input" <?php echo $trek['status'] ? 'checked' : ''; ?>>
                                            <label for="status" class="form-check-label">
                                                <i class="fas fa-toggle-on"></i> Active
                                            </label>
                                            <small class="text-muted d-block">Only active treks are visible on the website</small>
                                        </div>
                                        
                                        <div class="form-check">
                                            <input type="checkbox" id="featured" name="featured" value="1" class="form-check-input" <?php echo $trek['featured'] ? 'checked' : ''; ?>>
                                            <label for="featured" class="form-check-label">
                                                <i class="fas fa-star"></i> Featured Trek
                                            </label>
                                            <small class="text-muted d-block">Show this trek in featured sections</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SEO Section -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-search"></i>
                                        SEO Settings
                                    </h3>
                                    
                                    <div class="form-group">
                                        <label for="meta_title" class="form-label">
                                            <i class="fas fa-tag"></i>Meta Title
                                        </label>
                                        <input type="text" id="meta_title" name="meta_title" class="form-control" 
                                               value="<?php echo htmlspecialchars($trek['meta_title']); ?>"
                                               placeholder="Meta title for SEO (max 60 characters)">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_description" class="form-label">
                                            <i class="fas fa-file-alt"></i>Meta Description
                                        </label>
                                        <textarea id="meta_description" name="meta_description" class="form-control" rows="3" 
                                                  placeholder="Meta description for SEO (max 160 characters)"><?php echo htmlspecialchars($trek['meta_description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="meta_keywords" class="form-label">
                                            <i class="fas fa-key"></i>Meta Keywords
                                        </label>
                                        <textarea id="meta_keywords" name="meta_keywords" class="form-control" rows="2" 
                                                  placeholder="Comma separated keywords"><?php echo htmlspecialchars($trek['meta_keywords']); ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Trek Information Preview -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <i class="fas fa-info-circle"></i>
                                        Trek Information Preview
                                    </h3>
                                    
                                    <div class="info-preview" id="trek_info_preview">
                                        <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <span id="preview_duration"><?php echo htmlspecialchars($trek['duration']); ?></span></p>
                                        <p><i class="fas fa-mountain"></i> <strong>Difficulty:</strong> <span id="preview_difficulty"><?php echo htmlspecialchars($trek['difficulty_level']); ?></span></p>
                                        <p><i class="fas fa-arrow-up"></i> <strong>Max Altitude:</strong> <span id="preview_altitude"><?php echo htmlspecialchars($trek['max_altitude']); ?></span></p>
                                        <p><i class="fas fa-map-marker-alt"></i> <strong>Starting Point:</strong> <span id="preview_start"><?php echo htmlspecialchars($trek['starting_point']); ?></span></p>
                                        <p><i class="fas fa-calendar-alt"></i> <strong>Best Season:</strong> <span id="preview_season"><?php echo htmlspecialchars($trek['best_season']); ?></span></p>
                                        <p><i class="fas fa-rupee-sign"></i> <strong>Price:</strong> <span id="preview_price">₹<?php echo number_format($trek['price'], 2); ?></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQs Section -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-question-circle"></i>
                                Frequently Asked Questions
                            </h3>
                            
                            <div id="faqs_container">
                                <div class="faq-section" id="faq_template" style="display: none;">
                                    <div class="faq-header">
                                        <h4>FAQ <span class="faq-number">1</span></h4>
                                        <button type="button" class="btn-remove-faq">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="form-group">
                                        <label>Question</label>
                                        <input type="text" class="form-control faq-question" name="faq_question[]" placeholder="Enter question">
                                    </div>
                                    <div class="form-group">
                                        <label>Answer</label>
                                        <textarea class="form-control faq-answer" name="faq_answer[]" rows="3" placeholder="Enter answer"></textarea>
                                    </div>
                                </div>
                                
                                <div id="added_faqs">
                                    <!-- Existing FAQs -->
                                    <?php if (!empty($faq)): ?>
                                        <?php foreach ($faq as $index => $faq_item): ?>
                                            <div class="faq-section" id="faq_<?php echo $index + 1; ?>">
                                                <div class="faq-header">
                                                    <h4>FAQ <span class="faq-number"><?php echo $index + 1; ?></span></h4>
                                                    <button type="button" class="btn-remove-faq">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="form-group">
                                                    <label>Question</label>
                                                    <input type="text" class="form-control faq-question" name="faq_question[]" 
                                                           value="<?php echo htmlspecialchars($faq_item['question']); ?>" placeholder="Enter question">
                                                </div>
                                                <div class="form-group">
                                                    <label>Answer</label>
                                                    <textarea class="form-control faq-answer" name="faq_answer[]" rows="3" 
                                                              placeholder="Enter answer"><?php echo htmlspecialchars($faq_item['answer']); ?></textarea>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="empty_faq_message" class="empty-message" <?php echo !empty($faq) ? 'style="display:none;"' : ''; ?>>
                                    <p>No FAQs added yet. Click the button below to add one.</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="button" id="add_faq_btn" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add FAQ
                                </button>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Trek
                            </button>
                            <a href="manage-treks.php" class="btn btn-outline">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    
    <!-- Admin Script -->
    <script>
    $(document).ready(function() {
        console.log('Edit trek form loaded');
        
        // Initialize Summernote
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
        
        // Featured image preview
        $('#featured_image').on('change', function(e) {
            var file = e.target.files[0];
            var previewContainer = $('#featured_preview');
            var previewImg = $('#featured_preview_img');
            
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg.length === 0) {
                        previewImg = $('<img>').attr({
                            id: 'featured_preview_img',
                            class: 'tour-image-preview'
                        });
                        previewContainer.append(previewImg);
                    }
                    previewImg.attr('src', e.target.result);
                    previewContainer.show();
                    
                    // Hide current image preview
                    $('#current_featured_image').hide();
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.hide();
                $('#current_featured_image').show();
            }
        });
        
        // Gallery images preview
        $('#gallery_images').on('change', function(e) {
            var files = e.target.files;
            var previewContainer = $('#new_gallery_preview');
            previewContainer.empty();
            
            for (var i = 0; i < files.length; i++) {
                (function(file) {
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        var img = $('<img>').attr('src', e.target.result)
                            .addClass('gallery-image')
                            .css({
                                width: '100px',
                                height: '80px',
                                objectFit: 'cover',
                                borderRadius: '6px',
                                border: '2px solid #e2e8f0',
                                margin: '5px'
                            });
                        
                        previewContainer.append(img);
                    };
                    
                    reader.readAsDataURL(file);
                })(files[i]);
            }
        });
        
        // Live preview updates
        function updatePreview() {
            $('#preview_duration').text($('#duration').val() || '-');
            $('#preview_difficulty').text($('#difficulty_level option:selected').text() || '-');
            $('#preview_altitude').text($('#max_altitude').val() || '-');
            $('#preview_start').text($('#starting_point').val() || '-');
            $('#preview_season').text($('#best_season').val() || '-');
            
            var price = $('#price').val();
            $('#preview_price').text(price ? '₹' + parseFloat(price).toLocaleString() : '-');
        }
        
        $('#duration, #difficulty_level, #max_altitude, #starting_point, #best_season, #price').on('input change', updatePreview);
        
        // Itinerary System
        var itineraryCounter = <?php echo count($itinerary_items); ?>;
        
        // Add remove functionality to existing itinerary items
        $('.itinerary-section').each(function() {
            $(this).find('.btn-remove-itinerary').on('click', function() {
                $(this).closest('.itinerary-section').remove();
                updateEmptyItineraryMessage();
                renumberItineraryDays();
            });
        });
        
        // Add Itinerary button click
        $('#add_itinerary_btn').on('click', function() {
            itineraryCounter++;
            var newItineraryId = 'itinerary_' + itineraryCounter;
            
            // Clone the template
            var newItinerary = $('#itinerary_template').clone().removeAttr('id').attr('id', newItineraryId);
            
            // Update day number display
            var currentCount = $('#added_itinerary .itinerary-section').length + 1;
            newItinerary.find('.day-number').text(currentCount);
            newItinerary.find('.itinerary-day').val(currentCount);
            
            // Add remove functionality
            newItinerary.find('.btn-remove-itinerary').on('click', function() {
                $(this).closest('.itinerary-section').remove();
                updateEmptyItineraryMessage();
                renumberItineraryDays();
            });
            
            // Show the new itinerary
            newItinerary.show();
            
            // Add to container
            $('#added_itinerary').append(newItinerary);
            
            // Hide empty message
            $('#empty_itinerary_message').hide();
            
            // Focus on the title input
            newItinerary.find('.itinerary-title').focus();
        });
        
        // Renumber itinerary days after removal
        function renumberItineraryDays() {
            $('#added_itinerary .itinerary-section').each(function(index) {
                var dayNumber = index + 1;
                $(this).find('.day-number').text(dayNumber);
                $(this).find('.itinerary-day').val(dayNumber);
                $(this).attr('id', 'itinerary_' + dayNumber);
            });
            itineraryCounter = $('#added_itinerary .itinerary-section').length;
        }
        
        // FAQ System
        var faqCounter = <?php echo count($faq); ?>;
        
        // Add remove functionality to existing FAQs
        $('.faq-section').each(function() {
            $(this).find('.btn-remove-faq').on('click', function() {
                $(this).closest('.faq-section').remove();
                updateEmptyFaqMessage();
            });
        });
        
        // Add FAQ button click
        $('#add_faq_btn').on('click', function() {
            faqCounter++;
            var newFaqId = 'faq_' + faqCounter;
            
            // Clone the template
            var newFaq = $('#faq_template').clone().removeAttr('id').attr('id', newFaqId);
            
            // Update FAQ number display
            var currentCount = $('#added_faqs .faq-section').length + 1;
            newFaq.find('.faq-number').text(currentCount);
            
            // Add remove functionality
            newFaq.find('.btn-remove-faq').on('click', function() {
                $(this).closest('.faq-section').remove();
                updateEmptyFaqMessage();
            });
            
            // Show the new FAQ
            newFaq.show();
            
            // Add to container
            $('#added_faqs').append(newFaq);
            
            // Hide empty message
            $('#empty_faq_message').hide();
            
            // Focus on the question input
            newFaq.find('.faq-question').focus();
        });
        
        // Function to update empty messages
        function updateEmptyItineraryMessage() {
            if ($('#added_itinerary .itinerary-section').length === 0) {
                $('#empty_itinerary_message').show();
            } else {
                $('#empty_itinerary_message').hide();
            }
        }
        
        function updateEmptyFaqMessage() {
            if ($('#added_faqs .faq-section').length === 0) {
                $('#empty_faq_message').show();
            } else {
                $('#empty_faq_message').hide();
            }
        }
        
        // Auto-generate meta data from name (only if empty)
        $('#name').on('blur', function() {
            var name = $(this).val();
            if (name) {
                // Update meta title if empty
                if (!$('#meta_title').val()) {
                    $('#meta_title').val(name + ' - Trek');
                }
                
                // Update meta description if empty
                if (!$('#meta_description').val()) {
                    $('#meta_description').val('Experience the ' + name + ' - An unforgettable trekking adventure. Book now for an amazing journey!');
                }
                
                // Update meta keywords if empty
                if (!$('#meta_keywords').val()) {
                    var keywords = name.toLowerCase().split(' ').filter(function(word) {
                        return word.length > 3;
                    });
                    keywords.push('trek', 'hiking', 'adventure', 'mountain', 'nepal');
                    $('#meta_keywords').val(keywords.join(', '));
                }
            }
        });
        
        // Form validation
        $('#trekForm').on('submit', function(e) {
            var isValid = true;
            
            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.error-message').remove();
            
            // Validate required fields
            var requiredFields = [
                { id: 'name', message: 'Trek name is required' },
                { id: 'duration', message: 'Duration is required' },
                { id: 'difficulty_level', message: 'Difficulty level is required' },
                { id: 'price', message: 'Price is required' }
            ];
            
            requiredFields.forEach(function(field) {
                var element = $('#' + field.id);
                if (!element.val() || element.val().trim() === '') {
                    element.addClass('is-invalid');
                    element.after('<div class="error-message">' + field.message + '</div>');
                    isValid = false;
                }
            });
            
            // Validate itinerary
            var itineraryItems = $('.itinerary-section').length;
            if (itineraryItems === 0) {
                $('#itinerary_container').addClass('is-invalid');
                $('#itinerary_container').after('<div class="error-message">Please add at least one day to itinerary</div>');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 500);
            } else {
                // Show loading
                $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
            }
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Save on Ctrl+S
            if ((e.ctrlKey || e.metaKey) && e.key === 's' && !$(e.target).is('textarea, input[type="text"]')) {
                e.preventDefault();
                $('#trekForm').submit();
            }
        });
        
        // Function to mark existing gallery image for deletion
        window.markImageForDeletion = function(imagePath, element) {
            var inputId = 'delete_' + md5(imagePath);
            var input = document.getElementById(inputId);
            
            if (input) {
                input.value = imagePath;
                var imgContainer = element.closest('.gallery-item');
                if (imgContainer) {
                    imgContainer.style.opacity = '0.5';
                    imgContainer.style.pointerEvents = 'none';
                    element.style.display = 'none';
                }
            }
        };
        
        // Simple hash function for generating unique IDs
        function md5(str) {
            return str.split('').reduce(function(acc, char) {
                return acc + char.charCodeAt(0);
            }, 0);
        }
        
        // Auto-add first itinerary day if none exists
        if ($('#added_itinerary .itinerary-section').length === 0) {
            $('#add_itinerary_btn').click();
        }
        
        // Initialize preview
        updatePreview();
    });
    </script>
</body>
</html>
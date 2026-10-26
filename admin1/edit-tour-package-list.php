<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Security-Policy: script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net");

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin('login.php');
$user = $auth->getUserInfo();

if (!$auth->isAdmin()) {
    die('<div style="text-align:center; padding:50px;"><h1 style="color:#c66b05;">Access Denied</h1><p>You do not have permission to access the admin panel.</p><a href="../index.php" style="color:#8f1402;">Return to Home</a></div>');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('Location: manage-tour-lists.php'); exit(); }
$package_id = intval($_GET['id']);

$current_page = 'edit-tour-package-list.php';
$conn = $db->getConnection();
$message = '';
$message_type = '';

if (isset($_GET['message'])) { $message = urldecode($_GET['message']); $message_type = $_GET['type'] ?? 'success'; }

/* ===================== UPLOAD PATH CONFIG ===================== */
define('ADMIN_UPLOAD_DIR', __DIR__ . '/assets/upload/');
define('ADMIN_UPLOAD_DB_PREFIX', 'admin1/assets/upload/');

/* ===================== HELPERS ===================== */
function getParentOptions($conn, $parentSlug = null, $level = 0, &$options = []) {
    if ($level > 5) return $options;
    if ($parentSlug === null) {
        $res = $conn->query("SELECT title, slug FROM tour_packages WHERE status='active' ORDER BY position ASC, id ASC");
    } else {
        $stmt = $conn->prepare("SELECT title, slug FROM tour_package_lists WHERE parent_slug=? AND status='active' ORDER BY position ASC, id ASC");
        $stmt->bind_param("s", $parentSlug);
        $stmt->execute();
        $res = $stmt->get_result();
    }
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['level'] = $level;
            $options[] = $row;
            getParentOptions($conn, $row['slug'], $level + 1, $options);
        }
    }
    return $options;
}

function saveUploadedImage($fileKey, $isMultiple = false, $index = null) {
    if ($isMultiple) {
        if (!isset($_FILES[$fileKey]['name'][$index]) || $_FILES[$fileKey]['error'][$index] !== 0) return null;
        $name = $_FILES[$fileKey]['name'][$index];
        $tmp  = $_FILES[$fileKey]['tmp_name'][$index];
        $size = $_FILES[$fileKey]['size'][$index];
    } else {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== 0) return null;
        $name = $_FILES[$fileKey]['name'];
        $tmp  = $_FILES[$fileKey]['tmp_name'];
        $size = $_FILES[$fileKey]['size'];
    }
    if ($size > 5 * 1024 * 1024) return null; // 5MB limit
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        $okMimes = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $okMimes)) return null;
    }
    
    // Ensure directory exists and is writable
    if (!is_dir(ADMIN_UPLOAD_DIR)) {
        @mkdir(ADMIN_UPLOAD_DIR, 0777, true);
        @chmod(ADMIN_UPLOAD_DIR, 0777);
    }
    
    $unique_name = bin2hex(random_bytes(8)) . '_' . time();
    if ($isMultiple) $unique_name .= '_' . $index;
    $unique_name .= '.' . $ext;
    
    if (move_uploaded_file($tmp, ADMIN_UPLOAD_DIR . $unique_name)) {
        return ADMIN_UPLOAD_DB_PREFIX . $unique_name;
    }
    return null;
}

function deleteUploadedImage($dbPath) {
    if (empty($dbPath)) return;
    if (filter_var($dbPath, FILTER_VALIDATE_URL)) return;
    $filename = basename($dbPath);
    $fullPath = ADMIN_UPLOAD_DIR . $filename;
    if (file_exists($fullPath)) @unlink($fullPath);
}

$parent_options = getParentOptions($conn);

$individual_tours = [];
$result = $conn->query("SELECT id, title FROM tour_packages WHERE status='active' ORDER BY title");
if ($result) while ($row = $result->fetch_assoc()) $individual_tours[] = $row;

$package = null;
$result = $conn->query("SELECT * FROM tour_package_lists WHERE id = $package_id");
if ($result && $result->num_rows > 0) $package = $result->fetch_assoc();
else { header('Location: manage-tour-lists.php'); exit(); }

$selected_tours  = $package['selected_tours'] ? json_decode($package['selected_tours'], true) : [];
$gallery_images  = $package['gallery_images'] ? json_decode($package['gallery_images'], true) : [];
$faqs            = $package['faqs'] ? json_decode($package['faqs'], true) : [];
$itinerary_items = [];
if (!empty($package['tour_itinerary'])) {
    $decoded = json_decode($package['tour_itinerary'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $itinerary_items = $decoded;
}

/* ===================== UPDATE — NO MANDATORY ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_package') {

    $title              = trim($_POST['title'] ?? '');
    if ($title === '') $title = 'Untitled Package';
    $parent_slug        = !empty($_POST['parent_slug']) ? trim($_POST['parent_slug']) : null;
    $selected_tours     = isset($_POST['selected_tours']) ? json_encode($_POST['selected_tours']) : '[]';
    $duration_days      = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : 0;
    $duration_nights    = !empty($_POST['duration_nights']) ? intval($_POST['duration_nights']) : 0;
    $price              = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
    $price_per_person   = isset($_POST['price_per_person']) ? 1 : 0;
    $tour_overview      = trim($_POST['tour_overview'] ?? '');
    $show_in_menu       = isset($_POST['show_in_menu']) ? intval($_POST['show_in_menu']) : 1;

    if ($parent_slug === $package['slug']) $parent_slug = null;

    if ($parent_slug !== null) {
        $check = $conn->prepare("SELECT slug FROM tour_packages WHERE slug=? UNION SELECT slug FROM tour_package_lists WHERE slug=?");
        $check->bind_param("ss", $parent_slug, $parent_slug);
        $check->execute();
        if ($check->get_result()->num_rows === 0) $parent_slug = $package['parent_slug'];
        $check->close();
    }

    $tour_itinerary_data = [];
    if (isset($_POST['itinerary_day'], $_POST['itinerary_title'], $_POST['itinerary_content'])) {
        for ($i = 0; $i < count($_POST['itinerary_day']); $i++) {
            if (!empty(trim($_POST['itinerary_title'][$i]))) {
                $tour_itinerary_data[] = ['day'=>trim($_POST['itinerary_day'][$i]),'title'=>trim($_POST['itinerary_title'][$i]),'content'=>trim($_POST['itinerary_content'][$i])];
            }
        }
    }
    $tour_itinerary = !empty($tour_itinerary_data) ? json_encode($tour_itinerary_data) : '';

    $faq_list = [];
    if (isset($_POST['faq_question'], $_POST['faq_answer'])) {
        for ($i = 0; $i < count($_POST['faq_question']); $i++) {
            if (!empty(trim($_POST['faq_question'][$i]))) {
                $faq_list[] = ['question'=>trim($_POST['faq_question'][$i]),'answer'=>trim($_POST['faq_answer'][$i])];
            }
        }
    }
    $faqs_json = !empty($faq_list) ? json_encode($faq_list) : null;

    $inclusions          = trim($_POST['inclusions'] ?? '');
    $exclusions          = trim($_POST['exclusions'] ?? '');
    $important_notes     = trim($_POST['important_notes'] ?? '');
    $difficulty_level    = $_POST['difficulty_level'] ?? 'moderate';
    $best_season         = trim($_POST['best_season'] ?? '');
    $group_size          = trim($_POST['group_size'] ?? '');
    $age_limit           = trim($_POST['age_limit'] ?? '');
    $accommodation       = trim($_POST['accommodation'] ?? '');
    $transport           = trim($_POST['transport'] ?? '');
    $meals               = trim($_POST['meals'] ?? '');
    $guide_service       = trim($_POST['guide_service'] ?? '');
    $permit_info         = trim($_POST['permit_info'] ?? '');
    $cancellation_policy = trim($_POST['cancellation_policy'] ?? '');
    $status              = $_POST['status'] ?? 'draft';
    $position            = !empty($_POST['position']) ? intval($_POST['position']) : 0;
    $meta_title          = trim($_POST['meta_title'] ?? '');
    $meta_description    = trim($_POST['meta_description'] ?? '');
    $meta_keywords       = trim($_POST['meta_keywords'] ?? '');

    // ================= FEATURED IMAGE UPLOAD =================
    $featured_image = $package['featured_image'];
    
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['name'] !== '') {
        if ($_FILES['featured_image']['error'] !== 0) {
            $upload_errors = [
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                4 => 'No file was uploaded.',
                6 => 'Missing a temporary folder.',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
            ];
            $err_code = $_FILES['featured_image']['error'];
            $message = 'Image Upload Error: ' . ($upload_errors[$err_code] ?? 'Unknown error');
            $message_type = 'error';
        } else {
            $new_img = saveUploadedImage('featured_image');
            if ($new_img) {
                deleteUploadedImage($featured_image);
                $featured_image = $new_img;
            } else {
                $message = 'Error: Could not upload the featured image. Please check: File size must be under 5MB, format must be JPG/PNG/GIF/WEBP, and the upload folder must be writable.';
                $message_type = 'error';
            }
        }
    }

    // ================= GALLERY IMAGES UPLOAD =================
    $current_gallery = $package['gallery_images'] ? json_decode($package['gallery_images'], true) : [];
    $gallery_images  = is_array($current_gallery) ? $current_gallery : [];

    if (!empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $key => $n) {
            if ($_FILES['gallery_images']['error'][$key] === 0) {
                $saved = saveUploadedImage('gallery_images', true, $key);
                if ($saved) $gallery_images[] = $saved;
            }
        }
    }

    if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
        foreach ($_POST['delete_gallery_images'] as $img) {
            if (($key = array_search($img, $gallery_images)) !== false) {
                deleteUploadedImage($img);
                unset($gallery_images[$key]);
            }
        }
        $gallery_images = array_values($gallery_images);
    }
    $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;

    // ================= UPDATE QUERY =================
    $stmt = $conn->prepare("UPDATE tour_package_lists SET
        title=?, parent_slug=?, selected_tours=?, duration_days=?, duration_nights=?,
        gallery_images=?, price=?, price_per_person=?, tour_overview=?, tour_itinerary=?,
        faqs=?, inclusions=?, exclusions=?, important_notes=?, difficulty_level=?,
        best_season=?, group_size=?, age_limit=?, accommodation=?, transport=?,
        meals=?, guide_service=?, permit_info=?, cancellation_policy=?, featured_image=?,
        meta_title=?, meta_description=?, meta_keywords=?, status=?, position=?, show_in_menu=?,
        updated_at=NOW() WHERE id=?");

    // **FIX: Corrected bind_param string. The previous one had only 31 chars. Now 32.**
    $stmt->bind_param(
        "sssiisdisssssssssssssssssssssiii",
        $title, $parent_slug, $selected_tours, $duration_days, $duration_nights, $gallery_json, $price,
        $price_per_person, $tour_overview, $tour_itinerary, $faqs_json, $inclusions, $exclusions,
        $important_notes, $difficulty_level, $best_season, $group_size, $age_limit, $accommodation,
        $transport, $meals, $guide_service, $permit_info, $cancellation_policy, $featured_image,
        $meta_title, $meta_description, $meta_keywords, $status, $position, $show_in_menu, $package_id
    );

    if ($stmt->execute()) {
        if ($message_type !== 'error') {
            $message = 'Tour package list updated successfully!';
            $message_type = 'success';
        }
        
        // Refresh package data
        $result = $conn->query("SELECT * FROM tour_package_lists WHERE id = $package_id");
        if ($result && $result->num_rows > 0) {
            $package = $result->fetch_assoc();
            $selected_tours = $package['selected_tours'] ? json_decode($package['selected_tours'], true) : [];
            $gallery_images = $package['gallery_images'] ? json_decode($package['gallery_images'], true) : [];
            $faqs           = $package['faqs'] ? json_decode($package['faqs'], true) : [];
            $itinerary_items = [];
            if (!empty($package['tour_itinerary'])) {
                $dec = json_decode($package['tour_itinerary'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $itinerary_items = $dec;
            }
        }
    } else {
        $message = 'Database Error: ' . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tour Package List - Newa Yatra Admin</title>
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
<div class="admin-container">
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="navbar-top">
            <div class="welcome-text"><i class="fas fa-edit me-2"></i>Edit Tour Package List</div>
            <div><a href="manage-tour-lists.php" class="btn btn-outline-header"><i class="fas fa-arrow-left me-1"></i>Back to List</a></div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" id="packageForm" novalidate>
                    <input type="hidden" name="action" value="update_package">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">

                    <div class="form-columns">
                        <div>
                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>

                                <div class="form-group">
                                    <label for="title" class="form-label"><i class="fas fa-heading"></i>Package Title</label>
                                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($package['title']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="parent_slug" class="form-label"><i class="fas fa-sitemap"></i>Parent Package / Parent Destination</label>
                                    <select id="parent_slug" name="parent_slug" class="form-control">
                                        <option value="">— None (Top Level) —</option>
                                        <?php foreach ($parent_options as $opt): ?>
                                            <?php if ($opt['slug'] === $package['slug']) continue; ?>
                                            <option value="<?php echo htmlspecialchars($opt['slug']); ?>"
                                                <?php echo ($package['parent_slug'] === $opt['slug']) ? 'selected' : ''; ?>>
                                                <?php echo str_repeat('— ', $opt['level']) . htmlspecialchars($opt['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Current parent will be preserved if unchanged.</small>
                                </div>

                                <div class="form-grid-2">
                                    <div class="form-group"><label for="duration_days" class="form-label"><i class="fas fa-sun"></i>Total Days</label><input type="number" id="duration_days" name="duration_days" min="0" class="form-control" value="<?php echo $package['duration_days']; ?>"></div>
                                    <div class="form-group"><label for="duration_nights" class="form-label"><i class="fas fa-moon"></i>Total Nights</label><input type="number" id="duration_nights" name="duration_nights" min="0" class="form-control" value="<?php echo $package['duration_nights']; ?>"></div>
                                </div>

                                <div class="form-group">
                                    <label for="price" class="form-label"><i class="fas fa-rupee-sign"></i>Package Price (₹)</label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" class="form-control" value="<?php echo $package['price']; ?>">
                                    <div class="form-check">
                                        <input type="checkbox" id="price_per_person" name="price_per_person" value="1" class="form-check-input" <?php echo $package['price_per_person'] ? 'checked' : ''; ?>>
                                        <label for="price_per_person" class="form-check-label">Price is per person</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-file-alt"></i> Tour Content</h3>
                                <div class="form-group"><label for="tour_overview" class="form-label"><i class="fas fa-align-left"></i>Tour Overview</label><textarea id="tour_overview" name="tour_overview" class="form-control summernote" rows="6"><?php echo htmlspecialchars($package['tour_overview'] ?? ''); ?></textarea></div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-route"></i> Detailed Itinerary</h3>
                                <div id="itinerary_container">
                                    <div class="itinerary-section" id="itinerary_template" style="display: none;">
                                        <div class="itinerary-header"><h4>Day <span class="day-number">1</span></h4><button type="button" class="btn-remove-itinerary"><i class="fas fa-times"></i></button></div>
                                        <div class="form-group"><label>Day Number</label><input type="number" class="form-control itinerary-day" name="itinerary_day[]" min="1" value="1"></div>
                                        <div class="form-group"><label>Day Title</label><input type="text" class="form-control itinerary-title" name="itinerary_title[]"></div>
                                        <div class="form-group"><label>Day Content</label><textarea class="form-control itinerary-content" name="itinerary_content[]" rows="4"></textarea></div>
                                    </div>
                                    <div id="added_itinerary">
                                        <?php if (!empty($itinerary_items)): foreach ($itinerary_items as $index => $item): ?>
                                            <div class="itinerary-section">
                                                <div class="itinerary-header"><h4>Day <span class="day-number"><?php echo $index + 1; ?></span></h4><button type="button" class="btn-remove-itinerary"><i class="fas fa-times"></i></button></div>
                                                <div class="form-group"><label>Day Number</label><input type="number" class="form-control itinerary-day" name="itinerary_day[]" min="1" value="<?php echo $item['day'] ?? ($index+1); ?>"></div>
                                                <div class="form-group"><label>Day Title</label><input type="text" class="form-control itinerary-title" name="itinerary_title[]" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>"></div>
                                                <div class="form-group"><label>Day Content</label><textarea class="form-control itinerary-content" name="itinerary_content[]" rows="4"><?php echo htmlspecialchars($item['content'] ?? ''); ?></textarea></div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </div>
                                    <div id="empty_itinerary_message" class="empty-message" <?php echo !empty($itinerary_items) ? 'style="display:none;"' : ''; ?>><p>No itinerary added yet.</p></div>
                                </div>
                                <div class="form-group"><button type="button" id="add_itinerary_btn" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Day</button></div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-list-check"></i> Inclusions & Exclusions</h3>
                                <div class="form-group"><label for="inclusions" class="form-label"><i class="fas fa-check-circle"></i>Inclusions</label><textarea id="inclusions" name="inclusions" class="form-control" rows="6"><?php echo htmlspecialchars($package['inclusions'] ?? ''); ?></textarea></div>
                                <div class="form-group"><label for="exclusions" class="form-label"><i class="fas fa-times-circle"></i>Exclusions</label><textarea id="exclusions" name="exclusions" class="form-control" rows="6"><?php echo htmlspecialchars($package['exclusions'] ?? ''); ?></textarea></div>
                            </div>
                        </div>

                        <div>
                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-images"></i> Images & Media</h3>
                                <div class="form-group">
                                    <label for="featured_image" class="form-label"><i class="fas fa-image"></i>Featured Image</label>
                                    <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                    <?php if ($package['featured_image']): ?>
                                        <div id="current_featured_image" class="current-image">
                                            <img src="../<?php echo htmlspecialchars($package['featured_image']); ?>" class="tour-image-preview">
                                            <small class="text-muted">Current featured image</small>
                                        </div>
                                    <?php endif; ?>
                                    <div id="featured_preview" style="display: none; margin-top: 10px;"><img id="featured_preview_img" src="" class="tour-image-preview"></div>
                                    <small class="text-muted">Max: 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
                                </div>
                                <div class="form-group">
                                    <label for="gallery_images" class="form-label"><i class="fas fa-images"></i>Gallery Images</label>
                                    <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                    <?php if (!empty($gallery_images)): ?>
                                        <div class="mt-3">
                                            <label>Current Gallery Images (Click × to remove):</label>
                                            <div class="gallery-preview" id="existing_gallery_preview">
                                                <?php foreach ($gallery_images as $image): ?>
                                                    <div class="gallery-item">
                                                        <img src="../<?php echo htmlspecialchars($image); ?>" class="existing-gallery-image">
                                                        <span class="remove-existing-image" onclick="markImageForDeletion('<?php echo htmlspecialchars($image); ?>', this)"><i class="fas fa-times"></i></span>
                                                        <input type="hidden" name="delete_gallery_images[]" id="delete_<?php echo md5($image); ?>" value="">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="gallery-preview" id="new_gallery_preview" style="min-height: 50px;"></div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-map-marked-alt"></i> Select Destination</h3>
                                <div class="form-group">
                                    <input type="text" id="tourSearch" class="search-box" placeholder="Search destinations...">
                                    <div class="checkbox-multiselect" id="toursCheckboxContainer">
                                        <?php foreach ($individual_tours as $tour): ?>
                                            <div class="checkbox-item">
                                                <input type="checkbox" name="selected_tours[]" value="<?php echo $tour['id']; ?>" id="tour_<?php echo $tour['id']; ?>" class="tour-checkbox" <?php echo in_array($tour['id'], $selected_tours) ? 'checked' : ''; ?>>
                                                <label for="tour_<?php echo $tour['id']; ?>"><?php echo htmlspecialchars($tour['title']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="selected-items-preview">
                                    <h5><i class="fas fa-list me-2"></i>Selected Destination</h5>
                                    <div id="selected_tours_list" class="mt-3">
                                        <?php if (!empty($selected_tours)): foreach ($selected_tours as $tid):
                                            foreach ($individual_tours as $tour) {
                                                if ($tour['id'] == $tid) { echo '<span class="selected-item-badge">' . htmlspecialchars($tour['title']) . '</span>'; break; }
                                            }
                                        endforeach; else: ?>
                                            <p class="text-muted">No destination selected yet</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-cog"></i> Settings</h3>
                                <div class="form-group">
                                    <label for="difficulty_level" class="form-label"><i class="fas fa-mountain"></i>Difficulty Level</label>
                                    <select id="difficulty_level" name="difficulty_level" class="form-control">
                                        <option value="easy" <?php echo $package['difficulty_level'] == 'easy' ? 'selected' : ''; ?>>Easy</option>
                                        <option value="moderate" <?php echo ($package['difficulty_level'] == 'moderate' || empty($package['difficulty_level'])) ? 'selected' : ''; ?>>Moderate</option>
                                        <option value="difficult" <?php echo $package['difficulty_level'] == 'difficult' ? 'selected' : ''; ?>>Difficult</option>
                                    </select>
                                </div>
                                <div class="form-group"><label for="group_size" class="form-label"><i class="fas fa-users"></i>Group Size</label><input type="text" id="group_size" name="group_size" class="form-control" value="<?php echo htmlspecialchars($package['group_size'] ?? ''); ?>"></div>
                                <div class="form-group"><label for="best_season" class="form-label"><i class="fas fa-calendar-alt"></i>Best Season</label><input type="text" id="best_season" name="best_season" class="form-control" value="<?php echo htmlspecialchars($package['best_season'] ?? ''); ?>"></div>
                                <div class="form-group"><label for="age_limit" class="form-label"><i class="fas fa-user-friends"></i>Age Limit</label><input type="text" id="age_limit" name="age_limit" class="form-control" value="<?php echo htmlspecialchars($package['age_limit'] ?? ''); ?>"></div>
                                <div class="form-group">
                                    <label for="position" class="form-label"><i class="fas fa-sort-numeric-down"></i>Display Position</label>
                                    <input type="number" id="position" name="position" class="form-control" value="<?php echo $package['position']; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="show_in_menu" class="form-label"><i class="fas fa-eye"></i>Show in Menu</label>
                                    <select id="show_in_menu" name="show_in_menu" class="form-control">
                                        <option value="1" <?php echo $package['show_in_menu'] ? 'selected' : ''; ?>>Yes — Show in frontend menu</option>
                                        <option value="0" <?php echo !$package['show_in_menu'] ? 'selected' : ''; ?>>No — Hide from frontend menu</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="status" class="form-label"><i class="fas fa-toggle-on"></i>Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="draft" <?php echo $package['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="active" <?php echo $package['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $package['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-search"></i> SEO Settings</h3>
                                <div class="form-group"><label for="meta_title" class="form-label"><i class="fas fa-tag"></i>Meta Title</label><input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($package['meta_title'] ?? ''); ?>"></div>
                                <div class="form-group"><label for="meta_description" class="form-label"><i class="fas fa-file-alt"></i>Meta Description</label><textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($package['meta_description'] ?? ''); ?></textarea></div>
                                <div class="form-group"><label for="meta_keywords" class="form-label"><i class="fas fa-key"></i>Meta Keywords</label><textarea id="meta_keywords" name="meta_keywords" class="form-control" rows="2"><?php echo htmlspecialchars($package['meta_keywords'] ?? ''); ?></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title"><i class="fas fa-hotel"></i> Services & Facilities</h3>
                        <div class="form-grid">
                            <div class="form-group"><label for="accommodation" class="form-label"><i class="fas fa-bed"></i>Accommodation</label><textarea id="accommodation" name="accommodation" class="form-control" rows="4"><?php echo htmlspecialchars($package['accommodation'] ?? ''); ?></textarea></div>
                            <div class="form-group"><label for="transport" class="form-label"><i class="fas fa-car"></i>Transport</label><textarea id="transport" name="transport" class="form-control" rows="4"><?php echo htmlspecialchars($package['transport'] ?? ''); ?></textarea></div>
                            <div class="form-group"><label for="meals" class="form-label"><i class="fas fa-utensils"></i>Meals</label><textarea id="meals" name="meals" class="form-control" rows="4"><?php echo htmlspecialchars($package['meals'] ?? ''); ?></textarea></div>
                            <div class="form-group"><label for="guide_service" class="form-label"><i class="fas fa-user-tie"></i>Guide Service</label><textarea id="guide_service" name="guide_service" class="form-control" rows="4"><?php echo htmlspecialchars($package['guide_service'] ?? ''); ?></textarea></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title"><i class="fas fa-exclamation-triangle"></i> Important Information</h3>
                        <div class="form-grid">
                            <div class="form-group"><label for="permit_info" class="form-label"><i class="fas fa-passport"></i>Permit Information</label><textarea id="permit_info" name="permit_info" class="form-control" rows="4"><?php echo htmlspecialchars($package['permit_info'] ?? ''); ?></textarea></div>
                            <div class="form-group"><label for="important_notes" class="form-label"><i class="fas fa-exclamation-circle"></i>Important Notes</label><textarea id="important_notes" name="important_notes" class="form-control" rows="4"><?php echo htmlspecialchars($package['important_notes'] ?? ''); ?></textarea></div>
                            <div class="form-group"><label for="cancellation_policy" class="form-label"><i class="fas fa-ban"></i>Cancellation Policy</label><textarea id="cancellation_policy" name="cancellation_policy" class="form-control" rows="4"><?php echo htmlspecialchars($package['cancellation_policy'] ?? ''); ?></textarea></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title"><i class="fas fa-question-circle"></i> FAQs</h3>
                        <div id="faqs_container">
                            <div class="faq-section" id="faq_template" style="display: none;">
                                <div class="faq-header"><h4>FAQ <span class="faq-number">1</span></h4><button type="button" class="btn-remove-faq"><i class="fas fa-times"></i></button></div>
                                <div class="form-group"><label>Question</label><input type="text" class="form-control faq-question" name="faq_question[]"></div>
                                <div class="form-group"><label>Answer</label><textarea class="form-control faq-answer" name="faq_answer[]" rows="3"></textarea></div>
                            </div>
                            <div id="added_faqs">
                                <?php if (!empty($faqs)): foreach ($faqs as $index => $faq): ?>
                                    <div class="faq-section">
                                        <div class="faq-header"><h4>FAQ <span class="faq-number"><?php echo $index + 1; ?></span></h4><button type="button" class="btn-remove-faq"><i class="fas fa-times"></i></button></div>
                                        <div class="form-group"><label>Question</label><input type="text" class="form-control faq-question" name="faq_question[]" value="<?php echo htmlspecialchars($faq['question'] ?? ''); ?>"></div>
                                        <div class="form-group"><label>Answer</label><textarea class="form-control faq-answer" name="faq_answer[]" rows="3"><?php echo htmlspecialchars($faq['answer'] ?? ''); ?></textarea></div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                            <div id="empty_faq_message" class="empty-message" <?php echo !empty($faqs) ? 'style="display:none;"' : ''; ?>><p>No FAQs added yet.</p></div>
                        </div>
                        <div class="form-group"><button type="button" id="add_faq_btn" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add FAQ</button></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Package</button>
                        <a href="manage-tour-lists.php" class="btn btn-outline"><i class="fas fa-times me-1"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('.summernote').summernote({ height: 200, toolbar: [['style', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link']], ['view', ['fullscreen', 'codeview']]] });

    $('#featured_image').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var r = new FileReader();
            r.onload = function(ev) { $('#featured_preview_img').attr('src', ev.target.result); $('#featured_preview').show(); $('#current_featured_image').hide(); };
            r.readAsDataURL(file);
        }
    });

    $('#gallery_images').on('change', function(e) {
        var files = e.target.files, c = $('#new_gallery_preview').empty();
        for (var i = 0; i < files.length; i++) {
            (function(f) {
                var r = new FileReader();
                r.onload = function(ev) { $('<img>').attr('src', ev.target.result).css({width:'100px',height:'80px',objectFit:'cover',borderRadius:'6px',border:'2px solid #e2e8f0',margin:'5px'}).appendTo(c); };
                r.readAsDataURL(f);
            })(files[i]);
        }
    });

    window.markImageForDeletion = function(path, el) {
        var input = document.getElementById('delete_' + md5(path));
        if (input) { input.value = path; var item = el.closest('.gallery-item'); if (item) { item.style.opacity = '0.4'; item.style.pointerEvents = 'none'; } }
    };
    function md5(str) { return str.split('').reduce(function(acc, c) { return acc + c.charCodeAt(0); }, 0); }

    function updateSel() {
        var chk = $('.tour-checkbox:checked'), c = $('#selected_tours_list');
        if (chk.length > 0) { c.empty(); chk.each(function(){ c.append('<span class="selected-item-badge">' + $(this).next('label').text() + '</span>'); }); }
        else c.html('<p class="text-muted">No destination selected yet</p>');
    }
    $('.tour-checkbox').on('change', updateSel);
    $('#tourSearch').on('keyup', function() {
        var t = $(this).val().toLowerCase();
        $('.checkbox-item').each(function(){ $(this).toggle($(this).find('label').text().toLowerCase().indexOf(t) > -1); });
    });

    var itin = <?php echo count($itinerary_items); ?>;
    $('.itinerary-section').each(function(){ $(this).find('.btn-remove-itinerary').on('click', function(){ $(this).closest('.itinerary-section').remove(); renumberItin(); updateIE(); }); });
    $('#add_itinerary_btn').on('click', function() {
        itin++;
        var item = $('#itinerary_template').clone().removeAttr('id');
        var c = $('#added_itinerary .itinerary-section').length + 1;
        item.find('.day-number').text(c); item.find('.itinerary-day').val(c);
        item.find('.btn-remove-itinerary').on('click', function(){ $(this).closest('.itinerary-section').remove(); renumberItin(); updateIE(); });
        item.show(); $('#added_itinerary').append(item); $('#empty_itinerary_message').hide();
        item.find('.itinerary-title').focus();
    });
    function renumberItin() { $('#added_itinerary .itinerary-section').each(function(i){ $(this).find('.day-number').text(i+1); $(this).find('.itinerary-day').val(i+1); }); }
    function updateIE() { $('#empty_itinerary_message').toggle($('#added_itinerary .itinerary-section').length === 0); }

    var faq = <?php echo count($faqs); ?>;
    $('.faq-section').each(function(){ $(this).find('.btn-remove-faq').on('click', function(){ $(this).closest('.faq-section').remove(); updateFE(); }); });
    $('#add_faq_btn').on('click', function() {
        faq++;
        var item = $('#faq_template').clone().removeAttr('id');
        var c = $('#added_faqs .faq-section').length + 1;
        item.find('.faq-number').text(c);
        item.find('.btn-remove-faq').on('click', function(){ $(this).closest('.faq-section').remove(); updateFE(); });
        item.show(); $('#added_faqs').append(item); $('#empty_faq_message').hide();
        item.find('.faq-question').focus();
    });
    function updateFE() { $('#empty_faq_message').toggle($('#added_faqs .faq-section').length === 0); }

    // NO validation — everything optional
    $('#packageForm').on('submit', function() {
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
    });

    if ($('#added_itinerary .itinerary-section').length === 0) $('#add_itinerary_btn').click();
});
</script>
</body>
</html>
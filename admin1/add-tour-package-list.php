<?php
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

$current_page = 'add-tour-package-list.php';
$conn = $db->getConnection();
$message = '';
$message_type = '';

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
    if ($size > 5 * 1024 * 1024) return null;
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
    if (!is_dir(ADMIN_UPLOAD_DIR)) @mkdir(ADMIN_UPLOAD_DIR, 0777, true);
    $unique_name = bin2hex(random_bytes(8)) . '_' . time();
    if ($isMultiple) $unique_name .= '_' . $index;
    $unique_name .= '.' . $ext;
    if (move_uploaded_file($tmp, ADMIN_UPLOAD_DIR . $unique_name)) {
        return ADMIN_UPLOAD_DB_PREFIX . $unique_name;
    }
    return null;
}

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'package-' . time() : $text;
}

$parent_options = getParentOptions($conn);

$individual_tours = [];
$result = $conn->query("SELECT id, title FROM tour_packages WHERE status='active' ORDER BY title");
if ($result) while ($row = $result->fetch_assoc()) $individual_tours[] = $row;

/* ===================== FORM HANDLER — NO MANDATORY FIELDS ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_package') {

    $title              = trim($_POST['title'] ?? '');
    if ($title === '') $title = 'Untitled Package';
    $slug               = createSlug($title);
    $parent_slug        = !empty($_POST['parent_slug']) ? trim($_POST['parent_slug']) : null;
    $selected_tours     = isset($_POST['selected_tours']) ? json_encode($_POST['selected_tours']) : '[]';
    $duration_days      = !empty($_POST['duration_days']) ? intval($_POST['duration_days']) : 0;
    $duration_nights    = !empty($_POST['duration_nights']) ? intval($_POST['duration_nights']) : 0;
    $price              = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
    $price_per_person   = isset($_POST['price_per_person']) ? 1 : 0;
    $tour_overview      = trim($_POST['tour_overview'] ?? '');
    $show_in_menu       = isset($_POST['show_in_menu']) ? intval($_POST['show_in_menu']) : 1;

    // Validate parent (only if provided)
    if ($parent_slug !== null) {
        $check = $conn->prepare("SELECT slug FROM tour_packages WHERE slug=? UNION SELECT slug FROM tour_package_lists WHERE slug=?");
        $check->bind_param("ss", $parent_slug, $parent_slug);
        $check->execute();
        if ($check->get_result()->num_rows === 0) $parent_slug = null;
        $check->close();
    }

    // Duplicate slug
    $dup = $conn->prepare("SELECT id FROM tour_package_lists WHERE slug=?");
    $dup->bind_param("s", $slug);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) $slug .= '-' . time();
    $dup->close();

    // Itinerary
    $tour_itinerary_data = [];
    if (isset($_POST['itinerary_day'], $_POST['itinerary_title'], $_POST['itinerary_content'])) {
        for ($i = 0; $i < count($_POST['itinerary_day']); $i++) {
            if (!empty(trim($_POST['itinerary_title'][$i]))) {
                $tour_itinerary_data[] = [
                    'day'     => trim($_POST['itinerary_day'][$i]),
                    'title'   => trim($_POST['itinerary_title'][$i]),
                    'content' => trim($_POST['itinerary_content'][$i]),
                ];
            }
        }
    }
    $tour_itinerary = !empty($tour_itinerary_data) ? json_encode($tour_itinerary_data) : '';

    // FAQs
    $faqs = [];
    if (isset($_POST['faq_question'], $_POST['faq_answer'])) {
        for ($i = 0; $i < count($_POST['faq_question']); $i++) {
            if (!empty(trim($_POST['faq_question'][$i]))) {
                $faqs[] = ['question' => trim($_POST['faq_question'][$i]), 'answer' => trim($_POST['faq_answer'][$i])];
            }
        }
    }
    $faqs_json = !empty($faqs) ? json_encode($faqs) : null;

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
    $status              = $_POST['status'] ?? 'active'; // Default changed to active
    $position            = !empty($_POST['position']) ? intval($_POST['position']) : 0;
    $meta_title          = trim($_POST['meta_title'] ?? '');
    $meta_description    = trim($_POST['meta_description'] ?? '');
    $meta_keywords       = trim($_POST['meta_keywords'] ?? '');

    // Featured image
    $featured_image = saveUploadedImage('featured_image') ?? '';

    // Gallery
    $gallery_images = [];
    if (!empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $key => $n) {
            $saved = saveUploadedImage('gallery_images', true, $key);
            if ($saved) $gallery_images[] = $saved;
        }
    }
    $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;

    $stmt = $conn->prepare("INSERT INTO tour_package_lists (
        title, slug, parent_slug, selected_tours, duration_days, duration_nights, gallery_images,
        price, price_per_person, tour_overview, tour_itinerary, faqs, inclusions, exclusions,
        important_notes, difficulty_level, best_season, group_size, age_limit, accommodation,
        transport, meals, guide_service, permit_info, cancellation_policy, featured_image,
        meta_title, meta_description, meta_keywords, status, position, show_in_menu
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // **FIX: Corrected bind_param string**
    $stmt->bind_param(
        "ssssiisdissssssssssssssssssssssii",
        $title, $slug, $parent_slug, $selected_tours, $duration_days, $duration_nights, $gallery_json,
        $price, $price_per_person, $tour_overview, $tour_itinerary, $faqs_json, $inclusions, $exclusions,
        $important_notes, $difficulty_level, $best_season, $group_size, $age_limit, $accommodation,
        $transport, $meals, $guide_service, $permit_info, $cancellation_policy, $featured_image,
        $meta_title, $meta_description, $meta_keywords, $status, $position, $show_in_menu
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage-tour-lists.php");
        exit();
    } else {
        $message = 'Error: ' . $stmt->error;
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
    <title>Add Tour Package List - Newa Yatra Admin</title>
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
            <div class="welcome-text"><i class="fas fa-plus-circle me-2"></i>Add New Tour Package List</div>
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
                    <input type="hidden" name="action" value="add_package">

                    <div class="form-columns">
                        <div>
                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>

                                <div class="form-group">
                                    <label for="title" class="form-label"><i class="fas fa-heading"></i>Package Title</label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Best of Darjeeling & Sikkim Tour Package">
                                </div>

                                <div class="form-group">
                                    <label for="parent_slug" class="form-label"><i class="fas fa-sitemap"></i>Parent Package / Parent Destination</label>
                                    <select id="parent_slug" name="parent_slug" class="form-control">
                                        <option value="">— None (Top Level) —</option>
                                        <?php foreach ($parent_options as $opt): ?>
                                            <option value="<?php echo htmlspecialchars($opt['slug']); ?>">
                                                <?php echo str_repeat('— ', $opt['level']) . htmlspecialchars($opt['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Select a parent to nest this package underneath.</small>
                                </div>

                                <div class="form-grid-2">
                                    <div class="form-group"><label for="duration_days" class="form-label"><i class="fas fa-sun"></i>Total Days</label><input type="number" id="duration_days" name="duration_days" min="0" class="form-control" placeholder="e.g., 7"></div>
                                    <div class="form-group"><label for="duration_nights" class="form-label"><i class="fas fa-moon"></i>Total Nights</label><input type="number" id="duration_nights" name="duration_nights" min="0" class="form-control" placeholder="e.g., 6"></div>
                                </div>

                                <div class="form-group">
                                    <label for="price" class="form-label"><i class="fas fa-rupee-sign"></i>Package Price (₹)</label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" class="form-control" placeholder="e.g., 25000.00">
                                    <div class="form-check">
                                        <input type="checkbox" id="price_per_person" name="price_per_person" value="1" class="form-check-input" checked>
                                        <label for="price_per_person" class="form-check-label">Price is per person</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-file-alt"></i> Tour Content</h3>
                                <div class="form-group">
                                    <label for="tour_overview" class="form-label"><i class="fas fa-align-left"></i>Tour Overview</label>
                                    <textarea id="tour_overview" name="tour_overview" class="form-control summernote" rows="6"></textarea>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-route"></i> Detailed Itinerary</h3>
                                <div id="itinerary_container">
                                    <div class="itinerary-section" id="itinerary_template" style="display: none;">
                                        <div class="itinerary-header"><h4>Day <span class="day-number">1</span></h4><button type="button" class="btn-remove-itinerary"><i class="fas fa-times"></i></button></div>
                                        <div class="form-group"><label>Day Number</label><input type="number" class="form-control itinerary-day" name="itinerary_day[]" min="1" value="1"></div>
                                        <div class="form-group"><label>Day Title</label><input type="text" class="form-control itinerary-title" name="itinerary_title[]" placeholder="e.g., Arrival in Darjeeling"></div>
                                        <div class="form-group"><label>Day Content</label><textarea class="form-control itinerary-content" name="itinerary_content[]" rows="4"></textarea></div>
                                    </div>
                                    <div id="added_itinerary"></div>
                                    <div id="empty_itinerary_message" class="empty-message"><p>No itinerary added yet. Click the button below to add the first day.</p></div>
                                </div>
                                <div class="form-group"><button type="button" id="add_itinerary_btn" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Day</button></div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-list-check"></i> Inclusions & Exclusions</h3>
                                <div class="form-group"><label for="inclusions" class="form-label"><i class="fas fa-check-circle"></i>Inclusions</label><textarea id="inclusions" name="inclusions" class="form-control" rows="6"></textarea></div>
                                <div class="form-group"><label for="exclusions" class="form-label"><i class="fas fa-times-circle"></i>Exclusions</label><textarea id="exclusions" name="exclusions" class="form-control" rows="6"></textarea></div>
                            </div>
                        </div>

                        <div>
                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-images"></i> Images & Media</h3>
                                <div class="form-group">
                                    <label for="featured_image" class="form-label"><i class="fas fa-image"></i>Featured Image</label>
                                    <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                    <div id="featured_preview" style="display: none; margin-top: 10px;"><img id="featured_preview_img" src="" class="tour-image-preview"></div>
                                    <small class="text-muted">Max: 5MB. Saved to admin1/assets/upload/</small>
                                </div>
                                <div class="form-group">
                                    <label for="gallery_images" class="form-label"><i class="fas fa-images"></i>Gallery Images</label>
                                    <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                    <small class="text-muted">Max: 5MB each. Saved to admin1/assets/upload/</small>
                                    <div class="gallery-preview" id="new_gallery_preview" style="min-height: 50px;"></div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-map-marked-alt"></i> Select Destination</h3>
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-layer-group"></i>Select Destination</label>
                                    <input type="text" id="tourSearch" class="search-box" placeholder="Search destinations...">
                                    <div class="checkbox-multiselect" id="toursCheckboxContainer">
                                        <?php foreach ($individual_tours as $tour): ?>
                                            <div class="checkbox-item">
                                                <input type="checkbox" name="selected_tours[]" value="<?php echo $tour['id']; ?>" id="tour_<?php echo $tour['id']; ?>" class="tour-checkbox">
                                                <label for="tour_<?php echo $tour['id']; ?>"><?php echo htmlspecialchars($tour['title']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="selected-items-preview">
                                    <h5><i class="fas fa-list me-2"></i>Selected Destination</h5>
                                    <div id="selected_tours_list" class="mt-3"><p class="text-muted">No destination selected yet</p></div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-cog"></i> Settings</h3>
                                <div class="form-group">
                                    <label for="difficulty_level" class="form-label"><i class="fas fa-mountain"></i>Difficulty Level</label>
                                    <select id="difficulty_level" name="difficulty_level" class="form-control">
                                        <option value="easy">Easy</option>
                                        <option value="moderate" selected>Moderate</option>
                                        <option value="difficult">Difficult</option>
                                    </select>
                                </div>
                                <div class="form-group"><label for="group_size" class="form-label"><i class="fas fa-users"></i>Group Size</label><input type="text" id="group_size" name="group_size" class="form-control" placeholder="e.g., 2-12 people"></div>
                                <div class="form-group"><label for="best_season" class="form-label"><i class="fas fa-calendar-alt"></i>Best Season</label><input type="text" id="best_season" name="best_season" class="form-control" placeholder="e.g., March to June"></div>
                                <div class="form-group"><label for="age_limit" class="form-label"><i class="fas fa-user-friends"></i>Age Limit</label><input type="text" id="age_limit" name="age_limit" class="form-control" placeholder="e.g., 12-65 years"></div>
                                <div class="form-group">
                                    <label for="position" class="form-label"><i class="fas fa-sort-numeric-down"></i>Display Position</label>
                                    <input type="number" id="position" name="position" value="0" class="form-control">
                                    <small class="text-muted">Lower numbers appear first</small>
                                </div>

                                <div class="form-group">
                                    <label for="show_in_menu" class="form-label"><i class="fas fa-eye"></i>Show in Menu</label>
                                    <select id="show_in_menu" name="show_in_menu" class="form-control">
                                        <option value="1" selected>Yes — Show in frontend menu</option>
                                        <option value="0">No — Hide from frontend menu</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="status" class="form-label"><i class="fas fa-toggle-on"></i>Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <!-- YAHAN CHANGE KIYA GAYA HAI: Default ab Active hai -->
                                        <option value="active" selected>Active</option>
                                        <option value="draft">Draft</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-search"></i> SEO Settings</h3>
                                <div class="form-group"><label for="meta_title" class="form-label"><i class="fas fa-tag"></i>Meta Title</label><input type="text" id="meta_title" name="meta_title" class="form-control"></div>
                                <div class="form-group"><label for="meta_description" class="form-label"><i class="fas fa-file-alt"></i>Meta Description</label><textarea id="meta_description" name="meta_description" class="form-control" rows="3"></textarea></div>
                                <div class="form-group"><label for="meta_keywords" class="form-label"><i class="fas fa-key"></i>Meta Keywords</label><textarea id="meta_keywords" name="meta_keywords" class="form-control" rows="2"></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title"><i class="fas fa-hotel"></i> Services & Facilities</h3>
                        <div class="form-grid">
                            <div class="form-group"><label for="accommodation" class="form-label"><i class="fas fa-bed"></i>Accommodation</label><textarea id="accommodation" name="accommodation" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label for="transport" class="form-label"><i class="fas fa-car"></i>Transport</label><textarea id="transport" name="transport" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label for="meals" class="form-label"><i class="fas fa-utensils"></i>Meals</label><textarea id="meals" name="meals" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label for="guide_service" class="form-label"><i class="fas fa-user-tie"></i>Guide Service</label><textarea id="guide_service" name="guide_service" class="form-control" rows="4"></textarea></div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title"><i class="fas fa-exclamation-triangle"></i> Important Information</h3>
                        <div class="form-grid">
                            <div class="form-group"><label for="permit_info" class="form-label"><i class="fas fa-passport"></i>Permit Information</label><textarea id="permit_info" name="permit_info" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label for="important_notes" class="form-label"><i class="fas fa-exclamation-circle"></i>Important Notes</label><textarea id="important_notes" name="important_notes" class="form-control" rows="4"></textarea></div>
                            <div class="form-group"><label for="cancellation_policy" class="form-label"><i class="fas fa-ban"></i>Cancellation Policy</label><textarea id="cancellation_policy" name="cancellation_policy" class="form-control" rows="4"></textarea></div>
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
                            <div id="added_faqs"></div>
                            <div id="empty_faq_message" class="empty-message"><p>No FAQs added yet. Click the button below to add one.</p></div>
                        </div>
                        <div class="form-group"><button type="button" id="add_faq_btn" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add FAQ</button></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Package</button>
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
            r.onload = function(ev) { $('#featured_preview_img').attr('src', ev.target.result); $('#featured_preview').show(); };
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

    function updateSel() {
        var chk = $('.tour-checkbox:checked'), c = $('#selected_tours_list');
        if (chk.length > 0) { c.empty(); chk.each(function(){ c.append('<span class="selected-item-badge">' + $(this).next('label').text() + '</span>'); }); }
        else c.html('<p class="text-muted">No destination selected yet</p>');
    }
    $('.tour-checkbox').on('change', updateSel); updateSel();
    $('#tourSearch').on('keyup', function() {
        var t = $(this).val().toLowerCase();
        $('.checkbox-item').each(function(){ $(this).toggle($(this).find('label').text().toLowerCase().indexOf(t) > -1); });
    });

    var itin = 0;
    $('#add_itinerary_btn').on('click', function() {
        itin++;
        var item = $('#itinerary_template').clone().removeAttr('id');
        item.find('.day-number').text(itin); item.find('.itinerary-day').val(itin);
        item.find('.btn-remove-itinerary').on('click', function(){ $(this).closest('.itinerary-section').remove(); updateIE(); });
        item.show(); $('#added_itinerary').append(item); $('#empty_itinerary_message').hide();
        item.find('.itinerary-title').focus();
    });
    function updateIE() { $('#empty_itinerary_message').toggle($('#added_itinerary .itinerary-section').length === 0); }

    var faq = 0;
    $('#add_faq_btn').on('click', function() {
        faq++;
        var item = $('#faq_template').clone().removeAttr('id');
        item.find('.faq-number').text(faq);
        item.find('.btn-remove-faq').on('click', function(){ $(this).closest('.faq-section').remove(); updateFE(); });
        item.show(); $('#added_faqs').append(item); $('#empty_faq_message').hide();
        item.find('.faq-question').focus();
    });
    function updateFE() { $('#empty_faq_message').toggle($('#added_faqs .faq-section').length === 0); }

    $('#title').on('blur', function() {
        var t = $(this).val();
        if (t) {
            if (!$('#meta_title').val()) $('#meta_title').val(t + ' - Tour Package');
            if (!$('#meta_description').val()) $('#meta_description').val('Book ' + t + ' - Best tour package.');
        }
    });

    // NO validation — everything optional
    $('#packageForm').on('submit', function() {
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
    });

    $('#add_itinerary_btn').click();
});
</script>
</body>
</html>
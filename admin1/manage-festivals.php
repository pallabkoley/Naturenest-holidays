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

if (!function_exists('createSlug')) {
    function createSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'festival-' . time() : $text;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {

            // =========================
            // ADD FESTIVAL
            // =========================
            case 'add_festival':
                $name = trim($_POST['name']);
                $slug = createSlug($name);
                $description = trim($_POST['description']);
                $location = trim($_POST['location']);
                $state = trim($_POST['state']);
                $duration = trim($_POST['duration']);
                $festival_date = trim($_POST['festival_date']);
                $best_time = trim($_POST['best_time']);
                $starting_point = trim($_POST['starting_point']);
                $price = floatval($_POST['price']);
                $highlights = trim($_POST['highlights']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $faq = trim($_POST['faq']);
                $important_notes = trim($_POST['important_notes']);
                $permit_info = trim($_POST['permit_info']);
                $cancellation_policy = trim($_POST['cancellation_policy']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $meta_keywords = trim($_POST['meta_keywords']);
                $status = $_POST['status'] ?? 'active';
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = intval($_POST['display_order']);

                // Featured image upload
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = __DIR__ . '/assets/upload/festivals/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024;

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['featured_image']['tmp_name']);
                    finfo_close($finfo);

                    if (in_array($file_extension, $allowed_types) && in_array($mime, $allowed_mime)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = 'admin1/assets/upload/festivals/' . $unique_name;
                            }
                        }
                    }
                }

                // Gallery images
                $gallery_images = [];
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = __DIR__ . '/assets/upload/festivals/gallery/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024;

                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === 0) {
                            $file_extension = strtolower(pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION));
                            $unique_name = uniqid() . '_' . time() . '_' . $key . '.' . $file_extension;
                            $target_file = $upload_dir . $unique_name;

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $tmp_name);
                            finfo_close($finfo);

                            if (in_array($file_extension, $allowed_types) && in_array($mime, $allowed_mime)) {
                                if ($_FILES['gallery_images']['size'][$key] <= $max_size) {
                                    if (move_uploaded_file($tmp_name, $target_file)) {
                                        $gallery_images[] = 'admin1/assets/upload/festivals/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }

                $stmt = $conn->prepare("INSERT INTO festivals (
                    name, slug, description, featured_image, gallery_images,
                    location, state, duration, festival_date, best_time, starting_point,
                    price, highlights, itinerary, inclusions, exclusions, faq,
                    important_notes, permit_info, cancellation_policy,
                    meta_title, meta_description, meta_keywords,
                    status, featured, display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;

                // Type string: 11s + 1d + 12s + 2i = 26 characters
                $stmt->bind_param(
                    "sssssssssssdssssssssssssii",
                    $name, $slug, $description, $featured_image, $gallery_json,
                    $location, $state, $duration, $festival_date, $best_time, $starting_point,
                    $price, $highlights, $itinerary, $inclusions, $exclusions, $faq,
                    $important_notes, $permit_info, $cancellation_policy,
                    $meta_title, $meta_description, $meta_keywords,
                    $status, $featured, $display_order
                );

                if ($stmt->execute()) {
                    $message = 'Festival added successfully!';
                    $message_type = 'success';
                } else {
                    error_log('Festival add error: ' . $stmt->error);
                    $message = 'Error adding festival. Please try again.';
                    $message_type = 'error';
                }
                $stmt->close();
                break;

            // =========================
            // UPDATE FESTIVAL
            // =========================
            case 'update_festival':
                $festival_id = intval($_POST['festival_id']);
                $name = trim($_POST['name']);
                $slug = createSlug($name);
                $description = trim($_POST['description']);
                $location = trim($_POST['location']);
                $state = trim($_POST['state']);
                $duration = trim($_POST['duration']);
                $festival_date = trim($_POST['festival_date']);
                $best_time = trim($_POST['best_time']);
                $starting_point = trim($_POST['starting_point']);
                $price = floatval($_POST['price']);
                $highlights = trim($_POST['highlights']);
                $itinerary = trim($_POST['itinerary']);
                $inclusions = trim($_POST['inclusions']);
                $exclusions = trim($_POST['exclusions']);
                $faq = trim($_POST['faq']);
                $important_notes = trim($_POST['important_notes']);
                $permit_info = trim($_POST['permit_info']);
                $cancellation_policy = trim($_POST['cancellation_policy']);
                $meta_title = trim($_POST['meta_title']);
                $meta_description = trim($_POST['meta_description']);
                $meta_keywords = trim($_POST['meta_keywords']);
                $status = $_POST['status'] ?? 'active';
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = intval($_POST['display_order']);

                // Fetch current data
                $current = ['featured_image' => '', 'gallery_images' => null];
                $stmt = $conn->prepare("SELECT featured_image, gallery_images FROM festivals WHERE id = ?");
                $stmt->bind_param("i", $festival_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $current = $res->fetch_assoc();
                }
                $stmt->close();

                // Featured image update
                $featured_image = $current['featured_image'] ?? '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $upload_dir = __DIR__ . '/assets/upload/festivals/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    if ($featured_image && file_exists(__DIR__ . '/' . $featured_image)) {
                        @unlink(__DIR__ . '/' . $featured_image);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_name;

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024;

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['featured_image']['tmp_name']);
                    finfo_close($finfo);

                    if (in_array($file_extension, $allowed_types) && in_array($mime, $allowed_mime)) {
                        if ($_FILES['featured_image']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                                $featured_image = 'admin1/assets/upload/festivals/' . $unique_name;
                            }
                        }
                    }
                }

                // Gallery
                $gallery_images = $current['gallery_images'] ? json_decode($current['gallery_images'], true) : [];
                if (!is_array($gallery_images)) $gallery_images = [];

                // Handle gallery deletion (by path, not index)
                if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                    foreach ($_POST['delete_gallery_images'] as $img_to_delete) {
                        if (!empty($img_to_delete)) {
                            $key = array_search($img_to_delete, $gallery_images);
                            if ($key !== false) {
                                if (file_exists(__DIR__ . '/' . $img_to_delete)) {
                                    @unlink(__DIR__ . '/' . $img_to_delete);
                                }
                                unset($gallery_images[$key]);
                            }
                        }
                    }
                    $gallery_images = array_values($gallery_images);
                }

                // New gallery upload
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    $upload_dir = __DIR__ . '/assets/upload/festivals/gallery/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024;

                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === 0) {
                            $file_extension = strtolower(pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION));
                            $unique_name = uniqid() . '_' . time() . '_' . $key . '.' . $file_extension;
                            $target_file = $upload_dir . $unique_name;

                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $tmp_name);
                            finfo_close($finfo);

                            if (in_array($file_extension, $allowed_types) && in_array($mime, $allowed_mime)) {
                                if ($_FILES['gallery_images']['size'][$key] <= $max_size) {
                                    if (move_uploaded_file($tmp_name, $target_file)) {
                                        $gallery_images[] = 'admin1/assets/upload/festivals/gallery/' . $unique_name;
                                    }
                                }
                            }
                        }
                    }
                }

                $gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : null;

                $stmt = $conn->prepare("UPDATE festivals SET
                    name = ?, slug = ?, description = ?, featured_image = ?, gallery_images = ?,
                    location = ?, state = ?, duration = ?, festival_date = ?, best_time = ?, starting_point = ?,
                    price = ?, highlights = ?, itinerary = ?, inclusions = ?, exclusions = ?, faq = ?,
                    important_notes = ?, permit_info = ?, cancellation_policy = ?,
                    meta_title = ?, meta_description = ?, meta_keywords = ?,
                    status = ?, featured = ?, display_order = ?
                    WHERE id = ?");

                // Type string: 11s + 1d + 12s + 3i = 27 characters
                $stmt->bind_param(
                    "sssssssssssdssssssssssssiii",
                    $name, $slug, $description, $featured_image, $gallery_json,
                    $location, $state, $duration, $festival_date, $best_time, $starting_point,
                    $price, $highlights, $itinerary, $inclusions, $exclusions, $faq,
                    $important_notes, $permit_info, $cancellation_policy,
                    $meta_title, $meta_description, $meta_keywords,
                    $status, $featured, $display_order, $festival_id
                );

                if ($stmt->execute()) {
                    $message = 'Festival updated successfully!';
                    $message_type = 'success';
                } else {
                    error_log('Festival update error: ' . $stmt->error);
                    $message = 'Error updating festival. Please try again.';
                    $message_type = 'error';
                }
                $stmt->close();
                break;

            // =========================
            // DELETE FESTIVAL
            // =========================
            case 'delete_festival':
                $festival_id = intval($_POST['festival_id']);

                $stmt = $conn->prepare("SELECT featured_image, gallery_images FROM festivals WHERE id = ?");
                $stmt->bind_param("i", $festival_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if (!empty($row['featured_image']) && file_exists(__DIR__ . '/' . $row['featured_image'])) {
                        @unlink(__DIR__ . '/' . $row['featured_image']);
                    }
                    if (!empty($row['gallery_images'])) {
                        $gallery = json_decode($row['gallery_images'], true);
                        if (is_array($gallery)) {
                            foreach ($gallery as $img) {
                                if (file_exists(__DIR__ . '/' . $img)) {
                                    @unlink(__DIR__ . '/' . $img);
                                }
                            }
                        }
                    }
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM festivals WHERE id = ?");
                $stmt->bind_param("i", $festival_id);

                if ($stmt->execute()) {
                    $message = 'Festival deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting festival.';
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all festivals
$festivals = [];
$result = $conn->query("SELECT * FROM festivals ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $festivals[] = $row;
    }
}

// Stats
$total_festivals = 0;
$active_festivals = 0;
$featured_festivals = 0;

if ($result = $conn->query("SELECT COUNT(*) as c FROM festivals")) {
    $total_festivals = $result->fetch_assoc()['c'];
}
if ($result = $conn->query("SELECT COUNT(*) as c FROM festivals WHERE status = 'active'")) {
    $active_festivals = $result->fetch_assoc()['c'];
}
if ($result = $conn->query("SELECT COUNT(*) as c FROM festivals WHERE featured = 1 AND status = 'active'")) {
    $featured_festivals = $result->fetch_assoc()['c'];
}

// Sidebar stats
$total_tours = 0; $total_destinations = 0; $total_services = 0; $total_cars = 0;
$total_testimonials = 0; $total_activities = 0; $total_bookings = 0; $total_messages = 0;
$unread_messages = 0; $total_destination_weddings = 0; $total_package_lists = 0;

if ($r = $conn->query("SELECT COUNT(*) as c FROM tour_packages WHERE status = 'active'")) $total_tours = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM destinations WHERE status = 'active'")) $total_destinations = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM services WHERE status = 'active'")) $total_services = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM rental_cars WHERE status = 'available'")) $total_cars = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM testimonials WHERE status = 'approved' AND featured = 1")) $total_testimonials = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM popular_activities WHERE status = 'active'")) $total_activities = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM bookings")) $total_bookings = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM contact_messages")) $total_messages = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM contact_messages WHERE status = 'unread'")) $unread_messages = $r->fetch_assoc()['c'];
if ($r = @$conn->query("SELECT COUNT(*) as c FROM destination_weddings WHERE status = 1")) $total_destination_weddings = $r->fetch_assoc()['c'];
if ($r = $conn->query("SELECT COUNT(*) as c FROM tour_package_lists")) $total_package_lists = $r->fetch_assoc()['c'];

// Edit mode
$edit_mode = false;
$current_festival = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $fid = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM festivals WHERE id = ?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $current_festival = $res->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Festivals - Nature Nest Admin</title>
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
        .status-active { background: #28a745; color: white; }
        .status-draft { background: #ffc107; color: #000; }
        .status-inactive { background: #dc3545; color: white; }
        .featured-badge { background: #ffc107; color: #212529; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .price-badge { background: #6f42c1; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 600; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-icon { padding: 5px 10px; font-size: 12px; }
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
        .table img { max-width: 120px !important; max-height: 80px !important; width: 120px !important; height: 80px !important; object-fit: cover !important; border-radius: 5px; border: 2px solid #e9ecef; }
        .table-cell-image { width: 120px; min-width: 120px; max-width: 120px; }
        .image-placeholder { width: 120px; height: 80px; background: #e9ecef; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #6c757d; }
        @media (max-width: 768px) {
            .table-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .table-actions { width: 100%; }
        }
        .form-columns { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        @media (max-width: 1024px) { .form-columns { grid-template-columns: 1fr; } }
        .festival-badge { background: #e83e8c; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .location-badge { background: #17a2b8; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; display: inline-block; margin-top: 4px; }
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
                <div class="welcome-text"><i class="fas fa-dharmachakra me-2"></i>Festivals Management</div>
                <div>
                    <span class="text-muted me-3"><i class="fas fa-dharmachakra me-1"></i><?php echo $total_festivals; ?> Total Festivals</span>
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
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(232,62,140,.1); color:#e83e8c;"><i class="fas fa-dharmachakra"></i></div><div class="stat-number"><?php echo $total_festivals; ?></div><div class="stat-title">Total Festivals</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(40,167,69,.1); color:#28a745;"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?php echo $active_festivals; ?></div><div class="stat-title">Active Festivals</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(255,193,7,.1); color:#ffc107;"><i class="fas fa-star"></i></div><div class="stat-number"><?php echo $featured_festivals; ?></div><div class="stat-title">Featured Festivals</div></div>
                    <div class="stat-card"><div class="stat-icon" style="background: rgba(111,66,193,.1); color:#6f42c1;"><i class="fas fa-plus"></i></div><div class="stat-number">New</div><div class="stat-title"><a href="manage-festivals.php?action=add" style="color: inherit; text-decoration: none;">Add Festival <i class="fas fa-arrow-right"></i></a></div></div>
                </div>

                <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data" id="festivalForm">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_festival' : 'add_festival'; ?>">
                            <?php if ($edit_mode): ?><input type="hidden" name="festival_id" value="<?php echo $current_festival['id']; ?>"><?php endif; ?>

                            <div class="form-columns">
                                <div>
                                    <div class="form-group">
                                        <label for="name"><i class="fas fa-heading me-1"></i> Festival Name *</label>
                                        <input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['name']) : ''; ?>" required class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="description"><i class="fas fa-align-left me-1"></i> Description *</label>
                                        <textarea id="description" name="description" rows="4" required class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['description']) : ''; ?></textarea>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="location"><i class="fas fa-map-marker-alt me-1"></i> Location</label>
                                            <input type="text" id="location" name="location" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['location']) : ''; ?>" class="form-control" placeholder="e.g. Kohima, Kisama">
                                        </div>
                                        <div class="form-group">
                                            <label for="state"><i class="fas fa-map me-1"></i> State</label>
                                            <input type="text" id="state" name="state" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['state']) : ''; ?>" class="form-control" placeholder="e.g. Nagaland">
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="duration"><i class="fas fa-clock me-1"></i> Duration *</label>
                                            <input type="text" id="duration" name="duration" required value="<?php echo $edit_mode ? htmlspecialchars($current_festival['duration']) : ''; ?>" class="form-control" placeholder="e.g. 6 Nights / 7 Days">
                                        </div>
                                        <div class="form-group">
                                            <label for="festival_date"><i class="fas fa-calendar-day me-1"></i> Festival Date</label>
                                            <input type="text" id="festival_date" name="festival_date" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['festival_date']) : ''; ?>" class="form-control" placeholder="e.g. 1-10 December 2026">
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="best_time"><i class="fas fa-calendar-alt me-1"></i> Best Time</label>
                                            <input type="text" id="best_time" name="best_time" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['best_time']) : ''; ?>" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="starting_point"><i class="fas fa-flag me-1"></i> Starting Point</label>
                                            <input type="text" id="starting_point" name="starting_point" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['starting_point']) : ''; ?>" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="itinerary"><i class="fas fa-route me-1"></i> Detailed Itinerary</label>
                                        <textarea id="itinerary" name="itinerary" rows="8" class="form-control" placeholder='JSON format: [{"day":"1","title":"...","content":"..."}]'><?php echo $edit_mode ? htmlspecialchars($current_festival['itinerary']) : ''; ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="highlights"><i class="fas fa-star me-1"></i> Highlights</label>
                                        <textarea id="highlights" name="highlights" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['highlights']) : ''; ?></textarea>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label for="inclusions"><i class="fas fa-check-circle me-1"></i> Inclusions</label>
                                            <textarea id="inclusions" name="inclusions" rows="5" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['inclusions']) : ''; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="exclusions"><i class="fas fa-times-circle me-1"></i> Exclusions</label>
                                            <textarea id="exclusions" name="exclusions" rows="5" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['exclusions']) : ''; ?></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="faq"><i class="fas fa-question-circle me-1"></i> FAQs</label>
                                        <textarea id="faq" name="faq" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['faq']) : ''; ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="important_notes"><i class="fas fa-exclamation-triangle me-1"></i> Important Notes</label>
                                        <textarea id="important_notes" name="important_notes" rows="4" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['important_notes']) : ''; ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="permit_info"><i class="fas fa-id-card me-1"></i> Permit Info</label>
                                        <textarea id="permit_info" name="permit_info" rows="3" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['permit_info']) : ''; ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="cancellation_policy"><i class="fas fa-file-contract me-1"></i> Cancellation Policy</label>
                                        <textarea id="cancellation_policy" name="cancellation_policy" rows="3" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['cancellation_policy']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <div>
                                    <div class="form-group">
                                        <label for="featured_image"><i class="fas fa-image me-1"></i> Featured Image</label>
                                        <input type="file" id="featured_image" name="featured_image" accept="image/*" class="form-control">
                                        <small class="text-muted">Stored in: admin1/assets/upload/festivals/</small>
                                        <?php if ($edit_mode && !empty($current_festival['featured_image'])): ?>
                                            <img id="featured_image_preview" src="../<?php echo htmlspecialchars($current_festival['featured_image']); ?>" alt="Featured Image" class="trek-image-preview">
                                        <?php else: ?>
                                            <img id="featured_image_preview" src="" alt="Preview" class="trek-image-preview" style="display: none;">
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="gallery_images"><i class="fas fa-images me-1"></i> Gallery Images</label>
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*" class="form-control">
                                        <small class="text-muted">Stored in: admin1/assets/upload/festivals/gallery/</small>

                                        <?php if ($edit_mode && !empty($current_festival['gallery_images'])): ?>
                                            <?php $gallery = json_decode($current_festival['gallery_images'], true); ?>
                                            <?php if (!empty($gallery) && is_array($gallery)): ?>
                                                <div class="gallery-container" id="existing_gallery">
                                                    <?php foreach ($gallery as $img): ?>
                                                        <div class="gallery-item">
                                                            <img src="../<?php echo htmlspecialchars($img); ?>" alt="Gallery" class="gallery-image">
                                                            <div class="delete-gallery-image" onclick="markImageForDeletion(this, '<?php echo htmlspecialchars($img); ?>')">
                                                                <i class="fas fa-times"></i>
                                                            </div>
                                                            <input type="hidden" name="delete_gallery_images[]" value="" disabled>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <div class="gallery-container" id="new_gallery_preview"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="price"><i class="fas fa-rupee-sign me-1"></i> Price (Rs)</label>
                                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_mode ? $current_festival['price'] : '0'; ?>" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="display_order"><i class="fas fa-sort-numeric-down me-1"></i> Display Order</label>
                                        <input type="number" id="display_order" name="display_order" value="<?php echo $edit_mode ? $current_festival['display_order'] : '0'; ?>" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <?php $cur_status = $edit_mode ? $current_festival['status'] : 'active'; ?>
                                            <option value="active" <?php echo $cur_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="draft" <?php echo $cur_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="inactive" <?php echo $cur_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="form-check-group">
                                        <div class="form-check">
                                            <input type="checkbox" id="featured" name="featured" value="1" class="form-check-input" <?php echo ($edit_mode && $current_festival['featured'] == 1) ? 'checked' : ''; ?>>
                                            <label for="featured"><i class="fas fa-star me-1"></i> Featured Festival</label>
                                        </div>
                                    </div>

                                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                        <h4 style="margin-bottom: 15px;"><i class="fas fa-search"></i> SEO Settings</h4>
                                        <div class="form-group">
                                            <label>Meta Title</label>
                                            <input type="text" name="meta_title" value="<?php echo $edit_mode ? htmlspecialchars($current_festival['meta_title']) : ''; ?>" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Meta Description</label>
                                            <textarea name="meta_description" rows="2" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['meta_description']) : ''; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Meta Keywords</label>
                                            <textarea name="meta_keywords" rows="2" class="form-control"><?php echo $edit_mode ? htmlspecialchars($current_festival['meta_keywords']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Update Festival' : 'Add Festival'; ?>
                                </button>
                                <a href="manage-festivals.php" class="btn btn-outline"><i class="fas fa-times me-1"></i>Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <?php if (empty($festivals)): ?>
                        <div class="empty-state">
                            <i class="fas fa-dharmachakra"></i>
                            <h3>No Festivals Found</h3>
                            <p>Get started by adding your first festival.</p>
                            <a href="manage-festivals.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add First Festival</a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <span><i class="fas fa-list"></i> All Festivals</span>
                                <div class="table-actions">
                                    <a href="manage-festivals.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add New</a>
                                </div>
                            </div>
                            <div class="table-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width:150px;">Image</th>
                                            <th>Festival Details</th>
                                            <th style="width:130px;">Duration</th>
                                            <th style="width:150px;">Festival Date</th>
                                            <th style="width:100px;">Price</th>
                                            <th style="width:100px;">Status</th>
                                            <th style="width:80px;">Featured</th>
                                            <th style="width:150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($festivals as $fest): ?>
                                        <tr>
                                            <td class="table-cell-image">
                                                <?php if (!empty($fest['featured_image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($fest['featured_image']); ?>" alt="">
                                                <?php else: ?>
                                                    <div class="image-placeholder"><i class="fas fa-image"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($fest['name']); ?></strong><br>
                                                <small class="text-muted">/<?php echo htmlspecialchars($fest['slug']); ?></small>
                                                <?php if (!empty($fest['location'])): ?>
                                                    <div><small><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fest['location']); ?></small></div>
                                                <?php endif; ?>
                                                <?php if (!empty($fest['state'])): ?>
                                                    <span class="location-badge"><i class="fas fa-map"></i> <?php echo htmlspecialchars($fest['state']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="festival-badge"><?php echo htmlspecialchars($fest['duration']); ?></span></td>
                                            <td>
                                                <?php if (!empty($fest['festival_date'])): ?>
                                                    <small><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($fest['festival_date']); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="price-badge">Rs <?php echo number_format($fest['price'], 2); ?></span></td>
                                            <td><span class="status-badge status-<?php echo htmlspecialchars($fest['status']); ?>"><?php echo ucfirst($fest['status']); ?></span></td>
                                            <td><?php if ($fest['featured']): ?><span class="featured-badge"><i class="fas fa-star"></i></span><?php endif; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="manage-festivals.php?action=edit&id=<?php echo $fest['id']; ?>" class="btn btn-icon btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="../pages/festival-detail.php?slug=<?php echo urlencode($fest['slug']); ?>" target="_blank" class="btn btn-icon btn-info" title="View"><i class="fas fa-eye"></i></a>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this festival?');">
                                                        <input type="hidden" name="action" value="delete_festival">
                                                        <input type="hidden" name="festival_id" value="<?php echo $fest['id']; ?>">
                                                        <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                                <div class="mt-2"><span class="display-order-badge">Order: <?php echo $fest['display_order']; ?></span></div>
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
        const file = e.target.files[0];
        const preview = document.getElementById('featured_image_preview');
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = ev => {
                preview.src = ev.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('gallery_images')?.addEventListener('change', function(e) {
        const files = e.target.files;
        const previewContainer = document.getElementById('new_gallery_preview');
        previewContainer.innerHTML = '';
        for (let i = 0; i < files.length; i++) {
            const reader = new FileReader();
            reader.onload = ev => {
                const div = document.createElement('div');
                div.className = 'gallery-item';
                div.innerHTML = `<img src="${ev.target.result}" class="gallery-image">`;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(files[i]);
        }
    });

    function markImageForDeletion(el, imagePath) {
        const container = el.closest('.gallery-item');
        if (!container) return;
        const hidden = container.querySelector('input[name="delete_gallery_images[]"]');
        if (hidden) {
            hidden.value = imagePath;
            hidden.disabled = false;
        }
        container.style.opacity = '0.4';
        container.style.pointerEvents = 'none';
    }

    document.getElementById('festivalForm')?.addEventListener('submit', function(e) {
        const name = document.getElementById('name')?.value.trim();
        const desc = document.getElementById('description')?.value.trim();
        const dur  = document.getElementById('duration')?.value.trim();
        if (!name || !desc || !dur) {
            e.preventDefault();
            alert('Please fill in all required fields (Name, Description, Duration).');
            return false;
        }
    });
    </script>
</body>
</html>
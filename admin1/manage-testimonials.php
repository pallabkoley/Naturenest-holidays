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

// Create testimonials table if not exists (with video support)
$create_table_sql = "CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    client_location VARCHAR(255),
    testimonial_text TEXT NOT NULL,
    client_image VARCHAR(255),
    video_url VARCHAR(500),  -- New field for video URL
    video_thumbnail VARCHAR(255),  -- New field for video thumbnail
    video_type ENUM('youtube', 'upload', 'none') DEFAULT 'none',  -- New field for video type
    rating INT DEFAULT 5,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    featured TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_testimonial':
                // Add new testimonial
                $client_name = trim($_POST['client_name'] ?? '');
                $client_location = trim($_POST['client_location'] ?? '');
                $testimonial_text = trim($_POST['testimonial_text'] ?? '');
                $rating = intval($_POST['rating'] ?? 5);
                $status = $_POST['status'] ?? 'pending';
                $featured = isset($_POST['featured']) ? 1 : 0;
                $video_type = $_POST['video_type'] ?? 'none';
                $youtube_url = trim($_POST['youtube_url'] ?? '');
                
                // Handle client image upload
                $client_image = '';
                if (isset($_FILES['client_image']) && $_FILES['client_image']['error'] === 0) {
                    $client_image = handleFileUpload($_FILES['client_image'], 'testimonials', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5242880);
                }
                
                // Handle video upload
                $video_url = '';
                $video_thumbnail = '';
                
                if ($video_type === 'youtube' && !empty($youtube_url)) {
                    $video_url = $youtube_url;
                    // Extract YouTube thumbnail
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches)) {
                        $video_id = $matches[1];
                        $video_thumbnail = "https://img.youtube.com/vi/$video_id/maxresdefault.jpg";
                    }
                } elseif ($video_type === 'upload' && isset($_FILES['video_upload']) && $_FILES['video_upload']['error'] === 0) {
                    $video_url = handleFileUpload($_FILES['video_upload'], 'testimonials/videos', ['mp4', 'mov', 'avi', 'wmv', 'webm'], 104857600); // 100MB max for videos
                    
                    // Generate thumbnail from video (first frame)
                    if ($video_url) {
                        $video_thumbnail = generateVideoThumbnail($video_url, 'testimonials');
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO testimonials (client_name, client_location, testimonial_text, client_image, video_url, video_thumbnail, video_type, rating, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sssssssisi", $client_name, $client_location, $testimonial_text, $client_image, $video_url, $video_thumbnail, $video_type, $rating, $status, $featured);
                
                if ($stmt->execute()) {
                    $message = 'Testimonial added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding testimonial: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'update_testimonial':
                // Update existing testimonial
                $testimonial_id = intval($_POST['testimonial_id'] ?? 0);
                $client_name = trim($_POST['client_name'] ?? '');
                $client_location = trim($_POST['client_location'] ?? '');
                $testimonial_text = trim($_POST['testimonial_text'] ?? '');
                $rating = intval($_POST['rating'] ?? 5);
                $status = $_POST['status'] ?? 'pending';
                $featured = isset($_POST['featured']) ? 1 : 0;
                $video_type = $_POST['video_type'] ?? 'none';
                $youtube_url = trim($_POST['youtube_url'] ?? '');
                
                // Get current testimonial data
                $current_testimonial = [];
                if ($testimonial_id > 0) {
                    $result = $conn->query("SELECT client_image, video_url, video_thumbnail, video_type FROM testimonials WHERE id = $testimonial_id");
                    if ($result) {
                        $current_testimonial = $result->fetch_assoc();
                    }
                }
                
                // Handle client image update
                $client_image = $current_testimonial['client_image'] ?? '';
                if (isset($_FILES['client_image']) && $_FILES['client_image']['error'] === 0) {
                    // Delete old image if exists
                    if ($client_image && file_exists('../assets/uploads/testimonials/' . $client_image)) {
                        unlink('../assets/uploads/testimonials/' . $client_image);
                    }
                    $client_image = handleFileUpload($_FILES['client_image'], 'testimonials', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5242880);
                }
                
                // Handle video update
                $video_url = $current_testimonial['video_url'] ?? '';
                $video_thumbnail = $current_testimonial['video_thumbnail'] ?? '';
                
                if ($video_type === 'none') {
                    // Delete existing video files
                    if ($video_url && ($current_testimonial['video_type'] ?? '') === 'upload') {
                        if (file_exists('../assets/uploads/testimonials/videos/' . basename($video_url))) {
                            unlink('../assets/uploads/testimonials/videos/' . basename($video_url));
                        }
                        if ($video_thumbnail && file_exists('../assets/uploads/testimonials/' . basename($video_thumbnail))) {
                            unlink('../assets/uploads/testimonials/' . basename($video_thumbnail));
                        }
                    }
                    $video_url = '';
                    $video_thumbnail = '';
                } elseif ($video_type === 'youtube') {
                    // Delete uploaded video if exists
                    if (($current_testimonial['video_type'] ?? '') === 'upload') {
                        if ($video_url && file_exists('../assets/uploads/testimonials/videos/' . basename($video_url))) {
                            unlink('../assets/uploads/testimonials/videos/' . basename($video_url));
                        }
                        if ($video_thumbnail && file_exists('../assets/uploads/testimonials/' . basename($video_thumbnail))) {
                            unlink('../assets/uploads/testimonials/' . basename($video_thumbnail));
                        }
                    }
                    
                    $video_url = $youtube_url;
                    // Extract YouTube thumbnail
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches)) {
                        $video_id = $matches[1];
                        $video_thumbnail = "https://img.youtube.com/vi/$video_id/maxresdefault.jpg";
                    }
                } elseif ($video_type === 'upload' && isset($_FILES['video_upload']) && $_FILES['video_upload']['error'] === 0) {
                    // Delete old uploaded video if exists
                    if (($current_testimonial['video_type'] ?? '') === 'upload') {
                        if ($video_url && file_exists('../assets/uploads/testimonials/videos/' . basename($video_url))) {
                            unlink('../assets/uploads/testimonials/videos/' . basename($video_url));
                        }
                        if ($video_thumbnail && file_exists('../assets/uploads/testimonials/' . basename($video_thumbnail))) {
                            unlink('../assets/uploads/testimonials/' . basename($video_thumbnail));
                        }
                    }
                    
                    $video_url = handleFileUpload($_FILES['video_upload'], 'testimonials/videos', ['mp4', 'mov', 'avi', 'wmv', 'webm'], 104857600);
                    
                    // Generate thumbnail from video
                    if ($video_url) {
                        $video_thumbnail = generateVideoThumbnail($video_url, 'testimonials');
                    }
                }
                
                $stmt = $conn->prepare("UPDATE testimonials SET client_name = ?, client_location = ?, testimonial_text = ?, client_image = ?, video_url = ?, video_thumbnail = ?, video_type = ?, rating = ?, status = ?, featured = ? WHERE id = ?");
                
                $stmt->bind_param("sssssssisii", $client_name, $client_location, $testimonial_text, $client_image, $video_url, $video_thumbnail, $video_type, $rating, $status, $featured, $testimonial_id);
                
                if ($stmt->execute()) {
                    $message = 'Testimonial updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating testimonial: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
                
            case 'delete_testimonial':
                // Delete testimonial
                $testimonial_id = intval($_POST['testimonial_id'] ?? 0);
                
                if ($testimonial_id > 0) {
                    // Get testimonial data to delete files
                    $result = $conn->query("SELECT client_image, video_url, video_thumbnail, video_type FROM testimonials WHERE id = $testimonial_id");
                    if ($result && $row = $result->fetch_assoc()) {
                        // Delete client image
                        if ($row['client_image']) {
                            if (file_exists('../assets/uploads/testimonials/' . $row['client_image'])) {
                                unlink('../assets/uploads/testimonials/' . $row['client_image']);
                            }
                        }
                        
                        // Delete uploaded video files
                        if ($row['video_type'] === 'upload') {
                            if ($row['video_url']) {
                                if (file_exists('../assets/uploads/testimonials/videos/' . basename($row['video_url']))) {
                                    unlink('../assets/uploads/testimonials/videos/' . basename($row['video_url']));
                                }
                            }
                            if ($row['video_thumbnail']) {
                                if (file_exists('../assets/uploads/testimonials/' . basename($row['video_thumbnail']))) {
                                    unlink('../assets/uploads/testimonials/' . basename($row['video_thumbnail']));
                                }
                            }
                        }
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
                    $stmt->bind_param("i", $testimonial_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Testimonial deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error deleting testimonial: ' . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
                break;
                
            case 'approve_testimonial':
                // Approve testimonial
                $testimonial_id = intval($_POST['testimonial_id'] ?? 0);
                
                if ($testimonial_id > 0) {
                    $stmt = $conn->prepare("UPDATE testimonials SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $testimonial_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Testimonial approved successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error approving testimonial: ' . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
                break;
                
            case 'toggle_featured':
                // Toggle featured status
                $testimonial_id = intval($_POST['testimonial_id'] ?? 0);
                
                if ($testimonial_id > 0) {
                    // Get current featured status
                    $result = $conn->query("SELECT featured FROM testimonials WHERE id = $testimonial_id");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $new_featured = $row['featured'] ? 0 : 1;
                        
                        $stmt = $conn->prepare("UPDATE testimonials SET featured = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_featured, $testimonial_id);
                        
                        if ($stmt->execute()) {
                            $message = $new_featured ? 'Testimonial marked as featured!' : 'Testimonial unfeatured!';
                            $message_type = 'success';
                        } else {
                            $message = 'Error updating featured status: ' . $stmt->error;
                            $message_type = 'error';
                        }
                        $stmt->close();
                    }
                }
                break;
        }
    }
}

// Helper function for file uploads
function handleFileUpload($file, $subfolder, $allowed_types, $max_size) {
    if ($file['error'] !== 0) return '';
    
    $upload_dir = "../assets/uploads/$subfolder/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $unique_name;
    
    if (!in_array($file_extension, $allowed_types)) {
        return '';
    }
    
    if ($file['size'] > $max_size) {
        return '';
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $unique_name;
    }
    
    return '';
}

// Helper function to generate video thumbnail
function generateVideoThumbnail($video_filename, $subfolder) {
    $video_path = "../assets/uploads/$subfolder/videos/" . $video_filename;
    $thumbnail_path = "../assets/uploads/$subfolder/" . pathinfo($video_filename, PATHINFO_FILENAME) . '.jpg';
    
    // Check if video file exists
    if (!file_exists($video_path)) {
        return '';
    }
    
    // Using FFmpeg command (make sure FFmpeg is installed on your server)
    $command = "ffmpeg -i " . escapeshellarg($video_path) . " -ss 00:00:01 -vframes 1 -q:v 2 " . escapeshellarg($thumbnail_path);
    exec($command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($thumbnail_path)) {
        return basename($thumbnail_path);
    }
    
    // Fallback: return a default thumbnail
    return '';
}

// Get status filter
$status_filter = $_GET['status'] ?? '';
$featured_filter = isset($_GET['featured']) ? intval($_GET['featured']) : 0;

// Get all testimonials for listing
$testimonials = [];
$sql = "SELECT * FROM testimonials WHERE 1=1";

$params = [];
$types = '';

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($featured_filter) {
    $sql .= " AND featured = ?";
    $params[] = $featured_filter;
    $types .= 'i';
}

$sql .= " ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $testimonials[] = $row;
    }
}

// Get testimonial count for stats
$total_testimonials = 0;
$approved_testimonials = 0;
$pending_testimonials = 0;
$rejected_testimonials = 0;
$featured_testimonials = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials");
if ($result) {
    $row = $result->fetch_assoc();
    $total_testimonials = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'approved'");
if ($result) {
    $row = $result->fetch_assoc();
    $approved_testimonials = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    $pending_testimonials = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'rejected'");
if ($result) {
    $row = $result->fetch_assoc();
    $rejected_testimonials = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE featured = 1 AND status = 'approved'");
if ($result) {
    $row = $result->fetch_assoc();
    $featured_testimonials = $row['count'];
}

// Get other stats for sidebar
$total_destinations = 0;
$total_tours = 0;
$total_services = 0;
$total_cars = 0;
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

$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'");
if ($result) { $row = $result->fetch_assoc(); $total_cars = $row['count']; }

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
$current_testimonial = null;
if (isset($_GET['action']) && ($_GET['action'] === 'edit') && isset($_GET['id'])) {
    $testimonial_id = intval($_GET['id']);
    if ($testimonial_id > 0) {
        $result = $conn->query("SELECT * FROM testimonials WHERE id = $testimonial_id");
        if ($result && $result->num_rows > 0) {
            $current_testimonial = $result->fetch_assoc();
            $edit_mode = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for manage testimonials page */
        .testimonial-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            margin: 10px 0;
            object-fit: cover;
        }
        
        .video-preview {
            width: 200px;
            height: 120px;
            border-radius: 8px;
            border: 2px solid var(--primary-color);
            margin: 10px 0;
            object-fit: cover;
            display: none;
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .rating-star {
            font-size: 1.2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .rating-star.active {
            color: #ffc107;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        
        .featured-badge {
            background: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .testimonial-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .testimonial-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .client-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            margin-bottom: 10px;
        }
        
        .video-container {
            position: relative;
            margin: 10px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .video-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .video-play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
        }
        
        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .video-modal-content {
            max-width: 90%;
            max-height: 90%;
        }
        
        .video-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .video-option {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .video-option.active {
            border-color: var(--primary-color);
            background: rgba(198, 107, 5, 0.1);
        }
        
        .video-option input[type="radio"] {
            margin-right: 10px;
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
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
            text-decoration: none;
            color: #333;
        }
        
        .testimonial-text {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .testimonial-text.expanded {
            max-height: none;
        }
        
        .read-more {
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 5px;
            display: inline-block;
        }
        
        .client-initials {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .video-info-badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        
        .video-fields {
            display: none;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        
        .video-type-selector {
            margin-bottom: 20px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
        
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
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
                    <i class="fas fa-comment-dots me-2"></i>Testimonials Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-comment-dots me-1"></i>
                        <?php echo $total_testimonials; ?> Total Testimonials
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
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_testimonials; ?></div>
                        <div class="stat-title">Total Testimonials</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $approved_testimonials; ?></div>
                        <div class="stat-title">Approved</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_testimonials; ?></div>
                        <div class="stat-title">Pending Review</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number"><?php echo $featured_testimonials; ?></div>
                        <div class="stat-title">Featured</div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                    <!-- Add Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_testimonial">
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="client_name"><i class="fas fa-user me-1"></i> Client Name *</label>
                                        <input type="text" id="client_name" name="client_name" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_testimonial['client_name']) : ''; ?>" 
                                               required class="form-control" placeholder="Enter client name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="client_location"><i class="fas fa-map-marker-alt me-1"></i> Client Location</label>
                                        <input type="text" id="client_location" name="client_location" 
                                               value="<?php echo $edit_mode ? htmlspecialchars($current_testimonial['client_location']) : ''; ?>" 
                                               class="form-control" placeholder="e.g., Kolkata, India">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="testimonial_text"><i class="fas fa-comment me-1"></i> Testimonial Text *</label>
                                        <textarea id="testimonial_text" name="testimonial_text" rows="8" required class="form-control" 
                                                  placeholder="Enter the testimonial text"><?php echo $edit_mode ? htmlspecialchars($current_testimonial['testimonial_text']) : ''; ?></textarea>
                                        <small class="text-muted">What did the client say about their experience?</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="fas fa-star me-1"></i> Rating</label>
                                        <div class="rating-stars" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php 
                                                $is_active = $edit_mode ? ($i <= ($current_testimonial['rating'] ?? 5)) : ($i == 5);
                                                ?>
                                                <div class="rating-star <?php echo $is_active ? 'active' : ''; ?>" 
                                                     data-value="<?php echo $i; ?>">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" id="rating" name="rating" 
                                               value="<?php echo $edit_mode ? ($current_testimonial['rating'] ?? 5) : 5; ?>">
                                        <small class="text-muted">Click on stars to set rating (1-5)</small>
                                    </div>
                                    
                                    <!-- Video Section -->
                                    <div class="form-group video-type-selector">
                                        <label><i class="fas fa-video me-1"></i> Add Video Testimonial</label>
                                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                                            <label class="video-option" style="flex: 1;">
                                                <input type="radio" name="video_type" value="none" checked onchange="toggleVideoFields()">
                                                <i class="fas fa-ban me-2"></i> No Video
                                            </label>
                                            <label class="video-option" style="flex: 1;">
                                                <input type="radio" name="video_type" value="youtube" onchange="toggleVideoFields()">
                                                <i class="fab fa-youtube me-2"></i> YouTube Video
                                            </label>
                                            <label class="video-option" style="flex: 1;">
                                                <input type="radio" name="video_type" value="upload" onchange="toggleVideoFields()">
                                                <i class="fas fa-upload me-2"></i> Upload Video
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="youtube-fields" class="video-fields" style="display: none;">
                                        <div class="form-group">
                                            <label for="youtube_url"><i class="fab fa-youtube me-1"></i> YouTube Video URL</label>
                                            <input type="url" id="youtube_url" name="youtube_url" 
                                                   class="form-control" placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
                                            <small class="text-muted">Enter the full YouTube video URL</small>
                                        </div>
                                    </div>
                                    
                                    <div id="upload-fields" class="video-fields" style="display: none;">
                                        <div class="form-group">
                                            <label for="video_upload"><i class="fas fa-file-video me-1"></i> Upload Video</label>
                                            <input type="file" id="video_upload" name="video_upload" 
                                                   accept="video/*" class="form-control">
                                            <small class="text-muted">Max file size: 100MB (mp4, mov, avi, wmv, webm)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Settings & Image -->
                                <div>
                                    <div class="form-group">
                                        <label for="client_image"><i class="fas fa-image me-1"></i> Client Photo</label>
                                        <input type="file" id="client_image" name="client_image" accept="image/*" class="form-control">
                                        <img id="client_image_preview" src="" alt="Preview" class="testimonial-image-preview" style="display: none;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="pending">Pending Review</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="featured" name="featured">
                                            <label class="form-check-label" for="featured">
                                                <i class="fas fa-star me-1"></i> Mark as Featured
                                            </label>
                                        </div>
                                        <small class="text-muted">Featured testimonials appear on the homepage</small>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> Add Testimonial
                                        </button>
                                        <a href="manage-testimonials.php" class="btn btn-outline">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && $edit_mode): ?>
                    <!-- Edit Form -->
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_testimonial">
                            <input type="hidden" name="testimonial_id" value="<?php echo $current_testimonial['id']; ?>">
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                                <!-- Left Column - Main Details -->
                                <div>
                                    <div class="form-group">
                                        <label for="client_name"><i class="fas fa-user me-1"></i> Client Name *</label>
                                        <input type="text" id="client_name" name="client_name" 
                                               value="<?php echo htmlspecialchars($current_testimonial['client_name']); ?>" 
                                               required class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="client_location"><i class="fas fa-map-marker-alt me-1"></i> Client Location</label>
                                        <input type="text" id="client_location" name="client_location" 
                                               value="<?php echo htmlspecialchars($current_testimonial['client_location']); ?>" 
                                               class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="testimonial_text"><i class="fas fa-comment me-1"></i> Testimonial Text *</label>
                                        <textarea id="testimonial_text" name="testimonial_text" rows="8" required class="form-control"><?php echo htmlspecialchars($current_testimonial['testimonial_text']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="fas fa-star me-1"></i> Rating</label>
                                        <div class="rating-stars" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php $is_active = ($i <= $current_testimonial['rating']); ?>
                                                <div class="rating-star <?php echo $is_active ? 'active' : ''; ?>" 
                                                     data-value="<?php echo $i; ?>">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" id="rating" name="rating" value="<?php echo $current_testimonial['rating']; ?>">
                                    </div>
                                    
                                    <!-- Video Section -->
                                    <div class="form-group video-type-selector">
                                        <label><i class="fas fa-video me-1"></i> Video Testimonial</label>
                                        <div style="display: flex; gap: 15px; margin-top: 10px;">
                                            <label class="video-option <?php echo ($current_testimonial['video_type'] === 'none') ? 'active' : ''; ?>" style="flex: 1;">
                                                <input type="radio" name="video_type" value="none" 
                                                       <?php echo ($current_testimonial['video_type'] === 'none') ? 'checked' : ''; ?> 
                                                       onchange="toggleVideoFields()">
                                                <i class="fas fa-ban me-2"></i> No Video
                                            </label>
                                            <label class="video-option <?php echo ($current_testimonial['video_type'] === 'youtube') ? 'active' : ''; ?>" style="flex: 1;">
                                                <input type="radio" name="video_type" value="youtube" 
                                                       <?php echo ($current_testimonial['video_type'] === 'youtube') ? 'checked' : ''; ?> 
                                                       onchange="toggleVideoFields()">
                                                <i class="fab fa-youtube me-2"></i> YouTube Video
                                            </label>
                                            <label class="video-option <?php echo ($current_testimonial['video_type'] === 'upload') ? 'active' : ''; ?>" style="flex: 1;">
                                                <input type="radio" name="video_type" value="upload" 
                                                       <?php echo ($current_testimonial['video_type'] === 'upload') ? 'checked' : ''; ?> 
                                                       onchange="toggleVideoFields()">
                                                <i class="fas fa-upload me-2"></i> Upload Video
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="youtube-fields" class="video-fields" 
                                         style="display: <?php echo ($current_testimonial['video_type'] === 'youtube') ? 'block' : 'none'; ?>;">
                                        <div class="form-group">
                                            <label for="youtube_url"><i class="fab fa-youtube me-1"></i> YouTube Video URL</label>
                                            <input type="url" id="youtube_url" name="youtube_url" 
                                                   value="<?php echo htmlspecialchars($current_testimonial['video_url'] ?? ''); ?>"
                                                   class="form-control" placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
                                        </div>
                                    </div>
                                    
                                    <div id="upload-fields" class="video-fields" 
                                         style="display: <?php echo ($current_testimonial['video_type'] === 'upload') ? 'block' : 'none'; ?>;">
                                        <div class="form-group">
                                            <label for="video_upload"><i class="fas fa-file-video me-1"></i> Upload Video</label>
                                            <input type="file" id="video_upload" name="video_upload" 
                                                   accept="video/*" class="form-control">
                                            <?php if ($current_testimonial['video_type'] === 'upload' && $current_testimonial['video_url']): ?>
                                                <small class="text-muted">Current video: <?php echo basename($current_testimonial['video_url']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Max file size: 100MB (mp4, mov, avi, wmv, webm)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Column - Settings & Image -->
                                <div>
                                    <div class="form-group">
                                        <label for="client_image"><i class="fas fa-image me-1"></i> Client Photo</label>
                                        <input type="file" id="client_image" name="client_image" accept="image/*" class="form-control">
                                        <?php if (!empty($current_testimonial['client_image'])): ?>
                                            <img id="client_image_preview" src="<?php echo SITE_URL; ?>/assets/uploads/testimonials/<?php echo htmlspecialchars($current_testimonial['client_image']); ?>" 
                                                 alt="Client Photo" class="testimonial-image-preview">
                                            <br>
                                            <small>Current photo</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-toggle-on me-1"></i> Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <option value="pending" <?php echo ($current_testimonial['status'] == 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                                            <option value="approved" <?php echo ($current_testimonial['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo ($current_testimonial['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="featured" name="featured" 
                                                   <?php echo $current_testimonial['featured'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="featured">
                                                <i class="fas fa-star me-1"></i> Mark as Featured
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>
                                            Update Testimonial
                                        </button>
                                        <a href="manage-testimonials.php" class="btn btn-outline">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Testimonial List View -->
                    <div class="filter-buttons">
                        <a href="manage-testimonials.php" class="filter-btn <?php echo !$status_filter && !$featured_filter ? 'active' : ''; ?>">
                            All (<?php echo $total_testimonials; ?>)
                        </a>
                        <a href="manage-testimonials.php?status=approved" class="filter-btn <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">
                            Approved (<?php echo $approved_testimonials; ?>)
                        </a>
                        <a href="manage-testimonials.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                            Pending (<?php echo $pending_testimonials; ?>)
                        </a>
                        <a href="manage-testimonials.php?status=rejected" class="filter-btn <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">
                            Rejected (<?php echo $rejected_testimonials; ?>)
                        </a>
                        <a href="manage-testimonials.php?featured=1" class="filter-btn <?php echo $featured_filter ? 'active' : ''; ?>">
                            Featured (<?php echo $featured_testimonials; ?>)
                        </a>
                    </div>
                    
                    <?php if (empty($testimonials)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-dots"></i>
                            <h3>No Testimonials Found</h3>
                            <p>Get started by adding your first testimonial.</p>
                            <a href="manage-testimonials.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add First Testimonial
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <div class="table-header">
                                <i class="fas fa-list"></i> All Testimonials
                                <div class="table-actions">
                                    <a href="manage-testimonials.php?action=add" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Add New
                                    </a>
                                </div>
                            </div>
                            <div class="table-body">
                                <div class="row">
                                    <?php foreach ($testimonials as $testimonial): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="testimonial-card">
                                                <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 15px;">
                                                    <?php if (!empty($testimonial['client_image'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>/assets/uploads/testimonials/<?php echo htmlspecialchars($testimonial['client_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" 
                                                             class="client-image">
                                                    <?php else: ?>
                                                        <div class="client-initials">
                                                            <?php echo strtoupper(substr($testimonial['client_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div style="flex: 1;">
                                                        <h5 style="margin-bottom: 5px;">
                                                            <?php echo htmlspecialchars($testimonial['client_name']); ?>
                                                            <?php if (($testimonial['video_type'] ?? 'none') !== 'none'): ?>
                                                                <span class="video-info-badge" title="Has video testimonial">
                                                                    <i class="fas fa-video"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </h5>
                                                        <?php if (!empty($testimonial['client_location'])): ?>
                                                            <p style="color: #6c757d; margin-bottom: 5px;">
                                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                                <?php echo htmlspecialchars($testimonial['client_location']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <div style="display: flex; gap: 3px; margin-bottom: 10px;">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <?php if ($i <= $testimonial['rating']): ?>
                                                                    <i class="fas fa-star text-warning"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star text-warning"></i>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php 
                                                $video_type = $testimonial['video_type'] ?? 'none';
                                                $video_thumbnail = $testimonial['video_thumbnail'] ?? '';
                                                $video_url = $testimonial['video_url'] ?? '';
                                                
                                                if ($video_type !== 'none' && ($video_thumbnail || $video_url)): ?>
                                                    <div class="video-container">
                                                        <?php if (!empty($video_thumbnail)): ?>
                                                            <img src="<?php echo htmlspecialchars($video_thumbnail); ?>" 
                                                                 alt="Video testimonial" 
                                                                 class="video-thumbnail"
                                                                 onclick="openVideoModal('<?php echo htmlspecialchars($video_url); ?>', '<?php echo htmlspecialchars($video_type); ?>')">
                                                            <div class="video-play-button" 
                                                                 onclick="openVideoModal('<?php echo htmlspecialchars($video_url); ?>', '<?php echo htmlspecialchars($video_type); ?>')">
                                                                <i class="fas fa-play"></i>
                                                            </div>
                                                        <?php else: ?>
                                                            <div style="height: 150px; background: #333; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer;"
                                                                 onclick="openVideoModal('<?php echo htmlspecialchars($video_url); ?>', '<?php echo htmlspecialchars($video_type); ?>')">
                                                                <i class="fas fa-play-circle" style="font-size: 48px; color: white;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="testimonial-text">
                                                    <p style="margin-bottom: 10px; color: #555;">
                                                        <i class="fas fa-quote-left me-2" style="color: var(--primary-color);"></i>
                                                        <?php echo htmlspecialchars($testimonial['testimonial_text']); ?>
                                                        <i class="fas fa-quote-right ms-2" style="color: var(--primary-color);"></i>
                                                    </p>
                                                </div>
                                                
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                                    <div style="display: flex; gap: 10px; align-items: center;">
                                                        <span class="status-badge status-<?php echo $testimonial['status']; ?>">
                                                            <?php echo ucfirst($testimonial['status']); ?>
                                                        </span>
                                                        <?php if ($testimonial['featured']): ?>
                                                            <span class="featured-badge status-badge">
                                                                <i class="fas fa-star me-1"></i>Featured
                                                            </span>
                                                        <?php endif; ?>
                                                        <small style="color: #6c757d;">
                                                            <?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="action-buttons">
                                                        <?php if ($testimonial['status'] === 'pending'): ?>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Approve this testimonial?');">
                                                                <input type="hidden" name="action" value="approve_testimonial">
                                                                <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                                <button type="submit" class="btn btn-icon btn-success" title="Approve">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('<?php echo $testimonial['featured'] ? 'Unfeature' : 'Mark as featured'; ?> this testimonial?');">
                                                            <input type="hidden" name="action" value="toggle_featured">
                                                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                            <button type="submit" class="btn btn-icon btn-warning" title="<?php echo $testimonial['featured'] ? 'Unfeature' : 'Mark as Featured'; ?>">
                                                                <i class="fas fa-star"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="manage-testimonials.php?action=edit&id=<?php echo $testimonial['id']; ?>" 
                                                           class="btn btn-icon btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this testimonial?');">
                                                            <input type="hidden" name="action" value="delete_testimonial">
                                                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Video Modal -->
    <div id="videoModal" class="video-modal">
        <button class="video-modal-close" onclick="closeVideoModal()">&times;</button>
        <div class="video-modal-content">
            <iframe id="videoPlayer" width="100%" height="500" style="border: none;" allowfullscreen></iframe>
            <video id="uploadedVideoPlayer" controls style="width: 100%; max-height: 80vh; display: none;"></video>
        </div>
    </div>

    <!-- Local JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <script>
    // Image preview for client image
    document.getElementById('client_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('client_image_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Star rating system
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const ratingInput = document.getElementById('rating');
            
            // Update hidden input
            ratingInput.value = value;
            
            // Update star display
            document.querySelectorAll('.rating-star').forEach(s => {
                if (parseInt(s.getAttribute('data-value')) <= value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
    
    // Toggle testimonial text expansion
    document.querySelectorAll('.testimonial-text').forEach(textDiv => {
        const text = textDiv.querySelector('p');
        if (text.scrollHeight > text.clientHeight) {
            const readMore = document.createElement('a');
            readMore.className = 'read-more';
            readMore.textContent = 'Read more';
            readMore.addEventListener('click', function() {
                textDiv.classList.toggle('expanded');
                this.textContent = textDiv.classList.contains('expanded') ? 'Read less' : 'Read more';
            });
            textDiv.appendChild(readMore);
        }
    });
    
    // Video functionality
    function toggleVideoFields() {
        const videoType = document.querySelector('input[name="video_type"]:checked').value;
        
        // Hide all video fields
        document.getElementById('youtube-fields').style.display = 'none';
        document.getElementById('upload-fields').style.display = 'none';
        
        // Show relevant fields
        if (videoType === 'youtube') {
            document.getElementById('youtube-fields').style.display = 'block';
        } else if (videoType === 'upload') {
            document.getElementById('upload-fields').style.display = 'block';
        }
        
        // Update active class on options
        document.querySelectorAll('.video-option').forEach(option => {
            option.classList.remove('active');
        });
        document.querySelector('input[name="video_type"]:checked').closest('.video-option').classList.add('active');
    }
    
    // Video modal functions
    function openVideoModal(videoUrl, videoType) {
        const modal = document.getElementById('videoModal');
        const iframe = document.getElementById('videoPlayer');
        const videoPlayer = document.getElementById('uploadedVideoPlayer');
        
        if (videoType === 'youtube') {
            // Extract YouTube video ID
            const videoId = extractYouTubeId(videoUrl);
            iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            iframe.style.display = 'block';
            videoPlayer.style.display = 'none';
        } else if (videoType === 'upload') {
            // Show uploaded video
            iframe.style.display = 'none';
            videoPlayer.src = videoUrl;
            videoPlayer.style.display = 'block';
        }
        
        modal.style.display = 'flex';
    }
    
    function closeVideoModal() {
        const modal = document.getElementById('videoModal');
        const iframe = document.getElementById('videoPlayer');
        const videoPlayer = document.getElementById('uploadedVideoPlayer');
        
        iframe.src = '';
        videoPlayer.src = '';
        modal.style.display = 'none';
    }
    
    function extractYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }
    
    // Form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const clientName = document.getElementById('client_name')?.value.trim();
        const testimonialText = document.getElementById('testimonial_text')?.value.trim();
        const videoType = document.querySelector('input[name="video_type"]:checked')?.value;
        
        if (clientName && testimonialText) {
            if (testimonialText.length < 10) {
                e.preventDefault();
                alert('Testimonial text should be at least 10 characters long.');
                return false;
            }
            
            if (videoType === 'youtube') {
                const youtubeUrl = document.getElementById('youtube_url')?.value.trim();
                if (!youtubeUrl) {
                    e.preventDefault();
                    alert('Please enter a YouTube URL.');
                    return false;
                }
                
                const videoId = extractYouTubeId(youtubeUrl);
                if (!videoId) {
                    e.preventDefault();
                    alert('Please enter a valid YouTube URL.');
                    return false;
                }
            } else if (videoType === 'upload') {
                const videoUpload = document.getElementById('video_upload')?.files[0];
                if (!videoUpload) {
                    e.preventDefault();
                    alert('Please select a video file to upload.');
                    return false;
                }
                
                // Check file size (100MB max)
                if (videoUpload.size > 104857600) {
                    e.preventDefault();
                    alert('Video file size must be less than 100MB.');
                    return false;
                }
                
                // Check file type
                const allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/webm'];
                if (!allowedTypes.includes(videoUpload.type)) {
                    e.preventDefault();
                    alert('Please upload a valid video file (MP4, MOV, AVI, WMV, or WEBM).');
                    return false;
                }
            }
        }
        
        return true;
    });
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Testimonial Management loaded successfully');
        
        // Make testimonial cards clickable for better UX
        document.querySelectorAll('.testimonial-card').forEach(card => {
            const editLink = card.querySelector('a[title="Edit"]');
            if (editLink) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('.action-buttons') && !e.target.closest('form') && !e.target.closest('.read-more') && !e.target.closest('.video-container')) {
                        window.location.href = editLink.href;
                    }
                });
            }
        });
        
        // Close video modal when clicking outside
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });
        
        // Initialize video fields if needed
        if (document.querySelector('input[name="video_type"]:checked')) {
            toggleVideoFields();
        }
    });
    </script>
</body>
</html>
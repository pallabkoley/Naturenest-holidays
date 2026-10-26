<?php
require_once 'db.php';

function sanitizeInput($data) {
    global $db;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $db->escapeString($data);
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function generateBookingReference() {
    return 'MIL' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
}

// function getTourPackages($limit = null, $status = 'active') {
//     global $db;
//     $conn = $db->getConnection();
    
//     $sql = "SELECT * FROM tour_packages WHERE status = ?";
//     if ($limit) {
//         $sql .= " LIMIT ?";
//     }
    
//     $stmt = $conn->prepare($sql);
    
//     if ($limit) {
//         $stmt->bind_param("si", $status, $limit);
//     } else {
//         $stmt->bind_param("s", $status);
//     }
    
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     $packages = [];
//     while ($row = $result->fetch_assoc()) {
//         $packages[] = $row;
//     }
    
//     return $packages;
// }

function getSiteSetting($key) {
    global $db;
    $conn = $db->getConnection();
    
    $sql = "SELECT setting_value FROM site_settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return null;
}

function uploadImage($file, $directory) {
    $target_dir = UPLOAD_PATH . $directory . '/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $unique_name;
    
    // Check file size
    if ($file["size"] > MAX_FILE_SIZE) {
        return ['error' => 'File is too large. Maximum size is 5MB.'];
    }
    
    // Check file type
    if (!in_array($file_extension, ALLOWED_IMAGE_TYPES)) {
        return ['error' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $unique_name];
    } else {
        return ['error' => 'Sorry, there was an error uploading your file.'];
    }
}
// Function to get correct image URL
/*function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return SITE_URL . '/assets/images/default-slider.jpg';
    }
    
    // Check if it's an absolute URL
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // Check if image exists
    $fullPath = SITE_PATH . '/' . $imagePath;
    if (file_exists($fullPath)) {
        return SITE_URL . '/' . $imagePath;
    }
    
    // Try different paths
    $possiblePaths = [
        $imagePath,
        'assets/' . $imagePath,
        'assets/uploads/' . basename($imagePath),
        'assets/images/slider/' . basename($imagePath),
        'uploads/slider/' . basename($imagePath),
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists(SITE_PATH . '/' . $path)) {
            return SITE_URL . '/' . $path;
        }
    }
    
    // Return default image if none found
    return SITE_URL . '/assets/images/default-slider.jpg';
}  */
// Add this to includes/functions.php if not present
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return SITE_URL . '/assets/images/default-slider.jpg';
    }
    
    // Check if it's an absolute URL
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // Remove any leading slash
    $imagePath = ltrim($imagePath, '/');
    
    // Base path for checking file existence
    $basePath = $_SERVER['DOCUMENT_ROOT'];
    
    // Check various possible locations
    $possiblePaths = [
        $imagePath,
        'assets/uploads/' . $imagePath,
        'uploads/' . $imagePath,
        'assets/images/slider/' . basename($imagePath),
        'assets/images/' . basename($imagePath),
        'uploads/slider/' . basename($imagePath)
    ];
    
    foreach ($possiblePaths as $path) {
        $fullPath = $basePath . '/' . $path;
        if (file_exists($fullPath)) {
            return rtrim(SITE_URL, '/') . '/' . $path;
        }
    }
    
    // If image not found, try direct URL
    $directUrl = SITE_URL . '/' . $imagePath;
    
    // Return with proper URL structure
    return rtrim(SITE_URL, '/') . '/' . $imagePath;
}
?>
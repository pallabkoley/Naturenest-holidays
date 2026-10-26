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
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = $db->getConnection();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage-treks.php?error=Invalid request method');
    exit();
}

// Check if trek ID is provided
if (!isset($_POST['trek_id']) || !is_numeric($_POST['trek_id'])) {
    header('Location: manage-treks.php?error=Invalid trek ID');
    exit();
}

$trek_id = intval($_POST['trek_id']);

// Verify CSRF token if you're using one (optional but recommended)
// if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     header('Location: manage-treks.php?error=Invalid security token');
//     exit();
// }

// Get trek data to delete images
$result = $conn->query("SELECT name, featured_image, gallery_images FROM treks WHERE id = $trek_id");

if ($result && $result->num_rows > 0) {
    $trek = $result->fetch_assoc();
    $trek_name = $trek['name'];
    $deleted_images = [];
    $failed_images = [];
    
    // Delete featured image
    if (!empty($trek['featured_image'])) {
        $featured_image_path = '../' . $trek['featured_image'];
        if (file_exists($featured_image_path)) {
            if (unlink($featured_image_path)) {
                $deleted_images[] = $trek['featured_image'];
            } else {
                $failed_images[] = $trek['featured_image'];
            }
        }
    }
    
    // Delete gallery images
    if (!empty($trek['gallery_images'])) {
        $gallery_images = json_decode($trek['gallery_images'], true);
        if (is_array($gallery_images)) {
            foreach ($gallery_images as $image) {
                $gallery_image_path = '../' . $image;
                if (file_exists($gallery_image_path)) {
                    if (unlink($gallery_image_path)) {
                        $deleted_images[] = $image;
                    } else {
                        $failed_images[] = $image;
                    }
                }
            }
        }
    }
    
    // Delete trek from database
    $stmt = $conn->prepare("DELETE FROM treks WHERE id = ?");
    $stmt->bind_param("i", $trek_id);
    
    if ($stmt->execute()) {
        // Log successful deletion
        error_log("Trek deleted successfully: ID $trek_id - $trek_name");
        
        // Set success message with details
        $message = "Trek '$trek_name' deleted successfully!";
        if (!empty($deleted_images)) {
            $message .= " " . count($deleted_images) . " images were removed.";
        }
        if (!empty($failed_images)) {
            $message .= " Warning: " . count($failed_images) . " images could not be deleted.";
        }
        
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = 'success';
    } else {
        // Log error
        error_log("Error deleting trek ID $trek_id: " . $stmt->error);
        
        $_SESSION['message'] = 'Error deleting trek: ' . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
} else {
    $_SESSION['message'] = 'Trek not found';
    $_SESSION['message_type'] = 'error';
}

// Close connection
$conn->close();

// Redirect back to manage page
header('Location: manage-treks.php');
exit();
?>
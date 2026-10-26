<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'tour' => null
];

if (!isset($_GET['tour_id']) || empty($_GET['tour_id'])) {
    echo json_encode($response);
    exit;
}

$tour_id = intval($_GET['tour_id']);

try {
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM tour_packages WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $tour_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $tour = $result->fetch_assoc();
            $response['success'] = true;
            $response['message'] = 'Tour found';
            $response['tour'] = $tour;
        } else {
            $response['message'] = 'Tour not found';
        }
        
        $stmt->close();
    }
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>
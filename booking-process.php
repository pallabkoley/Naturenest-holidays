<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email-template.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Start session
session_start();

// Prevent CSRF attacks
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid']);
    exit();
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Get and sanitize form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $travel_date = sanitizeInput($_POST['travel_date'] ?? '');
    $return_date = sanitizeInput($_POST['return_date'] ?? '');
    $destination = sanitizeInput($_POST['destination'] ?? '');
    $journey_type = sanitizeInput($_POST['journey_type'] ?? '');
    $adults = intval($_POST['adults'] ?? 1);
    $children = intval($_POST['children'] ?? 0);
    $special_requests = sanitizeInput($_POST['special_requests'] ?? '');
    $tour_package_id = intval($_POST['tour_package_id'] ?? 0);
    $custom_tour_details = sanitizeInput($_POST['custom_tour_details'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($travel_date)) {
        $errors[] = 'Travel date is required';
    } else {
        // Check if travel date is in the future
        $travel_timestamp = strtotime($travel_date);
        $today_timestamp = strtotime(date('Y-m-d'));
        
        if ($travel_timestamp < $today_timestamp) {
            $errors[] = 'Travel date must be today or in the future';
        }
    }
    
    if (!empty($return_date) && $return_date < $travel_date) {
        $errors[] = 'Return date must be after travel date';
    }
    
    if ($adults < 1) {
        $errors[] = 'At least 1 adult is required';
    }
    
    if ($adults > 20) {
        $errors[] = 'Maximum 20 adults allowed per booking';
    }
    
    if ($children > 20) {
        $errors[] = 'Maximum 20 children allowed per booking';
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $response['message'] = implode('<br>', $errors);
        echo json_encode($response);
        exit();
    }
    
    // Get tour package details if ID is provided
    $tour_name = '';
    $tour_price = 0;
    
    if ($tour_package_id > 0) {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT title, starting_price FROM tour_packages WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $tour_package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $tour = $result->fetch_assoc();
            $tour_name = $tour['title'];
            $tour_price = $tour['starting_price'];
        }
    }
    
    // Calculate total amount (basic calculation - can be enhanced)
    $total_amount = 0;
    if ($tour_price > 0) {
        $total_amount = $tour_price * $adults;
        if ($children > 0) {
            $total_amount += ($tour_price * $children * 0.5); // 50% for children
        }
    }
    
    // Generate booking reference
    $booking_reference = generateBookingReference();
    
    // Prepare booking data for database
    $booking_data = [
        'booking_reference' => $booking_reference,
        'tour_package_id' => $tour_package_id > 0 ? $tour_package_id : NULL,
        'custom_tour_details' => $custom_tour_details,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'travel_date' => $travel_date,
        'return_date' => !empty($return_date) ? $return_date : NULL,
        'destination' => $destination,
        'journey_type' => $journey_type,
        'adults' => $adults,
        'children' => $children,
        'special_requests' => $special_requests,
        'total_amount' => $total_amount,
        'status' => 'pending',
        'payment_status' => 'pending',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    // Save booking to database
    $conn = $db->getConnection();
    
    // Prepare SQL query
    $sql = "INSERT INTO bookings (
        booking_reference, 
        tour_package_id, 
        custom_tour_details, 
        name, 
        email, 
        phone, 
        travel_date, 
        return_date, 
        destination, 
        journey_type, 
        adults, 
        children, 
        special_requests, 
        total_amount, 
        status, 
        payment_status,
        ip_address,
        user_agent
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sissssssssiiisdssss",
        $booking_data['booking_reference'],
        $booking_data['tour_package_id'],
        $booking_data['custom_tour_details'],
        $booking_data['name'],
        $booking_data['email'],
        $booking_data['phone'],
        $booking_data['travel_date'],
        $booking_data['return_date'],
        $booking_data['destination'],
        $booking_data['journey_type'],
        $booking_data['adults'],
        $booking_data['children'],
        $booking_data['special_requests'],
        $booking_data['total_amount'],
        $booking_data['status'],
        $booking_data['payment_status'],
        $booking_data['ip_address'],
        $booking_data['user_agent']
    );
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        
        // Update booking with notes
        $notes = "Booking created via website. IP: " . $booking_data['ip_address'];
        $update_sql = "UPDATE bookings SET notes = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $notes, $booking_id);
        $update_stmt->execute();
        
        // Send confirmation email to customer
        if (!empty($email)) {
            $email_data = [
                'booking_ref' => $booking_reference,
                'customer_name' => $name,
                'tour_name' => !empty($tour_name) ? $tour_name : 'Custom Tour Package',
                'travel_date' => date('F j, Y', strtotime($travel_date)),
                'total_amount' => number_format($total_amount, 2),
                'payment_status' => 'Pending'
            ];
            
            $email_sent = sendEmail(
                $email,
                'Booking Confirmation - ' . $booking_reference,
                'booking_confirmation',
                $email_data
            );
        }
        
        // Send notification email to admin
        $admin_email = getSiteSetting('site_email');
        if ($admin_email) {
            $admin_email_data = [
                'booking_ref' => $booking_reference,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'tour_name' => !empty($tour_name) ? $tour_name : 'Custom Tour Package',
                'travel_date' => date('F j, Y', strtotime($travel_date)),
                'adults' => $adults,
                'children' => $children,
                'total_amount' => number_format($total_amount, 2)
            ];
            
            sendEmail(
                $admin_email,
                'New Booking Received - ' . $booking_reference,
                'booking_received',
                $admin_email_data
            );
        }
        
        // Send WhatsApp notification (if configured)
        $whatsapp_number = getSiteSetting('whatsapp_number');
        if ($whatsapp_number) {
            sendWhatsAppNotification($booking_data);
        }
        
        // Prepare success response
        $response['success'] = true;
        $response['message'] = 'Booking submitted successfully!';
        $response['booking_ref'] = $booking_reference;
        $response['booking_id'] = $booking_id;
        
        // Store booking reference in session for thank you page
        $_SESSION['last_booking_ref'] = $booking_reference;
        
    } else {
        throw new Exception('Failed to save booking to database');
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Booking Process Error: ' . $e->getMessage());
    
    // Send error notification to admin
    $admin_email = getSiteSetting('site_email');
    if ($admin_email) {
        $error_message = "Booking Error:\n\n";
        $error_message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $error_message .= "Error: " . $e->getMessage() . "\n";
        $error_message .= "Form Data: " . json_encode($_POST) . "\n";
        
        mail($admin_email, 'Booking System Error - Newa Yatra', $error_message);
    }
    
    $response['message'] = 'An error occurred while processing your booking. Please try again or contact us directly.';
}

// Return JSON response
echo json_encode($response);

// Function to send WhatsApp notification
function sendWhatsAppNotification($booking_data) {
    $whatsapp_api_key = getSiteSetting('whatsapp_api_key');
    $whatsapp_number = getSiteSetting('whatsapp_number');
    
    if (!$whatsapp_api_key || !$whatsapp_number) {
        return false;
    }
    
    $message = "📋 *New Booking Received*\n\n";
    $message .= "*Booking Reference:* " . $booking_data['booking_reference'] . "\n";
    $message .= "*Customer:* " . $booking_data['name'] . "\n";
    $message .= "*Phone:* " . $booking_data['phone'] . "\n";
    $message .= "*Email:* " . ($booking_data['email'] ?: 'Not provided') . "\n";
    $message .= "*Travel Date:* " . date('d/m/Y', strtotime($booking_data['travel_date'])) . "\n";
    $message .= "*Destination:* " . $booking_data['destination'] . "\n";
    $message .= "*Persons:* " . $booking_data['adults'] . " Adults";
    if ($booking_data['children'] > 0) {
        $message .= ", " . $booking_data['children'] . " Children";
    }
    $message .= "\n*Amount:* ₹" . number_format($booking_data['total_amount'], 2) . "\n\n";
    $message .= "Please check admin panel for details.";
    
    // Encode message for URL
    $encoded_message = urlencode($message);
    
    // Send via WhatsApp API (example using WhatsApp Business API)
    $api_url = "https://api.whatsapp.com/send?phone=" . $whatsapp_number . "&text=" . $encoded_message;
    
    // For actual implementation, you would use cURL to send via API
    // This is a simplified example
    return true;
}

// Function to generate CSRF token (called from booking form)
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>
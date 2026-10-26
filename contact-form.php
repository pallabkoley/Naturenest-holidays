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

// Prevent spam with honeypot field
if (!empty($_POST['honeypot'])) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your message!']); // Silent success for bots
    exit();
}

// Rate limiting - prevent multiple submissions
if (isset($_SESSION['last_submission_time'])) {
    $time_since_last = time() - $_SESSION['last_submission_time'];
    if ($time_since_last < 30) { // 30 seconds cooldown
        echo json_encode(['success' => false, 'message' => 'Please wait a moment before sending another message.']);
        exit();
    }
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Get and sanitize form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $tour_interest = sanitizeInput($_POST['tour_interest'] ?? '');
    $travel_date = sanitizeInput($_POST['travel_date'] ?? '');
    $how_found = sanitizeInput($_POST['how_found'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    } elseif (strlen($subject) < 5) {
        $errors[] = 'Subject must be at least 5 characters';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    } elseif (strlen($message) > 2000) {
        $errors[] = 'Message cannot exceed 2000 characters';
    }
    
    if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number (10-15 digits)';
    }
    
    // Check for spam keywords
    $spam_keywords = ['viagra', 'cialis', 'casino', 'poker', 'lottery', 'forex', 'investment', 'earn money', 'work from home', 'http://', 'https://', 'www.', '.ru', '.cn'];
    $message_lower = strtolower($message);
    foreach ($spam_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            $errors[] = 'Message contains prohibited content';
            break;
        }
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        $response['message'] = implode('<br>', $errors);
        echo json_encode($response);
        exit();
    }
    
    // Get IP address and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Check for duplicate submissions (same content within 1 hour)
    $conn = $db->getConnection();
    $message_hash = md5($message . $email);
    $check_sql = "SELECT id FROM contact_messages WHERE message_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $message_hash);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $response['message'] = 'You have already submitted a similar message recently. Please wait before submitting again.';
        echo json_encode($response);
        exit();
    }
    
    // Prepare message data for database
    $message_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone ?: NULL,
        'subject' => $subject,
        'message' => $message,
        'tour_interest' => $tour_interest ?: NULL,
        'travel_date' => $travel_date ?: NULL,
        'how_found' => $how_found ?: NULL,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'message_hash' => $message_hash,
        'status' => 'unread'
    ];
    
    // Save message to database
    $sql = "INSERT INTO contact_messages (
        name, 
        email, 
        phone, 
        subject, 
        message, 
        tour_interest, 
        travel_date, 
        how_found,
        ip_address,
        user_agent,
        message_hash,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssss",
        $message_data['name'],
        $message_data['email'],
        $message_data['phone'],
        $message_data['subject'],
        $message_data['message'],
        $message_data['tour_interest'],
        $message_data['travel_date'],
        $message_data['how_found'],
        $message_data['ip_address'],
        $message_data['user_agent'],
        $message_data['message_hash'],
        $message_data['status']
    );
    
    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;
        
        // Send confirmation email to user
        $email_data = [
            'name' => $name,
            'subject' => $subject,
            'message' => $message
        ];
        
        $user_email_sent = sendEmail(
            $email,
            'Thank You for Contacting Newa Yatra',
            'contact_received_user',
            $email_data
        );
        
        // Send notification email to admin
        $admin_email = getSiteSetting('site_email');
        if ($admin_email) {
            $admin_email_data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: 'Not provided',
                'subject' => $subject,
                'message' => $message,
                'tour_interest' => $tour_interest ?: 'Not specified',
                'travel_date' => $travel_date ?: 'Not specified',
                'how_found' => $how_found ?: 'Not specified'
            ];
            
            $admin_email_sent = sendEmail(
                $admin_email,
                'New Contact Form Submission: ' . $subject,
                'contact_received_admin',
                $admin_email_data
            );
            
            // Send WhatsApp notification if configured
            $whatsapp_number = getSiteSetting('whatsapp_number');
            if ($whatsapp_number) {
                sendWhatsAppNotification($message_data);
            }
        }
        
        // Send SMS notification if configured
        $sms_enabled = getSiteSetting('sms_notifications');
        if ($sms_enabled === '1') {
            sendSMSNotification($message_data);
        }
        
        // Update submission time for rate limiting
        $_SESSION['last_submission_time'] = time();
        
        // Prepare success response
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you within 24 hours.';
        $response['message_id'] = $message_id;
        
        // Store in session for thank you page
        $_SESSION['last_contact_name'] = $name;
        $_SESSION['last_contact_email'] = $email;
        
    } else {
        throw new Exception('Failed to save message to database');
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Contact Form Error: ' . $e->getMessage());
    
    // Send error notification to admin
    $admin_email = getSiteSetting('site_email');
    if ($admin_email) {
        $error_message = "Contact Form Error:\n\n";
        $error_message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $error_message .= "Error: " . $e->getMessage() . "\n";
        $error_message .= "Form Data: " . json_encode($_POST) . "\n";
        
        mail($admin_email, 'Contact Form Error - Newa Yatra', $error_message);
    }
    
    $response['message'] = 'An error occurred while sending your message. Please try again or contact us directly.';
}

// Return JSON response
echo json_encode($response);

// Function to send WhatsApp notification
function sendWhatsAppNotification($message_data) {
    $whatsapp_api_key = getSiteSetting('whatsapp_api_key');
    $whatsapp_number = getSiteSetting('whatsapp_number');
    
    if (!$whatsapp_api_key || !$whatsapp_number) {
        return false;
    }
    
    $notification = "📨 *New Contact Form Submission*\n\n";
    $notification .= "*From:* " . $message_data['name'] . "\n";
    $notification .= "*Email:* " . $message_data['email'] . "\n";
    
    if (!empty($message_data['phone'])) {
        $notification .= "*Phone:* " . $message_data['phone'] . "\n";
    }
    
    $notification .= "*Subject:* " . $message_data['subject'] . "\n\n";
    
    // Truncate message for WhatsApp
    $short_message = substr($message_data['message'], 0, 100);
    if (strlen($message_data['message']) > 100) {
        $short_message .= "...";
    }
    $notification .= "*Message:* " . $short_message . "\n\n";
    
    if (!empty($message_data['tour_interest'])) {
        $notification .= "*Tour Interest:* " . $message_data['tour_interest'] . "\n";
    }
    
    $notification .= "Please check admin panel for full details.";
    
    // Encode message for URL
    $encoded_message = urlencode($notification);
    
    // For actual implementation, use WhatsApp Business API
    // This is a simplified example
    return true;
}

// Function to send SMS notification
function sendSMSNotification($message_data) {
    $sms_api_key = getSiteSetting('sms_api_key');
    $sms_sender_id = getSiteSetting('sms_sender_id');
    $admin_phone = getSiteSetting('admin_phone');
    
    if (!$sms_api_key || !$admin_phone) {
        return false;
    }
    
    // Prepare SMS message
    $sms_message = "New contact from " . $message_data['name'] . ": " . $message_data['subject'];
    
    // Truncate if too long
    if (strlen($sms_message) > 160) {
        $sms_message = substr($sms_message, 0, 157) . '...';
    }
    
    // For actual implementation, use SMS gateway API
    // This is a simplified example
    return true;
}
?>
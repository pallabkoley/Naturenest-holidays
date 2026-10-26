<?php
// Email Template System for Newa Yatra

class EmailTemplate {
    
    /**
     * Get email template by type
     */
    public static function getTemplate($type, $data = []) {
        switch ($type) {
            case 'password_reset':
                return self::passwordResetTemplate($data);
            
            case 'booking_confirmation':
                return self::bookingConfirmationTemplate($data);
            
            case 'booking_received':
                return self::bookingReceivedTemplate($data);
            
            case 'contact_received':
                return self::contactReceivedAdminTemplate($data);
            
            case 'contact_user_confirmation':
                return self::contactReceivedUserTemplate($data);
            
            case 'testimonial_approved':
                return self::testimonialApprovedTemplate($data);
            
            case 'welcome_admin':
                return self::welcomeAdminTemplate($data);
            
            case 'newsletter':
                return self::newsletterTemplate($data);
            
            default:
                return self::defaultTemplate($type);
        }
    }
    
    /**
     * Default email wrapper
     */
    private static function defaultTemplate($content) {
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Newa Yatra';
        $site_url = defined('SITE_URL') ? SITE_URL : '#';
        $admin_url = defined('ADMIN_URL') ? ADMIN_URL : '#';
        $year = date('Y');
        
        // Safely get site settings
        $site_email = self::getSiteSetting('site_email');
        $contact_phone1 = self::getSiteSetting('contact_phone1');
        $facebook_url = self::getSiteSetting('facebook_url');
        $instagram_url = self::getSiteSetting('instagram_url');
        $youtube_url = self::getSiteSetting('youtube_url');
        
        // Escape HTML content
        $content = htmlspecialchars_decode($content);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email from {$site_name}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .email-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .email-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .email-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .email-body {
            padding: 40px 30px;
        }
        
        .email-content {
            margin-bottom: 30px;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .contact-info {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .contact-info a {
            color: #2a5298;
            text-decoration: none;
        }
        
        .social-icons {
            margin-top: 20px;
        }
        
        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            color: #2a5298;
            font-size: 18px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .footer-text {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
        
        @media (max-width: 600px) {
            .email-body {
                padding: 20px 15px;
            }
            
            .email-header {
                padding: 20px 15px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{$site_name}</h1>
            <p>Your Trusted Travel Partner in Darjeeling & Sikkim</p>
        </div>
        
        <div class="email-body">
            <div class="email-content">
                {$content}
            </div>
        </div>
        
        <div class="email-footer">
            <p>Thank you for choosing {$site_name}</p>
            
            <div class="contact-info">
                <p><strong>Contact Us:</strong></p>
                <p>Email: <a href="mailto:{$site_email}">{$site_email}</a></p>
                <p>Phone: {$contact_phone1}</p>
                <p>Website: <a href="{$site_url}">{$site_url}</a></p>
            </div>
            
            <div class="social-icons">
                <a href="{$facebook_url}">Facebook</a>
                <a href="{$instagram_url}">Instagram</a>
                <a href="{$youtube_url}">YouTube</a>
            </div>
            
            <div class="footer-text">
                <p>&copy; {$year} {$site_name}. All rights reserved.</p>
                <p>This email was sent to you because you interacted with {$site_name} website.</p>
                <p>If you wish to unsubscribe from our emails, please <a href="{$site_url}/unsubscribe">click here</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Password Reset Template
     */
    private static function passwordResetTemplate($data) {
        $reset_link = htmlspecialchars($data['reset_link'] ?? '#');
        $expiry_time = htmlspecialchars($data['expiry_time'] ?? '1 hour');
        
        $content = <<<HTML
<h2>Password Reset Request</h2>
<p>Hello,</p>
<p>We received a request to reset your password for your Newa Yatra account.</p>
<p>Click the button below to reset your password:</p>
<p style="text-align: center;">
    <a href="{$reset_link}" class="btn">Reset Password</a>
</p>
<p>If the button doesn't work, copy and paste this link into your browser:</p>
<p><code>{$reset_link}</code></p>
<p><strong>Important:</strong> This link will expire in {$expiry_time}. If you didn't request a password reset, please ignore this email.</p>
<p>For security reasons, never share this link with anyone.</p>
<p>Best regards,<br>The Newa Yatra Team</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Booking Confirmation Template
     */
    private static function bookingConfirmationTemplate($data) {
        $booking_ref = htmlspecialchars($data['booking_ref'] ?? 'N/A');
        $customer_name = htmlspecialchars($data['customer_name'] ?? 'Guest');
        $tour_name = htmlspecialchars($data['tour_name'] ?? 'Tour Package');
        $travel_date = htmlspecialchars($data['travel_date'] ?? 'To be confirmed');
        $total_amount = htmlspecialchars($data['total_amount'] ?? '0');
        $payment_status = htmlspecialchars($data['payment_status'] ?? 'Pending');
        $site_url = defined('SITE_URL') ? SITE_URL : '#';
        $booking_link = $site_url . "/booking-status/{$booking_ref}";
        $contact_phone1 = self::getSiteSetting('contact_phone1');
        
        $content = <<<HTML
<h2>Booking Confirmed!</h2>
<p>Dear {$customer_name},</p>
<p>Thank you for booking with Newa Yatra. Your booking has been confirmed successfully.</p>
<p><strong>Booking Details:</strong></p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking Reference:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$booking_ref}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Tour Package:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$tour_name}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Travel Date:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$travel_date}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Total Amount:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">₹{$total_amount}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Payment Status:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$payment_status}</td>
    </tr>
</table>
<p>You can check your booking status anytime using this link:</p>
<p style="text-align: center;">
    <a href="{$booking_link}" class="btn">View Booking Details</a>
</p>
<p><strong>Next Steps:</strong></p>
<ol>
    <li>Our travel consultant will contact you within 24 hours</li>
    <li>Complete your payment as per the payment schedule</li>
    <li>Receive your detailed itinerary and travel documents</li>
</ol>
<p>If you have any questions, feel free to contact us at {$contact_phone1}</p>
<p>We look forward to serving you!</p>
<p>Best regards,<br>The Newa Yatra Team</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Booking Received (Admin Notification)
     */
    private static function bookingReceivedTemplate($data) {
        $booking_ref = htmlspecialchars($data['booking_ref'] ?? 'N/A');
        $customer_name = htmlspecialchars($data['customer_name'] ?? 'Guest');
        $customer_email = htmlspecialchars($data['customer_email'] ?? 'N/A');
        $customer_phone = htmlspecialchars($data['customer_phone'] ?? 'N/A');
        $tour_name = htmlspecialchars($data['tour_name'] ?? 'Tour Package');
        $travel_date = htmlspecialchars($data['travel_date'] ?? 'To be confirmed');
        $admin_url = defined('ADMIN_URL') ? ADMIN_URL : '#';
        $admin_link = $admin_url . "/bookings/view/{$booking_ref}";
        
        $content = <<<HTML
<h2>New Booking Received!</h2>
<p>A new booking has been submitted on Newa Yatra website.</p>
<p><strong>Booking Details:</strong></p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Booking Reference:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$booking_ref}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Customer Name:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$customer_name}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$customer_email}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Phone:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$customer_phone}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Tour Package:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$tour_name}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Travel Date:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$travel_date}</td>
    </tr>
</table>
<p style="text-align: center;">
    <a href="{$admin_link}" class="btn">View Booking in Admin Panel</a>
</p>
<p>Please process this booking within 24 hours.</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Contact Form - Admin Notification
     */
    private static function contactReceivedAdminTemplate($data) {
        $name = htmlspecialchars($data['name'] ?? 'Guest');
        $email = htmlspecialchars($data['email'] ?? 'No email');
        $phone = htmlspecialchars($data['phone'] ?? 'Not provided');
        $subject = htmlspecialchars($data['subject'] ?? 'General Inquiry');
        $message = nl2br(htmlspecialchars($data['message'] ?? ''));
        $tour_interest = htmlspecialchars($data['tour_interest'] ?? 'Not specified');
        $travel_date = htmlspecialchars($data['travel_date'] ?? 'Not specified');
        $how_found = htmlspecialchars($data['how_found'] ?? 'Not specified');
        $admin_url = defined('ADMIN_URL') ? ADMIN_URL : '#';
        $admin_link = $admin_url . "/contact-messages";
        
        $content = <<<HTML
<h2>New Contact Form Submission</h2>
<p>A new message has been received through the website contact form.</p>
<p><strong>Message Details:</strong></p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee; width: 150px;"><strong>Name:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$name}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Email:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$email}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Phone:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$phone}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Subject:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$subject}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Tour Interest:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$tour_interest}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Travel Date:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$travel_date}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>How Found:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$how_found}</td>
    </tr>
</table>
<p><strong>Message:</strong></p>
<div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
    {$message}
</div>
<p style="text-align: center; margin-top: 30px;">
    <a href="{$admin_link}" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #c66b05, #8f1402); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
        View in Admin Panel
    </a>
</p>
<p>Please respond to this inquiry within 24 hours.</p>
HTML;
    
        return self::defaultTemplate($content);
    }
    
    /**
     * Contact Form - User Confirmation
     */
    private static function contactReceivedUserTemplate($data) {
        $name = htmlspecialchars($data['name'] ?? 'Guest');
        $subject = htmlspecialchars($data['subject'] ?? 'General Inquiry');
        $site_url = defined('SITE_URL') ? SITE_URL : '#';
        $contact_phone1 = self::getSiteSetting('contact_phone1');
        
        $content = <<<HTML
<h2>Thank You for Contacting Newa Yatra!</h2>
<p>Dear {$name},</p>
<p>Thank you for reaching out to us. We have received your message regarding:</p>
<p style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #c66b05; margin: 20px 0;">
    <strong>{$subject}</strong>
</p>
<p>Our travel experts are reviewing your inquiry and will get back to you within 24 hours.</p>
<p><strong>What happens next?</strong></p>
<ol>
    <li>Our travel consultant will contact you to understand your requirements in detail</li>
    <li>We'll prepare a customized itinerary based on your preferences</li>
    <li>You'll receive a detailed quote with all inclusions and exclusions</li>
    <li>We'll assist you with booking confirmations and travel arrangements</li>
</ol>
<p>In the meantime, you can:</p>
<ul>
    <li>Browse our <a href="{$site_url}/pages/tour-packages.php">tour packages</a></li>
    <li>Check out our <a href="{$site_url}/pages/destinations.php">popular destinations</a></li>
    <li>Read <a href="{$site_url}/pages/testimonials.php">traveler reviews</a></li>
</ul>
<p><strong>Need immediate assistance?</strong><br>
Call us at {$contact_phone1} or reply to this email.</p>
<p>We look forward to helping you plan your perfect getaway!</p>
<p>Best regards,<br>The Newa Yatra Team</p>
HTML;
    
        return self::defaultTemplate($content);
    }
    
    /**
     * Testimonial Approved Notification
     */
    private static function testimonialApprovedTemplate($data) {
        $customer_name = htmlspecialchars($data['customer_name'] ?? 'Guest');
        $testimonial_text = nl2br(htmlspecialchars($data['testimonial_text'] ?? ''));
        $site_url = defined('SITE_URL') ? SITE_URL : '#';
        
        $content = <<<HTML
<h2>Your Testimonial Has Been Published!</h2>
<p>Dear {$customer_name},</p>
<p>Thank you for sharing your experience with Newa Yatra!</p>
<p>We're pleased to inform you that your testimonial has been approved and published on our website.</p>
<p><strong>Your Feedback:</strong></p>
<blockquote style="background-color: #f8f9fa; border-left: 4px solid #2a5298; padding: 15px; margin: 20px 0; font-style: italic;">
    "{$testimonial_text}"
</blockquote>
<p>You can view it on our website:</p>
<p style="text-align: center;">
    <a href="{$site_url}/testimonials" class="btn">View Testimonials</a>
</p>
<p>We truly appreciate you taking the time to share your experience. Your feedback helps us improve and inspires others to travel with us.</p>
<p>As a token of our appreciation, here's a special coupon code for your next booking:</p>
<p style="text-align: center; font-size: 18px; font-weight: bold; color: #2a5298;">
    THANKYOU10
</p>
<p><small>*Valid for 10% discount on your next booking. Expires in 6 months.</small></p>
<p>We look forward to serving you again soon!</p>
<p>Best regards,<br>The Newa Yatra Team</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Welcome Email for New Admin
     */
    private static function welcomeAdminTemplate($data) {
        $username = htmlspecialchars($data['username'] ?? '');
        $full_name = htmlspecialchars($data['full_name'] ?? '');
        $admin_url = defined('ADMIN_URL') ? ADMIN_URL : '#';
        $temporary_password = htmlspecialchars($data['temporary_password'] ?? '');
        
        $content = <<<HTML
<h2>Welcome to Newa Yatra Admin Panel!</h2>
<p>Dear {$full_name},</p>
<p>Congratulations! You have been added as an administrator to the Newa Yatra website.</p>
<p><strong>Your Login Details:</strong></p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Username:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$username}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Temporary Password:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$temporary_password}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong>Admin Panel URL:</strong></td>
        <td style="padding: 10px; border-bottom: 1px solid #eee;">{$admin_url}</td>
    </tr>
</table>
<p><strong>Important Security Notice:</strong></p>
<ul>
    <li>Change your password immediately after first login</li>
    <li>Never share your login credentials</li>
    <li>Use strong passwords</li>
    <li>Log out when not using the admin panel</li>
</ul>
<p style="text-align: center;">
    <a href="{$admin_url}" class="btn">Access Admin Panel</a>
</p>
<p><strong>Admin Panel Features:</strong></p>
<ul>
    <li>Manage tour packages and destinations</li>
    <li>Handle bookings and inquiries</li>
    <li>Update website content</li>
    <li>Manage testimonials and reviews</li>
    <li>View website analytics</li>
</ul>
<p>If you have any questions or need assistance, please contact the main administrator.</p>
<p>Welcome aboard!</p>
<p>Best regards,<br>The Newa Yatra Team</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Newsletter Template
     */
    private static function newsletterTemplate($data) {
        $title = htmlspecialchars($data['title'] ?? 'Latest News from Newa Yatra');
        $content_text = htmlspecialchars_decode($data['content'] ?? '');
        $featured_tours = $data['featured_tours'] ?? [];
        $site_url = defined('SITE_URL') ? SITE_URL : '#';
        $unsubscribe_link = $site_url . "/unsubscribe";
        
        $tours_html = '';
        if (!empty($featured_tours)) {
            $tours_html .= '<h3>Featured Tour Packages</h3>';
            foreach ($featured_tours as $tour) {
                $tour_link = $site_url . "/tour/" . htmlspecialchars($tour['slug'] ?? '');
                $tour_name = htmlspecialchars($tour['name'] ?? '');
                $tour_price = htmlspecialchars($tour['price'] ?? '');
                
                $tours_html .= <<<HTML
<div style="margin: 20px 0; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
    <h4>{$tour_name}</h4>
    <p>Starting from ₹{$tour_price}</p>
    <p style="text-align: center;">
        <a href="{$tour_link}" style="color: #2a5298; text-decoration: none; font-weight: bold;">View Package →</a>
    </p>
</div>
HTML;
            }
        }
        
        $content = <<<HTML
<h2>{$title}</h2>
{$content_text}
{$tours_html}
<hr style="margin: 30px 0;">
<p><strong>Why Choose Newa Yatra?</strong></p>
<ul>
    <li>Verified Travel Agents - Trusted & Reliable Experts</li>
    <li>100% Customization - Tailor-made trips just for you</li>
    <li>24x7 Assistance - Round-The-Clock Customer Support</li>
    <li>No Hidden Charges - Transparent pricing</li>
</ul>
<p style="text-align: center;">
    <a href="{$unsubscribe_link}" style="color: #666; font-size: 12px;">Unsubscribe from this newsletter</a>
</p>
HTML;
        
        return self::defaultTemplate($content);
    }
    
    /**
     * Send email using template
     */
    public static function send($to, $subject, $template_type, $data = []) {
        $template = self::getTemplate($template_type, $data);
        $site_email = self::getSiteSetting('site_email');
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Newa Yatra <{$site_email}>" . "\r\n";
        $headers .= "Reply-To: {$site_email}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // For production, consider using PHPMailer or similar
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        return mail($to, $subject, $template, $headers);
    }
    
    /**
     * Safe getSiteSetting replacement
     */
    private static function getSiteSetting($key) {
        // Replace this with your actual settings retrieval logic
        $settings = [
            'site_email' => 'contact@newayatra.com',
            'contact_phone1' => '+91 1234567890',
            'facebook_url' => 'https://facebook.com/newayatra',
            'instagram_url' => 'https://instagram.com/newayatra',
            'youtube_url' => 'https://youtube.com/newayatra'
        ];
        
        return $settings[$key] ?? '';
    }
}

// Helper function to send emails easily
function sendEmail($to, $subject, $template_type, $data = []) {
    return EmailTemplate::send($to, $subject, $template_type, $data);
}
?>
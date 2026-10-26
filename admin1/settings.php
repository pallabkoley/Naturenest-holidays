<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only admin can access settings
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update site settings
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8);
            $setting_value = trim($value);
            
            // Check if setting exists
            $check_sql = "SELECT id FROM site_settings WHERE setting_key = ?";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->bind_param('s', $setting_key);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing setting
                $sql = "UPDATE site_settings SET setting_value = ? WHERE setting_key = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ss', $setting_value, $setting_key);
                $stmt->execute();
            } else {
                // Insert new setting
                $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ss', $setting_key, $setting_value);
                $stmt->execute();
            }
        }
    }
    
    $_SESSION['success'] = 'Settings updated successfully!';
    header('Location: settings.php');
    exit();
}

// Get all settings
$settings = [];
$sql = "SELECT * FROM site_settings ORDER BY setting_group, setting_key";
$result = $db->query($sql);
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
}

// Get settings groups
$groups = ['general', 'contact', 'social', 'email', 'seo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'topbar.php'; ?>

        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- General Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cog me-2"></i>General Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="setting_site_name" class="form-label">Site Name</label>
                                    <input type="text" class="form-control" id="setting_site_name" 
                                           name="setting_site_name" 
                                           value="<?php echo isset($settings['general']['site_name']) ? htmlspecialchars($settings['general']['site_name']) : 'Newa Yatra'; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_site_email" class="form-label">Site Email</label>
                                    <input type="email" class="form-control" id="setting_site_email" 
                                           name="setting_site_email" 
                                           value="<?php echo isset($settings['general']['site_email']) ? htmlspecialchars($settings['general']['site_email']) : 'info@newayatra.com'; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_site_url" class="form-label">Site URL</label>
                                    <input type="url" class="form-control" id="setting_site_url" 
                                           name="setting_site_url" 
                                           value="<?php echo isset($settings['general']['site_url']) ? htmlspecialchars($settings['general']['site_url']) : 'https://newayatra.com/'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-address-book me-2"></i>Contact Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_contact_phone1" class="form-label">Primary Phone</label>
                                            <input type="text" class="form-control" id="setting_contact_phone1" 
                                                   name="setting_contact_phone1" 
                                                   value="<?php echo isset($settings['contact']['contact_phone1']) ? htmlspecialchars($settings['contact']['contact_phone1']) : '+91 8159901115'; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_contact_phone2" class="form-label">Secondary Phone</label>
                                            <input type="text" class="form-control" id="setting_contact_phone2" 
                                                   name="setting_contact_phone2" 
                                                   value="<?php echo isset($settings['contact']['contact_phone2']) ? htmlspecialchars($settings['contact']['contact_phone2']) : '+91 8670951999'; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!--<div class="row">-->
                                <!--    <div class="col-md-6">-->
                                <!--        <div class="mb-3">-->
                                <!--            <label for="setting_contact_phone3" class="form-label">Additional Phone</label>-->
                                <!--            <input type="text" class="form-control" id="setting_contact_phone3" -->
                                <!--                   name="setting_contact_phone3" -->
                                <!--                   value=" +91 97321 88802">-->
                                <!--        </div>-->
                                <!--    </div>-->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="setting_address" 
                                                   name="setting_address" 
                                                   value="<?php echo isset($settings['contact']['address']) ? htmlspecialchars($settings['contact']['address']) : 'Darjeeling, West Bengal'; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-share-alt me-2"></i>Social Media Links
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_facebook_url" class="form-label">Facebook URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fab fa-facebook"></i>
                                                </span>
                                                <input type="url" class="form-control" id="setting_facebook_url" 
                                                       name="setting_facebook_url" 
                                                       value="<?php echo isset($settings['social']['facebook_url']) ? htmlspecialchars($settings['social']['facebook_url']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_instagram_url" class="form-label">Instagram URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fab fa-instagram"></i>
                                                </span>
                                                <input type="url" class="form-control" id="setting_instagram_url" 
                                                       name="setting_instagram_url" 
                                                       value="<?php echo isset($settings['social']['instagram_url']) ? htmlspecialchars($settings['social']['instagram_url']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_youtube_url" class="form-label">YouTube URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fab fa-youtube"></i>
                                                </span>
                                                <input type="url" class="form-control" id="setting_youtube_url" 
                                                       name="setting_youtube_url" 
                                                       value="<?php echo isset($settings['social']['youtube_url']) ? htmlspecialchars($settings['social']['youtube_url']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="setting_whatsapp_number" class="form-label">WhatsApp Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fab fa-whatsapp"></i>
                                                </span>
                                                <input type="text" class="form-control" id="setting_whatsapp_number" 
                                                       name="setting_whatsapp_number" 
                                                       value="<?php echo isset($settings['social']['whatsapp_number']) ? htmlspecialchars($settings['social']['whatsapp_number']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-search me-2"></i>SEO Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="setting_meta_title" class="form-label">Default Meta Title</label>
                                    <input type="text" class="form-control" id="setting_meta_title" 
                                           name="setting_meta_title" 
                                           value="<?php echo isset($settings['seo']['meta_title']) ? htmlspecialchars($settings['seo']['meta_title']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_meta_description" class="form-label">Default Meta Description</label>
                                    <textarea class="form-control" id="setting_meta_description" 
                                              name="setting_meta_description" rows="3"><?php echo isset($settings['seo']['meta_description']) ? htmlspecialchars($settings['seo']['meta_description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_meta_keywords" class="form-label">Meta Keywords</label>
                                    <textarea class="form-control" id="setting_meta_keywords" 
                                              name="setting_meta_keywords" rows="2"><?php echo isset($settings['seo']['meta_keywords']) ? htmlspecialchars($settings['seo']['meta_keywords']) : ''; ?></textarea>
                                    <small class="text-muted">Separate keywords with commas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Email Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-envelope me-2"></i>Email Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="setting_smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="setting_smtp_host" 
                                           name="setting_smtp_host" 
                                           value="<?php echo isset($settings['email']['smtp_host']) ? htmlspecialchars($settings['email']['smtp_host']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="setting_smtp_port" 
                                           name="setting_smtp_port" 
                                           value="<?php echo isset($settings['email']['smtp_port']) ? htmlspecialchars($settings['email']['smtp_port']) : '587'; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_smtp_user" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="setting_smtp_user" 
                                           name="setting_smtp_user" 
                                           value="<?php echo isset($settings['email']['smtp_user']) ? htmlspecialchars($settings['email']['smtp_user']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="setting_smtp_pass" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="setting_smtp_pass" 
                                           name="setting_smtp_pass" 
                                           value="<?php echo isset($settings['email']['smtp_pass']) ? htmlspecialchars($settings['email']['smtp_pass']) : ''; ?>">
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Only change these settings if you know what you're doing.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Save Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Save All Changes
                                    </button>
                                </div>
                                
                                <div class="d-grid mt-2">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Info -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>System Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <strong>PHP Version:</strong>
                                        <span class="float-end"><?php echo phpversion(); ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Server Software:</strong>
                                        <span class="float-end"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Database:</strong>
                                        <span class="float-end">MySQL</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Server Time:</strong>
                                        <span class="float-end"><?php echo date('Y-m-d H:i:s'); ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Timezone:</strong>
                                        <span class="float-end"><?php echo date_default_timezone_get(); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-script.js"></script>
</body>
</html>
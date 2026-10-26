<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin Account - Newa Yatra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .setup-body {
            padding: 40px;
        }
        .credential-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
        }
        .btn-setup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .btn-setup:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102,126,234,0.3);
        }
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="setup-header">
                <i class="fas fa-user-shield fa-4x mb-3"></i>
                <h1>Admin Account Setup</h1>
                <p class="mb-0">Create temporary admin credentials for Newa Yatra</p>
            </div>
            
            <div class="setup-body">
                <?php
                // Check if admin already exists
                $sql = "SELECT id, username, email FROM users WHERE role = 'admin' AND status = 'active'";
                $result = $db->query($sql);
                
                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Admin user already exists!
                    </div>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div>
                            <h5 class="mb-1">Existing Admin Account</h5>
                            <p class="mb-0">Username: <strong><?php echo htmlspecialchars($admin['username']); ?></strong></p>
                            <p class="mb-0">Email: <strong><?php echo htmlspecialchars($admin['email']); ?></strong></p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div>
                            <h5 class="mb-1">Reset Password</h5>
                            <p class="mb-2">If you need to reset the password:</p>
                            <a href="forgot-password.php" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Reset Password
                            </a>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="btn btn-setup">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login Page
                        </a>
                    </div>
                    <?php
                } else {
                    // Handle form submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
                        // Generate temporary password
                        function generateTemporaryPassword($length = 12) {
                            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
                            $password = '';
                            for ($i = 0; $i < $length; $i++) {
                                $password .= $chars[random_int(0, strlen($chars) - 1)];
                            }
                            return $password;
                        }
                        
                        $username = 'admin';
                        $email = 'admin@newayatra.com';
                        $temp_password = generateTemporaryPassword();
                        $temp_password_hash = 'temp_' . $temp_password;
                        
                        // Create admin user
                        $sql = "INSERT INTO users (username, email, password, full_name, role, status) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $full_name = 'Administrator';
                        $role = 'admin';
                        $status = 'active';
                        
                        $stmt->bind_param('ssssss', $username, $email, $temp_password_hash, $full_name, $role, $status);
                        
                        if ($stmt->execute()) {
                            $success = true;
                            $credentials = [
                                'username' => $username,
                                'email' => $email,
                                'password' => $temp_password
                            ];
                            
                            // Save to session for display
                            $_SESSION['temp_admin_credentials'] = $credentials;
                            
                            // Also save to file
                            $backup_info = "========================================\n";
                            $backup_info .= "TEMPORARY ADMIN ACCOUNT CREATED\n";
                            $backup_info .= "Created on: " . date('Y-m-d H:i:s') . "\n";
                            $backup_info .= "========================================\n\n";
                            $backup_info .= "Login URL: " . SITE_URL . "/admin/login.php\n";
                            $backup_info .= "Username: " . $username . "\n";
                            $backup_info .= "Email: " . $email . "\n";
                            $backup_info .= "Temporary Password: " . $temp_password . "\n\n";
                            $backup_info .= "========================================\n";
                            $backup_info .= "IMPORTANT: Change password after first login!\n";
                            $backup_info .= "This file should be deleted after noting credentials.\n";
                            $backup_info .= "========================================\n";
                            
                            file_put_contents('temp_admin_credentials.txt', $backup_info);
                        }
                    }
                    
                    if (isset($success) && $success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Admin account created successfully!
                        </div>
                        
                        <div class="step">
                            <div class="step-number">1</div>
                            <div>
                                <h5 class="mb-1">Important Security Note</h5>
                                <p class="mb-0">Please save these credentials in a secure place and delete the generated file after noting them.</p>
                            </div>
                        </div>
                        
                        <div class="credential-box">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Login URL:</strong><br>
                                    <code><?php echo SITE_URL; ?>/admin/login.php</code>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Username:</strong><br>
                                    <code><?php echo htmlspecialchars($credentials['username']); ?></code>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Email:</strong><br>
                                    <code><?php echo htmlspecialchars($credentials['email']); ?></code>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Temporary Password:</strong><br>
                                    <code class="text-danger"><?php echo htmlspecialchars($credentials['password']); ?></code>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div>
                                <h5 class="mb-1">Next Steps</h5>
                                <p class="mb-2">1. Login using the credentials above</p>
                                <p class="mb-2">2. You will be prompted to change your password</p>
                                <p class="mb-2">3. Delete the generated file: <code>temp_admin_credentials.txt</code></p>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-setup me-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Go to Login Page
                            </a>
                            <a href="temp_admin_credentials.txt" download class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>Download Credentials
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <div class="step">
                            <div class="step-number">1</div>
                            <div>
                                <h5 class="mb-1">What This Will Do</h5>
                                <p class="mb-0">This setup will create a temporary admin account with a randomly generated password.</p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div>
                                <h5 class="mb-1">Account Details</h5>
                                <p class="mb-0">Username: <strong>admin</strong></p>
                                <p class="mb-0">Email: <strong>admin@newayatra.com</strong></p>
                                <p class="mb-0">Password: <strong>Randomly generated</strong></p>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div>
                                <h5 class="mb-1">Security Warning</h5>
                                <p class="mb-2">A text file with credentials will be generated. Delete it after saving the credentials.</p>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="text-center mt-4">
                                <button type="submit" name="create_admin" class="btn btn-setup">
                                    <i class="fas fa-user-plus me-2"></i>Create Admin Account
                                </button>
                            </div>
                        </form>
                    <?php endif;
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-copy functionality for credentials
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('Copied to clipboard!', 'success');
            });
        }
        
        function showToast(message, type) {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>
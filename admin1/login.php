<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = new Auth();

// Check if already logged in
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Process login form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Generate CSRF token for login form
if (empty($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Newa Yatra</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-user-shield me-2"></i>Admin Panel</h1>
                <p>Newa Yatra - Travel Management System</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger error-alert" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>You have been logged out successfully.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['expired']) && $_GET['expired'] == '1'): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-clock me-2"></i>Your session has expired. Please login again.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['login_token']; ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i> Username or Email
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter username or email" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required
                                   autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <a href="forgot-password.php" class="forgot-link">
                                <i class="fas fa-key me-1"></i> Forgot Password?
                            </a>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <p class="mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Newa Yatra Admin System v1.0
                </p>
            </div>
        </div>
        
        <div class="text-center mt-4 text-white">
            <p class="mb-1">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Restricted Access - Authorized Personnel Only
            </p>
            <p class="small mb-0">
                &copy; <?php echo date('Y'); ?> Newa Yatra. All rights reserved.
            </p>
        </div>
    </div>

    <!-- Local JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <script>
    // Login form specific JavaScript
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!username || !password) {
            e.preventDefault();
            alert('Please enter both username and password');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
        submitBtn.disabled = true;
        
        // Re-enable button after 5 seconds (in case of network error)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });
    
    // Auto-focus on username field
    document.addEventListener('DOMContentLoaded', function() {
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
    });
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>
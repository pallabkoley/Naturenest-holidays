<?php
// Authentication and Authorization System for Newa Yatra

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
  /**
 * Login user - FIXED VERSION
 */
public function login($username, $password) {
    $conn = $this->db->getConnection();
    
    $sql = "SELECT id, username, email, password, full_name, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) 
            AND status = 'active'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Debug: Check what's in the database
        error_log("Login attempt - Username: $username, DB Password Hash: " . $user['password']);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Set session expiry (8 hours)
            $_SESSION['expire_time'] = time() + (8 * 3600);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Debug
            error_log("Login successful for user: " . $user['username']);
            error_log("Session data set: " . json_encode($_SESSION));
            
            return ['success' => true, 'user' => $user];
        } else {
            error_log("Password verification failed for user: $username");
        }
    } else {
        error_log("User not found: $username");
    }
    
    return ['success' => false, 'message' => 'Invalid username or password'];
}
    
    /**
     * Register new user (for admin panel)
     */
    public function register($data) {
        $conn = $this->db->getConnection();
        
        // Check if user already exists
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $data['username'], $data['email']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, full_name, role) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $data['username'],
            $data['email'],
            $hashed_password,
            $data['full_name'],
            $data['role']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $stmt->insert_id];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Delete remember me cookie
        $this->deleteRememberMeCookie();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
   /**
 * Check if user is logged in - FIXED VERSION
 */
public function isLoggedIn() {
    // Check if session variables exist
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        error_log("isLoggedIn: logged_in session variable not set");
        return false;
    }
    
    // Check session expiry
    if (!isset($_SESSION['expire_time']) || $_SESSION['expire_time'] < time()) {
        error_log("isLoggedIn: Session expired");
        $this->logout();
        return false;
    }
    
    // Check if essential session variables exist
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        error_log("isLoggedIn: Essential session variables missing");
        return false;
    }
    
    error_log("isLoggedIn: User is logged in - User ID: " . $_SESSION['user_id']);
    return true;
}
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        if ($this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }
        return false;
    }
    
    /**
     * Get current user info
     */
    public function getUserInfo() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin($redirect_url = '/admin/login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: " . SITE_URL . $redirect_url);
            exit();
        }
    }
    
    /**
     * Require admin access - redirect if not admin
     */
    public function requireAdmin($redirect_url = '/admin/dashboard.php') {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            header("Location: " . SITE_URL . $redirect_url);
            exit();
        }
    }
    
    /**
     * Update user password
     */
    public function updatePassword($user_id, $new_password) {
        $conn = $this->db->getConnection();
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Reset password (generate reset token)
     */
    public function resetPassword($email) {
        $conn = $this->db->getConnection();
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $sql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        $stmt->execute();
        
        // Send reset email
        $reset_link = SITE_URL . "/admin/reset-password.php?token=" . $token;
        $email_sent = $this->sendPasswordResetEmail($email, $reset_link);
        
        return [
            'success' => true,
            'token' => $token,
            'email_sent' => $email_sent
        ];
    }
    
    /**
     * Validate reset token
     */
    public function validateResetToken($token) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows === 1;
    }
    
    /**
     * Complete password reset
     */
    public function completePasswordReset($token, $new_password) {
        $conn = $this->db->getConnection();
        
        // Validate token
        if (!$this->validateResetToken($token)) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL 
                WHERE reset_token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $token);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Password reset failed'];
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($user_id) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 3600); // 30 days
        
        // Store token in database
        $conn = $this->db->getConnection();
        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $token, $user_id);
        $stmt->execute();
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', false, true);
    }
    
    /**
     * Check remember me cookie
     */
    private function checkRememberMeCookie() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, username, email, full_name, role 
                FROM users 
                WHERE remember_token = ? AND status = 'active'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['expire_time'] = time() + (8 * 3600);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete remember me cookie
     */
    private function deleteRememberMeCookie() {
        if (isset($_COOKIE['remember_token'])) {
            // Remove from database
            $conn = $this->db->getConnection();
            $sql = "UPDATE users SET remember_token = NULL WHERE remember_token = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_COOKIE['remember_token']);
            $stmt->execute();
            
            // Delete cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $reset_link) {
        $subject = "Password Reset Request - Newa Yatra";
        $message = $this->getEmailTemplate('password_reset', [
            'reset_link' => $reset_link,
            'expiry_time' => '1 hour'
        ]);
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send email using SMTP
     */
    private function sendEmail($to, $subject, $message) {
        // Use PHPMailer or similar library for production
        // This is a basic implementation
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Newa Yatra <noreply@newayatra.com>" . "\r\n";
        $headers .= "Reply-To: " . getSiteSetting('site_email') . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers() {
        if (!$this->isAdmin()) {
            return [];
        }
        
        $conn = $this->db->getConnection();
        $sql = "SELECT id, username, email, full_name, role, status, last_login, created_at 
                FROM users 
                ORDER BY created_at DESC";
        
        $result = $conn->query($sql);
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Update user status
     */
    public function updateUserStatus($user_id, $status) {
        if (!$this->isAdmin()) {
            return false;
        }
        
        $conn = $this->db->getConnection();
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete user
     */
    public function deleteUser($user_id) {
        if (!$this->isAdmin()) {
            return false;
        }
        
        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            return false;
        }
        
        $conn = $this->db->getConnection();
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        return $stmt->execute();
    }
}

// Initialize auth object
$auth = new Auth();

// Auto-login from remember me cookie
if (!$auth->isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $auth->checkRememberMeCookie();
}
?>
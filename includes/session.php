<?php
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Session configuration
            session_name('NEWAYATRA_SESSID');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_start();
            
            // Initialize session array if not set
            if (!isset($_SESSION['initialized'])) {
                $_SESSION['initialized'] = true;
                $_SESSION['logged_in'] = false;
                $_SESSION['user_id'] = null;
                $_SESSION['username'] = null;
                $_SESSION['role'] = 'guest';
                $_SESSION['csrf_tokens'] = [];
            }
        }
    }
    
    public static function generateCSRFToken($formName) {
        self::start();
        
        if (!isset($_SESSION['csrf_tokens'][$formName])) {
            $_SESSION['csrf_tokens'][$formName] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_tokens'][$formName];
    }
    
    public static function validateCSRFToken($formName, $token) {
        self::start();
        
        if (!isset($_SESSION['csrf_tokens'][$formName])) {
            return false;
        }
        
        $isValid = hash_equals($_SESSION['csrf_tokens'][$formName], $token);
        
        // Remove token after validation (one-time use)
        unset($_SESSION['csrf_tokens'][$formName]);
        
        return $isValid;
    }
}
?>
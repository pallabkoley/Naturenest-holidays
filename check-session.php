<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<pre>";
echo "=== SESSION & LOGIN DEBUG ===\n\n";

// Check session
echo "1. Session Status:\n";
echo "   session_status(): " . session_status() . " (2 = active)\n";
echo "   session_id(): " . session_id() . "\n";
echo "   session_name(): " . session_name() . "\n";
echo "   session_save_path(): " . session_save_path() . "\n";

// Check session variables
echo "\n2. Session Variables:\n";
if (empty($_SESSION)) {
    echo "   ✗ No session data found\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "   \$_SESSION['$key'] = " . (is_array($value) ? print_r($value, true) : $value) . "\n";
    }
}

// Check headers
echo "\n3. Headers Sent:\n";
echo "   headers_sent(): " . (headers_sent($file, $line) ? "YES (file: $file, line: $line)" : "NO") . "\n";

// Check if user is logged in
echo "\n4. Login Status:\n";
if (isset($_SESSION['user_id'])) {
    echo "   ✓ Logged in as User ID: " . $_SESSION['user_id'] . "\n";
    echo "   Username: " . ($_SESSION['user_name'] ?? 'Not set') . "\n";
    echo "   Role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
    
    // Verify user exists in DB
    $sql = "SELECT id, username, status FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo "   ✓ User exists in database\n";
        echo "   Database username: " . $user['username'] . "\n";
        echo "   Status: " . $user['status'] . "\n";
    } else {
        echo "   ✗ User NOT found in database!\n";
    }
} else {
    echo "   ✗ Not logged in\n";
}

// Test redirect
echo "\n5. Redirect Test:\n";
echo "   <a href='test-redirect.php'>Test redirect</a>\n";

// Check PHP settings
echo "\n6. PHP Settings:\n";
echo "   output_buffering: " . ini_get('output_buffering') . "\n";
echo "   display_errors: " . ini_get('display_errors') . "\n";
echo "   error_reporting: " . error_reporting() . "\n";

// Check directory permissions
echo "\n7. Directory Permissions:\n";
echo "   admin/ directory exists: " . (is_dir('admin') ? 'YES' : 'NO') . "\n";
echo "   admin/index.php exists: " . (file_exists('admin/index.php') ? 'YES' : 'NO') . "\n";
echo "   admin/index.php readable: " . (is_readable('admin/index.php') ? 'YES' : 'NO') . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";

// Quick login test form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $_SESSION['test_user'] = 'test_' . time();
    echo "<p>Test session set: " . $_SESSION['test_user'] . "</p>";
    echo "<p><a href='check-session.php'>Refresh to check</a></p>";
}
?>

<h3>Test Session</h3>
<form method="POST">
    <button type="submit">Set Test Session</button>
</form>

<h3>Quick Actions:</h3>
<p><a href="destroy-session.php">Destroy Session</a></p>
<p><a href="admin/index.php">Try accessing index.php directly</a></p>
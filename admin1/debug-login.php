<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

echo "<h2>Debug Login System</h2>";
echo "<pre>";

// Test database connection
try {
    $db = new Database();
    echo "✓ Database connected successfully\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// Test users table
$conn = $db->getConnection();
$result = $conn->query("SELECT id, username, email, role FROM users");
echo "Users in database: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['username']} ({$row['email']}) - {$row['role']}\n";
}

// Check if admin exists
$result = $conn->query("SELECT id, password FROM users WHERE username = 'admin' OR email = 'admin'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✓ Admin user found (ID: {$user['id']})\n";
    
    // Test password hash
    $test_password = 'admin123';
    if (password_verify($test_password, $user['password'])) {
        echo "✓ Password 'admin123' is correct\n";
    } else {
        echo "✗ Password 'admin123' is NOT correct\n";
        echo "  Hash in DB: {$user['password']}\n";
        echo "  New hash for 'admin123': " . password_hash($test_password, PASSWORD_DEFAULT) . "\n";
    }
} else {
    echo "✗ Admin user not found\n";
}

// Test session
echo "\nSession ID: " . session_id() . "\n";
echo "Session data:\n";
print_r($_SESSION);

// Test auth class
$auth = new Auth();
echo "\nAuth class initialized\n";
echo "Is logged in? " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "\n";

echo "</pre>";

// Form to test login
?>
<form method="POST" action="login.php">
    <h3>Test Login Form</h3>
    <input type="text" name="username" placeholder="Username" value="admin" required><br>
    <input type="password" name="password" placeholder="Password" value="admin123" required><br>
    <button type="submit">Test Login</button>
</form>
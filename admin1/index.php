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
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Redirect to login
    header("Location: login.php");
    exit();
}
?>
<?php
// admin-sidebar.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = new Auth();
$auth->requireLogin('login.php');

// Get user info
$user = $auth->getUserInfo();

// If not admin, show restricted access
if (!$auth->isAdmin()) {
    die('<div style="text-align:center; padding:50px;">
        <h1 style="color:#c66b05;">Access Denied</h1>
        <p>You do not have permission to access the admin panel.</p>
        <a href="../index.php" style="color:#8f1402;">Return to Home</a>
    </div>');
}

// Database connection
$db = new Database();
$conn = $db->getConnection();

// Get all statistics for sidebar
$total_tours = 0;
$total_destinations = 0;
$total_services = 0;
$total_cars = 0;
$total_testimonials = 0;
$total_activities = 0;
$total_bookings = 0;
$total_messages = 0;
$unread_messages = 0;
$total_destination_weddings = 0;
$total_package_lists = 0;
$total_festivals = 0;

// Query for stats
$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_tours = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_destinations = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_services = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'");
if ($result) { $row = $result->fetch_assoc(); $total_cars = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'approved' AND featured = 1");
if ($result) { $row = $result->fetch_assoc(); $total_testimonials = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM popular_activities WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_activities = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
if ($result) { $row = $result->fetch_assoc(); $total_bookings = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
if ($result) { $row = $result->fetch_assoc(); $total_messages = $row['count']; }

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
if ($result) { $row = $result->fetch_assoc(); $unread_messages = $row['count']; }

// Get tour package lists count
$result = $conn->query("SELECT COUNT(*) as count FROM tour_package_lists");
if ($result) { $row = $result->fetch_assoc(); $total_package_lists = $row['count']; }

// Destination weddings count (only if table exists)
$result = @$conn->query("SELECT COUNT(*) as count FROM destination_weddings WHERE status = 1");
if ($result) { $row = $result->fetch_assoc(); $total_destination_weddings = $row['count']; }

// Festivals count (only if table exists)
$result = @$conn->query("SELECT COUNT(*) as count FROM festivals WHERE status = 'active'");
if ($result) { $row = $result->fetch_assoc(); $total_festivals = $row['count']; }

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Site name (from settings, fallback to default)
$adminSiteName = $SITE['site_name'] ?? 'Nature Nest Holidays';
?>

<div class="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-compass me-2"></i><?php echo htmlspecialchars($adminSiteName); ?></h2>
        <p>Admin Panel</p>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
        <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-tours.php' ? 'active' : ''; ?>" href="manage-tours.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Individual Tours</span>
                    <?php if ($total_tours > 0): ?>
                        <span class="badge" style="background: #28a745; margin-left: auto;"><?php echo $total_tours; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-tour-lists.php' ? 'active' : ''; ?>" href="manage-tour-lists.php">
                    <i class="fas fa-layer-group"></i>
                    <span>Tour Package Lists</span>
                    <?php if ($total_package_lists > 0): ?>
                        <span class="badge" style="background: #6f42c1; margin-left: auto;"><?php echo $total_package_lists; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-destinations.php' ? 'active' : ''; ?>" href="manage-destinations.php">
                    <i class="fas fa-mountain"></i>
                    <span>Destinations</span>
                    <?php if ($total_destinations > 0): ?>
                        <span class="badge" style="background: #007bff; margin-left: auto;"><?php echo $total_destinations; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-treks.php' ? 'active' : ''; ?>" href="manage-treks.php">
                    <i class="fas fa-hiking"></i>
                    <span>Treks</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-festivals.php' ? 'active' : ''; ?>" href="manage-festivals.php">
                    <i class="fas fa-dharmachakra"></i>
                    <span>Festivals</span>
                    <?php if ($total_festivals > 0): ?>
                        <span class="badge" style="background: #e83e8c; margin-left: auto;"><?php echo $total_festivals; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-destination-weddings.php' ? 'active' : ''; ?>" href="manage-destination-weddings.php">
                    <i class="fas fa-ring"></i>
                    <span>Destination Wedding</span>
                    <?php if ($total_destination_weddings > 0): ?>
                        <span class="badge" style="background: #e83e8c; margin-left: auto;"><?php echo $total_destination_weddings; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-services.php' ? 'active' : ''; ?>" href="manage-services.php">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Services</span>
                    <?php if ($total_services > 0): ?>
                        <span class="badge" style="background: #17a2b8; margin-left: auto;"><?php echo $total_services; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <!--<li class="nav-item">-->
            <!--    <a class="nav-link <?php echo $current_page == 'manage-cars.php' ? 'active' : ''; ?>" href="manage-cars.php">-->
            <!--        <i class="fas fa-car"></i>-->
            <!--        <span>Car Rental</span>-->
            <!--        <?php if ($total_cars > 0): ?>-->
            <!--            <span class="badge" style="background: #6f42c1; margin-left: auto;"><?php echo $total_cars; ?></span>-->
            <!--        <?php endif; ?>-->
            <!--    </a>-->
            <!--</li>-->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-testimonials.php' ? 'active' : ''; ?>" href="manage-testimonials.php">
                    <i class="fas fa-comment-dots"></i>
                    <span>Testimonials</span>
                    <?php if ($total_testimonials > 0): ?>
                        <span class="badge" style="background: #ffc107; color: #000; margin-left: auto;"><?php echo $total_testimonials; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <!--<li class="nav-item">-->
            <!--    <a class="nav-link <?php echo $current_page == 'manage-activities.php' ? 'active' : ''; ?>" href="manage-activities.php">-->
            <!--        <i class="fas fa-biking"></i>-->
            <!--        <span>Activities</span>-->
            <!--        <?php if ($total_activities > 0): ?>-->
            <!--            <span class="badge" style="background: #20c997; margin-left: auto;"><?php echo $total_activities; ?></span>-->
            <!--        <?php endif; ?>-->
            <!--    </a>-->
            <!--</li>-->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-bookings.php' ? 'active' : ''; ?>" href="manage-bookings.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                    <?php if ($total_bookings > 0): ?>
                        <span class="badge" style="background: #dc3545; margin-left: auto;"><?php echo $total_bookings; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'manage-slider.php' ? 'active' : ''; ?>" href="manage-slider.php">
                    <i class="fas fa-sliders-h"></i>
                    <span>Hero Slider</span>
                </a>
            </li>
            <!--<li class="nav-item">-->
            <!--    <a class="nav-link <?php echo $current_page == 'manage-messages.php' ? 'active' : ''; ?>" href="manage-messages.php">-->
            <!--        <i class="fas fa-envelope"></i>-->
            <!--        <span>Messages</span>-->
            <!--        <?php if ($total_messages > 0): ?>-->
            <!--            <span class="badge" style="background: #6c757d; margin-left: auto;"><?php echo $total_messages; ?></span>-->
            <!--            <?php if ($unread_messages > 0): ?>-->
            <!--                <span class="badge" style="background: #dc3545; margin-left: 5px;"><?php echo $unread_messages; ?></span>-->
            <!--            <?php endif; ?>-->
            <!--        <?php endif; ?>-->
            <!--    </a>-->
            <!--</li>-->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>
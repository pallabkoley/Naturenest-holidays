<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize auth and check login
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

// Get all statistics for dashboard
$conn = $db->getConnection();

// 1. Total active tours (from frontend - shows 6)
$total_tours = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM tour_packages WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_tours = $row['count'];
}

// 2. Total destinations (from frontend - shows 8)
$total_destinations = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM destinations WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_destinations = $row['count'];
}

// 3. Total services (from frontend - shows 6)
$total_services = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_services = $row['count'];
}

// 4. Total cars for rental (from frontend - shows 6)
$total_cars = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM rental_cars WHERE status = 'available'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_cars = $row['count'];
}

// 5. Total featured testimonials (from frontend - shows 4)
$total_testimonials = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM testimonials WHERE status = 'approved' AND featured = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $total_testimonials = $row['count'];
}

// 6. Total activities (from frontend - shows 3)
$total_activities = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM popular_activities WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_activities = $row['count'];
}

// 7. Total bookings
$total_bookings = 0;
$pending_bookings = 0;
$confirmed_bookings = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
if ($result) {
    $row = $result->fetch_assoc();
    $total_bookings = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    $pending_bookings = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
if ($result) {
    $row = $result->fetch_assoc();
    $confirmed_bookings = $row['count'];
}

// 8. Total messages
$total_messages = 0;
$unread_messages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages");
if ($result) {
    $row = $result->fetch_assoc();
    $total_messages = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'");
if ($result) {
    $row = $result->fetch_assoc();
    $unread_messages = $row['count'];
}

// 9. Recent data for tables
$recent_bookings = [];
$result = $conn->query("SELECT b.*, t.title as tour_name 
                        FROM bookings b 
                        LEFT JOIN tour_packages t ON b.tour_package_id = t.id 
                        ORDER BY b.created_at DESC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}

$recent_messages = [];
$result = $conn->query("SELECT * FROM contact_messages 
                        ORDER BY created_at DESC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_messages[] = $row;
    }
}

$recent_testimonials = [];
$result = $conn->query("SELECT * FROM testimonials 
                        WHERE status = 'approved'
                        ORDER BY created_at DESC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_testimonials[] = $row;
    }
}

// 10. Get today's date for reports
$today = date('Y-m-d');
$bookings_today = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = '$today'");
if ($result) {
    $row = $result->fetch_assoc();
    $bookings_today = $row['count'];
}

// 11. Calculate revenue (if you have payment data)
$total_revenue = 0;
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE payment_status = 'paid'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_revenue = $row['revenue'] ? $row['revenue'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Newa Yatra</title>
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Additional styles for enhanced dashboard */
        .revenue-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .revenue-card .stat-icon {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .today-card {
            background: linear-gradient(135deg, #007bff, #17a2b8);
            color: white;
        }
        
        .today-card .stat-icon {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-completed { background: #6c757d; color: white; }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Sidebar -->
         <?php include 'admin-sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="navbar-top">
                <div class="welcome-text">
                    <i class="fas fa-home me-2"></i>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>!
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline" onclick="return confirm('Are you sure you want to logout?');">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <!-- Total Revenue -->
                    <div class="stat-card revenue-card">
                        <div class="stat-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-number">₹<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-title">Total Revenue</div>
                    </div>
                    
                    <!-- Today's Bookings -->
                    <div class="stat-card today-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-number"><?php echo $bookings_today; ?></div>
                        <div class="stat-title">Today's Bookings</div>
                    </div>
                    
                    <!-- Total Bookings -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(0, 123, 255, 0.1); color: #007bff;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_bookings; ?></div>
                        <div class="stat-title">Total Bookings</div>
                    </div>
                    
                    <!-- Pending Bookings -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_bookings; ?></div>
                        <div class="stat-title">Pending Bookings</div>
                    </div>
                    
                    <!-- Total Tours -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_tours; ?></div>
                        <div class="stat-title">Active Tours</div>
                    </div>
                    
                    <!-- Total Destinations -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_destinations; ?></div>
                        <div class="stat-title">Destinations</div>
                    </div>
                    
                    <!-- Total Services -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_services; ?></div>
                        <div class="stat-title">Services</div>
                    </div>
                    
                    <!-- Total Cars -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(32, 201, 151, 0.1); color: #20c997;">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_cars; ?></div>
                        <div class="stat-title">Available Cars</div>
                    </div>
                </div>
                
                <!-- Recent Data Tables -->
                <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 30px;">
                    <!-- Recent Bookings -->
                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-history"></i> Recent Bookings
                        </div>
                        <div class="table-body">
                            <?php if (empty($recent_bookings)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No bookings found</p>
                                </div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ref No.</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr onclick="window.location='manage-bookings.php?action=view&id=<?php echo $booking['id']; ?>'" style="cursor: pointer;">
                                                <td><strong><?php echo substr($booking['booking_reference'], 0, 8); ?>...</strong></td>
                                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d', strtotime($booking['travel_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-end mt-3">
                                    <a href="manage-bookings.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View All
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Messages -->
                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-envelope"></i> Recent Messages
                        </div>
                        <div class="table-body">
                            <?php if (empty($recent_messages)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No messages found</p>
                                </div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_messages as $message): ?>
                                            <tr onclick="window.location='manage-messages.php?action=view&id=<?php echo $message['id']; ?>'" style="cursor: pointer;">
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($message['subject'], 0, 30)); ?>...</td>
                                                <td>
                                                    <span class="status-badge" style="background: <?php echo $message['status'] == 'unread' ? '#007bff' : '#6c757d'; ?>; color: white;">
                                                        <?php echo ucfirst($message['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-end mt-3">
                                    <a href="manage-messages.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View All
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Testimonials -->
                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-star"></i> Recent Testimonials
                        </div>
                        <div class="table-body">
                            <?php if (empty($recent_testimonials)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No testimonials found</p>
                                </div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Rating</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_testimonials as $testimonial): ?>
                                            <tr onclick="window.location='manage-testimonials.php?action=view&id=<?php echo $testimonial['id']; ?>'" style="cursor: pointer;">
                                                <td><?php echo htmlspecialchars($testimonial['client_name']); ?></td>
                                                <td>
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $testimonial['rating']): ?>
                                                                <i class="fas fa-star"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($testimonial['featured']): ?>
                                                        <span class="status-badge" style="background: #ffc107; color: #000;">
                                                            <i class="fas fa-star me-1"></i>Featured
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge" style="background: #6c757d; color: white;">
                                                            Approved
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="text-end mt-3">
                                    <a href="manage-testimonials.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View All
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="table-container mt-4">
                    <div class="table-header">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </div>
                    <div class="table-body">
                        <div class="quick-actions">
                            <a href="manage-tours.php?action=add" class="btn btn-outline">
                                <i class="fas fa-plus me-2"></i>Add New Tour
                            </a>
                            <a href="manage-services.php?action=add" class="btn btn-outline">
                                <i class="fas fa-concierge-bell me-2"></i>Add Service
                            </a>
                            <a href="manage-car.php?action=add" class="btn btn-outline">
                                <i class="fas fa-car me-2"></i>Add Car
                            </a>
                            <a href="manage-testimonials.php?action=add" class="btn btn-outline">
                                <i class="fas fa-comment-medical me-2"></i>Add Testimonial
                            </a>
                            <a href="manage-bookings.php" class="btn btn-outline">
                                <i class="fas fa-calendar-check me-2"></i>View Bookings
                            </a>
                            <a href="manage-messages.php" class="btn btn-outline">
                                <i class="fas fa-envelope me-2"></i>Check Messages
                            </a>
                            <a href="reports.php" class="btn btn-outline">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </a>
                            <a href="settings.php" class="btn btn-outline">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="table-container mt-4">
                    <div class="table-header">
                        <i class="fas fa-server"></i> System Status
                    </div>
                    <div class="table-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <h6>Database Status</h6>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 10px; height: 10px; background: #28a745; border-radius: 50%;"></div>
                                    <span>Connected</span>
                                </div>
                            </div>
                            <div>
                                <h6>PHP Version</h6>
                                <code><?php echo phpversion(); ?></code>
                            </div>
                            <div>
                                <h6>Server Time</h6>
                                <span><?php echo date('H:i:s'); ?></span>
                            </div>
                            <div>
                                <h6>Memory Usage</h6>
                                <span><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local JS -->
    <script src="assets/js/admin-script.js"></script>
    
    <script>
    // Dashboard specific JavaScript
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            if (sidebar.style.display === 'none') {
                sidebar.style.display = 'block';
                mainContent.style.marginLeft = '250px';
            } else {
                sidebar.style.display = 'none';
                mainContent.style.marginLeft = '0';
            }
        }
    }
    
    // Auto-refresh dashboard every 60 seconds
    setInterval(function() {
        // Refresh stats using AJAX
        fetch('api/dashboard-stats.php')
            .then(response => response.json())
            .then(data => {
                updateDashboardStats(data);
            })
            .catch(error => console.error('Auto-refresh error:', error));
    }, 60000);
    
    function updateDashboardStats(data) {
        // Update all stat cards
        const statElements = {
            'total-revenue': '.stat-card:nth-child(1) .stat-number',
            'today-bookings': '.stat-card:nth-child(2) .stat-number',
            'total-bookings': '.stat-card:nth-child(3) .stat-number',
            'pending-bookings': '.stat-card:nth-child(4) .stat-number',
            'total-tours': '.stat-card:nth-child(5) .stat-number',
            'total-destinations': '.stat-card:nth-child(6) .stat-number',
            'total-services': '.stat-card:nth-child(7) .stat-number',
            'total-cars': '.stat-card:nth-child(8) .stat-number'
        };
        
        Object.keys(statElements).forEach(key => {
            const element = document.querySelector(statElements[key]);
            if (element && data[key]) {
                if (key === 'total-revenue') {
                    element.textContent = '₹' + parseFloat(data[key]).toFixed(2);
                } else {
                    element.textContent = data[key];
                }
            }
        });
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded successfully');
        
        // Make table rows clickable
        document.querySelectorAll('.table tbody tr[onclick]').forEach(row => {
            row.style.cursor = 'pointer';
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + B: Bookings
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            window.location.href = 'manage-bookings.php';
        }
        
        // Ctrl + M: Messages
        if (e.ctrlKey && e.key === 'm') {
            e.preventDefault();
            window.location.href = 'manage-messages.php';
        }
        
        // Ctrl + D: Dashboard
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = 'dashboard.php';
        }
        
        // Ctrl + L: Logout
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    });
    </script>
</body>
</html>
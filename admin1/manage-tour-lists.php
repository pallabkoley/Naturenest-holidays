<?php
// manage-tour-lists.php - UPDATED VERSION
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

// Database connection
$conn = $db->getConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'delete_package':
                // Delete tour package list
                $package_id = intval($_POST['package_id']);
                
                // Get package data to delete images
                $result = $conn->query("SELECT featured_image, gallery_images FROM tour_package_lists WHERE id = $package_id");
                if ($result && $row = $result->fetch_assoc()) {
                    // Delete featured image
                    if ($row['featured_image'] && file_exists('../' . $row['featured_image'])) {
                        unlink('../' . $row['featured_image']);
                    }
                    
                    // Delete gallery images
                    if ($row['gallery_images']) {
                        $gallery_images = json_decode($row['gallery_images'], true);
                        foreach ($gallery_images as $image) {
                            if (file_exists('../' . $image)) {
                                unlink('../' . $image);
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM tour_package_lists WHERE id = ?");
                $stmt->bind_param("i", $package_id);
                
                if ($stmt->execute()) {
                    $message = 'Tour package list deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting package: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all tour package lists
$packages = [];
$result = $conn->query("
    SELECT tpl.*, 
           COUNT(tp.id) as total_tours,
           GROUP_CONCAT(tp.title SEPARATOR ', ') as tour_titles
    FROM tour_package_lists tpl
    LEFT JOIN tour_packages tp ON FIND_IN_SET(tp.id, REPLACE(REPLACE(REPLACE(tpl.selected_tours, '[', ''), ']', ''), '\"', ''))
    GROUP BY tpl.id
    ORDER BY tpl.position ASC, tpl.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}

// Get stats
$total_packages = count($packages);
$active_packages = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tour_package_lists WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $active_packages = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tour Package Lists - Newa Yatra Admin</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        .package-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        
        .package-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .package-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .tour-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 10px 0;
        }
        
        .tour-badge {
            background: #e9ecef;
            color: #495057;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .price-tag {
            font-size: 18px;
            font-weight: bold;
            color: #c66b05;
        }
        
        .duration-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        /* Table styles */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 20px;
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
            color: white;
            padding: 15px 25px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-cell-image {
            width: 100px;
            min-width: 100px;
        }
        
        .package-table-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Include Sidebar from separate file -->
        <?php include 'admin-sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="navbar-top">
                <div class="welcome-text">
                    <i class="fas fa-layer-group me-2"></i>Tour Package Lists Management
                </div>
                <div>
                    <span class="text-muted me-3">
                        <i class="fas fa-box me-1"></i>
                        <?php echo $total_packages; ?> Total Packages
                    </span>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_packages; ?></div>
                        <div class="stat-title">Total Packages</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_packages; ?></div>
                        <div class="stat-title">Active</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_packages - $active_packages; ?></div>
                        <div class="stat-title">Draft/Inactive</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number">New</div>
                        <div class="stat-title">
                            <a href="add-tour-package-list.php" style="color: inherit; text-decoration: none;">
                                Add Package <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Package Lists -->
                <?php if (empty($packages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-layer-group"></i>
                        <h3>No Tour Package Lists Found</h3>
                        <p>Get started by creating your first tour package list.</p>
                        <a href="add-tour-package-list.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Create First Package
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-list"></i> All Tour Package Lists
                            <div class="table-actions">
                                <a href="add-tour-package-list.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add New
                                </a>
                            </div>
                        </div>
                        <div class="table-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Image</th>
                                        <th>Package Details</th>
                                        <th style="width: 120px;">Duration</th>
                                        <th style="width: 120px;">Price</th>
                                        <th style="width: 100px;">Tours</th>
                                        <th style="width: 100px;">Status</th>
                                        <th style="width: 120px;">Created</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packages as $package): 
                                        $selected_tours = $package['selected_tours'] ? json_decode($package['selected_tours'], true) : [];
                                        $tour_count = count($selected_tours);
                                    ?>
                                    <tr>
                                        <td class="table-cell-image">
                                            <?php if ($package['featured_image']): ?>
                                                <img src="../<?php echo htmlspecialchars($package['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($package['title']); ?>" 
                                                     class="package-table-image">
                                            <?php else: ?>
                                                <div class="tour-table-image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($package['title']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php 
                                                    if ($package['tour_titles']) {
                                                        $titles = explode(', ', $package['tour_titles']);
                                                        echo htmlspecialchars(implode(', ', array_slice($titles, 0, 3)));
                                                        if (count($titles) > 3) echo '...';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="duration-badge">
                                                <?php echo $package['duration_days']; ?>D/<?php echo $package['duration_nights']; ?>N
                                            </span>
                                        </td>
                                        <td>
                                            <span class="price-tag">
                                                ₹<?php echo number_format($package['price'], 2); ?>
                                                <?php if ($package['price_per_person']): ?>
                                                    <small>/person</small>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $tour_count; ?> tours
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $package['status']; ?>">
                                                <?php echo ucfirst($package['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($package['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit-tour-package-list.php?id=<?php echo $package['id']; ?>" 
                                                   class="btn btn-icon btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!--<a href="../pages/tour-package-detail.php?slug=<?php echo $package['slug']; ?>" target="_blank" -->
                                                <!--   class="btn btn-icon btn-info" title="View">-->
                                                <!--    <i class="fas fa-eye"></i>-->
                                                <!--</a>-->
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this package list?');">
                                                    <input type="hidden" name="action" value="delete_package">
                                                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                                    <button type="submit" class="btn btn-icon btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin-script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Make rows clickable
        $('.table tbody tr').click(function(e) {
            if (!$(e.target).closest('.action-buttons').length) {
                const editLink = $(this).find('a[title="Edit"]').attr('href');
                if (editLink) {
                    window.location.href = editLink;
                }
            }
        });
        
        // Keyboard shortcuts
        $(document).keydown(function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                window.location.href = 'add-tour-package-list.php';
            }
        });
    });
    </script>
</body>
</html>
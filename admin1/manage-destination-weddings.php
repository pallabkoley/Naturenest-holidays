<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin('login.php');
$user = $auth->getUserInfo();

if (!$auth->isAdmin()) {
    die('<div style="text-align:center; padding:50px;">
        <h1 style="color:#c66b05;">Access Denied</h1>
        <p>You do not have permission to access the admin panel.</p>
        <a href="../index.php" style="color:#8f1402;">Return to Home</a>
    </div>');
}

$conn = $db->getConnection();
$message = '';
$message_type = '';

/* ---------- Table already exists — safe fallback ---------- */
$conn->query("CREATE TABLE IF NOT EXISTS destination_weddings (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    destination VARCHAR(100) NOT NULL,
    wedding_style VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT(11) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

/* Upload folder */
$upload_dir = '../assets/uploads/destination-weddings/';
$upload_url = SITE_URL . '/assets/uploads/destination-weddings/';

/* ======================= FORM ACTIONS ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    /* ---------- ADD ---------- */
    if ($action === 'add_destination_wedding') {
        $destination   = trim($_POST['destination'] ?? '');
        $wedding_style = trim($_POST['wedding_style'] ?? '');
        $sort_order    = intval($_POST['sort_order'] ?? 0);
        $status        = (isset($_POST['status']) && $_POST['status'] === 'active') ? 1 : 0;
        $image         = '';

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new = uniqid() . '_' . time() . '.' . $ext;

            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['image']['size'] <= 5*1024*1024) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new)) {
                    $image = $new;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO destination_weddings (destination, wedding_style, image, sort_order, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $destination, $wedding_style, $image, $sort_order, $status);

        if ($stmt->execute()) {
            $message = 'Destination Wedding added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    /* ---------- UPDATE ---------- */
    elseif ($action === 'update_destination_wedding') {
        $id            = intval($_POST['wedding_id']);
        $destination   = trim($_POST['destination'] ?? '');
        $wedding_style = trim($_POST['wedding_style'] ?? '');
        $sort_order    = intval($_POST['sort_order'] ?? 0);
        $status        = (isset($_POST['status']) && $_POST['status'] === 'active') ? 1 : 0;

        $image = '';
        $r = $conn->query("SELECT image FROM destination_weddings WHERE id = $id");
        if ($r && $row = $r->fetch_assoc()) $image = $row['image'];

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            if ($image && file_exists($upload_dir . $image)) unlink($upload_dir . $image);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new = uniqid() . '_' . time() . '.' . $ext;

            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['image']['size'] <= 5*1024*1024) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new)) {
                    $image = $new;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE destination_weddings SET destination = ?, wedding_style = ?, image = ?, sort_order = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $destination, $wedding_style, $image, $sort_order, $status, $id);

        if ($stmt->execute()) {
            $message = 'Destination Wedding updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    /* ---------- DELETE ---------- */
    elseif ($action === 'delete_destination_wedding') {
        $id = intval($_POST['wedding_id']);

        $r = $conn->query("SELECT image FROM destination_weddings WHERE id = $id");
        if ($r && $row = $r->fetch_assoc()) {
            if ($row['image'] && file_exists($upload_dir . $row['image'])) unlink($upload_dir . $row['image']);
        }

        $stmt = $conn->prepare("DELETE FROM destination_weddings WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Destination Wedding deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    /* ---------- TOGGLE STATUS ---------- */
    elseif ($action === 'toggle_wedding_status') {
        $id = intval($_POST['wedding_id']);
        $conn->query("UPDATE destination_weddings SET status = 1 - status WHERE id = $id");
        $message = 'Status updated successfully.';
        $message_type = 'success';
    }

    /* ---------- UPDATE ORDER ---------- */
    elseif ($action === 'update_wedding_order') {
        $id  = intval($_POST['wedding_id']);
        $ord = intval($_POST['sort_order']);
        $conn->query("UPDATE destination_weddings SET sort_order = $ord WHERE id = $id");
        $message = 'Order updated.';
        $message_type = 'success';
    }
}

/* ======================= FETCH ITEMS ======================= */
$weddings = [];
$r = $conn->query("SELECT * FROM destination_weddings ORDER BY sort_order ASC, id ASC");
if ($r) while ($row = $r->fetch_assoc()) $weddings[] = $row;

$total_weddings  = count($weddings);
$active_weddings = 0;
foreach ($weddings as $w) if ($w['status']) $active_weddings++;

/* ======================= SIDEBAR STATS ======================= */
$total_tours = $total_destinations = $total_services = $total_cars = 0;
$total_testimonials = $total_activities = $total_bookings = 0;
$total_messages = $unread_messages = 0;

$r = $conn->query("SELECT COUNT(*) c FROM tour_packages WHERE status='active'");
if ($r) { $total_tours = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM destinations WHERE status='active'");
if ($r) { $total_destinations = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM services WHERE status='active'");
if ($r) { $total_services = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM rental_cars WHERE status='available'");
if ($r) { $total_cars = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM testimonials WHERE status='approved' AND featured=1");
if ($r) { $total_testimonials = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM popular_activities WHERE status='active'");
if ($r) { $total_activities = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM bookings");
if ($r) { $total_bookings = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM contact_messages");
if ($r) { $total_messages = $r->fetch_assoc()['c']; }

$r = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE status='unread'");
if ($r) { $unread_messages = $r->fetch_assoc()['c']; }

/* ======================= EDIT MODE ======================= */
$edit_mode = false;
$current_wedding = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $wid = intval($_GET['id']);
    $r = $conn->query("SELECT * FROM destination_weddings WHERE id = $wid");
    if ($r && $r->num_rows > 0) {
        $current_wedding = $r->fetch_assoc();
        $edit_mode = true;
    }
}

/* Next sort order */
$next_order = 1;
if (!empty($weddings)) {
    $max = 0;
    foreach ($weddings as $w) if ((int)$w['sort_order'] > $max) $max = (int)$w['sort_order'];
    $next_order = $max + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Destination Weddings - Newa Yatra Admin</title>
<link rel="stylesheet" href="assets/css/admin-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.destination-image-preview{max-width:200px;max-height:150px;border-radius:5px;border:2px solid #ddd;margin:10px 0;}
.status-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600;text-transform:uppercase;}
.status-active{background:#28a745;color:#fff;}
.status-inactive{background:#dc3545;color:#fff;}
.action-buttons{display:flex;gap:5px;}
.btn-icon{padding:5px 10px;font-size:12px;}
.empty-state{text-align:center;padding:50px 20px;color:#6c757d;}
.empty-state i{font-size:3rem;margin-bottom:15px;opacity:.5;}
.form-actions{display:flex;gap:15px;margin-top:30px;padding-top:20px;border-top:1px solid #eee;}
.text-muted{color:#6c757d;}
.order-input{width:70px;padding:5px;border:1px solid #ddd;border-radius:3px;text-align:center;}
.thumb-table{width:90px;height:65px;object-fit:cover;border-radius:5px;border:1px solid #ddd;}
</style>
</head>
<body>

<div class="admin-container">
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">

        <div class="navbar-top">
            <div class="welcome-text">
                <i class="fas fa-ring me-2"></i>Destination Wedding Management
            </div>
            <div>
                <span class="text-muted me-3">
                    <i class="fas fa-ring me-1"></i>
                    <?php echo $total_weddings; ?> Total Destination Weddings
                </span>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(232,62,140,.1);color:#e83e8c;"><i class="fas fa-ring"></i></div>
                    <div class="stat-number"><?php echo $total_weddings; ?></div>
                    <div class="stat-title">Total Weddings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(40,167,69,.1);color:#28a745;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo $active_weddings; ?></div>
                    <div class="stat-title">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(220,53,69,.1);color:#dc3545;"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-number"><?php echo $total_weddings - $active_weddings; ?></div>
                    <div class="stat-title">Inactive</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(111,66,193,.1);color:#6f42c1;"><i class="fas fa-plus"></i></div>
                    <div class="stat-number">New</div>
                    <div class="stat-title">
                        <a href="manage-destination-weddings.php?action=add" style="color:inherit;text-decoration:none;">
                            Add Wedding <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['action']) && ($_GET['action'] === 'add' || ($_GET['action'] === 'edit' && $edit_mode))): ?>

                <!-- ============ ADD / EDIT FORM ============ -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_destination_wedding' : 'add_destination_wedding'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="wedding_id" value="<?php echo (int)$current_wedding['id']; ?>">
                        <?php endif; ?>

                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:30px;">
                            <div>
                                <div class="form-group">
                                    <label for="destination" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i> Destination Name *
                                    </label>
                                    <input type="text" id="destination" name="destination" required
                                           class="form-control" placeholder="e.g. Udaipur"
                                           maxlength="100"
                                           value="<?php echo $edit_mode ? htmlspecialchars($current_wedding['destination']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="wedding_style" class="form-label">
                                        <i class="fas fa-align-left me-1"></i> Wedding Style / Description
                                    </label>
                                    <textarea id="wedding_style" name="wedding_style" rows="5" class="form-control"
                                              maxlength="255"
                                              placeholder="e.g. Palace weddings on Lake Pichola with grand courtyards..."><?php echo $edit_mode ? htmlspecialchars($current_wedding['wedding_style']) : ''; ?></textarea>
                                    <small class="text-muted">Max 255 characters.</small>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label for="image" class="form-label">
                                        <i class="fas fa-image me-1"></i> Featured Image
                                    </label>
                                    <input type="file" id="image" name="image" accept="image/*" class="form-control">
                                    <small class="text-muted">Recommended: 900 × 600 px. Max 5MB.</small>

                                    <?php if ($edit_mode && !empty($current_wedding['image'])): ?>
                                        <img id="image_preview"
                                             src="<?php echo $upload_url . htmlspecialchars($current_wedding['image']); ?>"
                                             alt="Current" class="destination-image-preview">
                                        <br><small class="text-muted">Current image — upload new to replace.</small>
                                    <?php else: ?>
                                        <img id="image_preview" src="" alt="Preview"
                                             class="destination-image-preview" style="display:none;">
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="sort_order" class="form-label">
                                        <i class="fas fa-sort-numeric-down me-1"></i> Sort Order
                                    </label>
                                    <input type="number" id="sort_order" name="sort_order" min="0"
                                           class="form-control"
                                           value="<?php echo $edit_mode ? (int)$current_wedding['sort_order'] : $next_order; ?>">
                                    <small class="text-muted">Lower numbers appear first.</small>
                                </div>

                                <div class="form-group">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i> Status
                                    </label>
                                    <select id="status" name="status" class="form-control" required>
                                        <option value="active"   <?php echo (!$edit_mode || $current_wedding['status']) ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($edit_mode && !$current_wedding['status']) ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-1"></i>
                                <?php echo $edit_mode ? 'Update Destination Wedding' : 'Add Destination Wedding'; ?>
                            </button>
                            <a href="manage-destination-weddings.php" class="btn btn-outline">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>

            <?php else: ?>

                <!-- ============ LIST ============ -->
                <?php if (empty($weddings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ring"></i>
                        <h3>No Destination Weddings Found</h3>
                        <p>Get started by adding your first destination wedding.</p>
                        <a href="manage-destination-weddings.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add First Destination Wedding
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <i class="fas fa-list"></i> All Destination Weddings
                            <div class="table-actions">
                                <a href="manage-destination-weddings.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add New
                                </a>
                            </div>
                        </div>
                        <div class="table-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Destination</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weddings as $w): ?>
                                        <tr>
                                            <td>
                                                <form method="POST" style="display:inline;" onchange="this.submit()">
                                                    <input type="hidden" name="action" value="update_wedding_order">
                                                    <input type="hidden" name="wedding_id" value="<?php echo (int)$w['id']; ?>">
                                                    <input type="number" name="sort_order" class="order-input"
                                                           value="<?php echo (int)$w['sort_order']; ?>" min="0">
                                                </form>
                                            </td>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:15px;">
                                                    <?php if (!empty($w['image']) && file_exists($upload_dir . $w['image'])): ?>
                                                        <img src="<?php echo $upload_url . htmlspecialchars($w['image']); ?>"
                                                             alt="<?php echo htmlspecialchars($w['destination']); ?>"
                                                             class="thumb-table">
                                                    <?php else: ?>
                                                        <div class="thumb-table" style="display:flex;align-items:center;justify-content:center;background:#eef2ee;color:#aab8ad;">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div><strong><?php echo htmlspecialchars($w['destination']); ?></strong></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    $desc = $w['wedding_style'] ?? '';
                                                    echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc);
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $w['status'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $w['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="manage-destination-weddings.php?action=edit&id=<?php echo (int)$w['id']; ?>"
                                                       class="btn btn-icon btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <form method="POST" style="display:inline;"
                                                          onsubmit="return confirm('Toggle status for this item?');">
                                                        <input type="hidden" name="action" value="toggle_wedding_status">
                                                        <input type="hidden" name="wedding_id" value="<?php echo (int)$w['id']; ?>">
                                                        <button type="submit" class="btn btn-icon btn-info" title="Toggle Status">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>

                                                    <form method="POST" style="display:inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this destination wedding?');">
                                                        <input type="hidden" name="action" value="delete_destination_wedding">
                                                        <input type="hidden" name="wedding_id" value="<?php echo (int)$w['id']; ?>">
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

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="assets/js/admin-script.js"></script>
<script>
document.getElementById('image')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    const preview = document.getElementById('image_preview');
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(ev){
            preview.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
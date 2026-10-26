<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $booking_id = intval($_POST['booking_id']);
                $status = trim($_POST['status']);
                $notes = trim($_POST['notes']);
                
                $sql = "UPDATE bookings SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ssi', $status, $notes, $booking_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Booking status updated successfully!';
                    
                    // Send email notification if status changed to confirmed
                    if ($status === 'confirmed') {
                        // Get booking details
                        $sql = "SELECT * FROM bookings WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param('i', $booking_id);
                        $stmt->execute();
                        $booking = $stmt->get_result()->fetch_assoc();
                        
                        // Send confirmation email
                        sendBookingConfirmation($booking);
                    }
                }
                break;
                
            case 'add_note':
                $booking_id = intval($_POST['booking_id']);
                $note = trim($_POST['note']);
                
                // Get existing notes
                $sql = "SELECT notes FROM bookings WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('i', $booking_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $booking = $result->fetch_assoc();
                
                $existing_notes = $booking['notes'] ?: '';
                $new_note = date('Y-m-d H:i:s') . " - " . $_SESSION['user_name'] . ": " . $note . "\n";
                $updated_notes = $new_note . $existing_notes;
                
                $sql = "UPDATE bookings SET notes = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('si', $updated_notes, $booking_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Note added successfully!';
                }
                break;
        }
        
        header('Location: manage-bookings.php?action=view&id=' . $booking_id);
        exit();
    }
}

// Get booking details for view
$booking = null;
if ($id > 0 && $action === 'view') {
    $sql = "SELECT b.*, t.title as tour_title, t.slug as tour_slug 
            FROM bookings b 
            LEFT JOIN tour_packages t ON b.tour_package_id = t.id 
            WHERE b.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
}

// Get all bookings
$bookings = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "SELECT b.*, t.title as tour_title FROM bookings b 
        LEFT JOIN tour_packages t ON b.tour_package_id = t.id 
        WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $sql .= " AND (b.booking_reference LIKE ? OR b.name LIKE ? OR b.email LIKE ? OR b.phone LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

if ($status_filter) {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_filter) {
    $sql .= " AND DATE(b.created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$sql .= " ORDER BY b.created_at DESC";

if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <div class="main-content">
        <?php include 'topbar.php'; ?>

        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <!-- List View -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Manage Bookings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filter Form -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search booking ID, name, email..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" name="date" 
                                       value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="manage-bookings.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </form>
                        
                        <!-- Bookings Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Tour / Service</th>
                                        <th>Travel Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking_item): ?>
                                        <tr>
                                            <td>
                                                <code>#<?php echo $booking_item['booking_reference']; ?></code>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking_item['name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($booking_item['email']); ?><br>
                                                    <?php echo htmlspecialchars($booking_item['phone']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($booking_item['tour_title']): ?>
                                                    <?php echo htmlspecialchars($booking_item['tour_title']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Custom Tour</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking_item['travel_date'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($booking_item['total_amount']): ?>
                                                    ₹<?php echo number_format($booking_item['total_amount']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-status-<?php echo $booking_item['status']; ?>">
                                                    <?php echo ucfirst($booking_item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking_item['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=view&id=<?php echo $booking_item['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="mailto:<?php echo htmlspecialchars($booking_item['email']); ?>" 
                                                       class="btn btn-sm btn-secondary" title="Email">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <a href="tel:<?php echo htmlspecialchars($booking_item['phone']); ?>" 
                                                       class="btn btn-sm btn-success" title="Call">
                                                        <i class="fas fa-phone-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'view' && $booking): ?>
                <!-- View Booking Details -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Booking Details - #<?php echo $booking['booking_reference']; ?></h5>
                        <div>
                            <a href="manage-bookings.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Booking Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Booking Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Booking Reference:</strong><br>
                                                   <code>#<?php echo $booking['booking_reference']; ?></code></p>
                                                
                                                <p><strong>Customer Name:</strong><br>
                                                   <?php echo htmlspecialchars($booking['name']); ?></p>
                                                
                                                <p><strong>Email:</strong><br>
                                                   <a href="mailto:<?php echo htmlspecialchars($booking['email']); ?>">
                                                       <?php echo htmlspecialchars($booking['email']); ?>
                                                   </a></p>
                                                
                                                <p><strong>Phone:</strong><br>
                                                   <a href="tel:<?php echo htmlspecialchars($booking['phone']); ?>">
                                                       <?php echo htmlspecialchars($booking['phone']); ?>
                                                   </a></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Travel Date:</strong><br>
                                                   <?php echo date('F j, Y', strtotime($booking['travel_date'])); ?>
                                                   <?php if ($booking['return_date']): ?>
                                                       <br><small>Return: <?php echo date('F j, Y', strtotime($booking['return_date'])); ?></small>
                                                   <?php endif; ?>
                                                </p>
                                                
                                                <p><strong>Destination:</strong><br>
                                                   <?php echo htmlspecialchars($booking['destination']); ?></p>
                                                
                                                <p><strong>Journey Type:</strong><br>
                                                   <?php echo htmlspecialchars($booking['journey_type']); ?></p>
                                                
                                                <p><strong>Travelers:</strong><br>
                                                   Adults: <?php echo $booking['adults']; ?>, 
                                                   Children: <?php echo $booking['children']; ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['tour_title']): ?>
                                            <hr>
                                            <p><strong>Tour Package:</strong><br>
                                               <?php echo htmlspecialchars($booking['tour_title']); ?>
                                               <a href="<?php echo SITE_URL; ?>/pages/tour-detail.php?slug=<?php echo $booking['tour_slug']; ?>" 
                                                  target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                                   View Tour
                                               </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['custom_tour_details']): ?>
                                            <hr>
                                            <p><strong>Custom Tour Details:</strong></p>
                                            <div class="border rounded p-3 bg-light">
                                                <?php echo nl2br(htmlspecialchars($booking['custom_tour_details'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['special_requests']): ?>
                                            <hr>
                                            <p><strong>Special Requests:</strong></p>
                                            <div class="border rounded p-3 bg-light">
                                                <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Booking Notes -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Booking Notes</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($booking['notes']): ?>
                                            <div class="booking-notes">
                                                <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No notes added yet.</p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="add_note">
                                            <input type="hidden" name="booking_id" value="<?php echo $id; ?>">
                                            <div class="mb-3">
                                                <label for="note" class="form-label">Add Note</label>
                                                <textarea class="form-control" id="note" name="note" rows="3" 
                                                          placeholder="Add internal notes here..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add Note
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Status Management -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Status Management</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $id; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Booking Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="payment_status" class="form-label">Payment Status</label>
                                                <select class="form-select" id="payment_status" name="payment_status" 
                                                        onchange="document.getElementById('payment_status_input').value = this.value;">
                                                    <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="partial" <?php echo $booking['payment_status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                                    <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                </select>
                                                <input type="hidden" id="payment_status_input" name="payment_status" 
                                                       value="<?php echo $booking['payment_status']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="total_amount" class="form-label">Total Amount (₹)</label>
                                                <input type="number" class="form-control" id="total_amount" 
                                                       name="total_amount" step="0.01" 
                                                       value="<?php echo $booking['total_amount']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="notes" class="form-label">Status Notes (Visible to customer)</label>
                                                <textarea class="form-control" id="notes" name="notes" 
                                                          rows="4"><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                                                <small class="text-muted">These notes will be visible to the customer</small>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Update Status
                                                </button>
                                            </div>
                                        </form>
                                        
                                        <hr>
                                        <div class="text-center">
                                            <a href="mailto:<?php echo htmlspecialchars($booking['email']); ?>?subject=Booking%20Confirmation%20-%20Newa%20Yatra&body=Dear%20<?php echo urlencode($booking['name']); ?>%2C%0A%0AThank%20you%20for%20your%20booking%20with%20Newa%20Yatra.%20Your%20booking%20reference%20is%20%23<?php echo $booking['booking_reference']; ?>%0A%0ARegards%2C%0ANewa%20Yatra%20Team" 
                                               class="btn btn-success w-100 mb-2">
                                                <i class="fas fa-paper-plane me-2"></i>Send Confirmation Email
                                            </a>
                                            
                                            <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $booking['phone']); ?>?text=Dear%20<?php echo urlencode($booking['name']); ?>%2C%20Thank%20you%20for%20booking%20with%20Newa%20Yatra.%20Your%20booking%20reference%20is%20%23<?php echo $booking['booking_reference']; ?>" 
                                               target="_blank" class="btn btn-success w-100" style="background-color: #25D366;">
                                                <i class="fab fa-whatsapp me-2"></i>Send WhatsApp Message
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Booking Summary -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Booking Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Booking Date:</strong><br>
                                           <?php echo date('F j, Y, g:i a', strtotime($booking['created_at'])); ?></p>
                                        
                                        <p><strong>Last Updated:</strong><br>
                                           <?php echo $booking['updated_at'] ? date('F j, Y, g:i a', strtotime($booking['updated_at'])) : 'Never'; ?></p>
                                        
                                        <p><strong>IP Address:</strong><br>
                                           <small class="text-muted"><?php echo $_SERVER['REMOTE_ADDR']; ?></small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-script.js"></script>
</body>
</html>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'reply':
                $message_id = intval($_POST['message_id']);
                $reply_subject = trim($_POST['reply_subject']);
                $reply_message = trim($_POST['reply_message']);
                
                // Get message details
                $sql = "SELECT * FROM contact_messages WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('i', $message_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $message = $result->fetch_assoc();
                
                // Send email reply
                $to = $message['email'];
                $subject = $reply_subject;
                $body = "Dear " . htmlspecialchars($message['name']) . ",\n\n";
                $body .= "Thank you for contacting Newa Yatra.\n\n";
                $body .= "Regarding your inquiry:\n\n";
                $body .= $reply_message . "\n\n";
                $body .= "Best regards,\n";
                $body .= "Newa Yatra Team\n";
                $body .= SITE_URL . "\n";
                $body .= "Phone: " . getSiteSetting('contact_phone1');
                
                $headers = "From: " . getSiteSetting('site_email') . "\r\n";
                $headers .= "Reply-To: " . getSiteSetting('site_email') . "\r\n";
                
                if (mail($to, $subject, $body, $headers)) {
                    // Update message status
                    $sql = "UPDATE contact_messages SET status = 'replied', replied_at = NOW(), 
                            replied_by = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param('ii', $_SESSION['user_id'], $message_id);
                    $stmt->execute();
                    
                    $_SESSION['success'] = 'Reply sent successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to send reply email.';
                }
                break;
                
            case 'mark_read':
                $message_id = intval($_POST['message_id']);
                
                $sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param('i', $message_id);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
                exit();
                
            case 'delete':
                if ($id > 0) {
                    $sql = "DELETE FROM contact_messages WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param('i', $id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Message deleted successfully!';
                    }
                }
                header('Location: contact-messages.php');
                exit();
        }
    }
}

// Get message for view
$message = null;
if ($id > 0 && $action === 'view') {
    $sql = "SELECT * FROM contact_messages WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    
    // Mark as read if unread
    if ($message && $message['status'] === 'unread') {
        $update_sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->bind_param('i', $id);
        $update_stmt->execute();
    }
}

// Get all messages
$messages = [];
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];
$types = '';

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

$sql .= " ORDER BY created_at DESC";

if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

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
                <!-- Messages List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Contact Messages</h5>
                        <div class="btn-group" role="group">
                            <a href="contact-messages.php" class="btn btn-outline-secondary">
                                All
                            </a>
                            <a href="?status=unread" class="btn btn-outline-warning">
                                Unread
                            </a>
                            <a href="?status=read" class="btn btn-outline-info">
                                Read
                            </a>
                            <a href="?status=replied" class="btn btn-outline-success">
                                Replied
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by name, email, phone or subject..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                        
                        <!-- Messages Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Sender</th>
                                        <th>Subject</th>
                                        <th>Tour Interest</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr class="<?php echo $msg['status'] === 'unread' ? 'table-warning' : ''; ?>">
                                            <td><?php echo $msg['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($msg['email']); ?><br>
                                                    <?php echo htmlspecialchars($msg['phone']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($msg['subject']): ?>
                                                    <?php echo htmlspecialchars($msg['subject']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No subject</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($msg['tour_interest']): ?>
                                                    <?php echo htmlspecialchars($msg['tour_interest']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">General</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($msg['created_at'])); ?><br>
                                                <small><?php echo date('H:i', strtotime($msg['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $msg['status'] === 'unread' ? 'warning' : ($msg['status'] === 'read' ? 'info' : 'success'); ?>">
                                                    <?php echo ucfirst($msg['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=view&id=<?php echo $msg['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" 
                                                       class="btn btn-sm btn-secondary" title="Email">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-message" 
                                                            data-id="<?php echo $msg['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($msg['name']); ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'view' && $message): ?>
                <!-- View Message -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Message Details</h5>
                        <div>
                            <a href="contact-messages.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Message Details -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Message Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong><br>
                                                   <?php echo htmlspecialchars($message['name']); ?></p>
                                                
                                                <p><strong>Email:</strong><br>
                                                   <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>">
                                                       <?php echo htmlspecialchars($message['email']); ?>
                                                   </a></p>
                                                
                                                <p><strong>Phone:</strong><br>
                                                   <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>">
                                                       <?php echo htmlspecialchars($message['phone']); ?>
                                                   </a></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Tour Interest:</strong><br>
                                                   <?php echo htmlspecialchars($message['tour_interest']); ?></p>
                                                
                                                <p><strong>Travel Date:</strong><br>
                                                   <?php echo $message['travel_date'] ? date('F j, Y', strtotime($message['travel_date'])) : 'Not specified'; ?></p>
                                                
                                                <p><strong>Found Via:</strong><br>
                                                   <?php echo htmlspecialchars($message['how_found']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($message['subject']): ?>
                                            <hr>
                                            <p><strong>Subject:</strong><br>
                                               <?php echo htmlspecialchars($message['subject']); ?></p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        <p><strong>Message:</strong></p>
                                        <div class="border rounded p-3 bg-light">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        
                                        <div class="mt-3 text-muted">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                IP Address: <?php echo htmlspecialchars($message['ip_address']); ?> | 
                                                Received: <?php echo date('F j, Y, g:i a', strtotime($message['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Reply Form -->
                                <?php if ($message['status'] !== 'replied'): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Send Reply</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="reply">
                                                <input type="hidden" name="message_id" value="<?php echo $id; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="reply_subject" class="form-label">Subject</label>
                                                    <input type="text" class="form-control" id="reply_subject" 
                                                           name="reply_subject" 
                                                           value="Re: <?php echo htmlspecialchars($message['subject']); ?>"
                                                           required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="reply_message" class="form-label">Message</label>
                                                    <textarea class="form-control" id="reply_message" name="reply_message" 
                                                              rows="6" required></textarea>
                                                </div>
                                                
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane me-2"></i>Send Reply
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Message Status -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Message Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Status:</strong><br>
                                           <span class="badge bg-<?php echo $message['status'] === 'unread' ? 'warning' : ($message['status'] === 'read' ? 'info' : 'success'); ?>">
                                               <?php echo ucfirst($message['status']); ?>
                                           </span>
                                        </p>
                                        
                                        <?php if ($message['status'] === 'replied'): ?>
                                            <p><strong>Replied On:</strong><br>
                                               <?php echo date('F j, Y, g:i a', strtotime($message['replied_at'])); ?></p>
                                            
                                            <p><strong>Replied By:</strong><br>
                                               <?php 
                                               if ($message['replied_by']) {
                                                   $sql = "SELECT full_name FROM users WHERE id = ?";
                                                   $stmt = $db->prepare($sql);
                                                   $stmt->bind_param('i', $message['replied_by']);
                                                   $stmt->execute();
                                                   $result = $stmt->get_result();
                                                   $replier = $result->fetch_assoc();
                                                   echo htmlspecialchars($replier['full_name']);
                                               } else {
                                                   echo 'Unknown';
                                               }
                                               ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Quick Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-envelope me-2"></i>Send Email
                                            </a>
                                            
                                            <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-phone-alt me-2"></i>Make Call
                                            </a>
                                            
                                            <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $message['phone']); ?>" 
                                               target="_blank" class="btn btn-success" style="background-color: #25D366;">
                                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                            </a>
                                            
                                            <button type="button" class="btn btn-danger delete-message" 
                                                    data-id="<?php echo $message['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($message['name']); ?>">
                                                <i class="fas fa-trash me-2"></i>Delete Message
                                            </button>
                                        </div>
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
    <script>
    $(document).ready(function() {
        // Delete confirmation
        $('.delete-message').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            if (confirm(`Are you sure you want to delete message from "${name}"?`)) {
                window.location.href = `contact-messages.php?action=delete&id=${id}`;
            }
        });
        
        // Mark as read
        $('.mark-read').click(function() {
            const messageId = $(this).data('id');
            const button = $(this);
            
            $.ajax({
                url: 'ajax/mark-message-read.php',
                method: 'POST',
                data: { id: messageId },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').removeClass('table-warning');
                        button.remove();
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
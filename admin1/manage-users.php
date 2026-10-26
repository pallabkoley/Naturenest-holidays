<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only admin can manage users
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $role = trim($_POST['role']);
                $status = trim($_POST['status']);
                
                if ($_POST['action'] === 'add') {
                    $password = trim($_POST['password']);
                    $confirm_password = trim($_POST['confirm_password']);
                    
                    if ($password !== $confirm_password) {
                        $_SESSION['error'] = 'Passwords do not match!';
                        header('Location: manage-users.php?action=add');
                        exit();
                    }
                    
                    // Check if username or email already exists
                    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
                    $check_stmt = $db->prepare($check_sql);
                    $check_stmt->bind_param('ss', $username, $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $_SESSION['error'] = 'Username or email already exists!';
                        header('Location: manage-users.php?action=add');
                        exit();
                    }
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO users (username, email, password, full_name, role, status) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param('ssssss', $username, $email, $hashed_password, 
                                     $full_name, $role, $status);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'User added successfully!';
                        header('Location: manage-users.php');
                        exit();
                    }
                } else {
                    if ($id > 0) {
                        // Check if username or email already exists (excluding current user)
                        $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
                        $check_stmt = $db->prepare($check_sql);
                        $check_stmt->bind_param('ssi', $username, $email, $id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            $_SESSION['error'] = 'Username or email already exists!';
                            header('Location: manage-users.php?action=edit&id=' . $id);
                            exit();
                        }
                        
                        // If password is provided, update it
                        $password_update = '';
                        $password = trim($_POST['password']);
                        if (!empty($password)) {
                            $confirm_password = trim($_POST['confirm_password']);
                            if ($password !== $confirm_password) {
                                $_SESSION['error'] = 'Passwords do not match!';
                                header('Location: manage-users.php?action=edit&id=' . $id);
                                exit();
                            }
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $password_update = ", password = '$hashed_password'";
                        }
                        
                        $sql = "UPDATE users SET 
                                username = ?, email = ?, full_name = ?, role = ?, status = ?
                                $password_update WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        if (!empty($password)) {
                            $stmt->bind_param('ssssssi', $username, $email, $full_name, 
                                             $role, $status, $id);
                        } else {
                            $stmt->bind_param('sssssi', $username, $email, $full_name, 
                                             $role, $status, $id);
                        }
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'User updated successfully!';
                            header('Location: manage-users.php');
                            exit();
                        }
                    }
                }
                break;
                
            case 'delete':
                if ($id > 0) {
                    // Prevent deleting own account
                    if ($id == $_SESSION['user_id']) {
                        $_SESSION['error'] = 'You cannot delete your own account!';
                    } else {
                        $sql = "DELETE FROM users WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param('i', $id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'User deleted successfully!';
                        }
                    }
                }
                header('Location: manage-users.php');
                exit();
        }
    }
}

// Get user data
$user = null;
if ($id > 0 && $action === 'edit') {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

// Get all users
$users = [];
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $db->query($sql);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Manage Users</h5>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add User
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user_item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php 
                                                        $avatar_text = $user_item['full_name'] ? substr($user_item['full_name'], 0, 1) : substr($user_item['username'], 0, 1);
                                                        ?>
                                                        <div class="avatar-circle bg-primary text-white">
                                                            <?php echo strtoupper($avatar_text); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user_item['full_name'] ?: $user_item['username']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($user_item['username']); ?><br>
                                                            <?php echo htmlspecialchars($user_item['email']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user_item['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                    <?php echo ucfirst($user_item['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user_item['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($user_item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user_item['last_login']): ?>
                                                    <?php echo date('M d, Y', strtotime($user_item['last_login'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($user_item['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=edit&id=<?php echo $user_item['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user_item['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-sm btn-danger delete-user" 
                                                                data-id="<?php echo $user_item['id']; ?>" 
                                                                data-name="<?php echo htmlspecialchars($user_item['full_name'] ?: $user_item['username']); ?>"
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">User Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username *</label>
                                                <input type="text" class="form-control" id="username" 
                                                       name="username" 
                                                       value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address *</label>
                                                <input type="email" class="form-control" id="email" 
                                                       name="email" 
                                                       value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" 
                                                       name="full_name" 
                                                       value="<?php echo isset($user['full_name']) ? htmlspecialchars($user['full_name']) : ''; ?>">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="role" class="form-label">Role *</label>
                                                        <select class="form-select" id="role" name="role" required>
                                                            <option value="editor" <?php echo (isset($user['role']) && $user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                                                            <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="status" class="form-label">Status *</label>
                                                        <select class="form-select" id="status" name="status" required>
                                                            <option value="active" <?php echo (isset($user['status']) && $user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo (isset($user['status']) && $user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- Password -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">Password</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">
                                                    <?php echo $action === 'add' ? 'Password *' : 'New Password (leave blank to keep current)'; ?>
                                                </label>
                                                <input type="password" class="form-control" id="password" 
                                                       name="password" 
                                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">
                                                    <?php echo $action === 'add' ? 'Confirm Password *' : 'Confirm New Password'; ?>
                                                </label>
                                                <input type="password" class="form-control" id="confirm_password" 
                                                       name="confirm_password" 
                                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <?php if ($action === 'add'): ?>
                                                    Password must be at least 8 characters long.
                                                <?php else: ?>
                                                    Only fill these fields if you want to change the password.
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Actions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>
                                                    <?php echo $action === 'add' ? 'Create User' : 'Update User'; ?>
                                                </button>
                                            </div>
                                            
                                            <div class="d-grid mt-2">
                                                <a href="manage-users.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
        $('.delete-user').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            if (confirm(`Are you sure you want to delete user "${name}"? This action cannot be undone.`)) {
                window.location.href = `manage-users.php?action=delete&id=${id}`;
            }
        });
        
        // Password validation
        $('form').submit(function(e) {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if ($('#action').val() === 'add' && password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    });
    </script>
</body>
</html>
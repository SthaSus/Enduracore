<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

// ✅ FIX: Check if session has account_id
if (!isset($_SESSION['account_id'])) {
    // Try alternative session keys
    if (isset($_SESSION['user_id'])) {
        $account_id = $_SESSION['user_id'];
    } elseif (isset($_SESSION['id'])) {
        $account_id = $_SESSION['id'];
    } else {
        die("Session expired. Please <a href='../auth/login.php'>login again</a>.");
    }
} else {
    $account_id = $_SESSION['account_id'];
}

$success_message = '';
$error_message = '';

// Get admin account details
$stmt = $conn->prepare("SELECT username, role, created_on, last_login FROM ACCOUNT WHERE account_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if account exists
if (!$account) {
    die("Account not found! Please <a href='../auth/login.php'>login again</a>.");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    
    if (!empty($new_username)) {
        // Check if username already exists (excluding current user)
        $check_stmt = $conn->prepare("SELECT account_id FROM ACCOUNT WHERE username = ? AND account_id != ?");
        $check_stmt->bind_param("si", $new_username, $account_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Username already taken!";
        } else {
            $update_stmt = $conn->prepare("UPDATE ACCOUNT SET username = ? WHERE account_id = ?");
            $update_stmt->bind_param("si", $new_username, $account_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $account['username'] = $new_username;
            } else {
                $error_message = "Failed to update profile!";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    } else {
        $error_message = "Username cannot be empty!";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters!";
    } else {
        // Verify current password
        $verify_stmt = $conn->prepare("SELECT password FROM ACCOUNT WHERE account_id = ?");
        $verify_stmt->bind_param("i", $account_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if ($user_data && password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_stmt = $conn->prepare("UPDATE ACCOUNT SET password = ? WHERE account_id = ?");
            $pwd_stmt->bind_param("si", $hashed_password, $account_id);
            
            if ($pwd_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password!";
            }
            $pwd_stmt->close();
        } else {
            $error_message = "Current password is incorrect!";
        }
        $verify_stmt->close();
    }
}

// Get activity stats
$total_members = $conn->query("SELECT COUNT(*) as count FROM MEMBER")->fetch_assoc()['count'];
$total_trainers = $conn->query("SELECT COUNT(*) as count FROM TRAINER")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT COUNT(*) as count FROM PAYMENT WHERE payment_status = 'Paid'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="d-flex">

    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <?php include '../partials/header.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-user-shield me-2"></i>Admin Profile</h1>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Overview -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="avatar-circle mx-auto mb-3" style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user-shield fa-4x text-white"></i>
                                </div>
                            </div>
                            <h4 class="mb-1"><?= htmlspecialchars($account['username'] ?? 'Admin') ?></h4>
                            <p class="text-muted mb-3">
                                <span class="badge bg-danger"><?= $account['role'] ?? 'ADMIN' ?></span>
                            </p>
                            <hr>
                            <div class="text-start">
                                <p class="mb-2">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    <small><strong>Joined:</strong> <?= $account['created_on'] ? date('d M Y', strtotime($account['created_on'])) : 'N/A' ?></small>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock text-success me-2"></i>
                                    <small><strong>Last Login:</strong> <?= $account['last_login'] ? date('d M Y, h:i A', strtotime($account['last_login'])) : 'Never' ?></small>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Stats -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>System Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-0"><?= $total_members ?></h4>
                                    <small class="text-muted">Total Members</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-user-tie fa-2x text-success"></i>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-0"><?= $total_trainers ?></h4>
                                    <small class="text-muted">Total Trainers</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-0"><?= $total_payments ?></h4>
                                    <small class="text-muted">Completed Payments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Settings -->
                <div class="col-md-8">
                    <!-- Update Profile -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Account Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlspecialchars($account['username'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" 
                                           value="<?= $account['role'] ?? 'ADMIN' ?>" disabled>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password" required>
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Password Strength Indicator -->
                                <div class="mb-3">
                                    <div class="progress" style="height: 5px;">
                                        <div id="password-strength" class="progress-bar" role="progressbar" 
                                             style="width: 0%"></div>
                                    </div>
                                    <small id="password-strength-text" class="text-muted"></small>
                                </div>

                                <button type="submit" name="change_password" class="btn btn-danger">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Tips -->
                    <div class="card shadow-sm mt-4 border-info">
                        <div class="card-header bg-info bg-opacity-10">
                            <h5 class="mb-0"><i class="fas fa-shield-alt text-info me-2"></i>Security Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Use a strong password with at least 8 characters</li>
                                <li>Include uppercase, lowercase, numbers, and special characters</li>
                                <li>Never share your admin credentials</li>
                                <li>Change your password regularly (every 3-6 months)</li>
                                <li>Always log out when leaving your computer</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../script/admin/profile.js"></script>

</body>
</html>
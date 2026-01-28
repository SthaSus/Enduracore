<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['MEMBER']);

$account_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get member and account details
$query = "SELECT m.*, a.username FROM MEMBER m 
          JOIN ACCOUNT a ON m.account_id = a.account_id 
          WHERE a.account_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $member_name = sanitize_input($_POST['member_name']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $gender = $_POST['gender'];
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    
    $update_stmt = $conn->prepare("UPDATE MEMBER SET member_name = ?, age = ?, gender = ?, phone = ?, email = ? WHERE member_id = ?");
    $update_stmt->bind_param("sisssi", $member_name, $age, $gender, $phone, $email, $member['member_id']);
    
    if ($update_stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh member data
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Failed to update profile";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash
    $pass_query = $conn->prepare("SELECT password FROM ACCOUNT WHERE account_id = ?");
    $pass_query->bind_param("i", $account_id);
    $pass_query->execute();
    $current_hash = $pass_query->get_result()->fetch_assoc()['password'];
    
    if (!password_verify($current_password, $current_hash)) {
        $error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pass = $conn->prepare("UPDATE ACCOUNT SET password = ? WHERE account_id = ?");
        $update_pass->bind_param("si", $new_hash, $account_id);
        
        if ($update_pass->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <?php include '../partials/header.php'; ?>
            
            <div class="container-fluid">
                <h1 class="mb-4"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Information Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                        <i class="fas fa-user fa-4x"></i>
                                    </div>
                                </div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($member['member_name']); ?></h4>
                                <p class="text-muted mb-3">@<?php echo htmlspecialchars($member['username']); ?></p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h5 class="mb-0"><?php echo $member['age'] ?? 'N/A'; ?></h5>
                                            <small class="text-muted">Age</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h5 class="mb-0"><?php echo $member['gender']; ?></h5>
                                            <small class="text-muted">Gender</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h5 class="mb-0"><?php echo date('Y', strtotime($member['join_date'])); ?></h5>
                                            <small class="text-muted">Joined</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-start">
                                    <p class="mb-2">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <?php echo htmlspecialchars($member['email']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-phone text-success me-2"></i>
                                        <?php echo htmlspecialchars($member['phone']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar text-info me-2"></i>
                                        Joined: <?php echo date('M d, Y', strtotime($member['join_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Form -->
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" name="member_name" value="<?php echo htmlspecialchars($member['member_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['username']); ?>" disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Age</label>
                                            <input type="number" class="form-control" name="age" min="12" max="100" value="<?php echo $member['age']; ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Gender</label>
                                            <select class="form-select" name="gender">
                                                <option value="Male" <?php echo ($member['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($member['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo ($member['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Phone *</label>
                                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password Card -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">New Password *</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="change_password" class="btn btn-warning">
                                            <i class="fas fa-key"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Statistics -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Account Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            $stats_queries = [
                                'Total Workouts' => "SELECT COUNT(*) as count FROM member_workout_plan WHERE member_id = {$member['member_id']}",
                                'Total Visits' => "SELECT COUNT(*) as count FROM ATTENDANCE WHERE member_id = {$member['member_id']}",
                                'Active Memberships' => "SELECT COUNT(*) as count FROM MEMBERSHIP WHERE member_id = {$member['member_id']} AND status = 'Active'",
                                'Total Payments' => "SELECT COUNT(*) as count FROM PAYMENT p JOIN MEMBERSHIP m ON p.membership_id = m.membership_id WHERE m.member_id = {$member['member_id']}"
                            ];
                            
                            foreach ($stats_queries as $label => $query):
                                $count = $conn->query($query)->fetch_assoc()['count'];
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h3 class="text-primary"><?php echo $count; ?></h3>
                                            <p class="text-muted mb-0"><?php echo $label; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
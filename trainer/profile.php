<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['TRAINER']);

$account_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get trainer and account details
$query = "SELECT t.*, a.username FROM TRAINER t 
          JOIN ACCOUNT a ON t.account_id = a.account_id 
          WHERE a.account_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$trainer = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $trainer_name = sanitize_input($_POST['trainer_name']);
    $specialization = $_POST['specialization'];
    $experience_years = intval($_POST['experience_years']);
    $phone = sanitize_input($_POST['phone']);
    
    $update_stmt = $conn->prepare("UPDATE TRAINER SET trainer_name = ?, specialization = ?, experience_years = ?, phone = ? WHERE trainer_id = ?");
    $update_stmt->bind_param("ssisi", $trainer_name, $specialization, $experience_years, $phone, $trainer['trainer_id']);
    
    if ($update_stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh trainer data
        $stmt->execute();
        $trainer = $stmt->get_result()->fetch_assoc();
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

// Get trainer statistics
$trainer_id = $trainer['trainer_id'];
$stats_queries = [
    'Total Members' => "SELECT COUNT(DISTINCT mwp.member_id) as count 
                        FROM member_workout_plan mwp 
                        JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id 
                        WHERE wp.trainer_id = $trainer_id",
    'Workout Plans' => "SELECT COUNT(*) as count FROM WORKOUT_PLAN WHERE trainer_id = $trainer_id",
    'This Month Attendance' => "SELECT COUNT(DISTINCT a.member_id) as count 
                                FROM ATTENDANCE a
                                JOIN member_workout_plan mwp ON a.member_id = mwp.member_id
                                JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                                WHERE wp.trainer_id = $trainer_id 
                                AND MONTH(a.attendance_date) = MONTH(CURDATE())",
    'Active Since' => date('M Y', strtotime($trainer['trainer_id']))
];
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
                                        <i class="fas fa-user-tie fa-4x"></i>
                                    </div>
                                </div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($trainer['trainer_name']); ?></h4>
                                <p class="text-muted mb-3">@<?php echo htmlspecialchars($trainer['username']); ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="fas fa-award me-1"></i> <?php echo $trainer['specialization']; ?>
                                    </span>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <h5 class="mb-0"><?php echo $trainer['experience_years']; ?></h5>
                                            <small class="text-muted">Years</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <h5 class="mb-0">
                                                <?php 
                                                $member_count = $conn->query("SELECT COUNT(DISTINCT mwp.member_id) as count 
                                                                              FROM member_workout_plan mwp 
                                                                              JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id 
                                                                              WHERE wp.trainer_id = {$trainer['trainer_id']}")->fetch_assoc()['count'];
                                                echo $member_count;
                                                ?>
                                            </h5>
                                            <small class="text-muted">Members</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-start">
                                    <p class="mb-2">
                                        <i class="fas fa-phone text-success me-2"></i>
                                        <?php echo htmlspecialchars($trainer['phone']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-dumbbell text-primary me-2"></i>
                                        Specialization: <?php echo $trainer['specialization']; ?>
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
                                            <input type="text" class="form-control" name="trainer_name" value="<?php echo htmlspecialchars($trainer['trainer_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($trainer['username']); ?>" disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Specialization *</label>
                                            <select class="form-select" name="specialization" required>
                                                <option value="Strength" <?php echo ($trainer['specialization'] == 'Strength') ? 'selected' : ''; ?>>Strength</option>
                                                <option value="Cardio" <?php echo ($trainer['specialization'] == 'Cardio') ? 'selected' : ''; ?>>Cardio</option>
                                                <option value="Yoga" <?php echo ($trainer['specialization'] == 'Yoga') ? 'selected' : ''; ?>>Yoga</option>
                                                <option value="CrossFit" <?php echo ($trainer['specialization'] == 'CrossFit') ? 'selected' : ''; ?>>CrossFit</option>
                                                <option value="General" <?php echo ($trainer['specialization'] == 'General') ? 'selected' : ''; ?>>General</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Experience (Years) *</label>
                                            <input type="number" class="form-control" name="experience_years" min="0" max="50" value="<?php echo $trainer['experience_years']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($trainer['phone']); ?>" required>
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
                
                <!-- Statistics -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Training Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            $plan_count = $conn->query("SELECT COUNT(*) as count FROM WORKOUT_PLAN WHERE trainer_id = {$trainer['trainer_id']}")->fetch_assoc()['count'];
                            $member_count = $conn->query("SELECT COUNT(DISTINCT mwp.member_id) as count 
                                                          FROM member_workout_plan mwp 
                                                          JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id 
                                                          WHERE wp.trainer_id = {$trainer['trainer_id']}")->fetch_assoc()['count'];
                            $month_attendance = $conn->query("SELECT COUNT(DISTINCT a.member_id) as count 
                                                              FROM ATTENDANCE a
                                                              JOIN member_workout_plan mwp ON a.member_id = mwp.member_id
                                                              JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                                                              WHERE wp.trainer_id = {$trainer['trainer_id']} 
                                                              AND MONTH(a.attendance_date) = MONTH(CURDATE())")->fetch_assoc()['count'];
                            
                            $stats = [
                                ['label' => 'Workout Plans Created', 'value' => $plan_count, 'icon' => 'fa-dumbbell', 'color' => 'primary'],
                                ['label' => 'Assigned Members', 'value' => $member_count, 'icon' => 'fa-users', 'color' => 'success'],
                                ['label' => 'Active This Month', 'value' => $month_attendance, 'icon' => 'fa-calendar-check', 'color' => 'info'],
                                ['label' => 'Years Experience', 'value' => $trainer['experience_years'], 'icon' => 'fa-award', 'color' => 'warning']
                            ];
                            
                            foreach ($stats as $stat):
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <i class="fas <?php echo $stat['icon']; ?> fa-2x text-<?php echo $stat['color']; ?> mb-2"></i>
                                            <h3 class="mb-1"><?php echo $stat['value']; ?></h3>
                                            <p class="text-muted mb-0"><?php echo $stat['label']; ?></p>
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
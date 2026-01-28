<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['MEMBER']);

// Get member details (using member_name column)
$account_id = $_SESSION['user_id'];
$member_query = "SELECT m.*, a.username FROM MEMBER m 
                 JOIN ACCOUNT a ON m.account_id = a.account_id 
                 WHERE a.account_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$member_id = $member['member_id'];

// Get membership status
$membership_query = "SELECT * FROM MEMBERSHIP WHERE member_id = ? AND status = 'Active' ORDER BY end_date DESC LIMIT 1";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$membership = $stmt->get_result()->fetch_assoc();

// Get attendance count
$attendance_query = "SELECT COUNT(*) as total FROM ATTENDANCE WHERE member_id = ? AND MONTH(attendance_date) = MONTH(CURDATE())";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$attendance_count = $stmt->get_result()->fetch_assoc()['total'];

// Get workout plans (updated for member_workout_plan junction table)
$workout_query = "SELECT wp.*, t.trainer_name 
                  FROM WORKOUT_PLAN wp
                  JOIN member_workout_plan mwp ON wp.plan_id = mwp.plan_id
                  LEFT JOIN TRAINER t ON wp.trainer_id = t.trainer_id
                  WHERE mwp.member_id = ? 
                  ORDER BY wp.plan_id DESC";
$stmt = $conn->prepare($workout_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$workouts = $stmt->get_result();

// Get recent payments
$payment_query = "SELECT p.*, m.membership_type FROM PAYMENT p 
                  JOIN MEMBERSHIP m ON p.membership_id = m.membership_id 
                  WHERE m.member_id = ? ORDER BY p.payment_date DESC LIMIT 5";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include '../partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Header -->
            <?php include '../partials/header.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="container-fluid">
                <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($member['member_name']); ?>!</h1>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Membership Status</h6>
                                        <h4 class="mb-0">
                                            <?php if ($membership): ?>
                                                <span class="badge bg-success"><?php echo $membership['status']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-id-card fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">This Month Attendance</h6>
                                        <h4 class="mb-0"><?php echo $attendance_count; ?> Days</h4>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Workout Plans</h6>
                                        <h4 class="mb-0"><?php echo $workouts->num_rows; ?></h4>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-dumbbell fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Member Since</h6>
                                        <h4 class="mb-0"><?php echo date('M Y', strtotime($member['join_date'])); ?></h4>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-calendar-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Membership Details -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Membership Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($membership): ?>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><?php echo $membership['membership_type']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Start Date:</strong></td>
                                            <td><?php echo date('d M Y', strtotime($membership['start_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>End Date:</strong></td>
                                            <td><?php echo date('d M Y', strtotime($membership['end_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fee:</strong></td>
                                            <td>$<?php echo number_format($membership['fee'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge bg-success"><?php echo $membership['status']; ?></span></td>
                                        </tr>
                                    </table>
                                    
                                    <?php
                                    $days_left = ceil((strtotime($membership['end_date']) - time()) / 86400);
                                    $total_days = ceil((strtotime($membership['end_date']) - strtotime($membership['start_date'])) / 86400);
                                    $progress = max(0, min(100, (($total_days - $days_left) / $total_days) * 100));
                                    ?>
                                    
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Days Remaining</span>
                                            <span><?php echo $days_left; ?> days</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> You don't have an active membership. Please contact admin to renew.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Payments -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Recent Payments</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($payments->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td><?php echo $payment['membership_type']; ?></td>
                                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td>
                                                            <?php
                                                            $badge_class = '';
                                                            switch ($payment['payment_status']) {
                                                                case 'Paid': $badge_class = 'bg-success'; break;
                                                                case 'Pending': $badge_class = 'bg-warning'; break;
                                                                case 'Failed': $badge_class = 'bg-danger'; break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $payment['payment_status']; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No payment history available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Workout Plans -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>My Workout Plans</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($workouts->num_rows > 0): ?>
                                    <div class="row">
                                        <?php while ($workout = $workouts->fetch_assoc()): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card border-primary">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?php echo htmlspecialchars($workout['plan_name']); ?></h6>
                                                        <p class="card-text">
                                                            <i class="fas fa-bullseye text-primary"></i> Goal: <?php echo $workout['goal']; ?><br>
                                                            <i class="fas fa-clock text-info"></i> Duration: <?php echo $workout['duration']; ?>
                                                        </p>
                                                        <a href="./my_workouts.php"><button class="btn btn-sm btn-outline-primary w-100">View Details</button></a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No workout plans assigned yet. Contact your trainer to get started!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
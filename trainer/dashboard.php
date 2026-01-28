<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['TRAINER']);

// Get trainer details using account_id
$account_id = $_SESSION['user_id'];

// Note: Your schema doesn't have account_id in trainer table
// We'll need to link it or use a workaround
// For now, let's assume trainer_id = account_id - 1 (since admin is id 1)
$trainer_id = $account_id - 1;

// Get trainer info
$trainer_query = "SELECT * FROM TRAINER WHERE trainer_id = ?";
$stmt = $conn->prepare($trainer_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$trainer = $stmt->get_result()->fetch_assoc();

// If trainer not found, create basic info
if (!$trainer) {
    $trainer = [
        'trainer_name' => $_SESSION['username'],
        'specialization' => 'General',
        'experience_years' => 0,
        'phone' => 'N/A'
    ];
    $trainer_id = 1; // Default
}

// Get assigned members count
$members_query = "SELECT COUNT(DISTINCT mwp.member_id) as total 
                  FROM member_workout_plan mwp 
                  JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id 
                  WHERE wp.trainer_id = ?";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$members_count = $stmt->get_result()->fetch_assoc()['total'];

// Get total workout plans
$plans_query = "SELECT COUNT(*) as total FROM WORKOUT_PLAN WHERE trainer_id = ?";
$stmt = $conn->prepare($plans_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$plans_count = $stmt->get_result()->fetch_assoc()['total'];

// Get today's attendance count for assigned members
$attendance_query = "SELECT COUNT(DISTINCT a.member_id) as total 
                     FROM ATTENDANCE a
                     JOIN member_workout_plan mwp ON a.member_id = mwp.member_id
                     JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                     WHERE wp.trainer_id = ? AND DATE(a.attendance_date) = CURDATE()";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc()['total'];

// Get assigned members with their plans
$assigned_members = "SELECT DISTINCT m.member_id, m.member_name, m.email, m.phone, m.gender,
                     (SELECT COUNT(*) FROM member_workout_plan mwp2 
                      JOIN WORKOUT_PLAN wp2 ON mwp2.plan_id = wp2.plan_id 
                      WHERE mwp2.member_id = m.member_id AND wp2.trainer_id = ?) as plan_count
                     FROM MEMBER m
                     JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                     JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                     WHERE wp.trainer_id = ?
                     ORDER BY m.member_name
                     LIMIT 10";
$stmt = $conn->prepare($assigned_members);
$stmt->bind_param("ii", $trainer_id, $trainer_id);
$stmt->execute();
$members = $stmt->get_result();

// Get recent workout plans
$recent_plans = "SELECT wp.*, COUNT(mwp.member_id) as member_count
                 FROM WORKOUT_PLAN wp
                 LEFT JOIN member_workout_plan mwp ON wp.plan_id = mwp.plan_id
                 WHERE wp.trainer_id = ?
                 GROUP BY wp.plan_id
                 ORDER BY wp.plan_id DESC
                 LIMIT 5";
$stmt = $conn->prepare($recent_plans);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$plans = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - EnduraCore</title>
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
                <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($trainer['trainer_name']); ?>!</h1>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Assigned Members</h6>
                                        <h2 class="mb-0"><?php echo $members_count; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-users fa-2x"></i>
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
                                        <h2 class="mb-0"><?php echo $plans_count; ?></h2>
                                    </div>
                                    <div class="text-success">
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
                                        <h6 class="text-muted mb-2">Today's Attendance</h6>
                                        <h2 class="mb-0"><?php echo $today_attendance; ?></h2>
                                    </div>
                                    <div class="text-info">
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
                                        <h6 class="text-muted mb-2">Specialization</h6>
                                        <h5 class="mb-0"><?php echo $trainer['specialization']; ?></h5>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trainer Info Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <div class="card-body text-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h4 class="mb-3">Your Profile</h4>
                                        <p class="mb-2"><i class="fas fa-award me-2"></i> Specialization: <strong><?php echo $trainer['specialization']; ?></strong></p>
                                        <p class="mb-2"><i class="fas fa-briefcase me-2"></i> Experience: <strong><?php echo $trainer['experience_years']; ?> years</strong></p>
                                        <p class="mb-0"><i class="fas fa-phone me-2"></i> Phone: <strong><?php echo $trainer['phone']; ?></strong></p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <i class="fas fa-user-tie fa-5x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Assigned Members -->
                    <div class="col-md-7 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Assigned Members</h5>
                                <a href="my_members.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if ($members->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Contact</th>
                                                    <th>Gender</th>
                                                    <th>Plans</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($member = $members->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($member['member_name']); ?></strong></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($member['phone']); ?><br>
                                                                <?php echo htmlspecialchars($member['email']); ?>
                                                            </small>
                                                        </td>
                                                        <td><?php echo $member['gender']; ?></td>
                                                        <td><span class="badge bg-primary"><?php echo $member['plan_count']; ?> plans</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No members assigned yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Workout Plans -->
                    <div class="col-md-5 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i>Workout Plans</h5>
                                <a href="workout_plans.php" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-plus"></i> Create New
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($plans->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($plan = $plans->fetch_assoc()): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($plan['plan_name']); ?></h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-bullseye"></i> <?php echo $plan['goal']; ?> • 
                                                            <i class="fas fa-clock"></i> <?php echo $plan['duration']; ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-info"><?php echo $plan['member_count']; ?> members</span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No workout plans created yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="workout_plans.php" class="btn btn-outline-primary w-100 py-3">
                                            <i class="fas fa-plus-circle fa-2x d-block mb-2"></i>
                                            Create Workout Plan
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="my_members.php" class="btn btn-outline-success w-100 py-3">
                                            <i class="fas fa-users fa-2x d-block mb-2"></i>
                                            View Members
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="attendance.php" class="btn btn-outline-info w-100 py-3">
                                            <i class="fas fa-calendar-check fa-2x d-block mb-2"></i>
                                            Mark Attendance
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="schedule.php" class="btn btn-outline-warning w-100 py-3">
                                            <i class="fas fa-calendar-alt fa-2x d-block mb-2"></i>
                                            My Schedule
                                        </a>
                                    </div>
                                </div>
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
<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['TRAINER']);

$account_id = $_SESSION['user_id'];

// Get trainer info using account_id (FIXED)
$trainer_query = "SELECT trainer_id FROM TRAINER WHERE account_id = ?";
$stmt = $conn->prepare($trainer_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$trainer_result = $stmt->get_result()->fetch_assoc();

// Check if trainer profile exists
if (!$trainer_result) {
    die("<div class='alert alert-danger m-5'>
            <h4><i class='fas fa-exclamation-triangle'></i> Error: Trainer Profile Not Found</h4>
            <p>No trainer profile is associated with your account (Account ID: $account_id).</p>
            <p>Please contact the administrator to properly set up your trainer profile.</p>
         </div>");
}

$trainer_id = $trainer_result['trainer_id'];

// Get all members assigned to this trainer
$members_query = "SELECT DISTINCT 
                  m.member_id, 
                  m.member_name, 
                  m.email, 
                  m.phone, 
                  m.age,
                  m.gender,
                  m.join_date,
                  (SELECT COUNT(*) FROM member_workout_plan mwp2 
                   JOIN WORKOUT_PLAN wp2 ON mwp2.plan_id = wp2.plan_id 
                   WHERE mwp2.member_id = m.member_id AND wp2.trainer_id = ?) as plan_count,
                  (SELECT COUNT(*) FROM ATTENDANCE a 
                   WHERE a.member_id = m.member_id 
                   AND MONTH(a.attendance_date) = MONTH(CURDATE())) as attendance_count,
                  (SELECT status FROM MEMBERSHIP mem 
                   WHERE mem.member_id = m.member_id 
                   ORDER BY end_date DESC LIMIT 1) as membership_status
                  FROM MEMBER m
                  JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                  JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                  WHERE wp.trainer_id = ?
                  ORDER BY m.member_name";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("ii", $trainer_id, $trainer_id);
$stmt->execute();
$members = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Members - EnduraCore</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>My Assigned Members</h1>
                    <span class="badge bg-primary fs-5"><?php echo $members->num_rows; ?> Members</span>
                </div>
                
                <!-- Members Cards -->
                <?php if ($members->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($member = $members->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-user fa-2x"></i>
                                            </div>
                                            <div class="ms-3 flex-grow-1">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($member['member_name']); ?></h5>
                                                <small class="text-muted">
                                                    <?php echo $member['age'] ? $member['age'] . ' years' : 'Age N/A'; ?> • 
                                                    <?php echo $member['gender']; ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <i class="fas fa-envelope text-primary"></i>
                                            <small class="ms-2"><?php echo htmlspecialchars($member['email']); ?></small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <i class="fas fa-phone text-success"></i>
                                            <small class="ms-2"><?php echo htmlspecialchars($member['phone']); ?></small>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <h4 class="mb-0 text-primary"><?php echo $member['plan_count']; ?></h4>
                                                    <small class="text-muted">Plans</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <h4 class="mb-0 text-success"><?php echo $member['attendance_count']; ?></h4>
                                                    <small class="text-muted">This Month</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="border rounded p-2">
                                                    <?php
                                                    $status_class = '';
                                                    switch ($member['membership_status']) {
                                                        case 'Active': $status_class = 'text-success'; break;
                                                        case 'Expired': $status_class = 'text-danger'; break;
                                                        default: $status_class = 'text-secondary';
                                                    }
                                                    ?>
                                                    <div class="<?php echo $status_class; ?>">
                                                        <i class="fas fa-circle"></i>
                                                    </div>
                                                    <small class="text-muted">Status</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#viewMemberModal<?php echo $member['member_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-success flex-grow-1" onclick="assignPlan(<?php echo $member['member_id']; ?>)">
                                                <i class="fas fa-plus"></i> Assign Plan
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($member['join_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- View Member Modal -->
                            <div class="modal fade" id="viewMemberModal<?php echo $member['member_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Member Details - <?php echo htmlspecialchars($member['member_name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-3">Personal Information</h6>
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th>Name:</th>
                                                            <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Age:</th>
                                                            <td><?php echo $member['age'] ?? 'N/A'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Gender:</th>
                                                            <td><?php echo $member['gender']; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Email:</th>
                                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Phone:</th>
                                                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-3">Assigned Workout Plans</h6>
                                                    <?php
                                                    $plans_query = "SELECT wp.plan_name, wp.goal, wp.duration 
                                                                   FROM WORKOUT_PLAN wp
                                                                   JOIN member_workout_plan mwp ON wp.plan_id = mwp.plan_id
                                                                   WHERE mwp.member_id = ? AND wp.trainer_id = ?";
                                                    $plans_stmt = $conn->prepare($plans_query);
                                                    $plans_stmt->bind_param("ii", $member['member_id'], $trainer_id);
                                                    $plans_stmt->execute();
                                                    $plans = $plans_stmt->get_result();
                                                    ?>
                                                    <?php if ($plans->num_rows > 0): ?>
                                                        <div class="list-group">
                                                            <?php while ($plan = $plans->fetch_assoc()): ?>
                                                                <div class="list-group-item">
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($plan['plan_name']); ?></h6>
                                                                    <small class="text-muted">
                                                                        <?php echo $plan['goal']; ?> • <?php echo $plan['duration']; ?>
                                                                    </small>
                                                                </div>
                                                            <?php endwhile; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-muted">No plans assigned yet.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h4>No Members Assigned Yet</h4>
                        <p>Members will appear here once you create workout plans and assign them.</p>
                        <a href="workout_plans.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Workout Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function assignPlan(memberId) {
        alert('Assign workout plan to member ID: ' + memberId + '\nThis feature will redirect to workout plans page.');
        window.location.href = 'workout_plans.php';
    }
    </script>
</body>
</html>
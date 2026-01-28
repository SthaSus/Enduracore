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

$success = '';
$error = '';

// Handle Mark Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $member_id = intval($_POST['member_id']);
    $attendance_date = $_POST['attendance_date'];
    $check_in = $_POST['check_in'];
    $check_out = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    
    // Check if attendance already exists for this date
    $check_stmt = $conn->prepare("SELECT attendance_id FROM ATTENDANCE WHERE member_id = ? AND attendance_date = ?");
    $check_stmt->bind_param("is", $member_id, $attendance_date);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Attendance already marked for this member on this date";
    } else {
        $stmt = $conn->prepare("INSERT INTO ATTENDANCE (member_id, attendance_date, check_in, check_out) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $member_id, $attendance_date, $check_in, $check_out);
        
        if ($stmt->execute()) {
            $success = "Attendance marked successfully";
        } else {
            $error = "Failed to mark attendance";
        }
    }
}

// Get assigned members for attendance marking
$members_query = "SELECT DISTINCT m.member_id, m.member_name
                  FROM MEMBER m
                  JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                  JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                  WHERE wp.trainer_id = ?
                  ORDER BY m.member_name";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$members = $stmt->get_result();

// Get today's attendance
$today = date('Y-m-d');
$today_attendance_query = "SELECT a.*, m.member_name
                           FROM ATTENDANCE a
                           JOIN MEMBER m ON a.member_id = m.member_id
                           JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                           JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                           WHERE wp.trainer_id = ? AND a.attendance_date = ?
                           ORDER BY a.check_in DESC";
$stmt = $conn->prepare($today_attendance_query);
$stmt->bind_param("is", $trainer_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result();

// Get this week's attendance
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_attendance_query = "SELECT a.*, m.member_name
                          FROM ATTENDANCE a
                          JOIN MEMBER m ON a.member_id = m.member_id
                          JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                          JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                          WHERE wp.trainer_id = ? 
                          AND a.attendance_date >= ?
                          ORDER BY a.attendance_date DESC, a.check_in DESC";
$stmt = $conn->prepare($week_attendance_query);
$stmt->bind_param("is", $trainer_id, $week_start);
$stmt->execute();
$week_attendance = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - EnduraCore</title>
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
                    <h1>Member Attendance</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                        <i class="fas fa-check-circle"></i> Mark Attendance
                    </button>
                </div>
                
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
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Today's Attendance</h6>
                                        <h2 class="mb-0"><?php echo $today_attendance->num_rows; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-calendar-day fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">This Week</h6>
                                        <h2 class="mb-0"><?php echo $week_attendance->num_rows; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-calendar-week fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Members</h6>
                                        <h2 class="mb-0"><?php echo $members->num_rows; ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Attendance -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Attendance (<?php echo date('F d, Y'); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($today_attendance->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Member Name</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $today_attendance->data_seek(0);
                                        while ($att = $today_attendance->fetch_assoc()): 
                                            $check_in = new DateTime($att['check_in']);
                                            $check_out = $att['check_out'] ? new DateTime($att['check_out']) : null;
                                            $duration = $check_out ? $check_in->diff($check_out)->format('%H:%I') : 'In Progress';
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($att['member_name']); ?></strong></td>
                                                <td><i class="fas fa-sign-in-alt text-success"></i> <?php echo date('h:i A', strtotime($att['check_in'])); ?></td>
                                                <td>
                                                    <?php if ($att['check_out']): ?>
                                                        <i class="fas fa-sign-out-alt text-danger"></i> <?php echo date('h:i A', strtotime($att['check_out'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not checked out</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $duration; ?></td>
                                                <td>
                                                    <?php if ($att['check_out']): ?>
                                                        <span class="badge bg-secondary">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No attendance records for today yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- This Week's Attendance -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>This Week's Attendance</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($week_attendance->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Member Name</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $week_attendance->data_seek(0);
                                        while ($att = $week_attendance->fetch_assoc()): 
                                            $check_in = new DateTime($att['check_in']);
                                            $check_out = $att['check_out'] ? new DateTime($att['check_out']) : null;
                                            $duration = $check_out ? $check_in->diff($check_out)->format('%H:%I hours') : 'In Progress';
                                        ?>
                                            <tr>
                                                <td><?php echo date('D, M d', strtotime($att['attendance_date'])); ?></td>
                                                <td><strong><?php echo htmlspecialchars($att['member_name']); ?></strong></td>
                                                <td><?php echo date('h:i A', strtotime($att['check_in'])); ?></td>
                                                <td><?php echo $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '-'; ?></td>
                                                <td><?php echo $duration; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No attendance records for this week yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mark Attendance Modal -->
    <div class="modal fade" id="markAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member *</label>
                            <select class="form-select" name="member_id" required>
                                <option value="">Select Member</option>
                                <?php 
                                $members->data_seek(0);
                                while ($member = $members->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $member['member_id']; ?>">
                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check In Time *</label>
                                <input type="time" class="form-control" name="check_in" value="<?php echo date('H:i'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check Out Time</label>
                                <input type="time" class="form-control" name="check_out">
                                <small class="text-muted">Leave empty if still in gym</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="mark_attendance" class="btn btn-primary">
                            <i class="fas fa-check"></i> Mark Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
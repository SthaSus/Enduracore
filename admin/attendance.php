<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

// Get date filter
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get statistics
$today_attendance = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE DATE(attendance_date) = CURDATE()")->fetch_assoc()['count'];
$month_attendance = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM MEMBER")->fetch_assoc()['count'];
$attendance_rate = $total_members > 0 ? round(($today_attendance / $total_members) * 100) : 0;

// Get attendance records for selected date
$attendance_query = "SELECT a.*, m.member_name, m.phone
                     FROM ATTENDANCE a
                     JOIN MEMBER m ON a.member_id = m.member_id
                     WHERE DATE(a.attendance_date) = ?
                     ORDER BY a.check_in DESC";
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Get monthly summary
$monthly_summary = "SELECT DATE(attendance_date) as date, COUNT(*) as count
                    FROM ATTENDANCE
                    WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?
                    GROUP BY DATE(attendance_date)
                    ORDER BY date DESC";
$stmt = $conn->prepare($monthly_summary);
$stmt->bind_param("s", $filter_month);
$stmt->execute();
$monthly_data = $stmt->get_result();

// Get top members this month
$top_members = "SELECT m.member_name, COUNT(*) as visits
                FROM ATTENDANCE a
                JOIN MEMBER m ON a.member_id = m.member_id
                WHERE MONTH(a.attendance_date) = MONTH(CURDATE())
                AND YEAR(a.attendance_date) = YEAR(CURDATE())
                GROUP BY a.member_id
                ORDER BY visits DESC
                LIMIT 10";
$top_members_result = $conn->query($top_members);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - EnduraCore</title>
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
                <h1 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Attendance Management</h1>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Today's Attendance</h6>
                                        <h2 class="mb-0"><?php echo $today_attendance; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-calendar-day fa-2x"></i>
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
                                        <h6 class="text-muted mb-2">This Month</h6>
                                        <h2 class="mb-0"><?php echo $month_attendance; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-calendar-alt fa-2x"></i>
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
                                        <h6 class="text-muted mb-2">Total Members</h6>
                                        <h2 class="mb-0"><?php echo $total_members; ?></h2>
                                    </div>
                                    <div class="text-info">
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
                                        <h6 class="text-muted mb-2">Attendance Rate</h6>
                                        <h2 class="mb-0"><?php echo $attendance_rate; ?>%</h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Today's Attendance -->
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Attendance Records</h5>
                                <div>
                                    <input type="date" class="form-control form-control-sm" value="<?php echo $filter_date; ?>" 
                                           onchange="window.location.href='?date='+this.value">
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($attendance_records->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Check In</th>
                                                    <th>Check Out</th>
                                                    <th>Duration</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($record = $attendance_records->fetch_assoc()): 
                                                    $duration = 'In Progress';
                                                    if ($record['check_out']) {
                                                        $check_in = new DateTime($record['check_in']);
                                                        $check_out = new DateTime($record['check_out']);
                                                        $diff = $check_in->diff($check_out);
                                                        $duration = $diff->format('%H:%I');
                                                    }
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($record['member_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo $record['phone']; ?></small>
                                                        </td>
                                                        <td>
                                                            <i class="fas fa-sign-in-alt text-success"></i>
                                                            <?php echo date('h:i A', strtotime($record['check_in'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($record['check_out']): ?>
                                                                <i class="fas fa-sign-out-alt text-danger"></i>
                                                                <?php echo date('h:i A', strtotime($record['check_out'])); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $duration; ?></td>
                                                        <td>
                                                            <?php if ($record['check_out']): ?>
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
                                        <i class="fas fa-info-circle"></i> No attendance records for <?php echo date('M d, Y', strtotime($filter_date)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Members -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Members This Month</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($top_members_result->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php 
                                        $rank = 1;
                                        while ($member = $top_members_result->fetch_assoc()): 
                                            $medal_icon = '';
                                            $medal_color = '';
                                            if ($rank == 1) {
                                                $medal_icon = 'fa-trophy';
                                                $medal_color = 'text-warning';
                                            } elseif ($rank == 2) {
                                                $medal_icon = 'fa-medal';
                                                $medal_color = 'text-secondary';
                                            } elseif ($rank == 3) {
                                                $medal_icon = 'fa-medal';
                                                $medal_color = 'text-danger';
                                            } else {
                                                $medal_icon = 'fa-star';
                                                $medal_color = 'text-muted';
                                            }
                                        ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas <?php echo $medal_icon; ?> <?php echo $medal_color; ?> me-2"></i>
                                                        <strong><?php echo htmlspecialchars($member['member_name']); ?></strong>
                                                    </div>
                                                    <span class="badge bg-primary"><?php echo $member['visits']; ?> visits</span>
                                                </div>
                                            </div>
                                        <?php 
                                            $rank++;
                                        endwhile; 
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No attendance data for this month</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Summary -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Summary</h5>
                        <input type="month" class="form-control form-control-sm" style="width: 200px;" 
                               value="<?php echo $filter_month; ?>" 
                               onchange="window.location.href='?month='+this.value">
                    </div>
                    <div class="card-body">
                        <?php if ($monthly_data->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($day = $monthly_data->fetch_assoc()): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body text-center">
                                                <h6 class="text-muted mb-1"><?php echo date('M d, D', strtotime($day['date'])); ?></h6>
                                                <h3 class="mb-0 text-primary"><?php echo $day['count']; ?></h3>
                                                <small class="text-muted">visits</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center mb-0">
                                <i class="fas fa-info-circle"></i> No attendance data for <?php echo date('F Y', strtotime($filter_month)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
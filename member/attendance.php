<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['MEMBER']);

$account_id = $_SESSION['user_id'];

// Get member details
$member_query = "SELECT * FROM MEMBER WHERE account_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$member_id = $member['member_id'];

// Get attendance statistics
$total_visits = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE member_id = $member_id")->fetch_assoc()['count'];
$this_month = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE member_id = $member_id AND MONTH(attendance_date) = MONTH(CURDATE())")->fetch_assoc()['count'];
$this_week = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE member_id = $member_id AND WEEK(attendance_date) = WEEK(CURDATE())")->fetch_assoc()['count'];

// Calculate average workout duration
$avg_duration_query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, check_in, check_out)) as avg_duration 
                       FROM ATTENDANCE 
                       WHERE member_id = $member_id AND check_out IS NOT NULL";
$avg_duration = $conn->query($avg_duration_query)->fetch_assoc()['avg_duration'] ?? 0;

// Get attendance records for current month
$current_month_attendance = "SELECT * FROM ATTENDANCE 
                             WHERE member_id = ? 
                             AND MONTH(attendance_date) = MONTH(CURDATE())
                             AND YEAR(attendance_date) = YEAR(CURDATE())
                             ORDER BY attendance_date DESC";
$stmt = $conn->prepare($current_month_attendance);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$monthly_records = $stmt->get_result();

// Get all attendance records
$all_attendance = "SELECT * FROM ATTENDANCE 
                   WHERE member_id = ? 
                   ORDER BY attendance_date DESC, check_in DESC
                   LIMIT 50";
$stmt = $conn->prepare($all_attendance);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$all_records = $stmt->get_result();

// Create calendar data for current month
$first_day = date('Y-m-01');
$last_day = date('Y-m-t');
$calendar_data = [];

$cal_query = "SELECT DATE(attendance_date) as date FROM ATTENDANCE 
              WHERE member_id = ? 
              AND attendance_date BETWEEN ? AND ?";
$stmt = $conn->prepare($cal_query);
$stmt->bind_param("iss", $member_id, $first_day, $last_day);
$stmt->execute();
$cal_result = $stmt->get_result();

while ($row = $cal_result->fetch_assoc()) {
    $calendar_data[] = $row['date'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - EnduraCore</title>
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
                <h1 class="mb-4"><i class="fas fa-calendar-check me-2"></i>My Attendance</h1>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Visits</h6>
                                        <h2 class="mb-0"><?php echo $total_visits; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-chart-line fa-2x"></i>
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
                                        <h2 class="mb-0"><?php echo $this_month; ?></h2>
                                    </div>
                                    <div class="text-success">
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
                                        <h6 class="text-muted mb-2">This Week</h6>
                                        <h2 class="mb-0"><?php echo $this_week; ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-calendar-week fa-2x"></i>
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
                                        <h6 class="text-muted mb-2">Avg. Duration</h6>
                                        <h2 class="mb-0"><?php echo round($avg_duration); ?> min</h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Motivation Card -->
                <div class="card streak-card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-3"><i class="fas fa-fire me-2"></i>Keep Up The Great Work!</h3>
                                <p class="mb-2 fs-5">You've visited the gym <?php echo $this_month; ?> times this month</p>
                                <?php if ($this_month >= 20): ?>
                                    <p class="mb-0"><i class="fas fa-trophy me-2"></i>Outstanding commitment! You're a fitness champion!</p>
                                <?php elseif ($this_month >= 12): ?>
                                    <p class="mb-0"><i class="fas fa-star me-2"></i>Great consistency! Keep pushing!</p>
                                <?php else: ?>
                                    <p class="mb-0"><i class="fas fa-rocket me-2"></i>Let's aim for at least 12 visits this month!</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-medal fa-5x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar View -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i><?php echo date('F Y'); ?> Attendance Calendar</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <!-- Day headers -->
                            <?php
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            foreach ($days as $day):
                            ?>
                                <div class="col text-center">
                                    <strong class="text-muted"><?php echo $day; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="row g-2 mt-2">
                            <?php
                            $first_day_of_month = date('Y-m-01');
                            $last_day_of_month = date('Y-m-t');
                            $first_weekday = date('w', strtotime($first_day_of_month));
                            $total_days = date('t');
                            
                            // Empty cells before first day
                            for ($i = 0; $i < $first_weekday; $i++) {
                                echo '<div class="col"><div class="calendar-day"></div></div>';
                            }
                            
                            // Days of month
                            for ($day = 1; $day <= $total_days; $day++) {
                                $current_date = date('Y-m-' . str_pad($day, 2, '0', STR_PAD_LEFT));
                                $is_present = in_array($current_date, $calendar_data);
                                $is_today = ($current_date == date('Y-m-d'));
                                
                                $classes = ['calendar-day'];
                                if ($is_present) $classes[] = 'present';
                                if ($is_today) $classes[] = 'today';
                                
                                echo '<div class="col">';
                                echo '<div class="' . implode(' ', $classes) . '">';
                                echo '<div class="text-end mb-2">' . $day . '</div>';
                                if ($is_present) {
                                    echo '<div class="text-center"><i class="fas fa-check-circle text-success"></i></div>';
                                }
                                echo '</div></div>';
                                
                                if (($first_weekday + $day) % 7 == 0 && $day < $total_days) {
                                    echo '</div><div class="row g-2 mt-2">';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Attendance Records -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Attendance History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($all_records->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($record = $all_records->fetch_assoc()): 
                                            $duration = 'N/A';
                                            if ($record['check_out']) {
                                                $check_in = new DateTime($record['check_in']);
                                                $check_out = new DateTime($record['check_out']);
                                                $diff = $check_in->diff($check_out);
                                                $duration = $diff->format('%H:%I');
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
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
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">In Progress</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No attendance records yet. Start your fitness journey today!
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
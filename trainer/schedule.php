<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['TRAINER']);

$account_id = $_SESSION['user_id'];

// Get trainer info using account_id (FIXED)
$trainer_query = "SELECT * FROM TRAINER WHERE account_id = ?";
$stmt = $conn->prepare($trainer_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$trainer = $stmt->get_result()->fetch_assoc();

// Check if trainer profile exists
if (!$trainer) {
    die("<div class='alert alert-danger m-5'>
            <h4><i class='fas fa-exclamation-triangle'></i> Error: Trainer Profile Not Found</h4>
            <p>No trainer profile is associated with your account (Account ID: $account_id).</p>
            <p>Please contact the administrator to properly set up your trainer profile.</p>
         </div>");
}

$trainer_id = $trainer['trainer_id'];

// Get weekly attendance pattern (for schedule visualization)
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$schedule_data = [];

foreach ($week_days as $day) {
    $day_number = date('N', strtotime("this $day"));
    
    // Get members who typically attend on this day
    $day_query = "SELECT DISTINCT m.member_name, a.check_in
                  FROM MEMBER m
                  JOIN ATTENDANCE a ON m.member_id = a.member_id
                  JOIN member_workout_plan mwp ON m.member_id = mwp.member_id
                  JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                  WHERE wp.trainer_id = ?
                  AND DAYOFWEEK(a.attendance_date) = ?
                  AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  ORDER BY a.check_in
                  LIMIT 10";
    
    $stmt = $conn->prepare($day_query);
    // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, so we need to adjust
    $mysql_day = ($day_number % 7) + 1;
    $stmt->bind_param("ii", $trainer_id, $mysql_day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule_data[$day] = [];
    while ($row = $result->fetch_assoc()) {
        $schedule_data[$day][] = $row;
    }
}

// Get assigned members count per day this week
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$this_week_query = "SELECT DATE(a.attendance_date) as date, COUNT(*) as count
                    FROM ATTENDANCE a
                    JOIN member_workout_plan mwp ON a.member_id = mwp.member_id
                    JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id
                    WHERE wp.trainer_id = ?
                    AND a.attendance_date BETWEEN ? AND ?
                    GROUP BY DATE(a.attendance_date)";
$stmt = $conn->prepare($this_week_query);
$stmt->bind_param("iss", $trainer_id, $week_start, $week_end);
$stmt->execute();
$this_week_attendance = $stmt->get_result();

$week_attendance_map = [];
while ($row = $this_week_attendance->fetch_assoc()) {
    $week_attendance_map[$row['date']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>

    </style>
</head>
<body>
    <div class="d-flex">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <?php include '../partials/header.php'; ?>
            
            <div class="container-fluid">
                <h1 class="mb-4">My Training Schedule</h1>
                
                <!-- Trainer Info Card -->
                <div class="card shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <div class="card-body text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-3"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($trainer['trainer_name']); ?></h4>
                                <p class="mb-2"><i class="fas fa-award me-2"></i>Specialization: <strong><?php echo $trainer['specialization']; ?></strong></p>
                                <p class="mb-0"><i class="fas fa-briefcase me-2"></i>Experience: <strong><?php echo $trainer['experience_years']; ?> years</strong></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-calendar-alt fa-5x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- This Week Overview -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>This Week's Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php
                            $current_date = strtotime($week_start);
                            for ($i = 0; $i < 7; $i++):
                                $date = date('Y-m-d', $current_date);
                                $day_name = date('D', $current_date);
                                $day_date = date('d', $current_date);
                                $is_today = ($date == date('Y-m-d'));
                                $count = isset($week_attendance_map[$date]) ? $week_attendance_map[$date] : 0;
                                $current_date = strtotime('+1 day', $current_date);
                            ?>
                                <div class="col">
                                    <div class="card <?php echo $is_today ? 'border-primary' : ''; ?>">
                                        <div class="card-body p-2">
                                            <h6 class="mb-1 <?php echo $is_today ? 'text-primary' : 'text-muted'; ?>"><?php echo $day_name; ?></h6>
                                            <div class="h2 mb-1"><?php echo $day_date; ?></div>
                                            <span class="badge <?php echo $count > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $count; ?> members
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Schedule Pattern -->
                <h3 class="mb-3">Typical Weekly Schedule Pattern</h3>
                <p class="text-muted mb-4">Based on member attendance over the last 30 days</p>
                
                <div class="row">
                    <?php foreach ($week_days as $day): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card schedule-card shadow-sm <?php echo (date('l') == $day) ? 'current-day' : ''; ?>">
                                <div class="day-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-day me-2"></i><?php echo $day; ?>
                                        <?php if (date('l') == $day): ?>
                                            <span class="badge bg-warning float-end">Today</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($schedule_data[$day])): ?>
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-users"></i> <?php echo count($schedule_data[$day]); ?> members typically attend
                                        </p>
                                        <?php foreach ($schedule_data[$day] as $slot): ?>
                                            <div class="time-slot">
                                                <i class="fas fa-clock text-primary me-2"></i>
                                                <strong><?php echo date('h:i A', strtotime($slot['check_in'])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($slot['member_name']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-light text-center mb-0">
                                            <i class="fas fa-info-circle"></i>
                                            <p class="mb-0 mt-2">No regular schedule on this day</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-fire text-danger fa-3x mb-3"></i>
                                <h3>Peak Hours</h3>
                                <p class="text-muted">6:00 AM - 9:00 AM</p>
                                <p class="text-muted">5:00 PM - 8:00 PM</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line text-success fa-3x mb-3"></i>
                                <h3>Busiest Day</h3>
                                <?php
                                $busiest = '';
                                $max_count = 0;
                                foreach ($schedule_data as $day => $slots) {
                                    if (count($slots) > $max_count) {
                                        $max_count = count($slots);
                                        $busiest = $day;
                                    }
                                }
                                ?>
                                <p class="text-muted"><?php echo $busiest ?: 'No data'; ?></p>
                                <p class="text-muted"><?php echo $max_count; ?> members</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-moon text-primary fa-3x mb-3"></i>
                                <h3>Quietest Day</h3>
                                <?php
                                $quietest = '';
                                $min_count = PHP_INT_MAX;
                                foreach ($schedule_data as $day => $slots) {
                                    $count = count($slots);
                                    if ($count > 0 && $count < $min_count) {
                                        $min_count = $count;
                                        $quietest = $day;
                                    }
                                }
                                ?>
                                <p class="text-muted"><?php echo $quietest ?: 'No data'; ?></p>
                                <p class="text-muted"><?php echo $min_count == PHP_INT_MAX ? 0 : $min_count; ?> members</p>
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
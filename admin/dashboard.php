<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

// Get statistics
$total_members = $conn->query("SELECT COUNT(*) as count FROM MEMBER")->fetch_assoc()['count'];
$total_trainers = $conn->query("SELECT COUNT(*) as count FROM TRAINER")->fetch_assoc()['count'];
$active_memberships = $conn->query("SELECT COUNT(*) as count FROM MEMBERSHIP WHERE status = 'Active'")->fetch_assoc()['count'];
$today_attendance = $conn->query("SELECT COUNT(*) as count FROM ATTENDANCE WHERE DATE(attendance_date) = CURDATE()")->fetch_assoc()['count'];

// Get monthly revenue
$monthly_revenue = $conn->query("
    SELECT SUM(amount) as total 
    FROM PAYMENT 
    WHERE MONTH(payment_date) = MONTH(CURDATE())
    AND YEAR(payment_date) = YEAR(CURDATE())
    AND payment_status = 'Paid'
")->fetch_assoc()['total'] ?? 0;

// Get pending payments
$pending_payments = $conn->query("
    SELECT COUNT(*) as count 
    FROM PAYMENT 
    WHERE payment_status = 'Pending'
")->fetch_assoc()['count'];

// Get member status breakdown
$active_members = $conn->query("
    SELECT COUNT(DISTINCT m.member_id) as count 
    FROM MEMBER m 
    JOIN MEMBERSHIP mem ON m.member_id = mem.member_id 
    WHERE mem.status = 'Active'
")->fetch_assoc()['count'];

$expired_members = $conn->query("
    SELECT COUNT(DISTINCT m.member_id) as count 
    FROM MEMBER m 
    JOIN MEMBERSHIP mem ON m.member_id = mem.member_id 
    WHERE mem.status = 'Expired'
")->fetch_assoc()['count'];

// Get weekly attendance data for chart
$weekly_attendance = $conn->query("
    SELECT DATE(attendance_date) as date, COUNT(*) as count 
    FROM ATTENDANCE 
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(attendance_date)
    ORDER BY date ASC
");

$attendance_labels = [];
$attendance_data = [];
while($row = $weekly_attendance->fetch_assoc()) {
    $attendance_labels[] = date('M d', strtotime($row['date']));
    $attendance_data[] = $row['count'];
}

// Get monthly revenue data for chart
$monthly_revenue_data = $conn->query("
    SELECT MONTH(payment_date) as month, SUM(amount) as total 
    FROM PAYMENT 
    WHERE payment_status = 'Paid' 
    AND YEAR(payment_date) = YEAR(CURDATE())
    GROUP BY MONTH(payment_date)
    ORDER BY month ASC
");

$revenue_labels = [];
$revenue_data = [];
while($row = $monthly_revenue_data->fetch_assoc()) {
    $revenue_labels[] = date('M', mktime(0, 0, 0, $row['month'], 1));
    $revenue_data[] = $row['total'];
}

// Get recent members
$recent_members = $conn->query("
    SELECT member_name, email, join_date
    FROM MEMBER
    ORDER BY join_date DESC
    LIMIT 5
");

// Get expiring memberships
$expiring_memberships = $conn->query("
    SELECT m.member_name, mem.end_date, mem.membership_type 
    FROM MEMBER m 
    JOIN MEMBERSHIP mem ON m.member_id = mem.member_id 
    WHERE mem.status = 'Active' 
    AND mem.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY mem.end_date ASC
    LIMIT 5
");

// Get recent payments
$recent_payments = $conn->query("
    SELECT p.payment_id, m.member_name, p.amount, p.payment_date, p.payment_status
    FROM PAYMENT p
    JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
    JOIN MEMBER m ON mem.member_id = m.member_id
    ORDER BY p.payment_date DESC
    LIMIT 5
");

// Get equipment needing service
$equipment_service = $conn->query("
    SELECT * FROM EQUIPMENT 
    WHERE equipment_condition = 'Needs Repair' 
    OR DATEDIFF(CURDATE(), last_serviced) > 90
    ORDER BY last_serviced ASC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="d-flex">

    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <?php include '../partials/header.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Admin Dashboard</h1>
                <div>
                    <a href="members.php" class="btn btn-primary me-2">
                        <i class="fas fa-user-plus"></i> Add Member
                    </a>

                    <a href="payments.php" class="btn btn-success">
                        <i class="fas fa-dollar-sign"></i> Record Payment
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Members</h6>
                                <h2 class="mb-1"><?= $total_members ?></h2>
                                <small class="text-success">
                                    <i class="fas fa-arrow-up"></i> <?= $active_members ?> Active
                                </small>
                            </div>
                            <i class="fas fa-users fa-3x text-primary opacity-75"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Trainers</h6>
                                <h2 class="mb-1"><?= $total_trainers ?></h2>
                                <small class="text-muted">Active staff</small>
                            </div>
                            <i class="fas fa-user-tie fa-3x text-success opacity-75"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Active Memberships</h6>
                                <h2 class="mb-1"><?= $active_memberships ?></h2>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> <?= $expired_members ?> Expired
                                </small>
                            </div>
                            <i class="fas fa-id-card fa-3x text-info opacity-75"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Today's Attendance</h6>
                                <h2 class="mb-1"><?= $today_attendance ?></h2>
                                <small class="text-muted">Check-ins today</small>
                            </div>
                            <i class="fas fa-calendar-check fa-3x text-warning opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue & Payments -->
            <div class="row mb-4">
                <div class="col-md-8 mb-3">
                    <div class="card border-0 shadow-sm text-white h-100"
                         style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="opacity-75 mb-2">Monthly Revenue</h6>
                                <h2 class="mb-1">$<?= number_format($monthly_revenue, 2) ?></h2>
                                <small class="opacity-75"><?= date('F Y') ?></small>
                            </div>
                            <i class="fas fa-dollar-sign fa-4x opacity-50"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <div class="card-body text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="opacity-75 mb-2">Pending Payments</h6>
                                <h2 class="mb-1"><?= $pending_payments ?></h2>
                                <a href="payments.php?status=Pending" class="text-white text-decoration-none">
                                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-chart-line me-2 text-primary"></i>Weekly Attendance Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-chart-bar me-2 text-success"></i>Monthly Revenue</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Members -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Members</h5>
                            <a href="members.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_members->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Join Date</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($member = $recent_members->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['member_name']) ?></td>
                                                <td><?= htmlspecialchars($member['email']) ?></td>
                                                <td><?= date('d M Y', strtotime($member['join_date'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No recent members</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Recent Payments</h5>
                            <a href="payments.php" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_payments->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Member</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($payment['member_name']) ?></td>
                                                <td>$<?= number_format($payment['amount'], 2) ?></td>
                                                <td><?= date('d M Y', strtotime($payment['payment_date'])) ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = $payment['payment_status'] == 'Paid' ? 'success' : 
                                                                  ($payment['payment_status'] == 'Pending' ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $payment['payment_status'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No recent payments</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Expiring Memberships -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Expiring Soon</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($expiring_memberships->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Member</th>
                                            <th>Type</th>
                                            <th>Expires</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($exp = $expiring_memberships->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($exp['member_name']) ?></td>
                                                <td><?= $exp['membership_type'] ?></td>
                                                <td class="text-danger fw-bold"><?= date('d M Y', strtotime($exp['end_date'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">No expiring memberships</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Equipment Service -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger bg-opacity-10">
                            <h5 class="mb-0"><i class="fas fa-tools text-danger me-2"></i>Equipment Alerts</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($equipment_service->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Equipment</th>
                                            <th>Condition</th>
                                            <th>Last Service</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($equip = $equipment_service->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($equip['equipment_name']) ?></td>
                                                <td><span class="badge bg-danger"><?= $equip['equipment_condition'] ?></span></td>
                                                <td><?= date('d M Y', strtotime($equip['last_serviced'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-success text-center mb-0"><i class="fas fa-check-circle"></i> All equipment in good condition</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pass PHP arrays to JS
    const attendanceLabels = <?= json_encode($attendance_labels) ?>;
    const attendanceData = <?= json_encode($attendance_data) ?>;

    const revenueLabels = <?= json_encode($revenue_labels) ?>;
    const revenueData = <?= json_encode($revenue_data) ?>;
</script>

<!-- Then include external JS -->
<script src="../script/admin/dashboard.js"></script>

<!-- commented to move to js folder -->
<!-- <script>
// Attendance Chart
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($attendance_labels) ?>,
        datasets: [{
            label: 'Daily Attendance',
            data: <?= json_encode($attendance_data) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($revenue_labels) ?>,
        datasets: [{
            label: 'Monthly Revenue',
            data: <?= json_encode($revenue_data) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});
</script> -->
<script src="../script/admin/dashboard.js"></script>
</body>
</html>
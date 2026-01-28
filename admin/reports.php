<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

// Default date range (current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Revenue Report
$revenue_query = $conn->prepare("
    SELECT 
        SUM(amount) as total_revenue,
        COUNT(*) as total_payments,
        SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN payment_status = 'Failed' THEN amount ELSE 0 END) as failed_amount
    FROM PAYMENT
    WHERE payment_date BETWEEN ? AND ?
");
$revenue_query->bind_param("ss", $start_date, $end_date);
$revenue_query->execute();
$revenue_data = $revenue_query->get_result()->fetch_assoc();
$revenue_query->close();

// Revenue by Payment Method
$payment_methods = $conn->prepare("
    SELECT payment_method, COUNT(*) as count, SUM(amount) as total
    FROM PAYMENT
    WHERE payment_date BETWEEN ? AND ? AND payment_status = 'Paid'
    GROUP BY payment_method
");
$payment_methods->bind_param("ss", $start_date, $end_date);
$payment_methods->execute();
$methods_result = $payment_methods->get_result();
$payment_methods->close();

// Membership Report
$membership_query = $conn->prepare("
    SELECT 
        membership_type,
        COUNT(*) as count,
        SUM(fee) as total_value
    FROM MEMBERSHIP
    WHERE start_date BETWEEN ? AND ?
    GROUP BY membership_type
");
$membership_query->bind_param("ss", $start_date, $end_date);
$membership_query->execute();
$membership_data = $membership_query->get_result();
$membership_query->close();

// Member Statistics
$member_stats = $conn->prepare("
    SELECT 
        COUNT(*) as new_members,
        COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male_count,
        COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female_count,
        AVG(age) as avg_age
    FROM MEMBER
    WHERE join_date BETWEEN ? AND ?
");
$member_stats->bind_param("ss", $start_date, $end_date);
$member_stats->execute();
$member_data = $member_stats->get_result()->fetch_assoc();
$member_stats->close();

// Attendance Report
$attendance_query = $conn->prepare("
    SELECT 
        DATE(attendance_date) as date,
        COUNT(*) as total_checkins,
        COUNT(DISTINCT member_id) as unique_members
    FROM ATTENDANCE
    WHERE attendance_date BETWEEN ? AND ?
    GROUP BY DATE(attendance_date)
    ORDER BY date DESC
");
$attendance_query->bind_param("ss", $start_date, $end_date);
$attendance_query->execute();
$attendance_data = $attendance_query->get_result();
$attendance_query->close();

// Top Members by Attendance
$top_members = $conn->prepare("
    SELECT 
        m.member_name,
        COUNT(a.attendance_id) as visit_count
    FROM ATTENDANCE a
    JOIN MEMBER m ON a.member_id = m.member_id
    WHERE a.attendance_date BETWEEN ? AND ?
    GROUP BY a.member_id, m.member_name
    ORDER BY visit_count DESC
    LIMIT 10
");
$top_members->bind_param("ss", $start_date, $end_date);
$top_members->execute();
$top_members_result = $top_members->get_result();
$top_members->close();

// ✅ FIXED: Trainer Report using junction table
$trainer_stats = $conn->query("
    SELECT 
        t.trainer_name,
        t.specialization,
        COUNT(DISTINCT mwp.member_id) as assigned_members
    FROM TRAINER t
    LEFT JOIN WORKOUT_PLAN wp ON t.trainer_id = wp.trainer_id
    LEFT JOIN MEMBER_WORKOUT_PLAN mwp ON wp.plan_id = mwp.plan_id
    GROUP BY t.trainer_id, t.trainer_name, t.specialization
    ORDER BY assigned_members DESC
");

// Equipment Condition
$equipment_stats = $conn->query("
    SELECT 
        equipment_condition,
        COUNT(*) as count
    FROM EQUIPMENT
    GROUP BY equipment_condition
");

// Prepare chart data
$chart_data = [
    'paymentMethod' => null,
    'membership' => null,
    'equipment' => null
];

// Payment method chart data
if ($methods_result->num_rows > 0) {
    $method_labels = [];
    $method_data = [];
    $methods_result->data_seek(0);
    while($method = $methods_result->fetch_assoc()) {
        $method_labels[] = $method['payment_method'];
        $method_data[] = (float)$method['total'];
    }
    $chart_data['paymentMethod'] = [
        'labels' => $method_labels,
        'data' => $method_data
    ];
}

// Membership chart data
if ($membership_data->num_rows > 0) {
    $membership_labels = [];
    $membership_counts = [];
    $membership_data->data_seek(0);
    while($mem = $membership_data->fetch_assoc()) {
        $membership_labels[] = $mem['membership_type'];
        $membership_counts[] = (int)$mem['count'];
    }
    $chart_data['membership'] = [
        'labels' => $membership_labels,
        'data' => $membership_counts
    ];
}

// Equipment chart data
if ($equipment_stats->num_rows > 0) {
    $equip_labels = [];
    $equip_data = [];
    while($equip = $equipment_stats->fetch_assoc()) {
        $equip_labels[] = $equip['equipment_condition'];
        $equip_data[] = (int)$equip['count'];
    }
    $chart_data['equipment'] = [
        'labels' => $equip_labels,
        'data' => $equip_data
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin/reports.css">

</head>
<body>
<div class="d-flex">

    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <?php include '../partials/header.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-chart-line me-2"></i>Reports & Analytics</h1>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>

            <!-- Date Filter -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?= $start_date ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" 
                                   value="<?= $end_date ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type">
                                <option value="overview" <?= $report_type == 'overview' ? 'selected' : '' ?>>Overview</option>
                                <option value="revenue" <?= $report_type == 'revenue' ? 'selected' : '' ?>>Revenue</option>
                                <option value="members" <?= $report_type == 'members' ? 'selected' : '' ?>>Members</option>
                                <option value="attendance" <?= $report_type == 'attendance' ? 'selected' : '' ?>>Attendance</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-filter me-2"></i>Generate
                            </button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <small class="text-muted">Quick Filters: </small>
                        <a href="?start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
                        <a href="?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="btn btn-sm btn-outline-secondary">This Month</a>
                        <a href="?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-12-31') ?>" class="btn btn-sm btn-outline-secondary">This Year</a>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Report Period:</strong> <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>
            </div>

            <!-- Revenue Overview -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <div class="card-body text-white">
                            <h6 class="opacity-75">Total Revenue</h6>
                            <h2>$<?= number_format($revenue_data['total_revenue'] ?? 0, 2) ?></h2>
                            <small class="opacity-75"><?= $revenue_data['total_payments'] ?? 0 ?> transactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                        <div class="card-body text-white">
                            <h6 class="opacity-75">Paid Amount</h6>
                            <h2>$<?= number_format($revenue_data['paid_amount'] ?? 0, 2) ?></h2>
                            <small class="opacity-75">Completed payments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <div class="card-body text-white">
                            <h6 class="opacity-75">Pending Amount</h6>
                            <h2>$<?= number_format($revenue_data['pending_amount'] ?? 0, 2) ?></h2>
                            <small class="opacity-75">Awaiting payment</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        <div class="card-body text-white">
                            <h6 class="opacity-75">New Members</h6>
                            <h2><?= $member_data['new_members'] ?? 0 ?></h2>
                            <small class="opacity-75">Joined in period</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Revenue by Payment Method -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-credit-card me-2"></i>Revenue by Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentMethodChart" height="200"></canvas>
                            <?php if (!$chart_data['paymentMethod']): ?>
                                <div class="no-data-overlay text-muted text-center py-5">
                                    <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
                                    <p>No payment data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Membership Type Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-id-card me-2"></i>Membership Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="membershipChart" height="200"></canvas>
                            <?php if (!$chart_data['membership']): ?>
                                <div class="no-data-overlay text-muted text-center py-5">
                                    <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
                                    <p>No membership data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Attendance Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Daily Attendance</h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if ($attendance_data->num_rows > 0): ?>
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Date</th>
                                            <th>Check-ins</th>
                                            <th>Unique Members</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($att = $attendance_data->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($att['date'])) ?></td>
                                                <td><span class="badge bg-primary"><?= $att['total_checkins'] ?></span></td>
                                                <td><?= $att['unique_members'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted text-center">No attendance data</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Members -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-trophy me-2 text-warning"></i>Top Active Members</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($top_members_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Member Name</th>
                                                <th>Visits</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $rank = 1;
                                            while($member = $top_members_result->fetch_assoc()): 
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if($rank == 1): ?>
                                                            <i class="fas fa-crown text-warning"></i>
                                                        <?php elseif($rank == 2): ?>
                                                            <i class="fas fa-medal text-secondary"></i>
                                                        <?php elseif($rank == 3): ?>
                                                            <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                                        <?php else: ?>
                                                            <?= $rank ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($member['member_name']) ?></td>
                                                    <td><span class="badge bg-success"><?= $member['visit_count'] ?></span></td>
                                                </tr>
                                            <?php 
                                            $rank++;
                                            endwhile; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Trainer Performance -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-user-tie me-2"></i>Trainer Performance</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($trainer_stats->num_rows > 0): ?>
                                <table class="table table-hover table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Trainer</th>
                                            <th>Specialization</th>
                                            <th>Clients</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($trainer = $trainer_stats->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($trainer['trainer_name']) ?></td>
                                                <td><span class="badge bg-info"><?= $trainer['specialization'] ?></span></td>
                                                <td><?= $trainer['assigned_members'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted text-center">No trainer data</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Equipment Status -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-dumbbell me-2"></i>Equipment Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="equipmentChart" height="200"></canvas>
                            <?php if (!$chart_data['equipment']): ?>
                                <div class="no-data-overlay text-muted text-center py-5">
                                    <i class="fas fa-dumbbell fa-3x mb-3 opacity-25"></i>
                                    <p>No equipment data</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Member Demographics -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5><i class="fas fa-users me-2"></i>Member Demographics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h3 class="text-primary"><?= $member_data['new_members'] ?? 0 ?></h3>
                                    <p class="text-muted">New Members</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-info"><?= $member_data['male_count'] ?? 0 ?></h3>
                                    <p class="text-muted">Male</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-danger"><?= $member_data['female_count'] ?? 0 ?></h3>
                                    <p class="text-muted">Female</p>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-success"><?= round($member_data['avg_age'] ?? 0) ?></h3>
                                    <p class="text-muted">Avg Age</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Pass PHP data to JavaScript -->
<script>
// Chart data from PHP
const chartData = <?= json_encode($chart_data, JSON_PRETTY_PRINT) ?>;
console.log('=== CHART DATA DEBUG ===');
console.log('Full chart data:', chartData);
console.log('Payment Method:', chartData?.paymentMethod);
console.log('Membership:', chartData?.membership);
console.log('Equipment:', chartData?.equipment);

// Check if Chart.js is loaded
if (typeof Chart === 'undefined') {
    console.error('Chart.js is not loaded!');
} else {
    console.log('Chart.js version:', Chart.version);
}

// Check if we have any data at all
const hasData = (chartData?.paymentMethod && chartData.paymentMethod.labels?.length > 0) ||
                (chartData?.membership && chartData.membership.labels?.length > 0) ||
                (chartData?.equipment && chartData.equipment.labels?.length > 0);

if (!hasData) {
    console.warn('⚠️ No chart data available. This could be because:');
    console.warn('1. No data exists in the database for the selected date range');
    console.warn('2. Database queries are not returning results');
    console.warn('Current date range: <?= $start_date ?> to <?= $end_date ?>');
}
</script>

<!-- External Reports JavaScript -->
<script>
// Try to load the external JS file with fallback paths
(function() {
    const paths = [
        '../scripts/admin/reports.js',  // Original path
        '../js/reports.js',              // Alternative: js folder
        'reports.js',                    // Same directory
        './reports.js'                   // Explicit same directory
    ];
    
    let loaded = false;
    
    function tryLoadScript(index) {
        if (index >= paths.length) {
            console.error('Could not load reports.js from any path. Tried:', paths);
            console.log('Using inline initialization as fallback...');
            // Initialize inline as fallback
            setTimeout(initializeChartsInline, 100);
            return;
        }
        
        const script = document.createElement('script');
        script.src = paths[index];
        script.onload = function() {
            console.log('✓ Successfully loaded reports.js from:', paths[index]);
            loaded = true;
        };
        script.onerror = function() {
            console.warn('✗ Failed to load from:', paths[index]);
            tryLoadScript(index + 1);
        };
        document.head.appendChild(script);
    }
    
    // Inline fallback initialization function
    window.initializeChartsInline = function() {
        console.log('Initializing charts inline...');
        
        // Payment Method Chart
        if (chartData?.paymentMethod?.labels?.length > 0) {
            const ctx1 = document.getElementById('paymentMethodChart')?.getContext('2d');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.paymentMethod.labels,
                        datasets: [{
                            data: chartData.paymentMethod.data,
                            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#11998e', '#38ef7d', '#fa709a', '#fee140']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                console.log('✓ Payment chart created');
            }
        }
        
        // Membership Chart
        if (chartData?.membership?.labels?.length > 0) {
            const ctx2 = document.getElementById('membershipChart')?.getContext('2d');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'pie',
                    data: {
                        labels: chartData.membership.labels,
                        datasets: [{
                            data: chartData.membership.data,
                            backgroundColor: ['#36a2eb', '#ff6384', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
                console.log('✓ Membership chart created');
            }
        }
        
        // Equipment Chart
        if (chartData?.equipment?.labels?.length > 0) {
            const ctx3 = document.getElementById('equipmentChart')?.getContext('2d');
            if (ctx3) {
                new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: chartData.equipment.labels,
                        datasets: [{
                            label: 'Equipment Count',
                            data: chartData.equipment.data,
                            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
                console.log('✓ Equipment chart created');
            }
        }
    };
    
    tryLoadScript(0);
})();
</script>

</body>
</html>
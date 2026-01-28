<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $payment_id = $_POST['payment_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE PAYMENT SET payment_status = ? WHERE payment_id = ?");
    $stmt->bind_param("si", $new_status, $payment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Payment status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update payment status.";
    }
    header("Location: payments.php");
    exit();
}

// Get success/error messages
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get all payments with member and membership info
$payments_query = "SELECT p.*, m.member_name, mem.membership_type
                   FROM PAYMENT p
                   JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
                   JOIN MEMBER m ON mem.member_id = m.member_id";

if ($filter != 'all') {
    $payments_query .= " WHERE p.payment_status = '" . ucfirst($filter) . "'";
}

$payments_query .= " ORDER BY p.payment_date DESC";
$payments = $conn->query($payments_query);

// Get statistics
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM PAYMENT WHERE payment_status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$monthly_revenue = $conn->query("SELECT SUM(amount) as total FROM PAYMENT WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) AND payment_status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM PAYMENT WHERE payment_status = 'Pending'")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT COUNT(*) as count FROM PAYMENT")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin/payments.css">
    
</head>
<body>
    <div class="d-flex">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <?php include '../partials/header.php'; ?>
            
            <div class="container-fluid">
                <h1 class="mb-4 text-gradient">Payment Management</h1>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Revenue</h6>
                                        <h2 class="mb-0 text-success">$<?php echo number_format($total_revenue, 2); ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-dollar-sign fa-3x"></i>
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
                                        <h2 class="mb-0 text-primary">$<?php echo number_format($monthly_revenue, 2); ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-calendar fa-3x"></i>
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
                                        <h6 class="text-muted mb-2">Pending</h6>
                                        <h2 class="mb-0 text-warning"><?php echo $pending_payments; ?></h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-clock fa-3x"></i>
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
                                        <h6 class="text-muted mb-2">Total Payments</h6>
                                        <h2 class="mb-0 text-info"><?php echo $total_payments; ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-receipt fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Chart -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Revenue Overview</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $months_revenue = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $month = date('Y-m', strtotime("-$i months"));
                            $revenue_query = $conn->query("SELECT SUM(amount) as total FROM PAYMENT WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$month' AND payment_status = 'Paid'");
                            $revenue = $revenue_query->fetch_assoc()['total'] ?? 0;
                            $months_revenue[date('M Y', strtotime($month))] = $revenue;
                        }
                        ?>
                        <div class="row text-center">
                            <?php foreach ($months_revenue as $month => $revenue): ?>
                                <div class="col-md-2 mb-3">
                                    <div class="card border-0 bg-light month-card">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-3"><?php echo $month; ?></h6>
                                            <h4 class="text-success mb-0">$<?php echo number_format($revenue, 0); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Payments Table -->
                <div class="card shadow-custom">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Payments</h5>
                        <div class="btn-group filter-btn-group">
                            <a href="payments.php?filter=all" class="btn btn-sm btn-outline-primary <?php echo $filter == 'all' ? 'active' : ''; ?>">
                                <i class="fas fa-filter"></i> All
                            </a>
                            <a href="payments.php?filter=paid" class="btn btn-sm btn-outline-success <?php echo $filter == 'paid' ? 'active' : ''; ?>">
                                Paid
                            </a>
                            <a href="payments.php?filter=pending" class="btn btn-sm btn-outline-warning <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                                Pending
                            </a>
                            <a href="payments.php?filter=failed" class="btn btn-sm btn-outline-danger <?php echo $filter == 'failed' ? 'active' : ''; ?>">
                                Failed
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Member</th>
                                        <th>Membership Type</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($payments->num_rows > 0):
                                        while ($payment = $payments->fetch_assoc()): 
                                            $status_badge = '';
                                            switch ($payment['payment_status']) {
                                                case 'Paid': $status_badge = 'bg-success'; break;
                                                case 'Pending': $status_badge = 'bg-warning'; break;
                                                case 'Failed': $status_badge = 'bg-danger'; break;
                                            }
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($payment['member_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $payment['membership_type']; ?></span>
                                                </td>
                                                <td><strong class="text-success">$<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $method_icon = '';
                                                    $method_color = '';
                                                    switch ($payment['payment_method']) {
                                                        case 'Cash': 
                                                            $method_icon = 'fa-money-bill-wave'; 
                                                            $method_color = 'text-success';
                                                            break;
                                                        case 'Card': 
                                                            $method_icon = 'fa-credit-card'; 
                                                            $method_color = 'text-primary';
                                                            break;
                                                        case 'eSewa': 
                                                            $method_icon = 'fa-wallet'; 
                                                            $method_color = 'text-success';
                                                            break;
                                                        case 'Khalti': 
                                                            $method_icon = 'fa-mobile-alt'; 
                                                            $method_color = 'text-danger';
                                                            break;
                                                        case 'Online Banking': 
                                                            $method_icon = 'fa-university'; 
                                                            $method_color = 'text-info';
                                                            break;
                                                        default:
                                                            $method_icon = 'fa-question-circle';
                                                            $method_color = 'text-muted';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $method_icon; ?> <?php echo $method_color; ?> me-1"></i>
                                                    <?php echo $payment['payment_method']; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $status_badge; ?>">
                                                        <?php echo $payment['payment_status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewPaymentModal<?php echo $payment['payment_id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($payment['payment_status'] == 'Pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updatePaymentStatus(<?php echo $payment['payment_id']; ?>, 'Paid')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No payments found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Receipt Modals -->
    <?php
    $payments->data_seek(0);
    while ($payment = $payments->fetch_assoc()):
        $status_badge = '';
        switch ($payment['payment_status']) {
            case 'Paid': $status_badge = 'bg-success'; break;
            case 'Pending': $status_badge = 'bg-warning'; break;
            case 'Failed': $status_badge = 'bg-danger'; break;
        }
    ?>
    <div class="modal fade" id="viewPaymentModal<?php echo $payment['payment_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header no-print">
                    <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Payment Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body print-area">
                    <div class="receipt-header">
                        <i class="fas fa-dumbbell fa-3x mb-3"></i>
                        <h3>EnduraCore Gym</h3>
                        <p>Payment Receipt</p>
                        <small>123 Fitness Street, Gym City, GY 12345 | Phone: (555) 123-4567</small>
                    </div>
                    
                    <div class="px-3">
                        <table class="table table-borderless receipt-table">
                            <tr>
                                <th><i class="fas fa-hashtag me-2"></i>Receipt ID:</th>
                                <td><strong>#<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-user me-2"></i>Member Name:</th>
                                <td><strong><?php echo htmlspecialchars($payment['member_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-id-card me-2"></i>Membership Type:</th>
                                <td><span class="badge bg-info"><?php echo $payment['membership_type']; ?></span></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-calendar-alt me-2"></i>Payment Date:</th>
                                <td><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-credit-card me-2"></i>Payment Method:</th>
                                <td>
                                    <?php
                                    $method_icon = '';
                                    switch ($payment['payment_method']) {
                                        case 'Cash': $method_icon = 'fa-money-bill-wave'; break;
                                        case 'Card': $method_icon = 'fa-credit-card'; break;
                                        case 'eSewa': $method_icon = 'fa-wallet'; break;
                                        case 'Khalti': $method_icon = 'fa-mobile-alt'; break;
                                        case 'Online Banking': $method_icon = 'fa-university'; break;
                                        default: $method_icon = 'fa-question-circle';
                                    }
                                    ?>
                                    <i class="fas <?php echo $method_icon; ?> me-2"></i>
                                    <?php echo $payment['payment_method']; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-info-circle me-2"></i>Payment Status:</th>
                                <td>
                                    <span class="badge <?php echo $status_badge; ?>">
                                        <?php echo $payment['payment_status']; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="receipt-total">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">Total Amount Paid:</h4>
                                <h3 class="mb-0 text-success">$<?php echo number_format($payment['amount'], 2); ?></h3>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4 pt-4 border-top">
                            <p class="text-muted mb-0"><small>Thank you for your payment!</small></p>
                            <p class="text-muted"><small>For any queries, please contact our support team.</small></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer no-print">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printReceipt('viewPaymentModal<?php echo $payment['payment_id']; ?>')">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    
    <!-- Update Status Form -->
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="payment_id" id="statusPaymentId">
        <input type="hidden" name="new_status" id="statusNewStatus">
        <input type="hidden" name="update_status" value="1">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script/admin/payments.js"></script>

</body>
</html>
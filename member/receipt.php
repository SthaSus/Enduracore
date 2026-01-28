<?php
session_start();
require_once '../config/db_config.php';
check_login();

// Get payment ID from URL
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$print = isset($_GET['print']) ? true : false;

if (!$payment_id) {
    die('Invalid payment ID');
}

// Get payment details with member info
$payment_query = $conn->prepare("
    SELECT 
        p.payment_id,
        p.payment_date,
        p.amount,
        p.payment_method,
        p.payment_status,
        m.member_name,
        m.email,
        m.phone,
        mem.membership_type,
        mem.start_date,
        mem.end_date
    FROM PAYMENT p
    JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
    JOIN MEMBER m ON mem.member_id = m.member_id
    WHERE p.payment_id = ?
");
$payment_query->bind_param("i", $payment_id);
$payment_query->execute();
$payment = $payment_query->get_result()->fetch_assoc();
$payment_query->close();

if (!$payment) {
    die('Payment not found');
}

// Check access - Members can only see their own receipts
if ($_SESSION['role'] == 'MEMBER') {
    $account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];
    $member_query = $conn->prepare("SELECT member_id FROM MEMBER WHERE account_id = ?");
    $member_query->bind_param("i", $account_id);
    $member_query->execute();
    $member_data = $member_query->get_result()->fetch_assoc();
    
    // Verify this payment belongs to the logged-in member
    $verify_query = $conn->prepare("
        SELECT 1 FROM PAYMENT p
        JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
        WHERE p.payment_id = ? AND mem.member_id = ?
    ");
    $verify_query->bind_param("ii", $payment_id, $member_data['member_id']);
    $verify_query->execute();
    $verified = $verify_query->get_result()->num_rows > 0;
    
    if (!$verified) {
        die('Access denied');
    }
}

$status_badge = '';
switch ($payment['payment_status']) {
    case 'Paid': $status_badge = 'bg-success'; break;
    case 'Pending': $status_badge = 'bg-warning'; break;
    case 'Failed': $status_badge = 'bg-danger'; break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?> - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/member/receipt.css">

</head>
<body>
    <div class="receipt-container">
        <!-- Receipt Header -->
        <div class="receipt-header">
            <i class="fas fa-dumbbell gym-icon"></i>
            <h1>EnduraCore Gym</h1>
            <p class="mb-0">123 Fitness Street, Gym City, GY 12345</p>
            <p>Phone: (555) 123-4567 | Email: info@enduracore.com</p>
        </div>
        
        <!-- Receipt Body -->
        <div class="receipt-body">
            <div class="text-center mb-4">
                <h2 class="text-primary">Payment Receipt</h2>
                <p class="text-muted">Receipt #<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
            
            <!-- Customer Info -->
            <div class="receipt-info">
                <h5 class="mb-3"><i class="fas fa-user me-2"></i>Customer Information</h5>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-user-circle"></i>
                        Member Name
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['member_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-envelope"></i>
                        Email
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-phone"></i>
                        Phone
                    </span>
                    <span class="info-value"><?php echo htmlspecialchars($payment['phone']); ?></span>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div class="receipt-info">
                <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment Details</h5>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-calendar-check"></i>
                        Payment Date
                    </span>
                    <span class="info-value">
                        <?php echo $payment['payment_date'] ? date('F d, Y', strtotime($payment['payment_date'])) : 'N/A'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-money-check-alt"></i>
                        Payment Method
                    </span>
                    <span class="info-value">
                        <?php 
                        $method_icon = '';
                        switch ($payment['payment_method']) {
                            case 'Cash': $method_icon = 'fa-money-bill-wave'; break;
                            case 'Card': $method_icon = 'fa-credit-card'; break;
                            case 'UPI': $method_icon = 'fa-mobile-alt'; break;
                            case 'Online': $method_icon = 'fa-globe'; break;
                            default: $method_icon = 'fa-question-circle';
                        }
                        ?>
                        <i class="fas <?php echo $method_icon; ?> me-1"></i>
                        <?php echo $payment['payment_method'] ?? 'N/A'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-info-circle"></i>
                        Payment Status
                    </span>
                    <span class="info-value">
                        <span class="badge <?php echo $status_badge; ?>">
                            <?php echo $payment['payment_status']; ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <!-- Membership Details -->
            <div class="membership-details">
                <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>Membership Information</h5>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <strong>Type:</strong><br>
                        <span class="badge bg-primary"><?php echo $payment['membership_type']; ?></span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>Start Date:</strong><br>
                        <?php echo date('M d, Y', strtotime($payment['start_date'])); ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <strong>End Date:</strong><br>
                        <?php echo date('M d, Y', strtotime($payment['end_date'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Amount Section -->
            <div class="amount-section">
                <h3>Total Amount Paid</h3>
                <div class="amount">$<?php echo number_format($payment['amount'], 2); ?></div>
                <?php if ($payment['payment_status'] == 'Paid'): ?>
                    <p class="mb-0 mt-3">
                        <i class="fas fa-check-circle fa-2x"></i><br>
                        Payment Successful
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Footer Note -->
            <div class="footer-note">
                <p><strong>Thank you for your payment!</strong></p>
                <p class="mb-0">
                    <small>
                        This is a computer-generated receipt and does not require a signature.<br>
                        For any queries, please contact our support team at support@enduracore.com
                    </small>
                </p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary btn-action">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>

            <button onclick="window.close()" class="btn btn-secondary btn-action">
                <i class="fas fa-times me-2"></i>Close
            </button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print if print parameter is set
        <?php if ($print): ?>
        window.onload = function() {
            window.print();
        };
        <?php endif; ?>
    </script>
</body>
</html>
<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['MEMBER']);

// Get member_id from session
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];
$member_query = $conn->prepare("SELECT member_id, member_name FROM MEMBER WHERE account_id = ?");
$member_query->bind_param("i", $account_id);
$member_query->execute();
$member_data = $member_query->get_result()->fetch_assoc();
$member_id = $member_data['member_id'];
$member_name = $member_data['member_name'];
$member_query->close();

$success_message = '';
$error_message = '';

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $payment_id = $_POST['payment_id'];
    $payment_method = $_POST['payment_method'];
    $card_number = $_POST['card_number'] ?? '';
    $esewa_id = $_POST['esewa_id'] ?? '';
    $khalti_id = $_POST['khalti_id'] ?? '';
    
    // Simulate payment processing (1 second delay)
    sleep(1);
    
    // Update payment status
    $update_stmt = $conn->prepare("
        UPDATE PAYMENT 
        SET payment_status = 'Paid', 
            payment_method = ?, 
            payment_date = CURDATE() 
        WHERE payment_id = ?
    ");
    $update_stmt->bind_param("si", $payment_method, $payment_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Payment successful! Thank you for your payment.";
        $_SESSION['last_payment_id'] = $payment_id;
    } else {
        $_SESSION['error_message'] = "Payment failed. Please try again.";
    }
    $update_stmt->close();
    
    header("Location: payments.php");
    exit();
}

// Get success/error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$last_payment_id = isset($_SESSION['last_payment_id']) ? $_SESSION['last_payment_id'] : null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['last_payment_id']);

// Get all payments for this member
$payments_query = $conn->prepare("
    SELECT 
        p.payment_id, p.payment_date, p.amount, 
        p.payment_method, p.payment_status,
        mem.membership_type, mem.start_date, mem.end_date
    FROM PAYMENT p
    JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
    WHERE mem.member_id = ?
    ORDER BY p.payment_date DESC
");
$payments_query->bind_param("i", $member_id);
$payments_query->execute();
$payments = $payments_query->get_result();
$payments_query->close();

// Get pending payments
$pending_query = $conn->prepare("
    SELECT 
        p.payment_id, p.amount, p.payment_method,
        mem.membership_type, mem.end_date
    FROM PAYMENT p
    JOIN MEMBERSHIP mem ON p.membership_id = mem.membership_id
    WHERE mem.member_id = ? AND p.payment_status = 'Pending'
    ORDER BY mem.end_date ASC
");
$pending_query->bind_param("i", $member_id);
$pending_query->execute();
$pending_payments = $pending_query->get_result();
$pending_query->close();

// Calculate total paid and pending
$total_paid = 0;
$total_pending = 0;
$payments->data_seek(0);
while ($payment = $payments->fetch_assoc()) {
    if ($payment['payment_status'] == 'Paid') {
        $total_paid += $payment['amount'];
    } else {
        $total_pending += $payment['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .payment-card {
            border-left: 4px solid #28a745;
            transition: transform 0.2s;
        }
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .pending-card {
            border-left: 4px solid #ffc107;
            background: #fff9e6;
        }
        .payment-method-option {
            cursor: pointer;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .payment-method-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .payment-method-option.active {
            border-color: #667eea;
            background: #f0f3ff;
        }
        .processing-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .processing-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <!-- Processing Overlay -->
    <div class="processing-overlay" id="processingOverlay">
        <div class="processing-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4>Processing Payment...</h4>
            <p class="text-muted">Please wait, do not close this window</p>
        </div>
    </div>

    <div class="d-flex">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <?php include '../partials/header.php'; ?>
            
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-wallet me-2"></i>My Payments</h1>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <?php if ($last_payment_id): ?>
                    <div class="mt-2">
                        <a href="receipt.php?id=<?= $last_payment_id ?>" target="_blank" class="btn btn-sm btn-light me-2">
                            <i class="fas fa-eye me-1"></i>View Receipt
                        </a>
                        <a href="receipt.php?id=<?= $last_payment_id ?>&print=1" target="_blank" class="btn btn-sm btn-light">
                            <i class="fas fa-print me-1"></i>Print Receipt
                        </a>
                    </div>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Payment Summary -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                            <div class="card-body text-white">
                                <h6 class="opacity-75">Total Paid</h6>
                                <h2>$<?= number_format($total_paid, 2) ?></h2>
                                <small class="opacity-75">All time payments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <div class="card-body text-white">
                                <h6 class="opacity-75">Pending Amount</h6>
                                <h2>$<?= number_format($total_pending, 2) ?></h2>
                                <small class="opacity-75"><?= $pending_payments->num_rows ?> pending payment(s)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <div class="card-body text-white">
                                <h6 class="opacity-75">Total Transactions</h6>
                                <h2><?= $payments->num_rows ?></h2>
                                <small class="opacity-75">Payment history</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Payments -->
                <?php if ($pending_payments->num_rows > 0): ?>
                <div class="card shadow-sm border-warning mb-4">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Pending Payments (<?= $pending_payments->num_rows ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php $pending_payments->data_seek(0); while ($pending = $pending_payments->fetch_assoc()): ?>
                        <div class="card pending-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-2"><?= $pending['membership_type'] ?> Membership</h5>
                                        <p class="mb-1">
                                            <strong>Amount:</strong> $<?= number_format($pending['amount'], 2) ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-calendar"></i> Due: <?= date('M d, Y', strtotime($pending['end_date'])) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-success btn-lg" onclick="showPaymentModal(<?= $pending['payment_id'] ?>, <?= $pending['amount'] ?>, '<?= $pending['membership_type'] ?>')">
                                            <i class="fas fa-credit-card me-2"></i>Pay Now
                                        </button>
                                        <p class="text-muted small mt-2 mb-0">
                                            <i class="fas fa-info-circle"></i> Secure payment
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Payment History -->
                <div class="card shadow-custom">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($payments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Membership Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $payments->data_seek(0); while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : 'N/A' ?></td>
                                        <td><?= $payment['membership_type'] ?></td>
                                        <td><strong class="text-success">$<?= number_format($payment['amount'], 2) ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= $payment['payment_method'] ?? 'N/A' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = $payment['payment_status'] == 'Paid' ? 'success' : 
                                                          ($payment['payment_status'] == 'Pending' ? 'warning' : 'danger'); 
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= $payment['payment_status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['payment_status'] == 'Paid'): ?>
                                            <a href="receipt.php?id=<?= $payment['payment_id'] ?>" target="_blank" 
                                               class="btn btn-sm btn-outline-primary me-1" title="View Receipt">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="receipt.php?id=<?= $payment['payment_id'] ?>&print=1" target="_blank" 
                                               class="btn btn-sm btn-outline-success" title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No payment history found</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Complete Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="modal_payment_id">
                        
                        <div class="alert alert-info">
                            <strong>Amount to Pay:</strong> $<span id="modal_amount"></span><br>
                            <strong>Membership:</strong> <span id="modal_membership"></span>
                        </div>

                        <h6 class="mb-3">Select Payment Method</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="payment-method-option" onclick="selectMethod('Card')">
                                    <input type="radio" name="payment_method" value="Card" id="method_card" required>
                                    <label for="method_card" class="d-block text-center">
                                        <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                        <p class="mb-0"><strong>Card</strong></p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="payment-method-option" onclick="selectMethod('eSewa')">
                                    <input type="radio" name="payment_method" value="eSewa" id="method_esewa" required>
                                    <label for="method_esewa" class="d-block text-center">
                                        <i class="fas fa-wallet fa-2x text-success mb-2"></i>
                                        <p class="mb-0"><strong>eSewa</strong></p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="payment-method-option" onclick="selectMethod('Khalti')">
                                    <input type="radio" name="payment_method" value="Khalti" id="method_khalti" required>
                                    <label for="method_khalti" class="d-block text-center">
                                        <i class="fas fa-mobile-alt fa-2x text-danger mb-2"></i>
                                        <p class="mb-0"><strong>Khalti</strong></p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="payment-method-option" onclick="selectMethod('Online Banking')">
                                    <input type="radio" name="payment_method" value="Online Banking" id="method_online" required>
                                    <label for="method_online" class="d-block text-center">
                                        <i class="fas fa-university fa-2x text-info mb-2"></i>
                                        <p class="mb-0"><strong>Online Banking</strong></p>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Card Details -->
                        <div id="cardDetails" style="display: none;">
                            <h6 class="mb-3">Card Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" name="card_number" 
                                       placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" placeholder="123" maxlength="3">
                                </div>
                            </div>
                        </div>

                        <!-- eSewa Details -->
                        <div id="esewaDetails" style="display: none;">
                            <h6 class="mb-3">eSewa Details</h6>
                            <div class="mb-3">
                                <label class="form-label">eSewa ID / Mobile Number</label>
                                <input type="text" class="form-control" name="esewa_id" 
                                       placeholder="98XXXXXXXX or your eSewa ID">
                            </div>
                        </div>

                        <!-- Khalti Details -->
                        <div id="khaltiDetails" style="display: none;">
                            <h6 class="mb-3">Khalti Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Khalti Mobile Number</label>
                                <input type="text" class="form-control" name="khalti_id" 
                                       placeholder="98XXXXXXXX">
                            </div>
                        </div>

                        <!-- Online Banking -->
                        <div id="onlineDetails" style="display: none;">
                            <h6 class="mb-3">Select Your Bank</h6>
                            <select class="form-select">
                                <option>Select Bank</option>
                                <option>Nepal Bank Limited</option>
                                <option>Rastriya Banijya Bank</option>
                                <option>NIC Asia Bank</option>
                                <option>Global IME Bank</option>
                                <option>Nabil Bank</option>
                                <option>Himalayan Bank</option>
                                <option>Everest Bank</option>
                                <option>Kumari Bank</option>
                            </select>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Demo Mode:</strong> This is a simulated payment. No real transaction will occur.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_payment" class="btn btn-success btn-lg">
                            <i class="fas fa-lock me-2"></i>Pay Securely
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script/member/payments.js"></script>
</body>
</html>
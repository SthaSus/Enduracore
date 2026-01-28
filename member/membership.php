<?php
session_start();
require_once '../config/db_config.php';

check_login();
check_role(['MEMBER']);

/* =========================
   FETCH MEMBER DETAILS
========================= */
$account_id = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'];

$member_query = $conn->prepare("
    SELECT member_id, member_name 
    FROM MEMBER 
    WHERE account_id = ?
");
$member_query->bind_param("i", $account_id);
$member_query->execute();
$member_data = $member_query->get_result()->fetch_assoc();
$member_query->close();

$member_id   = $member_data['member_id'];
$member_name = $member_data['member_name'];

/* =========================
   AUTO ACTIVATE PENDING MEMBERSHIPS (Do this FIRST)
========================= */
$conn->query("
    UPDATE MEMBERSHIP 
    SET status = 'Active'
    WHERE status = 'Pending'
    AND start_date <= CURDATE()
");

/* =========================
   AUTO EXPIRE MEMBERSHIPS (Do this AFTER activation)
========================= */
$conn->query("
    UPDATE MEMBERSHIP 
    SET status = 'Expired' 
    WHERE end_date < CURDATE() AND status = 'Active'
");

/* =========================
   FLASH MESSAGES
========================= */
$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

/* =========================
   MEMBERSHIP PRICES
========================= */
$fees = [
    'Monthly'     => 49.00,
    'Quarterly'   => 129.00,
    'Half-Yearly' => 239.00,
    'Yearly'      => 399.00
];

$duration_map = [
    'Monthly'     => '+1 month',
    'Quarterly'   => '+3 months',
    'Half-Yearly' => '+6 months',
    'Yearly'      => '+1 year'
];

/* =========================
   PURCHASE/RENEW MEMBERSHIP
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {

    $membership_type = $_POST['membership_type'];
    $payment_method  = $_POST['payment_method'];
    $is_renewal      = isset($_POST['is_renewal']) && $_POST['is_renewal'] == '1';
    $old_membership_id = $_POST['old_membership_id'] ?? null;

    $fee = $fees[$membership_type];
    
    // Determine start date
    if ($is_renewal && $old_membership_id) {
        // Get old membership end date
        $old_query = $conn->prepare("SELECT end_date FROM MEMBERSHIP WHERE membership_id = ?");
        $old_query->bind_param("i", $old_membership_id);
        $old_query->execute();
        $old_data = $old_query->get_result()->fetch_assoc();
        $old_query->close();
        
        $start_date = date('Y-m-d', strtotime($old_data['end_date'] . ' +1 day'));
    } else {
        // Check if there's an active membership
        $check = $conn->prepare("SELECT end_date FROM MEMBERSHIP WHERE member_id = ? AND status = 'Active'");
        $check->bind_param("i", $member_id);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows > 0) {
            $active_data = $check_result->fetch_assoc();
            $start_date = date('Y-m-d', strtotime($active_data['end_date'] . ' +1 day'));
        } else {
            $start_date = date('Y-m-d'); // Start today if no active membership
        }
        $check->close();
    }
    
    $end_date = date('Y-m-d', strtotime($start_date . ' ' . $duration_map[$membership_type]));

    // FIX: Determine status based on start_date
    $today = date('Y-m-d');
    if ($start_date <= $today) {
        $status = 'Active';  // Starts today or in the past
    } else {
        $status = 'Pending'; // Starts in the future
    }

    $insert = $conn->prepare("
        INSERT INTO MEMBERSHIP 
        (member_id, membership_type, start_date, end_date, fee, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "isssds",
        $member_id,
        $membership_type,
        $start_date,
        $end_date,
        $fee,
        $status
    );

    if ($insert->execute()) {
        $membership_id = $conn->insert_id;
        $insert->close();

        // Create payment record
        $pay = $conn->prepare("
            INSERT INTO PAYMENT 
            (membership_id, payment_date, amount, payment_method, payment_status)
            VALUES (?, CURDATE(), ?, ?, 'Paid')
        ");
        $pay->bind_param("ids", $membership_id, $fee, $payment_method);
        $pay->execute();
        $pay->close();

        // FIX: Don't expire old membership immediately if renewal is pending
        // The auto-expire logic will handle it when the date comes
        // (No need to manually expire here)

        $_SESSION['success_message'] = "Membership " . ($is_renewal ? "renewed" : "purchased") . " successfully! Your membership is " . ($status == 'Pending' ? 'pending and will be' : '') . " active from " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date));
        header("Location: membership.php");
        exit;
    }

    $_SESSION['error_message'] = "Failed to process membership.";
    header("Location: membership.php");
    exit;
}

/* =========================
   ACTIVE MEMBERSHIP
========================= */
$active_q = $conn->prepare("
    SELECT * FROM MEMBERSHIP 
    WHERE member_id = ? AND status = 'Active'
    ORDER BY end_date DESC
    LIMIT 1
");
$active_q->bind_param("i", $member_id);
$active_q->execute();
$active_membership = $active_q->get_result()->fetch_assoc();
$active_q->close();

/* =========================
   MEMBERSHIP HISTORY
========================= */
$history = $conn->prepare("
    SELECT * FROM MEMBERSHIP 
    WHERE member_id = ?
    ORDER BY start_date DESC
");
$history->bind_param("i", $member_id);
$history->execute();
$membership_history = $history->get_result();
$history->close();

/* =========================
   DAYS REMAINING & ELIGIBILITY
========================= */
$days_remaining    = 0;
$is_expiring_soon  = false;
$can_purchase      = false;
$has_pending       = false;

// Check if member already has a pending membership
$pending_q = $conn->prepare("
    SELECT membership_id 
    FROM MEMBERSHIP 
    WHERE member_id = ? AND status = 'Pending'
    LIMIT 1
");
$pending_q->bind_param("i", $member_id);
$pending_q->execute();
$pending_q->store_result();
$has_pending = $pending_q->num_rows > 0;
$pending_q->close();

if (!$active_membership) {
    // No active membership → can purchase immediately
    $can_purchase = true;
} else {
    $today = new DateTime();
    $end   = new DateTime($active_membership['end_date']);
    $days_remaining = (int)$today->diff($end)->format('%r%a');

    if ($days_remaining <= 30 && !$has_pending) {
        // Within last 30 days AND no pending membership yet
        $can_purchase = true;
        $is_expiring_soon = ($days_remaining <= 7);
    } else {
        // Either:
        // - More than 30 days remaining
        // - OR already purchased a pending membership
        $can_purchase = false;
    }
}

// Membership plans
$plans = [
    'Monthly' => ['price' => 49.00, 'duration' => '1 Month', 'discount' => ''],
    'Quarterly' => ['price' => 129.00, 'duration' => '3 Months', 'discount' => 'Save 10%'],
    'Half-Yearly' => ['price' => 239.00, 'duration' => '6 Months', 'discount' => 'Save 15%'],
    'Yearly' => ['price' => 399.00, 'duration' => '12 Months', 'discount' => 'Save 20%']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Membership - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/member/membership.css">

</head>
<body>
<div class="d-flex">
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <?php include '../partials/header.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-id-card me-2"></i>My Membership</h1>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current Membership -->
            <?php if ($active_membership): ?>
                <div class="card membership-card shadow-lg mb-4">
                    <div class="card-body p-4 position-relative">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-3">
                                    <i class="fas fa-crown me-2"></i>
                                    <?= $active_membership['membership_type'] ?> Membership
                                </h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1 opacity-75"><i class="fas fa-calendar-alt me-2"></i>Start Date</p>
                                        <h5><?= date('M d, Y', strtotime($active_membership['start_date'])) ?></h5>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1 opacity-75"><i class="fas fa-calendar-check me-2"></i>End Date</p>
                                        <h5><?= date('M d, Y', strtotime($active_membership['end_date'])) ?></h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3 mt-3">
                                    <span class="badge bg-white text-primary status-badge">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                    <?php if ($days_remaining > 0): ?>
                                        <span class="badge bg-light text-dark status-badge">
                                            <?= $days_remaining ?> days remaining
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger status-badge">
                                            Expired <?= abs($days_remaining) ?> days ago
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <p class="mb-1 opacity-75">Membership Fee</p>
                                    <h2 class="mb-0">$<?= number_format($active_membership['fee'], 2) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($is_expiring_soon): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Your membership is expiring soon!</strong> Renew now to continue enjoying our services.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No active membership!</strong> Purchase a membership plan to get started.
                </div>
            <?php endif; ?>

            <!-- Show pending membership notification -->
            <?php if ($has_pending): ?>
                <div class="alert alert-info">
                    <i class="fas fa-clock me-2"></i>
                    <strong>You have a pending membership!</strong> It will automatically activate when your current membership expires.
                </div>
            <?php endif; ?>

            <!-- Available Plans (Show only if eligible) -->
            <?php if ($can_purchase): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Available Membership Plans</h5>
                        <?php if ($active_membership && $days_remaining > 0): ?>
                            <small class="text-muted">Your new membership will start from <?= date('M d, Y', strtotime($active_membership['end_date'] . ' +1 day')) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach ($plans as $type => $details): ?>
                                <div class="col-md-3">
                                    <div class="plan-card p-4 h-100">
                                        <?php if ($details['discount']): ?>
                                            <span class="discount-badge"><?= $details['discount'] ?></span>
                                        <?php endif; ?>
                                        <div class="text-center">
                                            <h4 class="mb-3"><?= $type ?></h4>
                                            <div class="mb-3">
                                                <h2 class="text-primary mb-0">$<?= number_format($details['price'], 0) ?></h2>
                                                <small class="text-muted">per <?= strtolower($details['duration']) ?></small>
                                            </div>
                                            <p class="text-muted mb-3">
                                                <i class="fas fa-clock me-2"></i><?= $details['duration'] ?>
                                            </p>
                                            <button class="btn btn-primary w-100" type="button" 
                                                    onclick="selectPlan('<?= $type ?>', <?= $details['price'] ?>, '<?= $active_membership ? $active_membership['membership_id'] : 0 ?>', '<?= $active_membership ? 1 : 0 ?>')">
                                                <i class="fas fa-shopping-cart me-2"></i>Select Plan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary">
                    <i class="fas fa-lock me-2"></i>
                    <strong>Membership renewal not available yet.</strong> 
                    <?php if ($has_pending): ?>
                        You already have a pending membership that will activate after your current one expires.
                    <?php else: ?>
                        You can purchase or renew when you have 30 days or less remaining on your current membership.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Membership History -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Membership History</h5>
                </div>
                <div class="card-body">
                    <?php if ($membership_history->num_rows > 0): ?>
                        <?php while ($history_item = $membership_history->fetch_assoc()): ?>
                            <div class="timeline-item <?= strtolower($history_item['status']) ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= $history_item['membership_type'] ?> Membership</h6>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-calendar me-2"></i>
                                            <?= date('M d, Y', strtotime($history_item['start_date'])) ?> 
                                            - 
                                            <?= date('M d, Y', strtotime($history_item['end_date'])) ?>
                                        </p>
                                        <span class="badge bg-<?= $history_item['status'] == 'Active' ? 'success' : ($history_item['status'] == 'Pending' ? 'warning' : 'secondary') ?>">
                                            <?= $history_item['status'] ?>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="text-primary mb-0">$<?= number_format($history_item['fee'], 2) ?></h5>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No membership history</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Payment Method Selection Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Choose Payment Method</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="paymentForm">
                <input type="hidden" name="membership_type" id="selected_type">
                <input type="hidden" name="is_renewal" id="is_renewal">
                <input type="hidden" name="old_membership_id" id="old_membership_id">
                <input type="hidden" name="payment_method" id="selected_payment_method">
                
                <div class="modal-body">
                    <!-- Purchase Summary -->
                    <div class="alert alert-info mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1" id="summary_plan_name"></h5>
                                <p class="mb-0 text-muted" id="summary_dates"></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <h3 class="text-primary mb-0">$<span id="summary_price"></span></h3>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">Select Payment Gateway</h6>

                    <!-- Payment Options -->
                    <div class="payment-option" onclick="selectPayment('eSewa', this)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-success" style="font-size: 2rem;">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">eSewa</h6>
                                    <small class="text-muted">Digital wallet payment</small>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>

                    <div class="payment-option" onclick="selectPayment('Khalti', this)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-purple" style="font-size: 2rem; color: #5C2D91;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Khalti</h6>
                                    <small class="text-muted">Mobile wallet payment</small>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>

                    <div class="payment-option" onclick="selectPayment('Card', this)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-primary" style="font-size: 2rem;">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Credit/Debit Card</h6>
                                    <small class="text-muted">Visa, Mastercard, etc.</small>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>

                    <div class="payment-option" onclick="selectPayment('Online Banking', this)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-info" style="font-size: 2rem;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Online Banking</h6>
                                    <small class="text-muted">Net banking transfer</small>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>

                    <div class="payment-option" onclick="selectPayment('Cash', this)">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-success" style="font-size: 2rem;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Cash Payment</h6>
                                    <small class="text-muted">Pay at gym counter</small>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; display: none;"></i>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Demo Mode:</strong> This is a simulated payment for demonstration purposes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" name="confirm_purchase" class="btn btn-success" id="confirmPaymentBtn" disabled>
                        <i class="fas fa-check me-2"></i>Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../script/member/membership.js"></script>
</body>
</html>
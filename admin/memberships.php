<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

$success = '';
$error = '';

// Handle Add Membership
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_membership'])) {
    $member_id = intval($_POST['member_id']);
    $membership_type = $_POST['membership_type'];
    $start_date = $_POST['start_date'];
    $fee = floatval($_POST['fee']);
    
    // Calculate end date based on type
    $end_date = date('Y-m-d', strtotime($start_date . ' + ' . match($membership_type) {
        'Monthly' => '1 month',
        'Quarterly' => '3 months',
        'Half-Yearly' => '6 months',
        'Yearly' => '1 year',
        default => '1 month'
    }));
    
    $conn->begin_transaction();
    try {
        // Insert membership
        $stmt = $conn->prepare("INSERT INTO MEMBERSHIP (member_id, membership_type, start_date, end_date, fee, status) VALUES (?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("isssd", $member_id, $membership_type, $start_date, $end_date, $fee);
        $stmt->execute();
        $membership_id = $conn->insert_id;
        
        // Create payment record
        $payment_stmt = $conn->prepare("INSERT INTO PAYMENT (membership_id, payment_date, amount, payment_method, payment_status) VALUES (?, ?, ?, 'Cash', 'Pending')");
        $payment_stmt->bind_param("isd", $membership_id, $start_date, $fee);
        $payment_stmt->execute();
        
        $conn->commit();
        $success = "Membership added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to add membership: " . $e->getMessage();
    }
}

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $membership_id = intval($_POST['membership_id']);
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE MEMBERSHIP SET status = ? WHERE membership_id = ?");
    $stmt->bind_param("si", $new_status, $membership_id);
    
    if ($stmt->execute()) {
        $success = "Membership status updated successfully";
    } else {
        $error = "Failed to update status";
    }
}

// Get all memberships
$memberships_query = "SELECT mem.*, m.member_name, m.email, m.phone
                      FROM MEMBERSHIP mem
                      JOIN MEMBER m ON mem.member_id = m.member_id
                      ORDER BY mem.start_date DESC";
$memberships = $conn->query($memberships_query);

// Get statistics
$total_memberships = $conn->query("SELECT COUNT(*) as count FROM MEMBERSHIP")->fetch_assoc()['count'];
$active_memberships = $conn->query("SELECT COUNT(*) as count FROM MEMBERSHIP WHERE status = 'Active'")->fetch_assoc()['count'];
$pending_memberships = $conn->query("SELECT COUNT(*) as count FROM MEMBERSHIP WHERE status = 'Pending'")->fetch_assoc()['count'];
$expiring_soon = $conn->query("SELECT COUNT(*) as count FROM MEMBERSHIP WHERE status = 'Active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$monthly_revenue = $conn->query("SELECT SUM(fee) as total FROM MEMBERSHIP WHERE MONTH(start_date) = MONTH(CURDATE()) AND YEAR(start_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Get all members for dropdown
$members_list = $conn->query("SELECT member_id, member_name FROM MEMBER ORDER BY member_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memberships - EnduraCore</title>
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
                    <h1 class="text-gradient">Membership Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMembershipModal">
                        <i class="fas fa-plus"></i> Add Membership
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
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Memberships</h6>
                                        <h2 class="mb-0"><?php echo $total_memberships; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-id-card fa-3x"></i>
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
                                        <h6 class="text-muted mb-2">Active</h6>
                                        <h2 class="mb-0 text-success"><?php echo $active_memberships; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-check-circle fa-3x"></i>
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
                                        <h2 class="mb-0 text-info"><?php echo $pending_memberships; ?></h2>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-hourglass-half fa-3x"></i>
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
                                        <h6 class="text-muted mb-2">Expiring Soon</h6>
                                        <h2 class="mb-0 text-warning"><?php echo $expiring_soon; ?></h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Memberships Table -->
                <div class="card shadow-custom">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Member</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Duration</th>
                                        <th>Fee</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($membership = $memberships->fetch_assoc()): 
                                        $days_remaining = ceil((strtotime($membership['end_date']) - time()) / 86400);
                                        $status_badge = match($membership['status']) {
                                            'Active' => 'bg-success',
                                            'Pending' => 'bg-info',
                                            'Expired' => 'bg-secondary',
                                            'Cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $membership['membership_id']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($membership['member_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($membership['email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $membership['membership_type']; ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($membership['start_date'])); ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($membership['end_date'])); ?>
                                                <?php if ($membership['status'] == 'Active' && $days_remaining <= 7 && $days_remaining > 0): ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $days_remaining; ?> days left</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $duration_days = ceil((strtotime($membership['end_date']) - strtotime($membership['start_date'])) / 86400);
                                                echo $duration_days . ' days';
                                                ?>
                                            </td>
                                            <td><strong class="text-success">$<?php echo number_format($membership['fee'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo $status_badge; ?>">
                                                    <?php echo $membership['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $membership['membership_id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $membership['membership_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Membership Modal -->
    <div class="modal fade" id="addMembershipModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Membership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Member *</label>
                            <select class="form-select" name="member_id" required>
                                <option value="">Choose member...</option>
                                <?php while ($member = $members_list->fetch_assoc()): ?>
                                    <option value="<?php echo $member['member_id']; ?>">
                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Membership Type *</label>
                            <select class="form-select" name="membership_type" id="membershipType" required onchange="updateFee()">
                                <option value="">Choose type...</option>
                                <option value="Monthly" data-fee="49">Monthly - $49</option>
                                <option value="Quarterly" data-fee="129">Quarterly - $129</option>
                                <option value="Half-Yearly" data-fee="239">Half-Yearly - $239</option>
                                <option value="Yearly" data-fee="449">Yearly - $449</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fee *</label>
                            <input type="number" step="0.01" class="form-control" name="fee" id="membershipFee" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> End date will be calculated automatically based on membership type.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_membership" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Membership
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ================= MODALS (OUTSIDE TABLE) ================= -->
    <?php
    $memberships->data_seek(0);
    while ($membership = $memberships->fetch_assoc()):
        $status_badge = match($membership['status']) {
            'Active' => 'bg-success',
            'Pending' => 'bg-info',
            'Expired' => 'bg-secondary',
            'Cancelled' => 'bg-danger',
            default => 'bg-secondary'
        };
    ?>
    <!-- View Modal -->
    <div class="modal fade" id="viewModal<?php echo $membership['membership_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Membership Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr><th>Member:</th><td><?php echo htmlspecialchars($membership['member_name']); ?></td></tr>
                        <tr><th>Email:</th><td><?php echo htmlspecialchars($membership['email']); ?></td></tr>
                        <tr><th>Phone:</th><td><?php echo htmlspecialchars($membership['phone']); ?></td></tr>
                        <tr><th>Type:</th><td><?php echo $membership['membership_type']; ?></td></tr>
                        <tr><th>Start Date:</th><td><?php echo date('M d, Y', strtotime($membership['start_date'])); ?></td></tr>
                        <tr><th>End Date:</th><td><?php echo date('M d, Y', strtotime($membership['end_date'])); ?></td></tr>
                        <tr><th>Fee:</th><td>$<?php echo number_format($membership['fee'], 2); ?></td></tr>
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge <?php echo $status_badge; ?>"><?php echo $membership['status']; ?></span></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal<?php echo $membership['membership_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="membership_id" value="<?php echo $membership['membership_id']; ?>">
                    <div class="modal-body">
                        <p><strong><?php echo htmlspecialchars($membership['member_name']); ?></strong> - <?php echo $membership['membership_type']; ?></p>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" name="new_status" required>
                                <option value="Active" <?php echo ($membership['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Pending" <?php echo ($membership['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Expired" <?php echo ($membership['status'] == 'Expired') ? 'selected' : ''; ?>>Expired</option>
                                <option value="Cancelled" <?php echo ($membership['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <!-- =========================================================== -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script>
    function updateFee() {
        const select = document.getElementById('membershipType');
        const feeInput = document.getElementById('membershipFee');
        const selectedOption = select.options[select.selectedIndex];
        const fee = selectedOption.getAttribute('data-fee');
        if (fee) {
            feeInput.value = fee;
        }
    }
    </script> -->
    <script src="../script/admin/membership.js"></script>
</body>
</html>
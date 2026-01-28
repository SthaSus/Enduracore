<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

$success_msg = '';
$error_msg = '';

// Handle Delete Member
if (isset($_GET['delete'])) {
    $member_id = intval($_GET['delete']);

    $acc_query = $conn->prepare("SELECT account_id FROM MEMBER WHERE member_id = ?");
    $acc_query->bind_param("i", $member_id);
    $acc_query->execute();
    $result = $acc_query->get_result();
    $account = $result->fetch_assoc();
    $account_id = $account['account_id'] ?? null;

    if ($account_id) {
        $delete_stmt = $conn->prepare("DELETE FROM ACCOUNT WHERE account_id = ?");
        $delete_stmt->bind_param("i", $account_id);
        if ($delete_stmt->execute()) {
            $success_msg = "Member deleted successfully";
        } else {
            $error_msg = "Failed to delete member";
        }
    } else {
        $error_msg = "Member not found";
    }
}

// Handle Edit Member POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member'])) {
    $member_id = intval($_POST['member_id']);
    $member_name = trim($_POST['member_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $gender = $_POST['gender'];
    $join_date = $_POST['join_date'];

    $conn->begin_transaction();
    try {
        // Update MEMBER table
        $stmt = $conn->prepare("
            UPDATE MEMBER
            SET member_name=?, email=?, phone=?, age=?, gender=?, join_date=?
            WHERE member_id=?
        ");
        $stmt->bind_param("sssissi", $member_name, $email, $phone, $age, $gender, $join_date, $member_id);
        $stmt->execute();

        // Update username in ACCOUNT table
        $acc_stmt = $conn->prepare("
            UPDATE ACCOUNT a
            JOIN MEMBER m ON a.account_id = m.account_id
            SET a.username=?
            WHERE m.member_id=?
        ");
        $acc_stmt->bind_param("si", $username, $member_id);
        $acc_stmt->execute();

        $conn->commit();
        $success_msg = "Member updated successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Failed to update member: " . $e->getMessage();
    }
}

// Fetch members
$members_query = "
    SELECT m.*, a.username, a.last_login,
    (SELECT COUNT(*) FROM MEMBERSHIP 
     WHERE member_id = m.member_id AND status = 'Active') AS active_membership
    FROM MEMBER m
    JOIN ACCOUNT a ON m.account_id = a.account_id
    ORDER BY m.join_date DESC
";
$members_result = $conn->query($members_query);

// Fetch all members into array to avoid multiple data_seek calls
$members_list = [];
while ($row = $members_result->fetch_assoc()) {
    $members_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Members Management - EnduraCore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="d-flex">
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content flex-grow-1">
        <?php include '../partials/header.php'; ?>

        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Members Management</h1>

            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Join Date</th>
                                    <th>Membership</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($members_list as $member): ?>
                                <tr>
                                    <td><?= $member['member_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($member['member_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($member['username']) ?></td>
                                    <td><?= htmlspecialchars($member['email']) ?></td>
                                    <td><?= htmlspecialchars($member['phone']) ?></td>
                                    <td><?= $member['age'] ?? 'N/A' ?></td>
                                    <td><?= $member['gender'] ?></td>
                                    <td><?= date('d M Y', strtotime($member['join_date'])) ?></td>
                                    <td>
                                        <?= $member['active_membership'] > 0
                                            ? '<span class="badge bg-success">Active</span>'
                                            : '<span class="badge bg-danger">Inactive</span>'; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewMemberModal<?= $member['member_id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editMemberModal<?= $member['member_id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <a href="?delete=<?= $member['member_id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this member?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= VIEW MODALS ================= -->
            <?php foreach ($members_list as $member): ?>
            <div class="modal fade" id="viewMemberModal<?= $member['member_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Member Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-borderless">
                                <tr><th>Name:</th><td><?= htmlspecialchars($member['member_name']) ?></td></tr>
                                <tr><th>Username:</th><td><?= htmlspecialchars($member['username']) ?></td></tr>
                                <tr><th>Email:</th><td><?= htmlspecialchars($member['email']) ?></td></tr>
                                <tr><th>Phone:</th><td><?= htmlspecialchars($member['phone']) ?></td></tr>
                                <tr><th>Age:</th><td><?= $member['age'] ?? 'N/A' ?></td></tr>
                                <tr><th>Gender:</th><td><?= $member['gender'] ?></td></tr>
                                <tr><th>Join Date:</th><td><?= date('d M Y', strtotime($member['join_date'])) ?></td></tr>
                                <tr>
                                    <th>Last Login:</th>
                                    <td><?= $member['last_login'] ? date('d M Y H:i', strtotime($member['last_login'])) : 'Never'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- ================= EDIT MODALS ================= -->
            <?php foreach ($members_list as $member): ?>
            <div class="modal fade" id="editMemberModal<?= $member['member_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Member</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="member_name"
                                               value="<?= htmlspecialchars($member['member_name']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username"
                                               value="<?= htmlspecialchars($member['username']) ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email"
                                               value="<?= htmlspecialchars($member['email']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" name="phone"
                                               value="<?= htmlspecialchars($member['phone']) ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Age</label>
                                        <input type="number" class="form-control" name="age"
                                               value="<?= $member['age'] ?? '' ?>" min="0">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <?php foreach (['Male','Female','Other'] as $g): ?>
                                                <option value="<?= $g ?>" <?= $member['gender'] === $g ? 'selected' : '' ?>>
                                                    <?= $g ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Join Date</label>
                                        <input type="date" class="form-control" name="join_date"
                                               value="<?= date('Y-m-d', strtotime($member['join_date'])) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="edit_member" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Update Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

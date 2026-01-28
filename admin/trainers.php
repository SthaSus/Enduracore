<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

$success = '';
$error = '';

// Handle Add Trainer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trainer'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $trainer_name = sanitize_input($_POST['trainer_name']);
    $specialization = $_POST['specialization'];
    $experience_years = intval($_POST['experience_years']);
    $phone = sanitize_input($_POST['phone']);
    
    // Check if username exists
    $check = $conn->prepare("SELECT account_id FROM ACCOUNT WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = "Username already exists";
    } else {
        $conn->begin_transaction();
        try {
            // Create account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $acc_stmt = $conn->prepare("INSERT INTO ACCOUNT (username, password, role, created_on) VALUES (?, ?, 'TRAINER', NOW())");
            $acc_stmt->bind_param("ss", $username, $hashed_password);
            $acc_stmt->execute();
            $account_id = $conn->insert_id;
            
            // Create trainer profile
            $trainer_stmt = $conn->prepare("INSERT INTO TRAINER (account_id, trainer_name, specialization, experience_years, phone) VALUES (?, ?, ?, ?, ?)");
            $trainer_stmt->bind_param("issis", $account_id, $trainer_name, $specialization, $experience_years, $phone);
            $trainer_stmt->execute();
            
            $conn->commit();
            $success = "Trainer added successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to add trainer: " . $e->getMessage();
        }
    }
}

// Handle Edit Trainer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_trainer'])) {
    $trainer_id = intval($_POST['trainer_id']);
    $trainer_name = sanitize_input($_POST['trainer_name']);
    $specialization = $_POST['specialization'];
    $experience_years = intval($_POST['experience_years']);
    $phone = sanitize_input($_POST['phone']);
    $username = sanitize_input($_POST['username']);

    $conn->begin_transaction();
    try {
        // Update trainer table
        $stmt = $conn->prepare(
            "UPDATE TRAINER 
             SET trainer_name=?, specialization=?, experience_years=?, phone=? 
             WHERE trainer_id=?"
        );
        $stmt->bind_param("ssisi", $trainer_name, $specialization, $experience_years, $phone, $trainer_id);
        $stmt->execute();

        // Update username if account exists
        if (!empty($username)) {
            $acc = $conn->prepare("SELECT account_id FROM TRAINER WHERE trainer_id=?");
            $acc->bind_param("i", $trainer_id);
            $acc->execute();
            $account_id = $acc->get_result()->fetch_assoc()['account_id'] ?? null;

            if ($account_id) {
                $u = $conn->prepare("UPDATE ACCOUNT SET username=? WHERE account_id=?");
                $u->bind_param("si", $username, $account_id);
                $u->execute();
            }
        }

        $conn->commit();
        $success = "Trainer updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Update failed: " . $e->getMessage();
    }
}


// Handle Delete Trainer
if (isset($_GET['delete'])) {
    $trainer_id = intval($_GET['delete']);
    
    // Get account_id first
    $acc_query = $conn->prepare("SELECT account_id FROM TRAINER WHERE trainer_id = ?");
    $acc_query->bind_param("i", $trainer_id);
    $acc_query->execute();
    $result = $acc_query->get_result();
    
    if ($result->num_rows > 0) {
        $account_id = $result->fetch_assoc()['account_id'];
        
        // Delete account (will cascade to trainer)
        if ($account_id) {
            $delete = $conn->prepare("DELETE FROM ACCOUNT WHERE account_id = ?");
            $delete->bind_param("i", $account_id);
            if ($delete->execute()) {
                $success = "Trainer deleted successfully";
            }
        } else {
            // If no account_id, delete trainer directly
            $delete = $conn->prepare("DELETE FROM TRAINER WHERE trainer_id = ?");
            $delete->bind_param("i", $trainer_id);
            if ($delete->execute()) {
                $success = "Trainer deleted successfully";
            }
        }
    }
}

// Get all trainers
$trainers_query = "SELECT t.*, a.username, a.last_login,
                   (SELECT COUNT(DISTINCT mwp.member_id) 
                    FROM member_workout_plan mwp 
                    JOIN WORKOUT_PLAN wp ON mwp.plan_id = wp.plan_id 
                    WHERE wp.trainer_id = t.trainer_id) as member_count,
                   (SELECT COUNT(*) FROM WORKOUT_PLAN WHERE trainer_id = t.trainer_id) as plan_count
                   FROM TRAINER t
                   LEFT JOIN ACCOUNT a ON t.account_id = a.account_id
                   ORDER BY t.trainer_name";
$trainers = $conn->query($trainers_query);

// Get statistics
$total_trainers = $conn->query("SELECT COUNT(*) as count FROM TRAINER")->fetch_assoc()['count'];
$trainers_with_accounts = $conn->query("SELECT COUNT(*) as count FROM TRAINER WHERE account_id IS NOT NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - EnduraCore</title>
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
                    <h1>Trainer Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainerModal">
                        <i class="fas fa-user-plus"></i> Add New Trainer
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
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Trainers</h6>
                                        <h2 class="mb-0"><?php echo $total_trainers; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-user-tie fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">With Accounts</h6>
                                        <h2 class="mb-0"><?php echo $trainers_with_accounts; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Without Accounts</h6>
                                        <h2 class="mb-0"><?php echo ($total_trainers - $trainers_with_accounts); ?></h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="fas fa-user-slash fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trainers Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Specialization</th>
                                        <th>Experience</th>
                                        <th>Phone</th>
                                        <th>Members</th>
                                        <th>Plans</th>
                                        <th>Account Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($trainer = $trainers->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $trainer['trainer_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($trainer['trainer_name']); ?></strong></td>
                                            <td>
                                                <?php if ($trainer['username']): ?>
                                                    <?php echo htmlspecialchars($trainer['username']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No account</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $trainer['specialization']; ?></span>
                                            </td>
                                            <td><?php echo $trainer['experience_years']; ?> years</td>
                                            <td><?php echo htmlspecialchars($trainer['phone']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $trainer['member_count']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $trainer['plan_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($trainer['account_id']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Has Account
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation"></i> No Account
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $trainer['trainer_id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal<?php echo $trainer['trainer_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <a href="?delete=<?php echo $trainer['trainer_id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure? This will delete the trainer and their account.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- ================= VIEW MODALS (OUTSIDE TABLE) ================= -->
                <?php
                $trainers->data_seek(0);
                while ($trainer = $trainers->fetch_assoc()):
                ?>
                <div class="modal fade" id="viewModal<?php echo $trainer['trainer_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Trainer Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Name:</th>
                                        <td><?php echo htmlspecialchars($trainer['trainer_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Username:</th>
                                        <td><?php echo $trainer['username'] ? htmlspecialchars($trainer['username']) : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Specialization:</th>
                                        <td><?php echo $trainer['specialization']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Experience:</th>
                                        <td><?php echo $trainer['experience_years']; ?> years</td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td><?php echo htmlspecialchars($trainer['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Assigned Members:</th>
                                        <td><?php echo $trainer['member_count']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Workout Plans:</th>
                                        <td><?php echo $trainer['plan_count']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Login:</th>
                                        <td><?php echo $trainer['last_login'] ? date('M d, Y H:i', strtotime($trainer['last_login'])) : 'Never'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <!-- =========================================================== -->
                 <!-- ================= EDIT MODALS ================= -->
<?php
$trainers->data_seek(0);
while ($trainer = $trainers->fetch_assoc()):
?>
<div class="modal fade" id="editModal<?php echo $trainer['trainer_id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Trainer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="trainer_id" value="<?php echo $trainer['trainer_id']; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="trainer_name"
                                   value="<?php echo htmlspecialchars($trainer['trainer_name']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username"
                                   value="<?php echo htmlspecialchars($trainer['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Specialization</label>
                            <select class="form-select" name="specialization">
                                <?php foreach (['Strength','Cardio','Yoga','CrossFit','General'] as $s): ?>
                                    <option value="<?php echo $s; ?>"
                                        <?php if ($trainer['specialization'] === $s) echo 'selected'; ?>>
                                        <?php echo $s; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" class="form-control" name="experience_years"
                                   value="<?php echo $trainer['experience_years']; ?>" min="0" max="50">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                   value="<?php echo htmlspecialchars($trainer['phone']); ?>">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_trainer" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Trainer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
<!-- ============================================== -->

                
            </div>
        </div>
    </div>
    
    <!-- Add Trainer Modal -->
    <div class="modal fade" id="addTrainerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Trainer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This will create both a trainer profile and login account.
                        </div>
                        
                        <h6 class="mb-3">Account Information</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required>
                                <small class="text-muted">Min 6 characters</small>
                            </div>
                        </div>
                        
                        <hr>
                        <h6 class="mb-3">Trainer Information</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="trainer_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialization *</label>
                                <select class="form-select" name="specialization" required>
                                    <option value="Strength">Strength</option>
                                    <option value="Cardio">Cardio</option>
                                    <option value="Yoga">Yoga</option>
                                    <option value="CrossFit">CrossFit</option>
                                    <option value="General">General</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience (Years) *</label>
                                <input type="number" class="form-control" name="experience_years" min="0" max="50" value="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_trainer" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Trainer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
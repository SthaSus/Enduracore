<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['TRAINER']);

$account_id = $_SESSION['user_id'];

// Get trainer info using account_id (FIXED)
$trainer_query = "SELECT trainer_id FROM TRAINER WHERE account_id = ?";
$stmt = $conn->prepare($trainer_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$trainer_result = $stmt->get_result()->fetch_assoc();

// Check if trainer profile exists
if (!$trainer_result) {
    die("<div class='alert alert-danger m-5'>
            <h4><i class='fas fa-exclamation-triangle'></i> Error: Trainer Profile Not Found</h4>
            <p>No trainer profile is associated with your account (Account ID: $account_id).</p>
            <p>Please contact the administrator to properly set up your trainer profile.</p>
         </div>");
}

$trainer_id = $trainer_result['trainer_id'];

// Handle Create Workout Plan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_plan'])) {
    $plan_name = sanitize_input($_POST['plan_name']);
    $goal = $_POST['goal'];
    $duration = $_POST['duration'];
    $equipment_ids = isset($_POST['equipment_ids']) ? $_POST['equipment_ids'] : [];
    $member_ids = isset($_POST['member_ids']) ? $_POST['member_ids'] : [];
    
    $conn->begin_transaction();
    try {
        // Insert workout plan
        $stmt = $conn->prepare("INSERT INTO WORKOUT_PLAN (trainer_id, plan_name, goal, duration) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $trainer_id, $plan_name, $goal, $duration);
        $stmt->execute();
        $plan_id = $conn->insert_id;
        
        // Assign equipment to plan
        if (!empty($equipment_ids)) {
            $equip_stmt = $conn->prepare("INSERT INTO workout_plan_equipment (plan_id, equipment_id) VALUES (?, ?)");
            foreach ($equipment_ids as $equipment_id) {
                $equip_stmt->bind_param("ii", $plan_id, $equipment_id);
                $equip_stmt->execute();
            }
        }
        
        // Assign to selected members
        if (!empty($member_ids)) {
            $assign_stmt = $conn->prepare("INSERT INTO member_workout_plan (member_id, plan_id) VALUES (?, ?)");
            foreach ($member_ids as $member_id) {
                $assign_stmt->bind_param("ii", $member_id, $plan_id);
                $assign_stmt->execute();
            }
        }
        
        $conn->commit();
        $success = "Workout plan created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to create workout plan: " . $e->getMessage();
    }
}

// Handle Update Workout Plan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_plan'])) {
    $plan_id = $_POST['plan_id'];
    $plan_name = sanitize_input($_POST['plan_name']);
    $goal = $_POST['goal'];
    $duration = $_POST['duration'];
    $equipment_ids = isset($_POST['equipment_ids']) ? $_POST['equipment_ids'] : [];
    
    $conn->begin_transaction();
    try {
        // Update workout plan
        $stmt = $conn->prepare("UPDATE WORKOUT_PLAN SET plan_name = ?, goal = ?, duration = ? WHERE plan_id = ? AND trainer_id = ?");
        $stmt->bind_param("sssii", $plan_name, $goal, $duration, $plan_id, $trainer_id);
        $stmt->execute();
        
        // Delete existing equipment assignments
        $conn->query("DELETE FROM workout_plan_equipment WHERE plan_id = $plan_id");
        
        // Add new equipment assignments
        if (!empty($equipment_ids)) {
            $equip_stmt = $conn->prepare("INSERT INTO workout_plan_equipment (plan_id, equipment_id) VALUES (?, ?)");
            foreach ($equipment_ids as $equipment_id) {
                $equip_stmt->bind_param("ii", $plan_id, $equipment_id);
                $equip_stmt->execute();
            }
        }
        
        $conn->commit();
        $success = "Workout plan updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to update workout plan: " . $e->getMessage();
    }
}

// Get all workout plans by this trainer with equipment
$plans_query = "SELECT wp.*, COUNT(DISTINCT mwp.member_id) as member_count,
                GROUP_CONCAT(DISTINCT e.equipment_name SEPARATOR ', ') as equipment_list,
                COUNT(DISTINCT wpe.equipment_id) as equipment_count
                FROM WORKOUT_PLAN wp
                LEFT JOIN member_workout_plan mwp ON wp.plan_id = mwp.plan_id
                LEFT JOIN workout_plan_equipment wpe ON wp.plan_id = wpe.plan_id
                LEFT JOIN EQUIPMENT e ON wpe.equipment_id = e.equipment_id
                WHERE wp.trainer_id = ?
                GROUP BY wp.plan_id
                ORDER BY wp.plan_id DESC";
$stmt = $conn->prepare($plans_query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$plans = $stmt->get_result();

// Get all members for assignment
$members_query = "SELECT member_id, member_name FROM MEMBER ORDER BY member_name";
$all_members = $conn->query($members_query);

// Get all equipment
$equipment_query = "SELECT equipment_id, equipment_name FROM EQUIPMENT ORDER BY equipment_name";
$all_equipment = $conn->query($equipment_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - EnduraCore</title>
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
            <h1>Workout Plans</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                <i class="fas fa-plus"></i> New Plan
            </button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($plans->num_rows > 0): ?>
            <div class="row">
                <?php while ($plan = $plans->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:60px;height:60px;">
                                        <i class="fas fa-dumbbell fa-2x"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
                                        <small class="text-muted"><?php echo $plan['goal']; ?> • <?php echo $plan['duration']; ?></small>
                                    </div>
                                </div>

                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h4 class="mb-0 text-primary"><?php echo $plan['member_count']; ?></h4>
                                            <small class="text-muted">Members</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h4 class="mb-0 text-success"><?php echo $plan['equipment_count']; ?></h4>
                                            <small class="text-muted">Equipment</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <h4 class="mb-0 text-info"><?php echo $plan['duration']; ?></h4>
                                            <small class="text-muted">Duration</small>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($plan['equipment_list'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1"><i class="fas fa-tools"></i> Equipment:</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php 
                                        $equipment_items = explode(', ', $plan['equipment_list']);
                                        foreach (array_slice($equipment_items, 0, 3) as $equipment): 
                                        ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($equipment); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($equipment_items) > 3): ?>
                                            <span class="badge bg-light text-dark">+<?php echo count($equipment_items) - 3; ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $plan['plan_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-success flex-grow-1" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $plan['plan_id']; ?>">
                                        <i class="fas fa-user-plus"></i> Assign
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted"><i class="fas fa-bullseye"></i> Goal: <?php echo $plan['goal']; ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?php echo $plan['plan_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Workout Plan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['plan_id']; ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Plan Name *</label>
                                            <input type="text" class="form-control" name="plan_name" value="<?php echo htmlspecialchars($plan['plan_name']); ?>" required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Goal *</label>
                                                <select class="form-select" name="goal" required>
                                                    <option value="Weight Loss" <?php echo $plan['goal'] == 'Weight Loss' ? 'selected' : ''; ?>>Weight Loss</option>
                                                    <option value="Muscle Gain" <?php echo $plan['goal'] == 'Muscle Gain' ? 'selected' : ''; ?>>Muscle Gain</option>
                                                    <option value="Endurance" <?php echo $plan['goal'] == 'Endurance' ? 'selected' : ''; ?>>Endurance</option>
                                                    <option value="Fitness" <?php echo $plan['goal'] == 'Fitness' ? 'selected' : ''; ?>>Fitness</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Duration *</label>
                                                <select class="form-select" name="duration" required>
                                                    <option value="1 Month" <?php echo $plan['duration'] == '1 Month' ? 'selected' : ''; ?>>1 Month</option>
                                                    <option value="3 Months" <?php echo $plan['duration'] == '3 Months' ? 'selected' : ''; ?>>3 Months</option>
                                                    <option value="6 Months" <?php echo $plan['duration'] == '6 Months' ? 'selected' : ''; ?>>6 Months</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Equipment</label>
                                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                                <?php 
                                                // Get currently assigned equipment for this plan
                                                $current_equipment = $conn->query("SELECT equipment_id FROM workout_plan_equipment WHERE plan_id = " . $plan['plan_id']);
                                                $current_equip_ids = [];
                                                while ($ce = $current_equipment->fetch_assoc()) {
                                                    $current_equip_ids[] = $ce['equipment_id'];
                                                }
                                                
                                                $equipment_list = $conn->query("SELECT equipment_id, equipment_name FROM EQUIPMENT ORDER BY equipment_name");
                                                while ($equip = $equipment_list->fetch_assoc()): 
                                                ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="equipment_ids[]" 
                                                               value="<?php echo $equip['equipment_id']; ?>" 
                                                               id="edit_equip_<?php echo $plan['plan_id']; ?>_<?php echo $equip['equipment_id']; ?>"
                                                               <?php echo in_array($equip['equipment_id'], $current_equip_ids) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="edit_equip_<?php echo $plan['plan_id']; ?>_<?php echo $equip['equipment_id']; ?>">
                                                            <?php echo htmlspecialchars($equip['equipment_name']); ?>
                                                        </label>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_plan" class="btn btn-primary">Update Plan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Assign Modal -->
                    <div class="modal fade" id="assignModal<?php echo $plan['plan_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="assign_plan.php">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['plan_id']; ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Assign Members</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" style="max-height:300px;overflow-y:auto;">
                                        <?php
                                        $members2 = $conn->query("SELECT member_id, member_name FROM MEMBER ORDER BY member_name");
                                        while ($m = $members2->fetch_assoc()): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="member_ids[]" value="<?php echo $m['member_id']; ?>">
                                                <label class="form-check-label"><?php echo htmlspecialchars($m['member_name']); ?></label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Assign</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h4>No Workout Plans</h4>
                <p>Create your first plan to assign members.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>
    
    <!-- Create Plan Modal -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Workout Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Plan Name *</label>
                            <input type="text" class="form-control" name="plan_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Goal *</label>
                                <select class="form-select" name="goal" required>
                                    <option value="Weight Loss">Weight Loss</option>
                                    <option value="Muscle Gain">Muscle Gain</option>
                                    <option value="Endurance">Endurance</option>
                                    <option value="Fitness">Fitness</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration *</label>
                                <select class="form-select" name="duration" required>
                                    <option value="1 Month">1 Month</option>
                                    <option value="3 Months">3 Months</option>
                                    <option value="6 Months">6 Months</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Equipment</label>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                <?php while ($equipment = $all_equipment->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="equipment_ids[]" value="<?php echo $equipment['equipment_id']; ?>" id="equip_<?php echo $equipment['equipment_id']; ?>">
                                        <label class="form-check-label" for="equip_<?php echo $equipment['equipment_id']; ?>">
                                            <?php echo htmlspecialchars($equipment['equipment_name']); ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Assign to Members (Optional)</label>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                <?php while ($member = $all_members->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="member_ids[]" value="<?php echo $member['member_id']; ?>" id="member_<?php echo $member['member_id']; ?>">
                                        <label class="form-check-label" for="member_<?php echo $member['member_id']; ?>">
                                            <?php echo htmlspecialchars($member['member_name']); ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_plan" class="btn btn-primary">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

$success = '';
$error = '';

// Handle Add Equipment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_equipment'])) {
    $equipment_name = sanitize_input($_POST['equipment_name']);
    $equipment_type = $_POST['equipment_type'];
    $equipment_condition = $_POST['equipment_condition'];
    $last_serviced = $_POST['last_serviced'];

    $stmt = $conn->prepare(
        "INSERT INTO equipment (equipment_name, equipment_type, equipment_condition, last_serviced)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $equipment_name, $equipment_type, $equipment_condition, $last_serviced);

    $stmt->execute()
        ? $success = "Equipment added successfully"
        : $error = "Failed to add equipment";
}

// Handle Update Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_service'])) {
    $equipment_id = intval($_POST['equipment_id']);
    $new_condition = $_POST['new_condition'];
    $today = date('Y-m-d');

    $stmt = $conn->prepare(
        "UPDATE equipment SET last_serviced = ?, equipment_condition = ? WHERE equipment_id = ?"
    );
    $stmt->bind_param("ssi", $today, $new_condition, $equipment_id);

    $stmt->execute()
        ? $success = "Service record updated"
        : $error = "Failed to update service";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $equipment_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM equipment WHERE equipment_id = ?");
    $stmt->bind_param("i", $equipment_id);

    $stmt->execute()
        ? $success = "Equipment deleted successfully"
        : $error = "Failed to delete equipment";
}

// Fetch equipment
$equipment = $conn->query("SELECT * FROM equipment ORDER BY equipment_name");

// Stats
$total_equipment = $conn->query("SELECT COUNT(*) c FROM equipment")->fetch_assoc()['c'];
$needs_repair = $conn->query("SELECT COUNT(*) c FROM equipment WHERE equipment_condition='Needs Repair'")->fetch_assoc()['c'];
$new_equipment = $conn->query("SELECT COUNT(*) c FROM equipment WHERE equipment_condition='New'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Management - EnduraCore</title>
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

<div class="d-flex justify-content-between mb-4">
    <h1>Equipment Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
        <i class="fas fa-plus"></i> Add Equipment
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= $success ?><button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $error ?><button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Total Equipment</h6>
                <h2><?= $total_equipment ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Needs Repair</h6>
                <h2 class="text-danger"><?= $needs_repair ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>New Equipment</h6>
                <h2 class="text-success"><?= $new_equipment ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body">
<table class="table table-hover">
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Type</th>
    <th>Condition</th>
    <th>Last Serviced</th>
    <th>Days Since</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php while ($eq = $equipment->fetch_assoc()):
    $days = floor((time() - strtotime($eq['last_serviced'])) / 86400);
    $badge = match($eq['equipment_condition']) {
        'New' => 'bg-success',
        'Good' => 'bg-primary',
        'Needs Repair' => 'bg-danger'
    };
?>
<tr>
<td><?= $eq['equipment_id'] ?></td>
<td><strong><?= htmlspecialchars($eq['equipment_name']) ?></strong></td>
<td><span class="badge bg-secondary"><?= $eq['equipment_type'] ?></span></td>
<td><span class="badge <?= $badge ?>"><?= $eq['equipment_condition'] ?></span></td>
<td><?= date('M d, Y', strtotime($eq['last_serviced'])) ?></td>
<td><?= $days ?> days</td>
<td>
<button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#view<?= $eq['equipment_id'] ?>">
<i class="fas fa-eye"></i></button>

<button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#service<?= $eq['equipment_id'] ?>">
<i class="fas fa-wrench"></i></button>

<a href="?delete=<?= $eq['equipment_id'] ?>" class="btn btn-sm btn-danger"
onclick="return confirm('Delete this equipment?')">
<i class="fas fa-trash"></i></a>
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

<!-- ===== MODALS ===== -->
<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<div class="modal-header">
<h5>Add Equipment</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="mb-3">
    <label class="form-label">Equipment Name</label>
    <input type="text" name="equipment_name" class="form-control" required>
</div>
<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="equipment_type" class="form-select" required>
        <option value="Machine">Machine</option>
        <option value="Free Weight">Free Weight</option>
        <option value="Accessory">Accessory</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Condition</label>
    <select name="equipment_condition" class="form-select" required>
        <option value="New">New</option>
        <option value="Good" selected>Good</option>
        <option value="Needs Repair">Needs Repair</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Last Serviced</label>
    <input type="date" name="last_serviced" class="form-control" value="<?= date('Y-m-d') ?>" required>
</div>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-primary" name="add_equipment">Add</button>
</div>
</form>
</div>
</div>
</div>

<!-- Existing View & Service Modals -->
<?php
$equipment->data_seek(0);
while ($eq = $equipment->fetch_assoc()):
$badge = match($eq['equipment_condition']) {
    'New' => 'bg-success',
    'Good' => 'bg-primary',
    'Needs Repair' => 'bg-danger'
};
$days = floor((time() - strtotime($eq['last_serviced'])) / 86400);
?>

<!-- View Modal -->
<div class="modal fade" id="view<?= $eq['equipment_id'] ?>">
<div class="modal-dialog"><div class="modal-content">
<div class="modal-header">
<h5>Equipment Details</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<p><strong><?= htmlspecialchars($eq['equipment_name']) ?></strong></p>
<p>Type: <?= $eq['equipment_type'] ?></p>
<p>Condition: <span class="badge <?= $badge ?>"><?= $eq['equipment_condition'] ?></span></p>
<p>Last Serviced: <?= date('M d, Y', strtotime($eq['last_serviced'])) ?></p>
<p>Days Since: <?= $days ?></p>
</div>
</div></div>
</div>

<!-- Service Modal -->
<div class="modal fade" id="service<?= $eq['equipment_id'] ?>">
<div class="modal-dialog"><div class="modal-content">
<form method="POST">
<div class="modal-header">
<h5>Update Service</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="equipment_id" value="<?= $eq['equipment_id'] ?>">
<select name="new_condition" class="form-select" required>
<option>New</option>
<option selected>Good</option>
<option>Needs Repair</option>
</select>
<p class="text-muted mt-2">Service date = Today</p>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-success" name="update_service">Update</button>
</div>
</form>
</div></div>
</div>

<?php endwhile; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

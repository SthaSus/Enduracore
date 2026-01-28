<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['MEMBER']);

$account_id = $_SESSION['user_id'];

// Get member details
$member_query = "SELECT * FROM MEMBER WHERE account_id = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$member_id = $member['member_id'];

// Get all workout plans assigned to this member
$workout_query = "SELECT wp.*, t.trainer_name, t.specialization
                  FROM WORKOUT_PLAN wp
                  JOIN member_workout_plan mwp ON wp.plan_id = mwp.plan_id
                  LEFT JOIN TRAINER t ON wp.trainer_id = t.trainer_id
                  WHERE mwp.member_id = ?
                  ORDER BY wp.plan_id DESC";
$stmt = $conn->prepare($workout_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$workouts = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workouts - EnduraCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .workout-card {
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }
        .workout-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .exercise-item {
            background: #f8f9fa;
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <?php include '../partials/header.php'; ?>
            
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-dumbbell me-2"></i>My Workout Plans</h1>
                    <span class="badge bg-primary fs-5"><?php echo $workouts->num_rows; ?> Plans</span>
                </div>
                
                <?php if ($workouts->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($workout = $workouts->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card workout-card h-100 shadow-sm">
                                    <div class="card-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($workout['plan_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Goal Badge -->
                                        <div class="mb-3">
                                            <span class="badge bg-success me-2">
                                                <i class="fas fa-bullseye"></i> <?php echo $workout['goal']; ?>
                                            </span>
                                            <span class="badge bg-info">
                                                <i class="fas fa-clock"></i> <?php echo $workout['duration']; ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Trainer Info -->
                                        <?php if ($workout['trainer_name']): ?>
                                            <div class="mb-3 p-2 bg-light rounded">
                                                <i class="fas fa-user-tie text-primary"></i>
                                                <strong>Trainer:</strong> <?php echo htmlspecialchars($workout['trainer_name']); ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-award"></i> <?php echo $workout['specialization']; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Equipment List -->
                                        <?php
                                        $equipment_query = "SELECT e.equipment_name, e.equipment_type
                                                           FROM EQUIPMENT e
                                                           JOIN workout_plan_equipment wpe ON e.equipment_id = wpe.equipment_id
                                                           WHERE wpe.plan_id = ?";
                                        $equip_stmt = $conn->prepare($equipment_query);
                                        $equip_stmt->bind_param("i", $workout['plan_id']);
                                        $equip_stmt->execute();
                                        $equipment = $equip_stmt->get_result();
                                        ?>
                                        
                                        <?php if ($equipment->num_rows > 0): ?>
                                            <div class="mb-3">
                                                <strong><i class="fas fa-tools text-secondary"></i> Equipment Needed:</strong>
                                                <div class="mt-2">
                                                    <?php while ($equip = $equipment->fetch_assoc()): ?>
                                                        <span class="badge bg-secondary me-1 mb-1">
                                                            <?php echo htmlspecialchars($equip['equipment_name']); ?>
                                                        </span>
                                                    <?php endwhile; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Sample Exercises (placeholder) -->
                                        <div class="mb-3">
                                            <strong><i class="fas fa-list-check text-success"></i> Sample Exercises:</strong>
                                            <div class="mt-2">
                                                <?php
                                                // Generate sample exercises based on goal
                                                $sample_exercises = [];
                                                switch ($workout['goal']) {
                                                    case 'Weight Loss':
                                                        $sample_exercises = ['Cardio - 30 mins', 'Jump Rope - 3 sets', 'Burpees - 3x15'];
                                                        break;
                                                    case 'Muscle Gain':
                                                        $sample_exercises = ['Bench Press - 4x8', 'Squats - 4x10', 'Deadlifts - 3x8'];
                                                        break;
                                                    case 'Endurance':
                                                        $sample_exercises = ['Running - 5km', 'Cycling - 20 mins', 'Swimming - 15 mins'];
                                                        break;
                                                    case 'Fitness':
                                                        $sample_exercises = ['Push-ups - 3x15', 'Plank - 3x60s', 'Lunges - 3x12'];
                                                        break;
                                                }
                                                foreach ($sample_exercises as $exercise):
                                                ?>
                                                    <div class="exercise-item">
                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                        <?php echo $exercise; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $workout['plan_id']; ?>">
                                            <i class="fas fa-eye"></i> View Full Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Workout Details Modal -->
                            <div class="modal fade" id="detailsModal<?php echo $workout['plan_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($workout['plan_name']); ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-3">Plan Details</h6>
                                                    <table class="table table-borderless">
                                                        <tr>
                                                            <th><i class="fas fa-bullseye text-success"></i> Goal:</th>
                                                            <td><span class="badge bg-success"><?php echo $workout['goal']; ?></span></td>
                                                        </tr>
                                                        <tr>
                                                            <th><i class="fas fa-clock text-info"></i> Duration:</th>
                                                            <td><span class="badge bg-info"><?php echo $workout['duration']; ?></span></td>
                                                        </tr>
                                                        <tr>
                                                            <th><i class="fas fa-user-tie text-primary"></i> Trainer:</th>
                                                            <td><?php echo htmlspecialchars($workout['trainer_name']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th><i class="fas fa-award text-warning"></i> Specialty:</th>
                                                            <td><?php echo $workout['specialization']; ?></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-3">Workout Schedule</h6>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-calendar-alt"></i> <strong>Recommended:</strong><br>
                                                        <?php
                                                        switch ($workout['goal']) {
                                                            case 'Weight Loss':
                                                                echo '5-6 days per week<br>45-60 mins per session';
                                                                break;
                                                            case 'Muscle Gain':
                                                                echo '4-5 days per week<br>60-90 mins per session';
                                                                break;
                                                            case 'Endurance':
                                                                echo '5-6 days per week<br>30-60 mins per session';
                                                                break;
                                                            case 'Fitness':
                                                                echo '3-4 days per week<br>45-60 mins per session';
                                                                break;
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <h6 class="text-muted mb-3">Complete Exercise List</h6>
                                            <div class="row">
                                                <div class="col-12">
                                                    <?php
                                                    $detailed_exercises = [];
                                                    switch ($workout['goal']) {
                                                        case 'Weight Loss':
                                                            $detailed_exercises = [
                                                                'Warm-up: 5 min light cardio',
                                                                'Treadmill running: 20 mins',
                                                                'Jump rope: 3 sets x 60 seconds',
                                                                'Burpees: 3 sets x 15 reps',
                                                                'Mountain climbers: 3 sets x 20 reps',
                                                                'Cool-down: 5 min stretching'
                                                            ];
                                                            break;
                                                        case 'Muscle Gain':
                                                            $detailed_exercises = [
                                                                'Warm-up: 5 min dynamic stretching',
                                                                'Bench Press: 4 sets x 8 reps',
                                                                'Squats: 4 sets x 10 reps',
                                                                'Deadlifts: 3 sets x 8 reps',
                                                                'Pull-ups: 3 sets x max reps',
                                                                'Dumbbell rows: 3 sets x 12 reps',
                                                                'Cool-down: 5 min stretching'
                                                            ];
                                                            break;
                                                        case 'Endurance':
                                                            $detailed_exercises = [
                                                                'Warm-up: 5 min light jog',
                                                                'Running: 5km steady pace',
                                                                'Cycling: 20 mins moderate',
                                                                'Swimming: 15 mins continuous',
                                                                'Rowing machine: 10 mins',
                                                                'Cool-down: 5 min walk + stretch'
                                                            ];
                                                            break;
                                                        case 'Fitness':
                                                            $detailed_exercises = [
                                                                'Warm-up: 5 min cardio',
                                                                'Push-ups: 3 sets x 15 reps',
                                                                'Plank: 3 sets x 60 seconds',
                                                                'Lunges: 3 sets x 12 reps each leg',
                                                                'Dumbbell curls: 3 sets x 12 reps',
                                                                'Sit-ups: 3 sets x 20 reps',
                                                                'Cool-down: 5 min stretching'
                                                            ];
                                                            break;
                                                    }
                                                    foreach ($detailed_exercises as $index => $exercise):
                                                    ?>
                                                        <div class="exercise-item">
                                                            <strong><?php echo ($index + 1); ?>.</strong>
                                                            <i class="fas fa-dumbbell text-primary ms-2 me-2"></i>
                                                            <?php echo $exercise; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <?php
                                            $equipment->data_seek(0);
                                            if ($equipment->num_rows > 0):
                                            ?>
                                                <h6 class="text-muted mb-3 mt-4">Required Equipment</h6>
                                                <div class="row">
                                                    <?php while ($equip = $equipment->fetch_assoc()): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="p-2 bg-light rounded">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                                <strong><?php echo htmlspecialchars($equip['equipment_name']); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo $equip['equipment_type']; ?></small>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="alert alert-warning mt-4">
                                                <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong>
                                                Always warm up before exercising and cool down afterwards. Stay hydrated and listen to your body. Consult with your trainer if you experience any pain or discomfort.
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h4>No Workout Plans Assigned Yet</h4>
                        <p>Contact your trainer to get a personalized workout plan created for your fitness goals!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
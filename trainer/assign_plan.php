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
    $_SESSION['error'] = "No trainer profile is associated with your account. Please contact the administrator.";
    header("Location: workout_plans.php");
    exit;
}

$trainer_id = $trainer_result['trainer_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $member_ids = isset($_POST['member_ids']) ? $_POST['member_ids'] : [];
    
    if ($plan_id <= 0) {
        $_SESSION['error'] = "Invalid workout plan selected.";
        header("Location: workout_plans.php");
        exit;
    }
    
    // Verify that this plan belongs to the current trainer
    $verify_query = $conn->prepare("SELECT plan_id FROM WORKOUT_PLAN WHERE plan_id = ? AND trainer_id = ?");
    $verify_query->bind_param("ii", $plan_id, $trainer_id);
    $verify_query->execute();
    $verify_result = $verify_query->get_result();
    
    if ($verify_result->num_rows == 0) {
        $_SESSION['error'] = "You don't have permission to assign this workout plan.";
        header("Location: workout_plans.php");
        exit;
    }
    $verify_query->close();
    
    if (empty($member_ids)) {
        $_SESSION['error'] = "Please select at least one member to assign.";
        header("Location: workout_plans.php");
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $success_count = 0;
        $already_assigned_count = 0;
        
        $check_stmt = $conn->prepare("SELECT 1 FROM member_workout_plan WHERE member_id = ? AND plan_id = ?");
        $insert_stmt = $conn->prepare("INSERT INTO member_workout_plan (member_id, plan_id) VALUES (?, ?)");
        
        foreach ($member_ids as $member_id) {
            $member_id = intval($member_id);
            
            // Check if already assigned
            $check_stmt->bind_param("ii", $member_id, $plan_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                $already_assigned_count++;
            } else {
                // Assign the plan
                $insert_stmt->bind_param("ii", $member_id, $plan_id);
                if ($insert_stmt->execute()) {
                    $success_count++;
                }
            }
            
            $check_stmt->free_result();
        }
        
        $check_stmt->close();
        $insert_stmt->close();
        
        $conn->commit();
        
        // Build success message
        $message_parts = [];
        if ($success_count > 0) {
            $message_parts[] = "$success_count member(s) successfully assigned";
        }
        if ($already_assigned_count > 0) {
            $message_parts[] = "$already_assigned_count member(s) already had this plan";
        }
        
        $_SESSION['success'] = implode(". ", $message_parts) . ".";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to assign workout plan: " . $e->getMessage();
    }
    
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: workout_plans.php");
exit;
?>
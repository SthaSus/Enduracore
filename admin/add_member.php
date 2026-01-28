<?php
session_start();
require_once '../config/db_config.php';
check_login();
check_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $member_name = trim($_POST['member_name']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    // Validation
    if (empty($username) || empty($password) || empty($member_name) || empty($phone) || empty($email)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: members.php");
        exit();
    }
    
    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT account_id FROM ACCOUNT WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Username already exists";
        header("Location: members.php");
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into ACCOUNT table
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $account_stmt = $conn->prepare("INSERT INTO ACCOUNT (username, password, role, created_on) VALUES (?, ?, 'MEMBER', NOW())");
        $account_stmt->bind_param("ss", $username, $hashed_password);
        $account_stmt->execute();
        $account_id = $conn->insert_id;
        
        // Insert into MEMBER table (using member_name column)
        $member_stmt = $conn->prepare("INSERT INTO MEMBER (account_id, member_name, age, gender, phone, email, join_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        $member_stmt->bind_param("isisss", $account_id, $member_name, $age, $gender, $phone, $email);
        $member_stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Member added successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to add member: " . $e->getMessage();
    }
    
    header("Location: members.php");
    exit();
}
?>
<?php
session_start();
require_once '../config/db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if membership_id is provided
if (!isset($_GET['membership_id']) || empty($_GET['membership_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Membership ID is required'
    ]);
    exit;
}

$membership_id = intval($_GET['membership_id']);

// Fetch membership, member, and payment data
$query = $conn->prepare("
    SELECT 
        ms.membership_id,
        ms.membership_type,
        ms.start_date,
        ms.end_date,
        ms.fee,
        ms.status,
        m.member_id,
        m.member_name,
        m.email,
        m.phone,
        p.payment_id,
        p.payment_date,
        p.payment_method,
        p.amount as payment_amount
    FROM MEMBERSHIP ms
    INNER JOIN MEMBER m ON ms.member_id = m.member_id
    LEFT JOIN PAYMENT p ON ms.membership_id = p.membership_id
    WHERE ms.membership_id = ?
");

$query->bind_param("i", $membership_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Receipt not found'
    ]);
    exit;
}

$data = $result->fetch_assoc();
$query->close();

// Format dates
$start_date = date('M d, Y', strtotime($data['start_date']));
$end_date = date('M d, Y', strtotime($data['end_date']));
$payment_date = $data['payment_date'] ? date('M d, Y', strtotime($data['payment_date'])) : 'N/A';

// Return JSON response
echo json_encode([
    'success' => true,
    'membership_id' => $data['membership_id'],
    'member_id' => $data['member_id'],
    'member_name' => $data['member_name'],
    'member_email' => $data['email'] ?? 'N/A',
    'member_phone' => $data['phone'] ?? 'N/A',
    'membership_type' => $data['membership_type'],
    'start_date' => $start_date,
    'end_date' => $end_date,
    'fee' => number_format($data['fee'], 2),
    'status' => $data['status'],
    'payment_date' => $payment_date,
    'payment_method' => $data['payment_method'] ?? 'N/A',
    'payment_id' => $data['payment_id'] ?? 'N/A'
]);
?>
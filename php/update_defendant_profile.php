<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent' || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$defendant_id = $_POST['defendant_id'] ?? '';
if (!$defendant_id) {
    echo json_encode(['success' => false, 'message' => 'No defendant ID provided']);
    exit;
}

try {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $case_number = $_POST['case_number'] ?? '';
    $bail_amount = $_POST['bail_amount'] ?? '';
    $court_date = $_POST['court_date'] ?? '';
    $agent_id = $_POST['agent_id'] ?? '';
    $agency_id = $_POST['agency_id'] ?? '';
    $verified = $_POST['verified'] ?? '0';
    $notes = $_POST['notes'] ?? '';

    // Fetch agent details
    $stmt = $pdo->prepare("SELECT name AS agent_name, agency_name FROM agents WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    $agent_name = $agent['agent_name'] ?? 'N/A';
    $agency_name = $agent['agency_name'] ?? 'N/A';

    $stmt = $pdo->prepare("
        UPDATE defendants 
        SET name = ?, email = ?, phone = ?, address = ?, case_number = ?, bail_amount = ?, 
            court_date = ?, agent_id = ?, agent_name = ?, agency_name = ?, verified = ?, notes = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $phone, $address, $case_number, $bail_amount, $court_date, 
                    $agent_id, $agent_name, $agency_name, $verified, $notes, $defendant_id]);

    echo json_encode(['success' => true, 'message' => 'Defendant profile updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>
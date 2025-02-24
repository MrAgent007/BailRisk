<?php
// File: /public_html/php/update_profile_admin.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

parse_str(file_get_contents('php://input'), $input);
$userId = $input['user_id'] ?? '';
$userType = $input['user_type'] ?? '';
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$username = $input['username'] ?? '';
$phone = $input['phone'] ?? '';
$address = $input['address'] ?? '';

try {
    if ($userType === 'agent') {
        $license = $input['license'] ?? '';
        $stmt = $pdo->prepare("UPDATE agents SET name = ?, email = ?, username = ?, phone = ?, address = ?, license = ? WHERE id = ?");
        $stmt->execute([$name, $email, $username, $phone, $address, $license, $userId]);
    } else {
        $caseNumber = $input['case_number'] ?? '';
        $bailAmount = $input['bail_amount'] ?? '';
        $courtDate = $input['court_date'] ?? '';
        $stmt = $pdo->prepare("UPDATE defendants SET name = ?, email = ?, username = ?, phone = ?, address = ?, case_number = ?, bail_amount = ?, court_date = ? WHERE id = ?");
        $stmt->execute([$name, $email, $username, $phone, $address, $caseNumber, $bailAmount, $courtDate, $userId]);
    }
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

exit;
?>
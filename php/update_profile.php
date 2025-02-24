<?php
// File: /public_html/php/update_profile.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? $_SESSION['user_id'];
$phone = $input['phone'] ?? '';
$address = $input['address'] ?? '';

try {
    $stmt = $pdo->prepare("UPDATE defendants SET phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$phone, $address, $userId]);
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Profile update failed: ' . $e->getMessage()]);
}

exit;
?>
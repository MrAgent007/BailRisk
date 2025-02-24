<?php
// File: /public_html/php/reset_password.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? '';
$userType = $input['user_type'] ?? '';
$newPassword = $input['new_password'] ?? '';

try {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $table = $userType === 'agent' ? 'agents' : 'defendants';
    $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Reset failed: ' . $e->getMessage()]);
}

exit;
?>
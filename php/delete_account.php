<?php
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

if (!$userId || !$userType) {
    echo json_encode(['success' => false, 'message' => 'User ID and type are required']);
    exit;
}

try {
    $table = $userType === 'agent' ? 'agents' : 'defendants';
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
}

exit;
?>
<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$defendantId = $input['defendant_id'] ?? '';
$userType = $input['user_type'] ?? '';

if (!$defendantId || !$userType) {
    echo json_encode(['success' => false, 'message' => 'Defendant ID and user type are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE defendants SET user_type = ? WHERE id = ?");
    $stmt->execute([$userType, $defendantId]);
    echo json_encode(['success' => true, 'message' => 'User type changed successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Change failed: ' . $e->getMessage()]);
}

exit;
?>
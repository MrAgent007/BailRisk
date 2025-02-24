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

if (!$defendantId) {
    echo json_encode(['success' => false, 'message' => 'Defendant ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM defendants WHERE id = ?");
    $stmt->execute([$defendantId]);
    $stmt = $pdo->prepare("DELETE FROM defendant_checkins WHERE defendant_id = ?");
    $stmt->execute([$defendantId]);
    echo json_encode(['success' => true, 'message' => 'Defendant deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
}

exit;
?>
<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$defendantId = $input['defendant_id'] ?? '';
$agentName = $input['agent_name'] ?? '';

if (!$defendantId || !$agentName) {
    echo json_encode(['success' => false, 'message' => 'Defendant ID and agent name are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE defendants SET agent_name = ? WHERE id = ?");
    $stmt->execute([$agentName, $defendantId]);
    echo json_encode(['success' => true, 'message' => 'Agent assigned successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Assignment failed: ' . $e->getMessage()]);
}

exit;
?>
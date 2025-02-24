<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$agent_id = $input['agent_id'];
$action = $input['action'];
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
try {
    if ($action === 'verify') {
        $stmt = $pdo->prepare("UPDATE agents SET verified = 1 WHERE id = ?");
        $stmt->execute([$agent_id]);
        echo json_encode(['success' => true, 'message' => 'Agent verified']);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ?");
        $stmt->execute([$agent_id]);
        echo json_encode(['success' => true, 'message' => 'Agent rejected']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
?>
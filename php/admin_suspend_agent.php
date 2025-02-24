<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$agent_id = json_decode(file_get_contents('php://input'), true)['agent_id'];
$stmt = $pdo->prepare("UPDATE agents SET verified = 0 WHERE id = ?");
$stmt->execute([$agent_id]);
echo json_encode(['success' => true, 'message' => 'Agent suspended']);
exit;
?>
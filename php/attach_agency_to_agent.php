<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$agent_id = $_POST['agent_id'] ?? '';
$agency_id = $_POST['agency_id'] ?? '';

if (empty($agent_id) || empty($agency_id)) {
    echo json_encode(['success' => false, 'message' => 'Agent ID and Agency ID are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT name FROM agencies WHERE id = ? AND approved = 1");
    $stmt->execute([$agency_id]);
    $agency_name = $stmt->fetchColumn();

    if (!$agency_name) {
        echo json_encode(['success' => false, 'message' => 'Agency not found or not approved']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE agents SET agency_id = ?, agency_name = ? WHERE id = ?");
    $stmt->execute([$agency_id, $agency_name, $agent_id]);
    echo json_encode(['success' => true, 'message' => 'Agency attached to agent successfully']);
} catch (Exception $e) {
    error_log("Attach agency error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>
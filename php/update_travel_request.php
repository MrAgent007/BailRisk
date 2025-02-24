<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = $data['request_id'] ?? null;
$status = $data['status'] ?? null;

if (!$requestId || !$status || !in_array($status, ['Approved', 'Denied'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE travel_requests SET status = ? WHERE id = ? AND agent_id = ?");
    $stmt->execute([$status, $requestId, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => "Travel request $status"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
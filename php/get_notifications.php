<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$agentId = $_GET['agent_id'] ?? $_SESSION['user_id'];

try {
    // Placeholder: Replace with actual notifications table/query
    $notifications = [
        ['request_date' => '2025-02-22', 'message' => 'New defendant assigned', 'status' => 'Pending'],
        ['request_date' => '2025-02-21', 'message' => 'Payment overdue', 'status' => 'Read']
    ];
    echo json_encode(['success' => true, 'data' => $notifications]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
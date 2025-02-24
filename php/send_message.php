<?php
// File: /public_html/php/send_message.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? $_SESSION['user_id'];
$agentId = $input['agent_id'] ?? $_SESSION['agent_id'];
$content = $input['content'] ?? '';

if (!$content) {
    echo json_encode(['success' => false, 'message' => 'Message content is required']);
    exit;
}

try {
    // Placeholder: Log message (real implementation needs a messages table)
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Message send failed: ' . $e->getMessage()]);
}

exit;
?>
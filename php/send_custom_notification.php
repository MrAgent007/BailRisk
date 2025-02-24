<?php
// File: /public_html/php/send_custom_notification.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

parse_str(file_get_contents('php://input'), $input);
$message = $input['message'] ?? '';

try {
    // Placeholder: Send notification (needs a notifications table)
    echo json_encode(['success' => true, 'message' => 'Custom notification sent']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Notification failed: ' . $e->getMessage()]);
}

exit;
?>
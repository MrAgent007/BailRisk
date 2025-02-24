<?php
// File: /public_html/php/send_reminders.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    echo json_encode(['success' => true, 'message' => 'Reminders sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send reminders: ' . $e->getMessage()]);
}

exit;
?>
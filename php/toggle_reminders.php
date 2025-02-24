<?php
// File: /public_html/php/toggle_reminders.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Placeholder: Toggle reminders (needs real logic)
    $status = true; // Simulate toggle
    echo json_encode(['success' => true, 'message' => 'Reminders toggled', 'status' => $status]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Toggle failed: ' . $e->getMessage()]);
}

exit;
?>
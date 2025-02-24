<?php
// File: /public_html/php/get_session_user_id.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Session not set']);
    exit;
}

if ($_SESSION['user_type'] !== 'defendant') {
    echo json_encode(['success' => false, 'message' => 'Not a defendant']);
    exit;
}

echo json_encode(['success' => true, 'user_id' => $_SESSION['user_id']]);
exit;
?>
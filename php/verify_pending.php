<?php
// File: /public_html/php/verify_pending.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE agents SET verified = 1 WHERE verified = 0");
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Pending agents verified']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
}

exit;
?>
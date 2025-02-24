<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$agency_id = $_POST['agency_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'

if (empty($agency_id) || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE agencies SET approved = 1 WHERE id = ?");
        $stmt->execute([$agency_id]);
        $stmt = $pdo->prepare("UPDATE agents SET verified = 1 WHERE agency_id = ? AND verified = 0");
        $stmt->execute([$agency_id]);
        echo json_encode(['success' => true, 'message' => 'Agency approved']);
    } else {
        $stmt = $pdo->prepare("DELETE FROM agencies WHERE id = ? AND approved = 0");
        $stmt->execute([$agency_id]);
        $stmt = $pdo->prepare("DELETE FROM agents WHERE agency_id = ? AND verified = 0");
        $stmt->execute([$agency_id]);
        echo json_encode(['success' => true, 'message' => 'Agency rejected']);
    }
} catch (Exception $e) {
    error_log("Agency approval error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
exit;
?>
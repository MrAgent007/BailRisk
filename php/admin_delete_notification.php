<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$notification_id = json_decode(file_get_contents('php://input'), true)['notification_id'];
$stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
$stmt->execute([$notification_id]);
echo json_encode(['success' => true, 'message' => 'Notification deleted']);
exit;
?>
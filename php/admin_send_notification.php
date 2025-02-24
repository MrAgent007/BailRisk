<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$content = $_POST['content'];
$stmt = $pdo->prepare("INSERT INTO notifications (content, timestamp) VALUES (?, NOW())");
$stmt->execute([$content]);
echo json_encode(['success' => true, 'message' => 'Notification sent']);
exit;
?>
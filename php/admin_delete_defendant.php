<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$defendant_id = json_decode(file_get_contents('php://input'), true)['defendant_id'];
$stmt = $pdo->prepare("DELETE FROM defendants WHERE id = ?");
$stmt->execute([$defendant_id]);
echo json_encode(['success' => true, 'message' => 'Defendant deleted']);
exit;
?>
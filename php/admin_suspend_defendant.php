<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$defendant_id = $input['defendant_id'];
$action = $input['action'];
$verified = $action === 'suspend' ? 0 : 1;
$stmt = $pdo->prepare("UPDATE defendants SET verified = ? WHERE id = ?");
$stmt->execute([$verified, $defendant_id]);
echo json_encode(['success' => true, 'message' => `Defendant ${action}ed`]);
exit;
?>
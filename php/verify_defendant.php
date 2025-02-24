<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$defendantId = $input['defendant_id'] ?? '';
$verified = $input['verified'] ? 1 : 0;

if (!$defendantId) {
    echo json_encode(['success' => false, 'message' => 'Defendant ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE defendants SET verified = ? WHERE id = ?");
    $stmt->execute([$verified, $defendantId]);
    echo json_encode(['success' => true, 'message' => $verified ? 'Defendant verified' : 'Defendant unverified']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
}

exit;
?>
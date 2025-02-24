<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Agency ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM agencies WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Agency deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
}

exit;
?>
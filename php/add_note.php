<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = [];
parse_str(file_get_contents('php://input'), $input);
$defendantId = $input['defendant_id'] ?? '';
$content = $input['content'] ?? '';

if (!$defendantId || !$content) {
    echo json_encode(['success' => false, 'message' => 'Defendant ID and note content are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE defendants SET notes = CONCAT(IFNULL(notes, ''), ?, '\n') WHERE id = ? AND agency_name = ?");
    $stmt->execute([$content, $defendantId, $_SESSION['agency_name']]);
    echo json_encode(['success' => true, 'message' => 'Note added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Note addition failed: ' . $e->getMessage()]);
}

exit;
?>
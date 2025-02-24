<?php
// File: /public_html/php/suspend_bulk.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$users = $input['users'] ?? [];

try {
    foreach ($users as $user) {
        $table = $user['type'] === 'agent' ? 'agents' : 'defendants';
        $stmt = $pdo->prepare("UPDATE $table SET verified = 0 WHERE id = ?");
        $stmt->execute([$user['id']]);
    }
    echo json_encode(['success' => true, 'message' => 'Users suspended successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Suspension failed: ' . $e->getMessage()]);
}

exit;
?>
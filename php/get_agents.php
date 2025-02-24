<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name FROM agents WHERE verified = 1");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'agents' => $agents]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
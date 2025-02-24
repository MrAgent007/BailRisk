<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM agencies ORDER BY name");
    $stmt->execute();
    $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'agencies' => $agencies]);
} catch (Exception $e) {
    error_log("Error fetching agencies: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
exit;
?>
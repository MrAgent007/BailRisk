<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

$defendantId = filter_var($_GET['defendant_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$markSeen = filter_var($_GET['mark_seen'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant' || !$defendantId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
    exit;
}

try {
    if ($markSeen) {
        $stmt = $pdo->prepare("UPDATE travel_requests SET seen = 1 WHERE defendant_id = ? AND seen = 0");
        $stmt->execute([$defendantId]);
    }
    
    $stmt = $pdo->prepare("SELECT request_date, destination, purpose, start_date, end_date, status FROM travel_requests WHERE defendant_id = ? AND seen = 0");
    $stmt->execute([$defendantId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $requests]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
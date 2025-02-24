<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$agentId = $_SESSION['user_id'];
$defendantId = $_POST['defendant_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$method = $_POST['method'] ?? null;
$date = $_POST['date'] ?? null;

if (!$defendantId || !$amount || !$method || !$date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM agents WHERE id = ?");
    $stmt->execute([$agentId]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
        exit;
    }
    $agentName = $agent['name'];

    $stmt = $pdo->prepare("SELECT bail_due FROM defendants WHERE id = ?");
    $stmt->execute([$defendantId]);
    $defendant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$defendant) {
        echo json_encode(['success' => false, 'message' => 'Defendant not found']);
        exit;
    }
    $currentBailDue = floatval($defendant['bail_due']);

    $stmt = $pdo->prepare("
        INSERT INTO payments (defendant_id, agent_id, agent_name, amount, method, date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$defendantId, $agentId, $agentName, $amount, $method, $date]);

    $newBailDue = $currentBailDue - floatval($amount);
    $stmt = $pdo->prepare("UPDATE defendants SET bail_due = ? WHERE id = ?");
    $stmt->execute([$newBailDue, $defendantId]);

    echo json_encode(['success' => true, 'message' => 'Payment added successfully']);
} catch (Exception $e) {
    error_log("Add payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
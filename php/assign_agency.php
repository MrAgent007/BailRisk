<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '../logs/assign_agency.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Assign agency script started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent' || !$_SESSION['is_admin']) {
    logMessage("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$agentId = filter_var($_POST['agent_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$agencyName = filter_var($_POST['agency_name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$agentId || !$agencyName) {
    logMessage("Validation failed: Missing agent_id or agency_name");
    echo json_encode(['success' => false, 'message' => 'Agent ID and Agency Name are required']);
    exit;
}

// Verify agent exists
$stmt = $pdo->prepare("SELECT id FROM agents WHERE id = ?");
$stmt->execute([$agentId]);
if (!$stmt->fetch()) {
    logMessage("Agent not found: $agentId");
    echo json_encode(['success' => false, 'message' => 'Agent not found']);
    exit;
}

// Verify agency exists
$stmt = $pdo->prepare("SELECT name FROM agencies WHERE name = ?");
$stmt->execute([$agencyName]);
if (!$stmt->fetch()) {
    logMessage("Agency not found: $agencyName");
    echo json_encode(['success' => false, 'message' => 'Agency not found']);
    exit;
}

// Update agency_name
try {
    $stmt = $pdo->prepare("UPDATE agents SET agency_name = ? WHERE id = ?");
    $stmt->execute([$agencyName, $agentId]);
    logMessage("Agent $agentId assigned to agency $agencyName");
    echo json_encode(['success' => true, 'message' => 'Agency assigned successfully']);
} catch (Exception $e) {
    logMessage("Assignment failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
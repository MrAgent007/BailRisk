<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '../logs/assign_defendant.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Assign defendant script started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$defendantId = filter_var($_POST['defendant_id'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
$agentId = filter_var($_POST['agent_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);

if (!$defendantId || !$agentId) {
    logMessage("Validation failed: Missing defendant_id or agent_id");
    echo json_encode(['success' => false, 'message' => 'Defendant ID and Agent ID are required']);
    exit;
}

// Verify defendant exists and belongs to the agency (unless admin)
$stmt = $pdo->prepare($_SESSION['is_admin'] ? 
    "SELECT id FROM defendants WHERE user_id = ?" : 
    "SELECT id FROM defendants WHERE user_id = ? AND agency_name = ?");
if ($_SESSION['is_admin']) {
    $stmt->execute([$defendantId]);
} else {
    $stmt->execute([$defendantId, $_SESSION['agency_name']]);
}
if (!$stmt->fetch()) {
    logMessage("Defendant not found or not in agency: $defendantId");
    echo json_encode(['success' => false, 'message' => 'Defendant not found or not in your agency']);
    exit;
}

// Verify agent exists and is in the same agency (unless admin)
$stmt = $pdo->prepare($_SESSION['is_admin'] ? 
    "SELECT id FROM agents WHERE id = ?" : 
    "SELECT id FROM agents WHERE id = ? AND agency_name = ?");
if ($_SESSION['is_admin']) {
    $stmt->execute([$agentId]);
} else {
    $stmt->execute([$agentId, $_SESSION['agency_name']]);
}
if (!$stmt->fetch()) {
    logMessage("Agent not found or not in agency: $agentId");
    echo json_encode(['success' => false, 'message' => 'Agent not found or not in your agency']);
    exit;
}

// Update assigned_agent_id
try {
    $stmt = $pdo->prepare("UPDATE defendants SET assigned_agent_id = ? WHERE user_id = ?");
    $stmt->execute([$agentId, $defendantId]);
    logMessage("Defendant $defendantId assigned to agent $agentId");
    echo json_encode(['success' => true, 'message' => 'Defendant assigned successfully']);
} catch (Exception $e) {
    logMessage("Assignment failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
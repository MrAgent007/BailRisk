<?php
// Start session and suppress any unexpected output
ob_start();
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/agent_dashboard.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Agent dashboard data request started");

// Check session variables
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", user_type=" . ($_SESSION['user_type'] ?? 'unset'));
    ob_end_clean(); // Clear any buffered output
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Fetch agent details
    $stmt = $pdo->prepare("SELECT id, name, email, profile_pic, is_admin, agency_name FROM agents WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        logMessage("Agent not found: ID=" . $_SESSION['user_id']);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
        exit;
    }

    // Fetch defendants for this agent's agency
    $stmt = $pdo->prepare("SELECT id, user_id, name, email, phone, address, case_number, bail_amount, court_date, payments, verified, mugshot, recurring_due_day, next_due_date, checkins FROM defendants WHERE agency_name = ?");
    $stmt->execute([$agent['agency_name']]);
    $defendants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($defendants as &$defendant) {
        $defendant['bail_amount'] = floatval($defendant['bail_amount'] ?? 0);
        $payments = $defendant['payments'] ? json_decode($defendant['payments'], true) : [];
        $totalPayments = array_reduce($payments, function($sum, $p) { return $sum + floatval($p['amount']); }, 0);
        $defendant['bail_due'] = $defendant['bail_amount'] - $totalPayments;
        $defendant['payments'] = $payments;
        $defendant['checkins'] = $defendant['checkins'] ? json_decode($defendant['checkins'], true) : [];
    }

    $data = [
        'agent_id' => $agent['id'],
        'agent_name' => $agent['name'],
        'agent_email' => $agent['email'],
        'agent_profile_pic' => $agent['profile_pic'] ?? '/images/default-profile.jpg',
        'is_admin' => (bool) $agent['is_admin'],
        'agency_name' => $agent['agency_name'],
        'defendants' => $defendants
    ];

    logMessage("Data retrieved successfully for agent ID=" . $agent['id']);
    ob_end_clean(); // Clear buffer before JSON output
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
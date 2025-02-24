<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/travel_requests.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Travel status update started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$requestId = filter_var($input['request_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$status = filter_var($input['status'] ?? '', FILTER_SANITIZE_STRING);

if (!$requestId || !in_array($status, ['Approved', 'Denied', 'Contact Agent'])) {
    logMessage("Invalid request ID or status: ID=$requestId, Status=$status");
    echo json_encode(['success' => false, 'message' => 'Invalid request ID or status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE travel_requests SET status = ? WHERE id = ? AND agent_id = ?");
    $stmt->execute([$status, $requestId, $_SESSION['user_id']]);
    if ($stmt->rowCount() === 0) {
        logMessage("Travel request not found or not owned by agent: ID=$requestId, Agent=" . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'Travel request not found or not in your agency']);
        exit;
    }

    // Update defendant's check-in status if applicable
    $stmt = $pdo->prepare("SELECT defendant_id FROM travel_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $defendantId = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT checkins FROM defendants WHERE id = ?");
    $stmt->execute([$defendantId]);
    $checkins = $stmt->fetchColumn();
    $checkins = $checkins ? json_decode($checkins, true) : [];

    foreach ($checkins as &$checkin) {
        if ($checkin['travel_plans'] === 'Out of State' && $checkin['travel_status'] === 'Pending Approval' && 
            $checkin['travel_details']['destination'] === $_POST['travel_destination']) { // Simplified match
            $checkin['travel_status'] = $status;
        }
    }

    $stmt = $pdo->prepare("UPDATE defendants SET checkins = ? WHERE id = ?");
    $stmt->execute([json_encode($checkins), $defendantId]);

    logMessage("Travel status updated successfully: ID=$requestId, Status=$status");
    echo json_encode(['success' => true, 'message' => 'Travel status updated successfully']);
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
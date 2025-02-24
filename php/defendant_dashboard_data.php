<?php
// File: /public_html/php/defendant_dashboard_data.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/defendant_dashboard.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Defendant dashboard data request started");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    logMessage("Unauthorized access attempt: user_id=unset, user_type=unset");
    echo json_encode(['success' => false, 'message' => 'Permission denied - session not set', 'code' => 403]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$defendantId = $_GET['id'] ?? null;
if ($user_type === 'defendant' && !$defendantId) {
    $defendantId = $user_id;
} elseif ($user_type !== 'agent' && $user_type !== 'defendant') {
    logMessage("Unauthorized access attempt: user_id=$user_id, user_type=$user_type");
    echo json_encode(['success' => false, 'message' => 'Permission denied - not an agent or defendant', 'code' => 403]);
    exit;
}

if (!$defendantId) {
    logMessage("No defendant ID provided for user_id=$user_id");
    echo json_encode(['success' => false, 'message' => 'No defendant ID provided', 'code' => 400]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, user_id, name, email, case_number, court_date, bail_amount, bail_due, next_checkin, mugshot, agency_name 
        FROM defendants 
        WHERE UPPER(user_id) = UPPER(?)");
    $stmt->execute([$defendantId]);
    $defendant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($defendant) {
        // Fetch check-ins from defendant_checkins
        $stmt = $pdo->prepare("
            SELECT date, status, latitude, longitude, selfie_path, employment_status, employer_name, living_situation, current_address, contact_number, travel_plans, comments, ip_address, spoofed 
            FROM defendant_checkins 
            WHERE defendant_id = ? 
            ORDER BY date DESC");
        $stmt->execute([$defendantId]);
        $defendant['checkins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch documents
        $stmt = $pdo->prepare("SELECT name, url FROM defendant_documents WHERE defendant_id = ?");
        $stmt->execute([$defendantId]);
        $defendant['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $defendant['user_name'] = $defendant['name'];
        $defendant['user_email'] = $defendant['email'];
        $defendant['upcoming_checkins'] = $defendant['next_checkin'] && new DateTime($defendant['next_checkin']) <= new DateTime() ? 1 : 0;
        $defendant['next_court_date'] = $defendant['court_date'];
        logMessage("Data sent successfully for defendant_id=$defendantId by user_id=$user_id");
        echo json_encode(['success' => true, 'data' => $defendant]);
    } else {
        logMessage("Defendant not found: defendant_id=$defendantId by user_id=$user_id");
        echo json_encode(['success' => false, 'message' => 'Defendant not found', 'code' => 404]);
    }
} catch (Exception $e) {
    logMessage("Database error for defendant_id=$defendantId by user_id=$user_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'code' => 500]);
}

exit;
?>
<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/travel_requests.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Travel permission request started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$defendantId = filter_var($_POST['defendant_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$employmentStatus = filter_var($_POST['employment_status'] ?? '', FILTER_SANITIZE_STRING);
$employerName = filter_var($_POST['employer_name'] ?? '', FILTER_SANITIZE_STRING);
$livingSituation = filter_var($_POST['living_situation'] ?? '', FILTER_SANITIZE_STRING);
$currentAddress = filter_var($_POST['current_address'] ?? '', FILTER_SANITIZE_STRING);
$contactNumber = filter_var($_POST['contact_number'] ?? '', FILTER_SANITIZE_STRING);
$travelPlans = filter_var($_POST['travel_plans'] ?? '', FILTER_SANITIZE_STRING);
$travelDestination = filter_var($_POST['travel_destination'] ?? '', FILTER_SANITIZE_STRING);
$travelPurpose = filter_var($_POST['travel_purpose'] ?? '', FILTER_SANITIZE_STRING);
$travelStartDate = filter_var($_POST['travel_start_date'] ?? '', FILTER_SANITIZE_STRING);
$travelEndDate = filter_var($_POST['travel_end_date'] ?? '', FILTER_SANITIZE_STRING);
$travelAccommodation = filter_var($_POST['travel_accommodation'] ?? '', FILTER_SANITIZE_STRING);
$comments = filter_var($_POST['comments'] ?? '', FILTER_SANITIZE_STRING);
$latitude = filter_var($_POST['latitude'] ?? '', FILTER_SANITIZE_STRING);
$longitude = filter_var($_POST['longitude'] ?? '', FILTER_SANITIZE_STRING);
$ipAddress = filter_var($_POST['ip_address'] ?? $_SERVER['REMOTE_ADDR'], FILTER_SANITIZE_STRING);

if (!$defendantId || !$employmentStatus || !$livingSituation || !$currentAddress || !$contactNumber || !$travelPlans || !$latitude || !$longitude || !$ipAddress) {
    logMessage("Missing required fields");
    echo json_encode(['success' => false, 'message' => 'Missing required check-in fields']);
    exit;
}

if (!isset($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
    logMessage("Selfie upload failed: " . ($_FILES['selfie']['error'] ?? 'No file'));
    echo json_encode(['success' => false, 'message' => 'Selfie is required']);
    exit;
}

if ($travelPlans === 'Out of State' && (!$travelDestination || !$travelPurpose || !$travelStartDate || !$travelEndDate)) {
    logMessage("Missing travel details for out-of-state request");
    echo json_encode(['success' => false, 'message' => 'Missing required travel details']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT checkins, recurring_due_day, agent_id FROM defendants WHERE id = ?");
    $stmt->execute([$defendantId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        logMessage("Defendant not found: ID=$defendantId");
        echo json_encode(['success' => false, 'message' => 'Defendant not found']);
        exit;
    }

    if (abs(floatval($latitude)) > 90 || abs(floatval($longitude)) > 180) {
        logMessage("Invalid location data: lat=$latitude, lng=$longitude");
        echo json_encode(['success' => false, 'message' => 'Invalid location data']);
        exit;
    }

    $uploadDir = '/home/hillardcorp/public_html/uploads/selfies/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    $selfiePath = $uploadDir . 'selfie_' . $defendantId . '_' . time() . '.' . pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
    move_uploaded_file($_FILES['selfie']['tmp_name'], $selfiePath);
    $selfieUrl = str_replace('/home/hillardcorp/public_html', '', $selfiePath);

    $checkins = $result['checkins'] ? json_decode($result['checkins'], true) : [];
    if (!is_array($checkins)) $checkins = [];

    $newCheckin = [
        'date' => date('Y-m-d H:i:s'),
        'status' => 'Completed',
        'employment_status' => $employmentStatus,
        'employer_name' => $employerName,
        'living_situation' => $livingSituation,
        'current_address' => $currentAddress,
        'contact_number' => $contactNumber,
        'travel_plans' => $travelPlans,
        'travel_status' => 'Pending Approval',
        'comments' => $comments,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'ip_address' => $ipAddress,
        'selfie_url' => $selfieUrl,
        'travel_details' => [
            'destination' => $travelDestination,
            'purpose' => $travelPurpose,
            'start_date' => $travelStartDate,
            'end_date' => $travelEndDate,
            'accommodation' => $travelAccommodation
        ]
    ];

    $checkins[] = $newCheckin;

    $nextDueDate = $result['recurring_due_day'] ? date('Y-m-d', strtotime("next " . $result['recurring_due_day'])) : null;
    $stmt = $pdo->prepare("UPDATE defendants SET checkins = ?, next_due_date = ? WHERE id = ?");
    $stmt->execute([json_encode($checkins), $nextDueDate, $defendantId]);

    // Log travel request
    $stmt = $pdo->prepare("INSERT INTO travel_requests (defendant_id, agent_id, request_date, destination, purpose, start_date, end_date, accommodation, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([
        $defendantId,
        $result['agent_id'],
        date('Y-m-d H:i:s'),
        $travelDestination,
        $travelPurpose,
        $travelStartDate,
        $travelEndDate,
        $travelAccommodation
    ]);

    // Notify agent
    $agentStmt = $pdo->prepare("INSERT INTO notifications (agent_id, message, timestamp) VALUES (?, ?, ?)");
    $agentStmt->execute([$result['agent_id'], "Travel permission requested by defendant ID $defendantId to $travelDestination from $travelStartDate to $travelEndDate", date('Y-m-d H:i:s')]);

    logMessage("Travel permission requested successfully for ID=$defendantId");
    echo json_encode(['success' => true, 'message' => 'Check-in submitted, travel permission requested']);
} catch (Exception $e) {
    logMessage("Travel permission request failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Travel permission request failed: ' . $e->getMessage()]);
}

exit;
?>
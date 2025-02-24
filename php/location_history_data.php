<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/location_history.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Location history data request started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", user_type=" . ($_SESSION['user_type'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$defendantId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$defendantId) {
    logMessage("No defendant ID provided");
    echo json_encode(['success' => false, 'message' => 'No defendant ID provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT checkins FROM defendants WHERE id = ? AND agency_name = ?");
    $stmt->execute([$defendantId, $_SESSION['agency_name']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        logMessage("Defendant not found or not in agency: ID=$defendantId, Agency=" . $_SESSION['agency_name']);
        echo json_encode(['success' => false, 'message' => 'Defendant not found or not in your agency']);
        exit;
    }

    $checkins = $result['checkins'] ? json_decode($result['checkins'], true) : [];
    if (!is_array($checkins)) $checkins = [];

    // Calculate most known locations
    $locationCounts = [];
    foreach ($checkins as $checkin) {
        $key = $checkin['latitude'] . ',' . $checkin['longitude'];
        $locationCounts[$key] = ($locationCounts[$key] ?? 0) + 1;
    }
    $knownLocations = array_map(function($key, $count) {
        [$lat, $lng] = explode(',', $key);
        return ['latitude' => $lat, 'longitude' => $lng, 'count' => $count];
    }, array_keys($locationCounts), $locationCounts);
    usort($knownLocations, function($a, $b) { return $b['count'] - $a['count']; });

    $data = [
        'agent_name' => $_SESSION['user_name'],
        'agent_profile_pic' => '/images/default-profile.jpg',
        'checkins' => $checkins,
        'known_locations' => array_slice($knownLocations, 0, 5) // Top 5 locations
    ];

    logMessage("Data retrieved successfully for defendant ID=$defendantId");
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
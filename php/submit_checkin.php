<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant') {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$defendantId = $_POST['defendant_id'] ?? null;
$date = date('Y-m-d H:i:s'); // Server timestamp
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$employmentStatus = $_POST['employment_status'] ?? null;
$employerName = $_POST['employer_name'] ?? null;
$livingSituation = $_POST['living_situation'] ?? null;
$currentAddress = $_POST['current_address'] ?? null;
$contactNumber = $_POST['contact_number'] ?? null;
$travelPlans = $_POST['travel_plans'] ?? null;
$comments = $_POST['comments'] ?? null;
$ipAddress = $_POST['ip_address'] ?? $_SERVER['REMOTE_ADDR'];
$spoofed = 0; // Default

if (!$defendantId || !$latitude || !$longitude || !$employmentStatus || !$livingSituation || !$currentAddress || !$contactNumber || !$travelPlans) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM defendants WHERE id = ? AND user_id = ?");
    $stmt->execute([$defendantId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid defendant ID']);
        exit;
    }

    $selfieUrl = null;
    if (isset($_FILES['selfie']) && $_FILES['selfie']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '/uploads/selfies/';
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadDir)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadDir, 0777, true);
        }
        $selfieName = 'selfie_' . $defendantId . '_' . time() . '.' . pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
        $selfiePath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . $selfieName;
        if (move_uploaded_file($_FILES['selfie']['tmp_name'], $selfiePath)) {
            $selfieUrl = $uploadDir . $selfieName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Selfie upload failed']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Selfie required']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO defendant_checkins (
            defendant_id, date, latitude, longitude, status, employment_status, employer_name, 
            living_situation, current_address, contact_number, travel_plans, comments, ip_address, spoofed, selfie_url
        ) VALUES (?, ?, ?, ?, 'Completed', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $defendantId, $date, $latitude, $longitude, $employmentStatus, $employerName,
        $livingSituation, $currentAddress, $contactNumber, $travelPlans, $comments, $ipAddress, $spoofed, $selfieUrl
    ]);

    error_log("Check-in recorded: defendant_id=$defendantId, date=$date");
    echo json_encode(['success' => true, 'message' => 'Check-in recorded successfully']);
} catch (Exception $e) {
    error_log("Check-in error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
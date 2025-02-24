<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/add_defendant.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Add defendant request started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);
$address = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_STRING);
$caseNumber = filter_var($_POST['case_number'] ?? '', FILTER_SANITIZE_STRING);
$bailAmount = filter_var($_POST['bail_amount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$courtDate = filter_var($_POST['court_date'] ?? '', FILTER_SANITIZE_STRING);

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("Missing or invalid required fields: name=$name, email=$email");
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        logMessage("Agent not found: ID=" . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
        exit;
    }

    $userId = uniqid('defendant_');
    $password = password_hash('default_password', PASSWORD_DEFAULT); // Placeholder, should be sent to user
    $stmt = $pdo->prepare("INSERT INTO defendants (user_id, name, email, phone, address, case_number, bail_amount, court_date, password, verified, agency_name, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
    $stmt->execute([$userId, $name, $email, $phone, $address, $caseNumber, $bailAmount, $courtDate, $password, $_SESSION['agency_name'], $_SESSION['user_id']]);

    logMessage("Defendant added successfully: Email=$email");
    echo json_encode(['success' => true, 'message' => 'Defendant added successfully']);
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
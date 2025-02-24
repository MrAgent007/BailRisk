<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/record_payment.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Record payment request started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$defendantId = filter_var($_POST['defendant_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$amount = filter_var($_POST['amount'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$paymentDate = filter_var($_POST['payment_date'] ?? '', FILTER_SANITIZE_STRING);

if (!$defendantId || !$amount || !$paymentDate) {
    logMessage("Missing required fields: defendant_id=$defendantId, amount=$amount, payment_date=$paymentDate");
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT payments, agency_name FROM defendants WHERE id = ?");
    $stmt->execute([$defendantId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result || $result['agency_name'] !== $_SESSION['agency_name']) {
        logMessage("Defendant not found or not in agency: ID=$defendantId, Agency=" . $_SESSION['agency_name']);
        echo json_encode(['success' => false, 'message' => 'Defendant not found or not in your agency']);
        exit;
    }

    $payments = $result['payments'] ? json_decode($result['payments'], true) : [];
    if (!is_array($payments)) $payments = [];

    $newPayment = [
        'amount' => $amount,
        'date' => $paymentDate
    ];
    $payments[] = $newPayment;

    $stmt = $pdo->prepare("UPDATE defendants SET payments = ? WHERE id = ?");
    $stmt->execute([json_encode($payments), $defendantId]);

    logMessage("Payment recorded successfully for defendant ID=$defendantId");
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
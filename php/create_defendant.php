<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['agent', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$agentId = $_POST['agent_id'] ?? $_SESSION['user_id'];
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;
$phone = $_POST['phone'] ?? null;
$address = $_POST['address'] ?? null;
$caseNumber = $_POST['case_number'] ?? null;
$courtDate = $_POST['court_date'] ?? null;
$bailAmount = $_POST['bail_amount'] ?? null;
$recurringAmount = $_POST['recurring_amount'] ?? null;
$recurringDueDay = $_POST['recurring_due_day'] ?? null;
$reminders = $_POST['reminders'] ?? '0';

if (!$name || !$email || !$password || !$phone || !$address || !$caseNumber || !$courtDate || !$bailAmount) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Fetch agent's details
    $stmt = $pdo->prepare("SELECT agency_name, name FROM agents WHERE id = ?");
    $stmt->execute([$agentId]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
        exit;
    }
    $agencyName = $agent['agency_name'];
    $agentName = $agent['name'];

    // Generate shorter unique user_id (e.g., DEF + 8-digit timestamp suffix + 3-digit random)
    $userId = 'DEF' . substr(time(), -8) . rand(100, 999); // e.g., DEF700523451234

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Calculate next due date if recurring payment is set
    $nextDueDate = $recurringDueDay ? date('Y-m-d', strtotime("next month +" . ($recurringDueDay - date('d')) . " days")) : null;

    // Insert new defendant (without username)
    $stmt = $pdo->prepare("
        INSERT INTO defendants (
            user_id, name, email, password, phone, address, case_number, court_date, bail_amount, bail_due,
            agent_id, agent_name, agency_name, recurring_amount, recurring_due_day, reminders, next_due_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, $name, $email, $hashedPassword, $phone, $address, $caseNumber, $courtDate, $bailAmount, $bailAmount,
        $agentId, $agentName, $agencyName, $recurringAmount, $recurringDueDay, $reminders, $nextDueDate
    ]);

    echo json_encode(['success' => true, 'message' => 'Defendant created successfully']);
} catch (Exception $e) {
    file_put_contents('debug.log', "Error: " . $e->getMessage() . "\nSQL: " . $stmt->queryString . "\nParams: " . json_encode([$userId, $name, $email, $hashedPassword, $phone, $address, $caseNumber, $courtDate, $bailAmount, $bailAmount, $agentId, $agentName, $agencyName, $recurringAmount, $recurringDueDay, $reminders, $nextDueDate]) . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
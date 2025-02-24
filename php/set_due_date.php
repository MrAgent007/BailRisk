<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = [];
parse_str(file_get_contents('php://input'), $input);
$defendantId = filter_var($input['defendant_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$recurringDueDay = filter_var($input['recurring_due_day'] ?? '', FILTER_SANITIZE_STRING);

if (!$defendantId || !$recurringDueDay) {
    echo json_encode(['success' => false, 'message' => 'Invalid defendant ID or due day']);
    exit;
}

$validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
if (!in_array($recurringDueDay, $validDays)) {
    echo json_encode(['success' => false, 'message' => 'Invalid recurring due day']);
    exit;
}

try {
    $nextDueDate = date('Y-m-d', strtotime("next $recurringDueDay"));
    $stmt = $pdo->prepare("UPDATE defendants SET recurring_due_day = ?, next_due_date = ? WHERE id = ? AND agency_name = ?");
    $stmt->execute([$recurringDueDay, $nextDueDate, $defendantId, $_SESSION['agency_name']]);
    echo json_encode(['success' => true, 'message' => 'Due date set successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Due date setting failed: ' . $e->getMessage()]);
}

exit;
?>
<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$useGetId = isset($_GET['id']);
$defendantId = $useGetId ? $_GET['id'] : $_SESSION['user_id'];
$column = $useGetId ? 'user_id' : 'id';
error_log("Fetching data for $column: " . $defendantId);

try {
    $stmt = $pdo->prepare("
        SELECT d.*, a.name AS agent_name, a.agency_name
        FROM defendants d
        LEFT JOIN agents a ON d.agent_id = a.id
        WHERE d.$column = ?
    ");
    $stmt->execute([$defendantId]);
    $defendant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$defendant) {
        error_log("No defendant found for $column: " . $defendantId);
        echo json_encode(['success' => false, 'message' => 'Defendant not found']);
        exit;
    }

    error_log("Defendant found - ID: " . $defendant['id'] . ", User ID: " . $defendant['user_id']);

    $stmt = $pdo->prepare("
        SELECT p.date, p.amount, p.method, p.agent_name
        FROM payments p
        WHERE p.defendant_id = ?
        ORDER BY p.date DESC
    ");
    $stmt->execute([$defendant['id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $defendant['payments'] = $payments;

    $stmt = $pdo->prepare("
        SELECT * FROM defendant_checkins WHERE defendant_id = ? ORDER BY date DESC
    ");
    $stmt->execute([$defendant['id']]);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $defendant['checkins'] = $checkins;

    error_log("Check-ins fetched: " . count($checkins) . " records");

    echo json_encode(['success' => true, 'data' => ['defendant' => $defendant]]);
} catch (Exception $e) {
    error_log("Profile data error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
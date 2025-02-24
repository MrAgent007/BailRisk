<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$agency_id = $_POST['agency_id'] ?? '';
$license = $_POST['license'] ?? '';

if (empty($name) || empty($email) || empty($agency_id) || empty($license)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT name FROM agencies WHERE id = ? AND approved = 1");
    $stmt->execute([$agency_id]);
    $agency_name = $stmt->fetchColumn();

    if (!$agency_name) {
        echo json_encode(['success' => false, 'message' => 'Agency not found or not approved']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO agents (name, email, license, agency_id, agency_name, verified) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$name, $email, $license, $agency_id, $agency_name]);
    echo json_encode(['success' => true, 'message' => 'Agent added successfully']);
} catch (Exception $e) {
    error_log("Agent add error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>
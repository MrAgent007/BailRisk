<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$license = $_POST['license'] ?? '';
$agency_name = $_POST['agency_name'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($license) || empty($agency_name)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Check if agency exists or create pending
    $stmt = $pdo->prepare("SELECT id FROM agencies WHERE name = ? AND approved = 1");
    $stmt->execute([$agency_name]);
    $agency_id = $stmt->fetchColumn();

    if (!$agency_id) {
        $stmt = $pdo->prepare("INSERT INTO agencies (name, approved) VALUES (?, 0) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
        $stmt->execute([$agency_name]);
        $agency_id = $pdo->lastInsertId();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO agents (name, email, password, license, agency_id, agency_name, verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$name, $email, $hashed_password, $license, $agency_id, $agency_name]);

    echo json_encode(['success' => true, 'message' => 'Agent registered, pending agency approval']);
} catch (Exception $e) {
    error_log("Agent registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
exit;
?>
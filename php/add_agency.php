<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = $_POST['name'] ?? '';

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Agency name is required']);
    exit;
}

try {
    // Check if agency name already exists
    $stmt = $pdo->prepare("SELECT id FROM agencies WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Agency name already exists']);
        exit;
    }

    // Insert new agency, approved by default
    $stmt = $pdo->prepare("INSERT INTO agencies (name, approved) VALUES (?, 1)");
    $stmt->execute([$name]);
    echo json_encode(['success' => true, 'message' => 'Agency added successfully']);
} catch (Exception $e) {
    error_log("Agency add error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>
<?php
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_GET['token'])) {
    echo json_encode(['success' => false, 'message' => 'No verification token provided']);
    exit;
}

$token = $_GET['token'];

try {
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE verification_token = ? AND verified = 0");
    $stmt->execute([$token]);
    $agent = $stmt->fetch();

    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired verification token']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE agents SET verified = 1, verification_token = NULL WHERE id = ?");
    $stmt->execute([$agent['id']]);

    // Redirect to login with a success message
    header('Location: /login.html?verified=true');
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
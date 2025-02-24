<?php
// File: /public_html/php/profile_management_data.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $data = [
        'user_name' => $_SESSION['user_name'],
        'profile_pic' => '/images/default-profile.jpg',
        'defendants' => [],
        'agents' => []
    ];

    $stmt = $pdo->query("SELECT id, user_id, name, email, username, phone, address, case_number, bail_amount, court_date FROM defendants");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['defendants'][] = array_merge($row, ['type' => 'defendant']);
    }

    $stmt = $pdo->query("SELECT id, name, email, username, phone, address, license FROM agents");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['agents'][] = array_merge($row, ['type' => 'agent']);
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
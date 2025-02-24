<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/admin_dashboard.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Admin dashboard data request started");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent' || !$_SESSION['is_admin']) {
    logMessage("Unauthorized access attempt: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", user_type=" . ($_SESSION['user_type'] ?? 'unset'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $data = [
        'user_name' => $_SESSION['user_name'],
        'user_id' => $_SESSION['user_id'],
        'user_email' => $_SESSION['user_email'],
        'agency_name' => $_SESSION['agency_name'],
        'license' => '',
        'subscription' => 'Active',
        'profile_pic' => '/images/default-profile.jpg',
        'defendants' => [],
        'agents' => [],
        'pending_checkins' => 0,
        'missed_checkins' => 0,
        'total_bail' => 0,
        'agencies' => [],
        'notifications' => [],
        'audit_logs' => [],
        'uptime' => '99.9%',
        'compliance_rate' => 95,
        'avg_risk_score' => 3.5,
        'auto_reminders' => false,
        'active_defendants' => 0,
        'suspended_defendants' => 0,
        'active_agents' => 0,
        'pending_agents' => 0
    ];

    $stmt = $pdo->prepare("SELECT license FROM agents WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['license'] = $admin['license'] ?? '';

    // Updated defendants query to include agent_name and agency_name
    $stmt = $pdo->query("
        SELECT d.id, d.user_id, d.name, d.email, d.username, d.phone, d.address, d.case_number, 
               d.bail_amount, d.court_date, d.notes, d.payments, d.verified, 
               a.name AS agent_name, a.agency_name
        FROM defendants d
        LEFT JOIN agents a ON d.agent_id = a.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['bail_amount'] = floatval($row['bail_amount'] ?? 0);
        $row['bail_due'] = $row['bail_amount']; // Placeholder
        $data['defendants'][] = $row;
        if ($row['verified']) $data['active_defendants']++;
        else $data['suspended_defendants']++;
    }
    $data['total_bail'] = array_sum(array_column($data['defendants'], 'bail_amount'));

    $stmt = $pdo->query("SELECT id, name, email, license, agency_name, verified FROM agents");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($agents as $agent) {
        $data['agents'][] = $agent;
        if ($agent['verified']) $data['active_agents']++;
        else $data['pending_agents']++;
    }

    $data['notifications'] = [
        ['id' => 1, 'content' => 'System update scheduled', 'timestamp' => '2025-02-22 08:00:00']
    ];

    $data['audit_logs'] = [
        ['action' => 'User login', 'user' => 'Admin', 'timestamp' => '2025-02-22 07:00:00']
    ];

    // Populate agencies from agents table (assuming agency_name is unique)
    $stmt = $pdo->query("SELECT DISTINCT agency_name AS name FROM agents WHERE agency_name IS NOT NULL ORDER BY agency_name");
    $data['agencies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data['agencies'] as $index => $agency) {
        $agency['id'] = $index + 1; // Assign a pseudo-ID
    }

    echo json_encode(['success' => true, 'data' => $data]);
    logMessage("Data sent successfully");
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
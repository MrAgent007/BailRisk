<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');
if (!$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$name = $_POST['name'];
$email = $_POST['email'];
$agent_id = $_POST['agent_id'];
$agency_id = $_POST['agency_id'];
$bail_amount = $_POST['bail_amount'];
$stmt = $pdo->prepare("SELECT name, agency_name FROM agents WHERE id = ?");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
$agent_name = $agent['name'];
$agency_name = $agent['agency_name'];
$stmt = $pdo->prepare("
    INSERT INTO defendants (name, email, agent_id, agent_name, agency_id, agency_name, bail_amount, verified)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
");
$stmt->execute([$name, $email, $agent_id, $agent_name, $agency_id, $agency_name, $bail_amount]);
echo json_encode(['success' => true, 'message' => 'Defendant added']);
exit;
?>
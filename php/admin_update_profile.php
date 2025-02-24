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
$stmt = $pdo->prepare("UPDATE agents SET name = ?, email = ? WHERE id = ?");
$stmt->execute([$name, $email, $_SESSION['user_id']]);
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;
echo json_encode(['success' => true, 'message' => 'Profile updated']);
exit;
?>
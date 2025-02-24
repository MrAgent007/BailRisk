<?php
// File: /public_html/php/login.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = filter_var($_POST['userId'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, verified, is_admin, agency_name FROM agents WHERE email = ?");
    $stmt->execute([$email]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent) {
        if (!password_verify($password, $agent['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }
        if (!$agent['verified']) {
            echo json_encode(['success' => false, 'message' => 'Account not verified. Check your email']);
            exit;
        }
        $_SESSION['user_id'] = $agent['id'];
        $_SESSION['user_name'] = $agent['name'];
        $_SESSION['user_email'] = $agent['email'];
        $_SESSION['user_type'] = 'agent';
        $_SESSION['is_admin'] = (int) $agent['is_admin'];
        $_SESSION['agency_name'] = $agent['agency_name'] ?? 'Test Agency';
        $redirect = $_SESSION['is_admin'] ? '/admin-dashboard.html' : '/agent-dashboard.html';
        echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => $redirect]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, user_id, name, email, password, verified, agent_id, agency_name FROM defendants WHERE email = ?");
    $stmt->execute([$email]);
    $defendant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($defendant) {
        if (!password_verify($password, $defendant['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }
        if (!$defendant['verified']) {
            echo json_encode(['success' => false, 'message' => 'Account not verified. Contact your agent']);
            exit;
        }
        $_SESSION['user_id'] = $defendant['id'];
        $_SESSION['user_name'] = $defendant['name'];
        $_SESSION['user_email'] = $defendant['email'];
        $_SESSION['user_type'] = 'defendant';
        $_SESSION['agent_id'] = $defendant['agent_id'];
        $_SESSION['agency_name'] = $defendant['agency_name'] ?? 'Test Agency';
        echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => '/defendant-dashboard.html']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
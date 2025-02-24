<?php
require_once 'db_config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id FROM agents WHERE verification_token = ? AND verified = 0");
    $stmt->execute([$token]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent) {
        $stmt = $pdo->prepare("UPDATE agents SET verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$agent['id']]);
        echo "<h1>Account Verified</h1><p>Your account is now verified. You can <a href='/login.html'>login here</a>.</p>";
    } else {
        echo "<h1>Invalid or Expired Token</h1><p>Please contact support at support@bailsafe.com.</p>";
    }
} else {
    echo "<h1>No Token Provided</h1><p>Please use the link from your verification email.</p>";
}
exit;
?>
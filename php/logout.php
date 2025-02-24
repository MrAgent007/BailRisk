<?php
// File: /public_html/php/logout.php
session_start();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
exit;
?>
<?php
// File: /public_html/php/export_all_data.php
session_start();
require_once 'db_config.php';

if (!$_SESSION['is_admin']) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

try {
    $csv = "ID,UserID,Name,Email,Username\n";
    $stmt = $pdo->query("SELECT id, user_id, name, email, username FROM defendants");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $csv .= "{$row['id']},{$row['user_id']},{$row['name']},{$row['email']},{$row['username']}\n";
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bailsafe_data.csv"');
    echo $csv;
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Export failed: " . $e->getMessage();
}

exit;
?>
<?php
// File: /public_html/php/upload_doc.php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'defendant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_FILES['doc']) || $_FILES['doc']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$userId = $_POST['user_id'] ?? $_SESSION['user_id'];
$uploadDir = '/home/hillardcorp/public_html/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = uniqid() . '-' . basename($_FILES['doc']['name']);
$targetPath = $uploadDir . $fileName;

try {
    if (move_uploaded_file($_FILES['doc']['tmp_name'], $targetPath)) {
        // Placeholder: Log document (real implementation needs a documents table)
        echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload document']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
}

exit;
?>
<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent' || !$_SESSION['is_admin']) {
    header("Location: /login.html");
    exit;
}

$defendant_id = $_GET['defendant_id'] ?? '';
if (!$defendant_id) {
    die("No defendant ID provided.");
}

// Fetch defendant data
$stmt = $pdo->prepare("
    SELECT d.id, d.name, d.email, d.username, d.phone, d.address, d.case_number, 
           d.bail_amount, d.court_date, d.notes, d.payments, d.verified, 
           a.name AS agent_name, a.agency_name
    FROM defendants d
    LEFT JOIN agents a ON d.agent_id = a.id
    WHERE d.id = ?
");
$stmt->execute([$defendant_id]);
$defendant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$defendant) {
    die("Defendant not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defendant Dashboard - BailSafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; background: #f5f8fa; color: #2c3e50; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        h2 { font-size: 24px; font-weight: 600; margin-bottom: 20px; }
        .detail-group { margin-bottom: 15px; }
        .detail-group label { font-weight: 600; margin-right: 10px; }
        .detail-group span { font-size: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; background: #007bff; color: #ffffff; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-user-tie"></i> Defendant Details</h2>
        <div class="detail-group">
            <label>ID:</label><span><?php echo htmlspecialchars($defendant['id']); ?></span>
        </div>
        <div class="detail-group">
            <label>Name:</label><span><?php echo htmlspecialchars($defendant['name']); ?></span>
        </div>
        <div class="detail-group">
            <label>Email:</label><span><?php echo htmlspecialchars($defendant['email']); ?></span>
        </div>
        <div class="detail-group">
            <label>Username:</label><span><?php echo htmlspecialchars($defendant['username'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Phone:</label><span><?php echo htmlspecialchars($defendant['phone'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Address:</label><span><?php echo htmlspecialchars($defendant['address'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Case Number:</label><span><?php echo htmlspecialchars($defendant['case_number'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Bail Amount:</label><span>$<?php echo number_format($defendant['bail_amount'], 2); ?></span>
        </div>
        <div class="detail-group">
            <label>Court Date:</label><span><?php echo htmlspecialchars($defendant['court_date'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Notes:</label><span><?php echo htmlspecialchars($defendant['notes'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Payments:</label><span><?php echo htmlspecialchars($defendant['payments'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Agent:</label><span><?php echo htmlspecialchars($defendant['agent_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Agency:</label><span><?php echo htmlspecialchars($defendant['agency_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="detail-group">
            <label>Status:</label><span><?php echo $defendant['verified'] ? 'Active' : 'Suspended'; ?></span>
        </div>
        <a href="/admin-dashboard.html" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
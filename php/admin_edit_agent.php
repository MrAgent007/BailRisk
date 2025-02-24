<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'agent' || !$_SESSION['is_admin']) {
    header("Location: /login.html");
    exit;
}

$agent_id = $_GET['agent_id'] ?? '';
if (!$agent_id) {
    die("No agent ID provided.");
}

// Fetch agent data
$stmt = $pdo->prepare("SELECT id, name, email, agency_name, license, verified FROM agents WHERE id = ?");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$agent) {
    die("Agent not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? $agent['name'];
    $email = $_POST['email'] ?? $agent['email'];
    $agency_name = $_POST['agency_name'] ?? $agent['agency_name'];
    $license = $_POST['license'] ?? $agent['license'];
    $verified = isset($_POST['verified']) ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE agents 
        SET name = ?, email = ?, agency_name = ?, license = ?, verified = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $agency_name, $license, $verified, $agent_id]);

    header("Location: /admin-dashboard.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Agent - BailSafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; background: #f5f8fa; color: #2c3e50; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        h2 { font-size: 24px; font-weight: 600; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d9e6; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .form-group input[type="checkbox"] { width: auto; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; background: #007bff; color: #ffffff; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-users"></i> Edit Agent</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($agent['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($agent['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="agency_name">Agency Name</label>
                <input type="text" id="agency_name" name="agency_name" value="<?php echo htmlspecialchars($agent['agency_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="license">License</label>
                <input type="text" id="license" name="license" value="<?php echo htmlspecialchars($agent['license']); ?>" required>
            </div>
            <div class="form-group">
                <label for="verified">Verified</label>
                <input type="checkbox" id="verified" name="verified" <?php echo $agent['verified'] ? 'checked' : ''; ?>>
            </div>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Update Agent</button>
        </form>
    </div>
</body>
</html>
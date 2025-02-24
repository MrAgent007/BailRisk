<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/signup.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Agent signup request started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Load PHPMailer with correct path
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/PHPMailer.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/SMTP.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/Exception.php';
} catch (Exception $e) {
    logMessage("PHPMailer loading failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: PHPMailer not found']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$dob = $_POST['dob'] ?? '';
$agency_name = filter_var($_POST['agency_name'] ?? '', FILTER_SANITIZE_STRING);
$agency_address = filter_var($_POST['agency_address'] ?? '', FILTER_SANITIZE_STRING);
$agency_phone = $_POST['agency_phone'] ?? '';
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$license = filter_var($_POST['license'] ?? '', FILTER_SANITIZE_STRING);
$password = $_POST['password'] ?? '';

if (!$name || !$dob || !$agency_name || !$agency_address || !$agency_phone || !$email || !$license || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("Missing or invalid required fields: name=$name, dob=$dob, agency_name=$agency_name, agency_address=$agency_address, agency_phone=$agency_phone, email=$email, license=$license");
    echo json_encode(['success' => false, 'message' => 'All fields are required and email must be valid']);
    exit;
}

if (!preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $agency_phone)) {
    logMessage("Invalid phone number format: $agency_phone");
    echo json_encode(['success' => false, 'message' => 'Phone number must be in format XXX-XXX-XXXX']);
    exit;
}

$license_picture = null;
if (isset($_FILES['license_picture']) && $_FILES['license_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '/uploads/licenses/';
    $fullUploadDir = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
    if (!file_exists($fullUploadDir)) {
        if (!mkdir($fullUploadDir, 0777, true)) {
            logMessage("Failed to create upload directory: $fullUploadDir");
            echo json_encode(['success' => false, 'message' => 'Server error: Cannot create upload directory']);
            exit;
        }
    }
    if (!is_writable($fullUploadDir)) {
        logMessage("Upload directory not writable: $fullUploadDir");
        echo json_encode(['success' => false, 'message' => 'Server error: Upload directory not writable']);
        exit;
    }
    $fileExt = pathinfo($_FILES['license_picture']['name'], PATHINFO_EXTENSION);
    $license_picture_name = 'license_' . uniqid() . '.' . $fileExt;
    $license_picture_path = $fullUploadDir . $license_picture_name;
    if (move_uploaded_file($_FILES['license_picture']['tmp_name'], $license_picture_path)) {
        $license_picture = $uploadDir . $license_picture_name;
    } else {
        logMessage("Failed to upload license picture to: $license_picture_path");
        echo json_encode(['success' => false, 'message' => 'Failed to upload license picture']);
        exit;
    }
} else {
    logMessage("License picture upload error: " . ($_FILES['license_picture']['error'] ?? 'No file'));
    echo json_encode(['success' => false, 'message' => 'License picture is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        logMessage("Email already exists: $email");
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    $verification_token = bin2hex(random_bytes(16));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO agents (name, dob, agency_name, agency_address, agency_phone, email, license, password, license_picture, verification_token, verified, is_admin)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
    ");
    $stmt->execute([$name, $dob, $agency_name, $agency_address, $agency_phone, $email, $license, $hashed_password, $license_picture, $verification_token]);

    $agent_id = $pdo->lastInsertId();
    logMessage("Agent signup successful: id=$agent_id, email=$email");

    // Send verification email to agent
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.hillardcorp.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@hillardcorp.com';
        $mail->Password = '-lM=YGz5@g@,';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@hillardcorp.com', 'BailSafe');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('no-reply@hillardcorp.com', 'BailSafe Support');
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your BailSafe Agent Account';
        $verification_link = "https://hillardcorp.com/php/verify_agent.php?token=$verification_token";
        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>BailSafe Verification</title>
            </head>
            <body style='margin: 0; padding: 0; font-family: Poppins, Arial, sans-serif; background-color: #f5f8fa; color: #2c3e50;'>
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f5f8fa; padding: 20px;'>
                    <tr>
                        <td align='center'>
                            <table width='600' cellpadding='0' cellspacing='0' border='0' style='background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);'>
                                <tr>
                                    <td style='background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 20px; text-align: center; border-top-left-radius: 12px; border-top-right-radius: 12px;'>
                                        <h2 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;'>Welcome to BailSafe, $name!</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 30px; text-align: center;'>
                                        <p style='font-size: 18px; color: #34495e; margin: 0 0 20px;'>You’re one step away from accessing your BailSafe agent account.</p>
                                        <p style='font-size: 16px; color: #34495e; margin: 0 0 30px;'>Please verify your account by clicking the button below:</p>
                                        <a href='$verification_link' style='display: inline-block; padding: 14px 30px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);'>Verify Account</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 0 30px 30px; text-align: center;'>
                                        <p style='font-size: 14px; color: #7f8c8d; margin: 0;'>If the button doesn’t work, copy and paste this link into your browser:<br><a href='$verification_link' style='color: #007bff; text-decoration: none;'>$verification_link</a></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background-color: #2c3e50; padding: 20px; text-align: center; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                                        <p style='font-size: 14px; color: #ffffff; margin: 0;'>© 2025 BailSafe. All rights reserved.</p>
                                        <p style='font-size: 12px; color: #bdc3c7; margin: 5px 0 0;'>If you didn’t sign up, please ignore this email.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
        ";
        $mail->AltBody = "Welcome to BailSafe, $name! Please verify your account by visiting this link: $verification_link\nIf you did not sign up, please ignore this email.";

        $mail->send();
        logMessage("Verification email sent to: $email");

        // Send notification email to admin
        $admin_mail = new PHPMailer(true);
        $admin_mail->isSMTP();
        $admin_mail->Host = 'mail.hillardcorp.com';
        $admin_mail->SMTPAuth = true;
        $admin_mail->Username = 'no-reply@hillardcorp.com';
        $admin_mail->Password = '-lM=YGz5@g@,';
        $admin_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $admin_mail->Port = 587;

        $admin_email = 'acostajohny14@gmail.com'; // Admin email updated
        $admin_mail->setFrom('no-reply@hillardcorp.com', 'BailSafe');
        $admin_mail->addAddress($admin_email, 'BailSafe Admin');
        $admin_mail->addReplyTo('no-reply@hillardcorp.com', 'BailSafe Support');
        $admin_mail->isHTML(true);
        $admin_mail->Subject = 'New Agent Signup Notification';
        $admin_mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>BailSafe New Signup</title>
            </head>
            <body style='margin: 0; padding: 0; font-family: Poppins, Arial, sans-serif; background-color: #f5f8fa; color: #2c3e50;'>
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f5f8fa; padding: 20px;'>
                    <tr>
                        <td align='center'>
                            <table width='600' cellpadding='0' cellspacing='0' border='0' style='background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);'>
                                <tr>
                                    <td style='background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 20px; text-align: center; border-top-left-radius: 12px; border-top-right-radius: 12px;'>
                                        <h2 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;'>New Agent Signup</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 30px; text-align: center;'>
                                        <p style='font-size: 18px; color: #34495e; margin: 0 0 20px;'>A new agent has signed up for BailSafe.</p>
                                        <table width='100%' cellpadding='10' cellspacing='0' border='0' style='text-align: left; font-size: 16px; color: #34495e;'>
                                            <tr><td><strong>Name:</strong></td><td>$name</td></tr>
                                            <tr><td><strong>Email:</strong></td><td>$email</td></tr>
                                            <tr><td><strong>Agency:</strong></td><td>$agency_name</td></tr>
                                            <tr><td><strong>License:</strong></td><td>$license</td></tr>
                                        </table>
                                        <p style='font-size: 16px; color: #34495e; margin: 20px 0 0;'>Please review this signup in the admin dashboard.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background-color: #2c3e50; padding: 20px; text-align: center; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                                        <p style='font-size: 14px; color: #ffffff; margin: 0;'>© 2025 BailSafe. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
        ";
        $admin_mail->AltBody = "New Agent Signup Notification\nName: $name\nEmail: $email\nAgency: $agency_name\nLicense: $license\nPlease review this signup in the admin dashboard.";

        $admin_mail->send();
        logMessage("Admin notification email sent for new signup: $email");

        echo json_encode(['success' => true, 'message' => 'Signup successful! Please check your email to verify your account.']);
    } catch (Exception $e) {
        logMessage("Email sending failed: " . $mail->ErrorInfo);
        echo json_encode(['success' => true, 'message' => 'Signup successful, but verification email failed: ' . $mail->ErrorInfo]);
    }
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
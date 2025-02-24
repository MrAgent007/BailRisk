<?php
session_start();
require_once 'db_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/SMTP.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

function logMessage($message) {
    $logFile = '/home/hillardcorp/public_html/logs/signup.log';
    if (!file_exists(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logMessage("Resend verification request started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logMessage("Invalid email: $email");
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, verification_token FROM agents WHERE email = ? AND verified = 0");
    $stmt->execute([$email]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        logMessage("No unverified agent found for email: $email");
        echo json_encode(['success' => false, 'message' => 'No unverified account found for this email']);
        exit;
    }

    $agent_id = $agent['id'];
    $name = $agent['name'];
    $verification_token = $agent['verification_token'] ?: bin2hex(random_bytes(16));

    // Update token if it was null
    if (!$agent['verification_token']) {
        $stmt = $pdo->prepare("UPDATE agents SET verification_token = ? WHERE id = ?");
        $stmt->execute([$verification_token, $agent_id]);
    }

    // Send verification email
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
        logMessage("Verification email resent to: $email");
        echo json_encode(['success' => true, 'message' => 'Verification email resent! Please check your inbox or spam folder.']);
    } catch (Exception $e) {
        logMessage("Email resending failed: " . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification email: ' . $mail->ErrorInfo]);
    }
} catch (Exception $e) {
    logMessage("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
?>
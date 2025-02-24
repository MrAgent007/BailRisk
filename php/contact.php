<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if ($name && $email && $message && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Simulate email sending (replace with actual mail() or SMTP in production)
        $to = "support@bailsafe.com";
        $subject = "Contact Form Submission from $name";
        $body = "Name: $name\nEmail: $email\nMessage: $message";
        // mail($to, $subject, $body); // Uncomment and configure SMTP in production

        echo json_encode(['success' => true, 'message' => 'Thank you! We’ll get back to you soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Please fill out all fields correctly.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit;
?>
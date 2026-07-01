<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$name    = input('name',    true);
$email   = input('email',   false) ?? input('mail', true);   // index.html uses "mail"
$phone   = input('phone');
$subject = input('subject');
$message = input('message', true);

if (!$name || !$email || !$message) {
    json_response(false, 'Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Please enter a valid email address.');
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, created_at)
        VALUES (:name, :email, :phone, :subject, :message, :ip, :created_at)
    ");
    $stmt->execute([
        ':name'       => $name,
        ':email'      => $email,
        ':phone'      => $phone,
        ':subject'    => $subject,
        ':message'    => $message,
        ':ip'         => $ip,
        ':created_at' => date('Y-m-d H:i:s'),
    ]);

    send_contact_notification($name, $email, $phone, $subject, $message);
    json_response(true, "Thank you, {$name}! Your message has been sent. We'll be in touch soon.");
} catch (Exception $e) {
    json_response(false, 'Something went wrong. Please try again later.');
}

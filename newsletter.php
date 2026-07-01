<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

// Accept both "email" and "EMAIL" (Brevo/SIB legacy field name)
$email = input('email') ?? input('EMAIL');
$email = $email ? strtolower(trim($email)) : null;

if (!$email) {
    json_response(false, 'Please enter your email address.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Please enter a valid email address.');
}

try {
    $db = get_db();

    // Check if already subscribed
    $check = $db->prepare("SELECT is_active FROM newsletter_subscribers WHERE email = :email");
    $check->execute([':email' => $email]);
    $existing = $check->fetch();

    if ($existing) {
        if ($existing['is_active']) {
            json_response(true, "You're already subscribed — good to have you!");
        }
        // Re-activate unsubscribed email
        $db->prepare("UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = CURRENT_TIMESTAMP WHERE email = :email")
           ->execute([':email' => $email]);
        send_welcome_email($email);
        json_response(true, "Welcome back! You've been re-subscribed.");
    }

    $db->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (:email, :subscribed_at)")
       ->execute([':email' => $email, ':subscribed_at' => date('Y-m-d H:i:s')]);

    send_welcome_email($email);
    json_response(true, "You're subscribed! Thanks for joining.");
} catch (Exception $e) {
    json_response(false, 'Something went wrong. Please try again later.');
}

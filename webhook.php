<?php
require_once __DIR__ . '/db.php';

$events = json_decode(file_get_contents('php://input'), true);
if (!$events) { http_response_code(400); exit; }

// Brevo sends an array of events
if (isset($events['event'])) $events = [$events];

$db = get_db();
foreach ($events as $event) {
    $type  = $event['event'] ?? '';
    $email = strtolower(trim($event['email'] ?? ''));
    if (!$email) continue;

    // Mark unsubscribed or spam-complained emails as inactive
    if (in_array($type, ['unsubscribed', 'complaint'])) {
        $db->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE email = :email")
           ->execute([':email' => $email]);
    }
}

http_response_code(200);
echo 'ok';

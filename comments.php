<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// ── GET — fetch approved comments for a post ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = trim(strip_tags($_GET['post_id'] ?? 'blog-details'));

    try {
        $db   = get_db();
        $stmt = $db->prepare("
            SELECT id, name, message, reply_to, created_at
            FROM   blog_comments
            WHERE  post_id = :post_id AND is_approved = 1
            ORDER  BY created_at ASC
        ");
        $stmt->execute([':post_id' => $post_id]);
        $comments = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'comments' => $comments]);
    } catch (Exception $e) {
        json_response(false, 'Could not load comments.');
    }
    exit;
}

// ── POST — submit a new comment ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot - bots fill every field, real users never see this one.
    if (is_spam()) {
        json_response(true, 'Your comment has been posted!');
    }

    $post_id  = input('post_id') ?? 'blog-details';
    $name     = input('name',    true);
    $email    = input('email',   true);
    $message  = input('message', true);
    $reply_to = input('reply_to');
    $turnstileToken = input('cf-turnstile-response');

    if (!$name || !$email || !$message) {
        json_response(false, 'Please fill in all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'Please enter a valid email address.');
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

    if (!verify_turnstile($turnstileToken, $ip)) {
        json_response(false, 'Please complete the CAPTCHA.');
    }

    if (rate_limited('blog_comments', $ip)) {
        json_response(false, 'Too many comments posted recently. Please try again later.');
    }

    try {
        $db = get_db();
        $now  = date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            INSERT INTO blog_comments (post_id, name, email, message, reply_to, ip_address, created_at)
            VALUES (:post_id, :name, :email, :message, :reply_to, :ip, :created_at)
        ");
        $stmt->execute([
            ':post_id'    => $post_id,
            ':name'       => $name,
            ':email'      => $email,
            ':message'    => $message,
            ':reply_to'   => $reply_to,
            ':ip'         => $ip,
            ':created_at' => $now,
        ]);

        $new_id = $db->lastInsertId();

        json_response(true, 'Your comment has been posted!', [
            'comment' => [
                'id'         => $new_id,
                'name'       => $name,
                'message'    => $message,
                'reply_to'   => $reply_to,
                'created_at' => $now,
            ]
        ]);
    } catch (Exception $e) {
        json_response(false, 'Something went wrong. Please try again later.');
    }
    exit;
}

http_response_code(405);

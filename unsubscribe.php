<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';
$token = $_GET['token'] ?? '';

function valid_token(string $email, string $token): bool {
    return hash_equals(hash_hmac('sha256', $email, UNSUB_SECRET), $token);
}

$error = $done = false;

if ($email && $token && valid_token($email, $token)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
        $db = get_db();
        $db->prepare("UPDATE newsletter_subscribers SET is_active = 0 WHERE email = :email")
           ->execute([':email' => $email]);
        $done = true;
    }
} else {
    $error = true;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Unsubscribe — FlatLab</title>
  <style>
    body{margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
    .box{background:#fff;border-radius:8px;padding:48px;max-width:480px;width:100%;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.08);}
    h1{font-size:22px;color:#111827;margin:0 0 12px;}
    p{font-size:15px;color:#555;line-height:1.7;margin:0 0 24px;}
    .email{font-weight:600;color:#111827;}
    button{background:#dc2626;color:#fff;border:none;padding:12px 28px;border-radius:6px;font-size:15px;cursor:pointer;}
    button:hover{background:#b91c1c;}
    .success{color:#16a34a;font-size:40px;margin-bottom:16px;}
    .error{color:#dc2626;}
    a{color:#555;font-size:13px;}
  </style>
</head>
<body>
<div class="box">
<?php if ($error): ?>
  <p class="error">This unsubscribe link is invalid or has expired.</p>
  <a href="<?= htmlspecialchars(SITE_URL) ?>">Return to FlatLab</a>
<?php elseif ($done): ?>
  <div class="success">✓</div>
  <h1>You've been unsubscribed</h1>
  <p><span class="email"><?= htmlspecialchars($email) ?></span> has been removed from the FlatLab newsletter.</p>
  <a href="<?= htmlspecialchars(SITE_URL) ?>">Return to FlatLab</a>
<?php else: ?>
  <h1>Unsubscribe</h1>
  <p>Are you sure you want to unsubscribe <span class="email"><?= htmlspecialchars($email) ?></span> from the FlatLab newsletter?</p>
  <form method="POST">
    <input type="hidden" name="confirm" value="1">
    <button type="submit">Yes, unsubscribe me</button>
  </form>
  <br><a href="<?= htmlspecialchars(SITE_URL) ?>">No, take me back</a>
<?php endif; ?>
</div>
</body>
</html>

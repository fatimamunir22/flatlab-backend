<?php
require_once __DIR__ . '/config.php';

function send_welcome_email(string $to_email): void {
    $payload = [
        'sender'      => ['name' => SENDER_NAME, 'email' => SENDER_EMAIL],
        'to'          => [['email' => $to_email]],
        'subject'     => 'Welcome to FlatLab — You\'re in!',
        'htmlContent' => _welcome_html(),
        'textContent' => _welcome_text(),
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function _welcome_html(): string {
    $logo_url = SITE_URL . '/images/logo/logo.svg';
    $site_url = SITE_URL;
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;">
        <tr>
          <td style="background:#111827;padding:32px;text-align:center;">
            <img src="{$logo_url}" alt="FlatLab" height="48" style="display:block;margin:0 auto;">
          </td>
        </tr>
        <tr>
          <td style="padding:40px 48px;">
            <h1 style="margin:0 0 16px;font-size:24px;color:#111827;font-weight:700;">Welcome to the FlatLab Newsletter</h1>
            <p style="margin:0 0 16px;font-size:16px;color:#555555;line-height:1.7;">
              Thanks for subscribing! You'll hear from us with a mix of company updates, industry tips and insights, product and service announcements, and exclusive offers.
            </p>
            <p style="margin:0 0 32px;font-size:16px;color:#555555;line-height:1.7;">
              Stay tuned — great things are coming.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f8f8;padding:24px 48px;text-align:center;border-top:1px solid #eeeeee;">
            <p style="margin:0;font-size:13px;color:#999999;">
              &copy; FlatLab &nbsp;&middot;&nbsp;
              <a href="{$site_url}" style="color:#999999;text-decoration:none;">{$site_url}</a>
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function send_contact_notification(string $name, string $email, ?string $phone, ?string $subject, string $message): void {
    $admin_email = getenv('ADMIN_EMAIL') ?: SENDER_EMAIL;
    $subject_line = 'New Contact Form Submission' . ($subject ? ": $subject" : '');
    $payload = [
        'sender'      => ['name' => SENDER_NAME, 'email' => SENDER_EMAIL],
        'to'          => [['email' => $admin_email]],
        'replyTo'     => ['email' => $email, 'name' => $name],
        'subject'     => $subject_line,
        'htmlContent' => _contact_notification_html($name, $email, $phone, $subject, $message),
        'textContent' => _contact_notification_text($name, $email, $phone, $subject, $message),
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function _contact_notification_html(string $name, string $email, ?string $phone, ?string $subject, string $message): string {
    $logo_url = SITE_URL . '/images/logo/logo.svg';
    $name    = htmlspecialchars($name);
    $email   = htmlspecialchars($email);
    $phone   = htmlspecialchars($phone ?? 'N/A');
    $subject = htmlspecialchars($subject ?? 'N/A');
    $message = nl2br(htmlspecialchars($message));
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;">
        <tr>
          <td style="background:#111827;padding:32px;text-align:center;">
            <img src="{$logo_url}" alt="FlatLab" height="48" style="display:block;margin:0 auto;">
          </td>
        </tr>
        <tr>
          <td style="padding:40px 48px;">
            <h1 style="margin:0 0 24px;font-size:22px;color:#111827;font-weight:700;">New Contact Form Submission</h1>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr><td style="padding:8px 0;font-size:14px;color:#888;width:80px;">Name</td><td style="padding:8px 0;font-size:15px;color:#111827;">{$name}</td></tr>
              <tr><td style="padding:8px 0;font-size:14px;color:#888;">Email</td><td style="padding:8px 0;font-size:15px;color:#111827;">{$email}</td></tr>
              <tr><td style="padding:8px 0;font-size:14px;color:#888;">Phone</td><td style="padding:8px 0;font-size:15px;color:#111827;">{$phone}</td></tr>
              <tr><td style="padding:8px 0;font-size:14px;color:#888;">Subject</td><td style="padding:8px 0;font-size:15px;color:#111827;">{$subject}</td></tr>
            </table>
            <hr style="border:none;border-top:1px solid #eeeeee;margin:24px 0;">
            <p style="margin:0 0 8px;font-size:14px;color:#888;">Message</p>
            <p style="margin:0;font-size:15px;color:#333333;line-height:1.7;">{$message}</p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f8f8;padding:24px 48px;text-align:center;border-top:1px solid #eeeeee;">
            <p style="margin:0;font-size:13px;color:#999999;">FlatLab Admin Notification</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function _contact_notification_text(string $name, string $email, ?string $phone, ?string $subject, string $message): string {
    return "New Contact Form Submission\n\n" .
        "Name: $name\nEmail: $email\nPhone: " . ($phone ?? 'N/A') . "\nSubject: " . ($subject ?? 'N/A') . "\n\nMessage:\n$message";
}

function _welcome_text(): string {
    return
        "Welcome to the FlatLab Newsletter!\n\n" .
        "Thanks for subscribing. You'll hear from us with company updates, " .
        "industry tips and insights, product and service announcements, and exclusive offers.\n\n" .
        "Stay tuned — great things are coming.\n\n" .
        "© FlatLab · " . SITE_URL;
}

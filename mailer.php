<?php
require_once __DIR__ . '/config.php';

function send_welcome_email(string $to_email): void {
    _brevo_send(
        to:      $to_email,
        subject: 'Welcome to FlatLab — You\'re in!',
        html:    _email_layout('Welcome to the FlatLab Newsletter',
            '<p style="margin:0 0 16px;font-size:16px;color:#555555;line-height:1.7;">
                Thanks for subscribing! You\'ll hear from us with a mix of company updates, industry tips and insights, product and service announcements, and exclusive offers.
            </p>
            <p style="margin:0;font-size:16px;color:#555555;line-height:1.7;">
                Stay tuned — great things are coming.
            </p>'
        ),
        text:    "Welcome to the FlatLab Newsletter!\n\nThanks for subscribing. You'll hear from us with company updates, industry tips and insights, product and service announcements, and exclusive offers.\n\nStay tuned — great things are coming.\n\n© FlatLab · " . SITE_URL
    );
}

function send_contact_notification(string $name, string $email, ?string $phone, ?string $subject, string $message): void {
    $rows = _detail_rows([
        'Name'    => $name,
        'Email'   => $email,
        'Phone'   => $phone ?? 'N/A',
        'Subject' => $subject ?? 'N/A',
    ]);
    _brevo_send(
        to:       getenv('ADMIN_EMAIL') ?: SENDER_EMAIL,
        subject:  'New Contact Form Submission' . ($subject ? ": $subject" : ''),
        html:     _email_layout('New Contact Form Submission',
            $rows .
            '<hr style="border:none;border-top:1px solid #eeeeee;margin:24px 0;">' .
            '<p style="margin:0 0 8px;font-size:14px;color:#888;">Message</p>' .
            '<p style="margin:0;font-size:15px;color:#333;line-height:1.7;">' . nl2br(htmlspecialchars($message)) . '</p>'
        ),
        text:     "New Contact Form Submission\n\nName: $name\nEmail: $email\nPhone: " . ($phone ?? 'N/A') . "\nSubject: " . ($subject ?? 'N/A') . "\n\nMessage:\n$message",
        reply_to: ['email' => $email, 'name' => $name]
    );
}

// --- Shared helpers ---

function _brevo_send(string $to, string $subject, string $html, string $text, array $reply_to = []): void {
    $payload = [
        'sender'      => ['name' => SENDER_NAME, 'email' => SENDER_EMAIL],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $html,
        'textContent' => $text,
    ];
    if ($reply_to) $payload['replyTo'] = $reply_to;

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
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || (isset(json_decode($response, true)['code']))) {
        error_log('[mailer] Brevo error — ' . ($err ?: $response));
    }
}

function _email_layout(string $title, string $body): string {
    $logo_url = SITE_URL . '/images/logo/logo.svg';
    $site_url = SITE_URL;
    $title    = htmlspecialchars($title);
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
            <h1 style="margin:0 0 24px;font-size:22px;color:#111827;font-weight:700;">{$title}</h1>
            {$body}
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

function _detail_rows(array $fields): string {
    $rows = '<table width="100%" cellpadding="0" cellspacing="0">';
    foreach ($fields as $label => $value) {
        $label = htmlspecialchars($label);
        $value = htmlspecialchars($value);
        $rows .= "<tr>
            <td style=\"padding:8px 0;font-size:14px;color:#888;width:80px;\">{$label}</td>
            <td style=\"padding:8px 0;font-size:15px;color:#111827;\">{$value}</td>
        </tr>";
    }
    return $rows . '</table>';
}

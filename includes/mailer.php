<?php
/* =============================================
   DLMS — Lightweight SMTP Mailer
   Uses PHP streams — no external library needed
   Gmail SMTP with App Password
   ============================================= */

define('SMTP_HOST',     'ssl://smtp.gmail.com');
define('SMTP_PORT',     465);
define('SMTP_USER',     '========GMAIL=======');
define('SMTP_PASS',     'smtp password');
define('SMTP_FROM',     '========GMAIL=======');
define('SMTP_FROM_NAME','DLMS Library');
define('APP_NAME',      'DLMS — Digital Library');
define('APP_URL',       'https://............');

/**
 * Send an email via Gmail SMTP (SSL port 465)
 *
 * @param string $toEmail  Recipient email
 * @param string $toName   Recipient display name
 * @param string $subject  Email subject
 * @param string $htmlBody HTML content
 * @return true|string     true on success, error string on failure
 */
function smtp_send(string $toEmail, string $toName, string $subject, string $htmlBody): true|string
{
    $sock = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$sock) {
        return "Connection failed: $errstr ($errno)";
    }

    $read = function() use ($sock): string {
        $data = '';
        while ($line = fgets($sock, 512)) {
            $data .= $line;
            if ($line[3] === ' ') break; // last line of response
        }
        return $data;
    };

    $cmd = function(string $c) use ($sock, $read): string {
        fputs($sock, $c . "\r\n");
        return $read();
    };

    // Greeting
    $read();

    // EHLO
    $r = $cmd('EHLO smtp.gmail.com');
    if (!str_starts_with($r, '2')) { fclose($sock); return "EHLO failed: $r"; }

    // AUTH LOGIN
    $r = $cmd('AUTH LOGIN');
    if (!str_starts_with($r, '3')) { fclose($sock); return "AUTH failed: $r"; }

    $r = $cmd(base64_encode(SMTP_USER));
    if (!str_starts_with($r, '3')) { fclose($sock); return "User rejected: $r"; }

    $r = $cmd(base64_encode(SMTP_PASS));
    if (!str_starts_with($r, '2')) { fclose($sock); return "Auth rejected: $r"; }

    // MAIL FROM
    $r = $cmd('MAIL FROM:<' . SMTP_FROM . '>');
    if (!str_starts_with($r, '2')) { fclose($sock); return "MAIL FROM failed: $r"; }

    // RCPT TO
    $r = $cmd('RCPT TO:<' . $toEmail . '>');
    if (!str_starts_with($r, '2')) { fclose($sock); return "RCPT TO failed: $r"; }

    // DATA
    $r = $cmd('DATA');
    if (!str_starts_with($r, '3')) { fclose($sock); return "DATA failed: $r"; }

    // Build message
    $boundary = 'dlms_' . md5(uniqid());
    $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    $plainText = preg_replace('/\n{3,}/', "\n\n", $plainText);

    $date    = date('r');
    $msgId   = '<' . uniqid('dlms', true) . '@gmail.com>';
    $fromEnc = '=?UTF-8?B?' . base64_encode(SMTP_FROM_NAME) . '?=';
    $toEnc   = '=?UTF-8?B?' . base64_encode($toName) . '?=';
    $subEnc  = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $msg  = "Date: $date\r\n";
    $msg .= "Message-ID: $msgId\r\n";
    $msg .= "From: $fromEnc <" . SMTP_FROM . ">\r\n";
    $msg .= "To: $toEnc <$toEmail>\r\n";
    $msg .= "Subject: $subEnc\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $msg .= "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $msg .= quoted_printable_encode($plainText) . "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $msg .= quoted_printable_encode($htmlBody) . "\r\n";
    $msg .= "--$boundary--\r\n";
    $msg .= ".\r\n";

    fputs($sock, $msg);
    $r = $read();
    if (!str_starts_with($r, '2')) { fclose($sock); return "Message rejected: $r"; }

    $cmd('QUIT');
    fclose($sock);
    return true;
}

/* ── Email Templates ── */

function mail_otp(string $toEmail, string $toName, string $otp): true|string
{
    $subject = 'Your DLMS Email Verification Code';
    $html = email_layout('Verify Your Email', "
        <p style='margin:0 0 18px;color:#374151;font-size:15px;'>
            Hi <strong>" . htmlspecialchars($toName) . "</strong>,<br><br>
            Use the 6-digit code below to verify your email address and complete registration.
        </p>
        <div style='text-align:center;margin:28px 0;'>
            <div style='display:inline-block;background:#f0f3fb;border:2px dashed #93a5d8;
                        border-radius:14px;padding:18px 40px;'>
                <span style='font-size:38px;font-weight:900;letter-spacing:10px;
                             color:#5e75ba;font-family:monospace;'>" . htmlspecialchars($otp) . "</span>
            </div>
        </div>
        <p style='margin:0 0 10px;color:#6b7280;font-size:13px;text-align:center;'>
            This code expires in <strong>10 minutes</strong>. Do not share it with anyone.
        </p>
    ");
    return smtp_send($toEmail, $toName, $subject, $html);
}

function mail_reset_code(string $toEmail, string $toName, string $code): true|string
{
    $subject = 'Your DLMS Password Reset Code';
    $html = email_layout('Password Reset Code', "
        <p style='margin:0 0 18px;color:#374151;font-size:15px;'>
            Hi <strong>" . htmlspecialchars($toName) . "</strong>,<br><br>
            Use the code below to reset your DLMS password.
            It expires in <strong>5 minutes</strong>.
        </p>
        <div style='text-align:center;margin:28px 0;'>
            <div style='display:inline-block;background:#f0f4ff;border:2px dashed #5e75ba;
                        border-radius:14px;padding:18px 40px;'>
                <span style='font-size:38px;font-weight:900;letter-spacing:10px;
                             color:#5e75ba;font-family:monospace;'>" . $code . "</span>
            </div>
        </div>
        <p style='margin:0 0 8px;color:#6b7280;font-size:13px;text-align:center;'>
            Enter this code on the password reset page.
        </p>
        <p style='margin:0;color:#9ca3af;font-size:12px;text-align:center;'>
            If you didn't request this, you can safely ignore this email.
        </p>
    ");
    return smtp_send($toEmail, $toName, $subject, $html);
}

function email_layout(string $heading, string $body): string
{
    return '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="color-scheme" content="light"></head>
<body style="margin:0;padding:0;font-family:Inter,system-ui,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
<tr><td align="center">
<table width="100%" style="max-width:520px;background:#ffffff;border-radius:18px;
       border:1px solid #e5e7ef;overflow:hidden;box-shadow:0 4px 24px rgba(58,86,212,.08);">

  <!-- Header -->
  <tr>
    <td style="background: #5e75ba;padding:28px 32px;text-align:center;border-radius:16px 16px 0 0;">
      <h1 style="margin:0;color:#fff;font-size:22px;font-weight:900;letter-spacing:-.02em;">
        📚 DLMS
      </h1>
      <p style="margin:6px 0 0;color:rgba(255,255,255,.75);font-size:13px;">Digital Library Management System</p>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="padding:32px 32px 24px;">
      <h2 style="margin:0 0 20px;color:#1a1f36;font-size:19px;font-weight:800;">' . htmlspecialchars($heading) . '</h2>
      ' . $body . '
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#f8f9fd;border-top:1px solid #e5e7ef;padding:18px
    32px;text-align:center;border-radius:0 0 16px 16px;">
      <p style="margin:0;color:#9ca3af;font-size:12px;">
        © ' . date('Y') . ' DLMS — Digital Library Management System<br>
        This is an automated message, please do not reply.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body></html>';
}

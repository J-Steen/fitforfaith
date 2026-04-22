<?php
namespace App\Services;

/**
 * Simple mailer — supports PHP mail() and SMTP (no Composer required).
 * Used for password reset and email verification emails.
 */
class MailService {

    /**
     * Send an email.
     *
     * @param string $to      Recipient email address
     * @param string $subject Email subject
     * @param string $html    HTML body
     * @param string $text    Plain-text fallback (auto-generated if empty)
     */
    public static function send(string $to, string $subject, string $html, string $text = ''): bool {
        if (!$text) $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html));

        if (MAIL_DRIVER === 'smtp') {
            return self::sendSmtp($to, $subject, $html, $text);
        }
        return self::sendMail($to, $subject, $html, $text);
    }

    // ── PHP mail() ─────────────────────────────────────────────

    private static function sendMail(string $to, string $subject, string $html, string $text): bool {
        $from    = MAIL_FROM_ADDRESS;
        $name    = MAIL_FROM_NAME;
        $boundary = 'BOUNDARY_' . md5(uniqid('', true));

        $headers  = "From: {$name} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: FitForFaith/1.0\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $text . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--{$boundary}--";

        $result = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
        if (!$result) {
            app_log("mail() failed for {$to}: " . error_get_last()['message'] ?? 'unknown', 'WARN');
        }
        return $result;
    }

    // ── SMTP ──────────────────────────────────────────────────

    private static function sendSmtp(string $to, string $subject, string $html, string $text): bool {
        $host       = MAIL_HOST;
        $port       = MAIL_PORT;
        $encryption = MAIL_ENCRYPTION;
        $username   = MAIL_USERNAME;
        $password   = MAIL_PASSWORD;
        $from       = MAIL_FROM_ADDRESS;
        $fromName   = MAIL_FROM_NAME;

        // Build connection string
        if ($encryption === 'ssl') {
            $dsn = "ssl://{$host}:{$port}";
        } else {
            $dsn = "tcp://{$host}:{$port}";
        }

        $errno  = 0;
        $errstr = '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);
        $conn = @stream_socket_client($dsn, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$conn) {
            app_log("SMTP connect failed: {$errstr} ({$errno})", 'ERROR');
            return false;
        }

        try {
            self::smtpExpect($conn, 220);

            // EHLO
            self::smtpSend($conn, "EHLO " . gethostname());
            $ehlo = self::smtpReadLines($conn); // read multi-line EHLO response

            // STARTTLS if tls encryption
            if ($encryption === 'tls') {
                self::smtpSend($conn, "STARTTLS");
                self::smtpExpect($conn, 220);
                stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
                self::smtpSend($conn, "EHLO " . gethostname());
                self::smtpReadLines($conn);
            }

            // AUTH LOGIN
            if ($username) {
                self::smtpSend($conn, "AUTH LOGIN");
                self::smtpExpect($conn, 334);
                self::smtpSend($conn, base64_encode($username));
                self::smtpExpect($conn, 334);
                self::smtpSend($conn, base64_encode($password));
                self::smtpExpect($conn, 235);
            }

            // Build message
            $boundary = 'BOUNDARY_' . md5(uniqid('', true));
            $msgId    = '<' . uniqid('', true) . '@' . parse_url(APP_URL, PHP_URL_HOST) . '>';
            $date     = date('r');

            $headers  = "From: {$fromName} <{$from}>\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "Message-ID: {$msgId}\r\n";
            $headers .= "Date: {$date}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "X-Mailer: FitForFaith/1.0\r\n";

            $body  = "\r\n--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $body .= $text . "\r\n";
            $body .= "\r\n--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $body .= $html . "\r\n";
            $body .= "\r\n--{$boundary}--\r\n";

            // Envelope
            self::smtpSend($conn, "MAIL FROM:<{$from}>");
            self::smtpExpect($conn, 250);
            self::smtpSend($conn, "RCPT TO:<{$to}>");
            self::smtpExpect($conn, [250, 251]);
            self::smtpSend($conn, "DATA");
            self::smtpExpect($conn, 354);
            fwrite($conn, $headers . $body . "\r\n.\r\n");
            self::smtpExpect($conn, 250);

            self::smtpSend($conn, "QUIT");
            fclose($conn);
            return true;

        } catch (\Throwable $e) {
            app_log("SMTP error for {$to}: " . $e->getMessage(), 'ERROR');
            @fclose($conn);
            return false;
        }
    }

    private static function smtpSend($conn, string $command): void {
        fwrite($conn, $command . "\r\n");
    }

    private static function smtpExpect($conn, $codes): string {
        $codes = (array)$codes;
        $line  = fgets($conn, 512);
        $code  = (int)substr($line, 0, 3);
        if (!in_array($code, $codes)) {
            throw new \RuntimeException("SMTP unexpected response: " . trim($line));
        }
        // Read continuation lines (e.g. multi-line EHLO)
        while (substr($line, 3, 1) === '-') {
            $line = fgets($conn, 512);
        }
        return trim($line);
    }

    private static function smtpReadLines($conn): string {
        $out  = '';
        do {
            $line = fgets($conn, 512);
            $out .= $line;
        } while (substr($line, 3, 1) === '-');
        return $out;
    }

    // ── Email templates ───────────────────────────────────────

    public static function sendSupportRequest(
        string $to,
        string $senderName,
        string $senderEmail,
        string $subject,
        string $message
    ): bool {
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $html = self::wrapTemplate("
            <h2 style='color:#8b5cf6;'>Support Request — " . APP_NAME . "</h2>
            <p><strong>From:</strong> " . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') . "
               &lt;" . htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8') . "&gt;</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</p>
            <hr style='border:none;border-top:1px solid rgba(255,255,255,.1);margin:20px 0;'>
            <p>{$safeMessage}</p>
            <hr style='border:none;border-top:1px solid rgba(255,255,255,.1);margin:20px 0;'>
            <p style='color:#888;font-size:.875rem;'>Reply directly to this email to respond to the user.</p>
        ");
        return self::send($to, APP_NAME . ' Support: ' . $subject, $html);
    }

    public static function sendPasswordReset(string $to, string $firstName, string $resetUrl): bool {
        $subject = APP_NAME . ' — Password Reset';
        $html    = self::wrapTemplate("
            <h2 style='color:#8b5cf6;'>Reset Your Password</h2>
            <p>Hi {$firstName},</p>
            <p>We received a request to reset your " . APP_NAME . " password.</p>
            <p>Click the button below to set a new password. This link is valid for <strong>1 hour</strong>.</p>
            <p style='margin:24px 0;'>
                <a href='{$resetUrl}'
                   style='background:#8b5cf6;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;'>
                    Reset Password
                </a>
            </p>
            <p style='color:#888;font-size:.875rem;'>
                If you didn't request a password reset, you can safely ignore this email. Your password will not change.
            </p>
            <p style='color:#888;font-size:.875rem;'>Or copy this link: {$resetUrl}</p>
        ");
        return self::send($to, $subject, $html);
    }

    public static function sendEmailVerification(string $to, string $firstName, string $verifyUrl): bool {
        $subject = 'Welcome to ' . APP_NAME . ' — Verify Your Email';
        $html    = self::wrapTemplate("
            <h2 style='color:#8b5cf6;'>Welcome, {$firstName}!</h2>
            <p>Thanks for joining " . APP_NAME . "! Please verify your email address to complete your registration.</p>
            <p style='margin:24px 0;'>
                <a href='{$verifyUrl}'
                   style='background:#8b5cf6;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;'>
                    Verify Email Address
                </a>
            </p>
            <p style='color:#888;font-size:.875rem;'>Or copy this link: {$verifyUrl}</p>
            <p style='color:#888;font-size:.875rem;'>
                If you did not create an account, no action is needed.
            </p>
        ");
        return self::send($to, $subject, $html);
    }

    private static function wrapTemplate(string $content): string {
        $name = APP_NAME;
        $url  = APP_URL;
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#1a1025;border-radius:12px;padding:40px;color:#e2e8f0;">
        <tr><td>
          <div style="text-align:center;margin-bottom:24px;">
            <span style="font-size:1.5rem;font-weight:bold;color:#8b5cf6;">{$name}</span>
          </div>
          {$content}
          <hr style="border:none;border-top:1px solid rgba(255,255,255,.1);margin:32px 0;">
          <p style="color:#666;font-size:.75rem;text-align:center;">
            &copy; {$name} &bull; <a href="{$url}" style="color:#8b5cf6;">{$url}</a>
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}

<?php
/**
 * PHPMailer-based OTP email. Uses MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD,
 * MAIL_FROM_ADDRESS, MAIL_FROM_NAME, MAIL_SMTP_PORT in auth/config.php.
 */
require_once __DIR__ . '/../auth/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$phpmailer_path = __DIR__ . '/PHPMailer/src/';
if (!file_exists($phpmailer_path . 'Exception.php')) {
    $phpmailer_path = __DIR__ . '/../PHPMailer/src/';
}
if (!file_exists($phpmailer_path . 'Exception.php')) {
    die('PHPMailer not found. Expected at php/PHPMailer/src/');
}

require_once $phpmailer_path . 'Exception.php';
require_once $phpmailer_path . 'PHPMailer.php';
require_once $phpmailer_path . 'SMTP.php';

/**
 * @return array{success:bool, message:string}
 */
function sendOTPEmail($to_email, $otp, $name = '') {
    $to_email = trim((string) $to_email);
    $otp = (string) $otp;
    $name = trim((string) $name);
    if ($name === '') {
        $name = 'User';
    }

    $host = defined('MAIL_HOST') && MAIL_HOST !== '' ? MAIL_HOST : (defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : '');
    $user = defined('MAIL_USERNAME') && MAIL_USERNAME !== '' ? MAIL_USERNAME : (defined('MAIL_SMTP_USER') ? MAIL_SMTP_USER : '');
    $pass = defined('MAIL_PASSWORD') && MAIL_PASSWORD !== '' ? MAIL_PASSWORD : (defined('MAIL_SMTP_PASS') ? MAIL_SMTP_PASS : '');
    $fromEmail = defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== '' ? MAIL_FROM_ADDRESS : (defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : '');
    if ($fromEmail === '' && $user !== '') {
        $fromEmail = $user;
    }
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'PowerCabs';
    $port = defined('MAIL_SMTP_PORT') ? (int) MAIL_SMTP_PORT : 587;

    if ($host === '' || $user === '' || $pass === '' || $fromEmail === '') {
        return sendOTPSimple($to_email, $otp, $name);
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;
        if ($mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $debug = defined('MAIL_SMTP_DEBUG') ? (int) MAIL_SMTP_DEBUG : 0;
        $mail->SMTPDebug = $debug;
        if ($debug > 0) {
            $mail->Debugoutput = 'error_log';
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to_email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - PowerCabs';
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"/>
  <style>
    body { font-family: Arial, sans-serif; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background-color: #f37a20; color: white; padding: 20px; text-align: center; }
    .content { padding: 30px; background-color: #f9f9f9; }
    .otp-box { background-color: white; padding: 20px; text-align: center; font-size: 32px;
      font-weight: bold; color: #f37a20; letter-spacing: 5px; margin: 20px 0;
      border-radius: 5px; border: 2px dashed #f37a20; }
    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header"><h2>Password Reset Request</h2></div>
    <div class="content">
      <p>Hello {$safeName},</p>
      <p>We received a request to reset your password. Use the following OTP code:</p>
      <div class="otp-box"><strong>{$safeOtp}</strong></div>
      <p>This OTP is valid for <strong>10 minutes</strong>.</p>
      <p>If you did not request this, please ignore this email.</p>
      <p>Best regards,<br><strong>PowerCabs Team</strong></p>
    </div>
    <div class="footer"><p>&copy; PowerCabs</p></div>
  </div>
</body>
</html>
HTML;

        $mail->AltBody = "Hello {$name},\n\nYour password reset OTP is: {$otp}\n\nValid for 10 minutes.\n\nIf you did not request this, ignore this email.";

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully'];
    } catch (MailException $e) {
        $err = $mail->ErrorInfo;
        $fallback = sendOTPSimple($to_email, $otp, $name);
        if ($fallback['success']) {
            return $fallback;
        }
        return ['success' => false, 'message' => $err];
    } catch (Throwable $e) {
        return sendOTPSimple($to_email, $otp, $name);
    }
}

/**
 * @return array{success:bool, message:string}
 */
function sendOTPSimple($to_email, $otp, $name = '') {
    $name = trim((string) $name);
    if ($name === '') {
        $name = 'User';
    }
    $subject = 'Password Reset OTP - PowerCabs';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $from = defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== ''
        ? MAIL_FROM_ADDRESS
        : (defined('MAIL_FROM_EMAIL') && MAIL_FROM_EMAIL !== '' ? MAIL_FROM_EMAIL : 'noreply@powercabs.com');
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'PowerCabs';
    $headers .= 'From: ' . $fromName . " <{$from}>\r\n";

    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $message = "<html><body><h2 style=\"color:#f37a20\">Password Reset OTP</h2><p>Hello {$safeName},</p><p>Your code: <strong>{$safeOtp}</strong></p><p>Valid 10 minutes.</p></body></html>";

    if (@mail($to_email, $subject, $message, $headers)) {
        return ['success' => true, 'message' => 'Sent via mail()'];
    }
    return ['success' => false, 'message' => 'mail() failed'];
}

        <?php
// Fixed path to PHPMailer - adjust based on your actual folder structure
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Method 1: If PHPMailer is in the same directory
$phpmailer_path = __DIR__ . '/PHPMailer/src/';

// Method 2: If you downloaded and placed PHPMailer in a folder
// $phpmailer_path = __DIR__ . '/PHPMailer/src/';

// Check if files exist and include them
if (file_exists($phpmailer_path . 'Exception.php')) {
    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';
} else {
    // Alternative: Try relative path from root
    $alt_path = __DIR__ . '/../PHPMailer/src/';
    if (file_exists($alt_path . 'Exception.php')) {
        require_once $alt_path . 'Exception.php';
        require_once $alt_path . 'PHPMailer.php';
        require_once $alt_path . 'SMTP.php';
    } else {
        // If still not found, show error
        die('PHPMailer files not found. Please download PHPMailer from https://github.com/PHPMailer/PHPMailer and place in php/PHPMailer/ folder');
    }
}

// Log function for debugging
// function logDebug($message) {
//     $logFile = __DIR__ . '/../email_debug.log';
//     $timestamp = date('Y-m-d H:i:s');
//     file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
// }

function sendOTPEmail($to_email, $otp, $name = '') {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for local development (use SMTP)
        $mail->SMTPDebug = 2; // Enable verbose debug output (set to 0 in production)
        $mail->Debugoutput = function($str, $level) {
            logDebug("SMTP Debug level $level: $str");
        };
        
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                       // For Gmail SMTP (use your email provider)
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'a.rehman@mindstremsoft.com';                 // Your Gmail address (use your actual email)
        $mail->Password   = 'rehman@54321';                    // Your Gmail app password (not regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
        $mail->Port       = 587;                                    // TCP port to connect to
        
        // For Hostinger, use these settings instead:
        /*
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;
        */
        
        // Recipients
        $mail->setFrom('a.rehman@mindstremsoft.com', 'PowerCabs');
        $mail->addAddress($to_email, $name);                        // Add a recipient
        
        // Content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = 'Password Reset OTP - PowerCabs';
        
        // HTML email body
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
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
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($name ?: 'User') . ",</p>
                    <p>We received a request to reset your password. Use the following OTP code:</p>
                    
                    <div class='otp-box'>
                        <strong>" . $otp . "</strong>
                    </div>
                    
                    <p>This OTP is valid for <strong>10 minutes</strong>.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    
                    <p>Best regards,<br>
                    <strong>PowerCabs Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 PowerCabs. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text version
        $mail->AltBody = "Your password reset OTP is: $otp\n\nThis OTP is valid for 10 minutes.\n\nIf you didn't request this, please ignore this email.";
        
        $mail->send();
        logDebug("Email sent successfully to: $to_email");
        return ['success' => true, 'message' => 'OTP sent successfully'];
        
    } catch (Exception $e) {
        logDebug("Email sending failed: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Mailer Error: {$mail->ErrorInfo}"];
    }
}

// Optional: If you want to use a simpler method without SMTP for local testing
function sendOTPSimple($to_email, $otp, $name = '') {
    $subject = "Password Reset OTP - PowerCabs";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: PowerCabs <noreply@powercabs.com>' . "\r\n";
    
    $message = "
    <html>
    <body>
        <h2 style='color: #f37a20;'>Password Reset OTP</h2>
        <p>Hello $name,</p>
        <p>Your OTP for password reset is:</p>
        <div style='font-size: 24px; font-weight: bold; color: #f37a20; padding: 10px; border: 2px dashed #f37a20; text-align: center;'>
            $otp
        </div>
        <p>This OTP is valid for 10 minutes.</p>
    </body>
    </html>
    ";
    
    if (mail($to_email, $subject, $message, $headers)) {
        return ['success' => true, 'message' => 'OTP sent via mail()'];
    } else {
        return ['success' => false, 'message' => 'Failed to send via mail()'];
    }
}
?>
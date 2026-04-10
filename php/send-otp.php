<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function
function logDebug($message) {
    $logFile = __DIR__ . '/../otp_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    logDebug("Processing OTP request for email: $email");
    
    try {
        $supabase = new SupabaseClient(true);
        $users = $supabase->select('corporate', ['email' => $email], 'email', null, 1);
        if (!empty($users)) {
        
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
            logDebug("Generated OTP: $otp for email: $email");
        
            $supabase->delete('password_resets', ['email' => $email]);
        
            $supabase->insert('password_resets', [
                'email' => $email,
                'otp' => $otp,
                'expiry' => $expiry
            ]);
            logDebug("OTP stored in database successfully");
            
            // Store in session for backup
            $_SESSION['otp'] = $otp;
            $_SESSION['reset_email'] = $email;
            
            // For testing, show OTP on screen
            $_SESSION['success'] = "OTP has been generated. For testing, use: <strong>" . $otp . "</strong>";
            
            // Optional: Try to send email but don't fail if it doesn't work
            $to = $email;
            $subject = "Password Reset OTP - PowerCabs";
            $message = "Your OTP for password reset is: " . $otp . "\n\nThis OTP is valid for 10 minutes.";
            $headers = "From: noreply@powercabs.com";
            
            // Try to send email (don't check result)
            @mail($to, $subject, $message, $headers);
            
            header("Location: ../forgot-password.php?step=otp&email=" . urlencode($email));
            exit();
            
        } else {
            logDebug("Email not found: $email");
            $_SESSION['error'] = "Email address not found in our records.";
            header("Location: ../forgot-password.php");
            exit();
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = "Failed to generate OTP. Please try again.";
        header("Location: ../forgot-password.php");
        exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>
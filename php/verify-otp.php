<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function
function logDebug($message) {
    $logFile = __DIR__ . '/../otp_verify_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $entered_otp = trim($_POST['otp']);
    
    logDebug("Verifying OTP for email: $email, entered OTP: $entered_otp");
    
    try {
    $supabase = new SupabaseClient(true);
    $records = $supabase->select('password_resets', ['email' => $email], '*', 'created_at.desc', 1);
    if (!empty($records)) {
        $stored_data = $records[0];
        logDebug("Stored in DB - OTP: " . ($stored_data['otp'] ?? '') . ", Expiry: " . ($stored_data['expiry'] ?? ''));
    }

    $valid = false;
    if (!empty($records)) {
        $row = $records[0];
        $valid = (($row['otp'] ?? '') === $entered_otp) && (strtotime($row['expiry'] ?? '') > time());
    }

    if ($valid) {
        // OTP is valid
        logDebug("OTP verified successfully for email: $email");
        
        $_SESSION['reset_verified'] = true;
        $_SESSION['reset_email'] = $email;
        $_SESSION['success'] = "OTP verified successfully. Please set your new password.";
        
        $supabase->delete('password_resets', ['email' => $email]);
        
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    } else {
        logDebug("Invalid or expired OTP for email: $email");

        $expired = false;
        if (!empty($records)) {
            $row = $records[0];
            $expired = (($row['otp'] ?? '') === $entered_otp) && (strtotime($row['expiry'] ?? '') <= time());
        }
        if ($expired) {
            $_SESSION['error'] = "OTP has expired. Please request a new one.";
        } else {
            $_SESSION['error'] = "Invalid OTP. Please try again.";
        }
        
        header("Location: ../forgot-password.php?step=otp&email=" . urlencode($email));
        exit();
    }
    } catch (Throwable $e) {
      $_SESSION['error'] = "Database error occurred.";
      header("Location: ../forgot-password.php?step=otp&email=" . urlencode($email));
      exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>
<?php
session_start();
require_once 'connection.php';

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
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $entered_otp = mysqli_real_escape_string($conn, $_POST['otp']);
    
    logDebug("Verifying OTP for email: $email, entered OTP: $entered_otp");
    
    // First, check what OTPs exist for this email
    $check_query = "SELECT * FROM password_resets WHERE email = '$email' ORDER BY created_at DESC LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $stored_data = mysqli_fetch_assoc($check_result);
        logDebug("Stored in DB - OTP: " . $stored_data['otp'] . ", Expiry: " . $stored_data['expiry']);
    }
    
    // Check OTP in database with proper validation
    $query = "SELECT * FROM password_resets 
              WHERE email = '$email' 
              AND otp = '$entered_otp' 
              AND expiry > NOW() 
              ORDER BY created_at DESC 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        logDebug("Query error: " . mysqli_error($conn));
        $_SESSION['error'] = "Database error occurred.";
        header("Location: ../forgot-password.php?step=otp&email=" . urlencode($email));
        exit();
    }
    
    if (mysqli_num_rows($result) > 0) {
        // OTP is valid
        $row = mysqli_fetch_assoc($result);
        logDebug("OTP verified successfully for email: $email");
        
        $_SESSION['reset_verified'] = true;
        $_SESSION['reset_email'] = $email;
        $_SESSION['success'] = "OTP verified successfully. Please set your new password.";
        
        // Delete the used OTP
        $delete_query = "DELETE FROM password_resets WHERE email = '$email'";
        mysqli_query($conn, $delete_query);
        
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    } else {
        logDebug("Invalid or expired OTP for email: $email");
        
        // Check if OTP exists but expired
        $expired_check = "SELECT * FROM password_resets 
                          WHERE email = '$email' 
                          AND otp = '$entered_otp' 
                          AND expiry <= NOW()";
        $expired_result = mysqli_query($conn, $expired_check);
        
        if (mysqli_num_rows($expired_result) > 0) {
            $_SESSION['error'] = "OTP has expired. Please request a new one.";
        } else {
            $_SESSION['error'] = "Invalid OTP. Please try again.";
        }
        
        header("Location: ../forgot-password.php?step=otp&email=" . urlencode($email));
        exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>
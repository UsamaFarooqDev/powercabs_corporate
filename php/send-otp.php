<?php
session_start();
require_once 'connection.php';

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
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    logDebug("Processing OTP request for email: $email");
    
    // Check if email exists in corporate table
    $query = "SELECT * FROM corporate WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        logDebug("Database error: " . mysqli_error($conn));
        $_SESSION['error'] = "Database error occurred. Please try again.";
        header("Location: ../forgot-password.php");
        exit();
    }
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        logDebug("Generated OTP: $otp for email: $email");
        
        // First, clear any existing OTPs for this email
        $clear_query = "DELETE FROM password_resets WHERE email = '$email'";
        mysqli_query($conn, $clear_query);
        
        // Store new OTP in database
        $insert_query = "INSERT INTO password_resets (email, otp, expiry) VALUES ('$email', '$otp', '$expiry')";
        
        if (mysqli_query($conn, $insert_query)) {
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
            logDebug("Failed to store OTP: " . mysqli_error($conn));
            $_SESSION['error'] = "Failed to generate OTP. Please try again.";
            header("Location: ../forgot-password.php");
            exit();
        }
    } else {
        logDebug("Email not found: $email");
        $_SESSION['error'] = "Email address not found in our records.";
        header("Location: ../forgot-password.php");
        exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>
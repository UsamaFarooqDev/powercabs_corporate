<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';
require_once __DIR__ . '/password_reset_otp.inc.php';

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
    $email = trim($_POST['email'] ?? '');
    logDebug("Processing OTP request for email: $email");

    try {
        $supabase = new SupabaseClient(true);
        $result = pr_issue_password_reset_otp($supabase, $email);

        if (!$result['ok']) {
            if (($result['reason'] ?? '') === 'not_found') {
                logDebug("Email not found: $email");
                $_SESSION['error'] = 'Email address not found in our records.';
            } else {
                $_SESSION['error'] = 'Please enter a valid email address.';
            }
            header('Location: ../forgot-password.php');
            exit();
        }

        $otp = $result['otp'];
        logDebug("Generated OTP: $otp for email: $email");
        logDebug('OTP stored in database successfully');

        $_SESSION['otp'] = $otp;
        $_SESSION['reset_email'] = $email;

        $_SESSION['success'] = 'OTP has been generated. For testing, use: <strong>' . htmlspecialchars($otp) . '</strong>';

        header('Location: ../forgot-password.php?step=otp&email=' . urlencode($email));
        exit();
    } catch (Throwable $e) {
        logDebug('OTP error: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to generate OTP. Please try again.';
        header('Location: ../forgot-password.php');
        exit();
    }
} else {
    header('Location: ../forgot-password.php');
    exit();
}

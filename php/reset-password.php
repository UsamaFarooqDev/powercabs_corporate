<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if reset was verified
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        $_SESSION['error'] = "Please verify your OTP first.";
        header("Location: ../forgot-password.php");
        exit();
    }
    
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $supabase = new SupabaseClient(true);
        $supabase->update('corporate', ['email' => $email], ['pass' => $hashed_password]);
        $supabase->delete('password_resets', ['email' => $email]);
        
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_email']);
        
        $_SESSION['success'] = "Password reset successful! You can now login with your new password.";
        header("Location: ../login.php");
        exit();
    } catch (Throwable $e) {
        $_SESSION['error'] = "Failed to reset password. Please try again.";
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>